<?php declare(strict_types=1);
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Kansas\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use System\ArgumentException;
use System\IO\FileUploadException;
use System\IO\UploadedFileAlreadyMovedException;

use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function is_dir;
use function is_resource;
use function is_string;
use function is_writable;
use function move_uploaded_file;
use function strpos;

use const PHP_SAPI;
use const UPLOAD_ERR_OK;

require_once 'Psr/Http/Message/StreamInterface.php';
require_once 'Psr/Http/Message/UploadedFileInterface.php';

class UploadedFile implements UploadedFileInterface {

    /**
     * @var null|string
     */
    private $file;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var null|StreamInterface
     */
    private $stream;

    /**
     * @param string|resource|StreamInterface $streamOrFile
     * @param int $size
     * @param int $errorStatus
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     * @throws ArgumentException
     */
    public function __construct(
        $streamOrFile,
        private int $size,
        private int $error,
        private string|null $clientFilename = null,
        private string|null $clientMediaType = null
    ) {
        if ($error === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            } elseif (is_resource($streamOrFile)) {
                $this->stream = new Stream($streamOrFile);
            } elseif ($streamOrFile instanceof StreamInterface) {
                $this->stream = $streamOrFile;
            }

            if (!$this->file && !$this->stream) {
                require_once 'System/ArgumentException.php';
                throw new ArgumentException('streamOrFile', 'Invalid stream or file provided for UploadedFile');
            }
        }

        if (0 > $error || 8 < $error) {
            require_once 'System/ArgumentException.php';
            throw new ArgumentException('error', 'Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant');
        }
    }

    /**
     * {@inheritdoc}
     * @throws System\IO\FileUploadException if the upload was not successful.
     */
    public function getStream() : StreamInterface {
        if ($this->error !== UPLOAD_ERR_OK) {
            require_once 'System/IO/FileUploadException.php';
            throw new FileUploadException($this->error);
        }

        if ($this->moved) {
            require_once 'System/IO/UploadedFileAlreadyMovedException.php';
            throw new UploadedFileAlreadyMovedException();
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws System\IO\FileUploadException if the upload was not successful.
     * @throws Exception\ArgumentException if the $path specified is invalid.
     * @throws System\IO\UploadedFileAlreadyMovedException on any error during the
     *     move operation, or on the second or subsequent call to the method.
     */
    public function moveTo($targetPath) : void {
        if ($this->moved) {
            require_once 'System/IO/UploadedFileAlreadyMovedException.php';
            throw new UploadedFileAlreadyMovedException();
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            require_once 'System/IO/FileUploadException.php';
            throw new FileUploadException($this->error);
        }

        if (! is_string($targetPath) || empty($targetPath)) {
            require_once 'System/ArgumentException.php';
            throw new ArgumentException('targetPath', 'Invalid path provided for move operation; must be a non-empty string');
        }

        $targetDirectory = dirname($targetPath);
        if (! is_dir($targetDirectory) || ! is_writable($targetDirectory)) {
            require_once 'System/IO/FileUploadException.php';
            throw FileUploadException::dueToUnwritableTarget($targetDirectory);
        }

        $sapi = PHP_SAPI;
        if(empty($sapi) || 0 === strpos($sapi, 'cli') || 0 === strpos($sapi, 'phpdbg') || ! $this->file) { // Non-SAPI environment, or no filename present
            $this->writeFile($targetPath);
        } else { // SAPI environment, with file present
            if (false === move_uploaded_file($this->file, $targetPath)) {
                require_once 'System/IO/FileUploadException.php';
                throw FileUploadException::forUnmovableFile();
            }
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize() : ?int {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError() : int {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename() : ?string {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType() : ?string {
        return $this->clientMediaType;
    }

    public function getTmpFilename() : ?string {
        return $this->file;
    }

    /**
     * Write internal stream to given path
     *
     * @param string $path
     */
    private function writeFile(string $path) : void {
        $handle = fopen($path, 'wb+');
        if (false === $handle) {
            require_once 'System/IO/FileUploadException.php';
            throw FileUploadException::dueToUnwritablePath();
        }

        $this->getStream();
        $this->stream->rewind();
        while (! $this->stream->eof()) {
            fwrite($handle, $this->stream->read(4096));
        }

        fclose($handle);
    }
}
