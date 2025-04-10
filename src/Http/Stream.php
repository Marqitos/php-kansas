<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Kansas\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function get_resource_type;
use function is_int;
use function is_resource;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function stream_get_contents;
use function stream_get_meta_data;
use function strstr;

use const E_WARNING;
use const SEEK_SET;

/**
  * Implementation of PSR HTTP streams
  */
class Stream implements StreamInterface {
    /**
      * @var resource|null
      */
    protected $resource;

    /**
      * @var string|resource
      */
    protected $stream;

    /**
      * @param string|resource $stream
      * @param string $mode Mode with which to open stream
      * @throws InvalidArgumentException
      */
    public function __construct($stream, $mode = 'r') {
        $this->setStream($stream, $mode);
    }

## Miembros de Psr\Http\Message\StreamInterface
    /**
      * {@inheritdoc}
      */
    public function __toString(): string {
        if (! $this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
      * {@inheritdoc}
      */
    public function close(): void {
        if (! $this->resource) {
            return;
        }

        $resource = $this->detach();
        fclose($resource);
    }

    /**
      * {@inheritdoc}
      */
    public function detach() {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
      * {@inheritdoc}
      */
    public function getSize(): ?int {
        if (null === $this->resource) {
            return null;
        }

        $stats = fstat($this->resource);
        if ($stats !== false) {
            return $stats['size'];
        }

        return null;
    }

    /**
      * {@inheritdoc}
      */
    public function tell(): int {
        if (! $this->resource) {
            throw new RuntimeException('No resource available; cannot tell position');
        }

        $result = ftell($this->resource);
        if (! is_int($result)) {
            throw new RuntimeException('Error occurred during tell operation');
        }

        return $result;
    }

    /**
      * {@inheritdoc}
      */
    public function eof(): bool {
        if (! $this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
      * {@inheritdoc}
      */
    public function isSeekable(): bool {
        if (! $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        return $meta['seekable'];
    }

    /**
      * {@inheritdoc}
      */
    public function seek(int $offset, int $whence = SEEK_SET): void {
        if (! $this->resource) {
            throw new RuntimeException('No resource available; cannot seek position');
        }

        if (! $this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        $result = fseek($this->resource, $offset, $whence);

        if (0 !== $result) {
            throw new RuntimeException('Error seeking within stream');
        }
    }

    /**
      * {@inheritdoc}
      */
    public function rewind(): void {
        $this->seek(0);
    }

    /**
      * {@inheritdoc}
      */
    public function isWritable(): bool {
        if (! $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return strstr($mode, 'x') ||
               strstr($mode, 'w') ||
               strstr($mode, 'c') ||
               strstr($mode, 'a') ||
               strstr($mode, '+');
    }

    /**
      * {@inheritdoc}
      */
    public function write(string $string): int {
        if (! $this->resource) {
            throw new RuntimeException('No resource available; cannot write');
        }

        if (! $this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $result = fwrite($this->resource, $string);

        if (false === $result) {
            throw new RuntimeException('Error writing to stream');
        }
        return $result;
    }

    /**
      * {@inheritdoc}
      */
    public function isReadable(): bool {
        if (! $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return strstr($mode, 'r') ||
               strstr($mode, '+');
    }

    /**
      * {@inheritdoc}
      */
    public function read(int $length): string {
        if (! $this->resource) {
            throw new RuntimeException('No resource available; cannot read');
        }

        if (! $this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $result = fread($this->resource, $length);

        if (false === $result) {
            throw new RuntimeException('Error reading stream');
        }

        return $result;
    }

    /**
      * {@inheritdoc}
      */
    public function getContents(): string {
        if (! $this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $result = stream_get_contents($this->resource);
        if (false === $result) {
            throw new RuntimeException('Error reading from stream');
        }
        return $result;
    }

    /**
      * {@inheritdoc}
      */
    public function getMetadata(?string $key = null) {
        if (null === $key) {
            return stream_get_meta_data($this->resource);
        }

        $metadata = stream_get_meta_data($this->resource);

        return isset($metadata[$key])
            ? $metadata[$key]
            : null;
    }
## Fin StreamInterface

    /**
      * Attach a new stream/resource to the instance.
      *
      * @param string|resource $resource
      * @param string $mode
      * @throws InvalidArgumentException for stream identifier that cannot be
      *     cast to a resource
      * @throws InvalidArgumentException for non-resource stream
      */
    public function attach($resource, $mode = 'r') {
        $this->setStream($resource, $mode);
    }

    /**
      * Set the internal stream resource.
      *
      * @param string|resource $stream String stream target or stream resource.
      * @param string $mode Resource mode for stream target.
      * @throws InvalidArgumentException for invalid streams or resources.
      */
    private function setStream($stream, $mode = 'r') {
        $error    = null;
        $resource = $stream;

        if (is_string($stream)) {
            set_error_handler(function ($e) use (&$error) {
                $error = $e;
            }, E_WARNING);
            $resource = fopen($stream, $mode);
            restore_error_handler();
        }

        if ($error) {
            throw new InvalidArgumentException('Invalid stream reference provided');
        }

        if (! is_resource($resource) || 'stream' !== get_resource_type($resource)) {
            throw new InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or stream resource'
            );
        }

        if ($stream !== $resource) {
            $this->stream = $stream;
        }

        $this->resource = $resource;
    }
}
