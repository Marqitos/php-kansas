<?php declare(strict_types = 1);
/**
 *
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 *
 */

namespace Kansas\Http;

use InvalidArgumentException;
use Kansas\Http\PhpInputStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

use function is_array;
use function array_map;
use function array_merge;
use function get_class;
use function gettype;
use function implode;
use function is_object;
use function is_resource;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;
use function array_keys;

require_once 'Psr/Http/Message/ServerRequestInterface.php';
require_once 'Psr/Http/Message/StreamInterface.php';
require_once 'Psr/Http/Message/UriInterface.php';

/**
 * Server-side HTTP request
 *
 * Extends the Request definition to add methods for accessing incoming data,
 * specifically server parameters, cookies, matched path parameters, query
 * string arguments, body parameters, and upload file information.
 *
 * "Attributes" are discovered via decomposing the request (and usually
 * specifically the URI path), and typically will be injected by the application.
 *
 * Requests are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
#[SuppressWarnings("php:S1448")]
class ServerRequest implements ServerRequestInterface {

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * List of all registered headers, as key => array of values.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Map of normalized header name to original name used to register header.
     *
     * @var array
     */
    protected $headerNames = [];

    /**
     * @var StreamInterface
     */
    private $stream;

        /**
     * @var string
     */
    private $method = '';

    /**
     * The request-target, if it has been provided or calculated.
     *
     * @var null|string
     */
    private $requestTarget;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
      * @param array $serverParams Server parameters, typically from $_SERVER
      * @param array $uploadedFiles Upload file information, a tree of UploadedFiles
      * @param ?string|UriInterface $uri URI for the request, if any.
      * @param ?string $method HTTP method for the request, if any.
      * @param string|resource|StreamInterface $body Message body, if any.
      * @param array $headers Headers for the message, if any.
      * @param array $cookies Cookies for the message, if any.
      * @param array $queryParams Query params for the message, if any.
      * @param null|array|object $parsedBody The deserialized body parameters, if any.
      * @param string $protocol HTTP protocol version.
      * @throws InvalidArgumentException for any invalid value.
     */
    public function __construct(
        private array $serverParams = [],
        private array $uploadedFiles = [],
        ?string $uri = null,
        ?string $method = null,
        $body = 'php://input',
        array $headers = [],
        private array $cookieParams = [],
        private array $queryParams = [],
        private null|array|object $parsedBody = null,
        private string $protocol = '1.1'
    ) {
        self::validateUploadedFiles($uploadedFiles);

        if ($body === 'php://input') {
            $body = new PhpInputStream();
        }

        $this->initialize($uri, $method, $body, $headers);
    }

## Miembros de Psr\Http\Message\ServerRequestInterface
    /**
      * {@inheritdoc}
      */
    public function getServerParams(): array {
        return $this->serverParams;
    }

    /**
      * {@inheritdoc}
      */
    public function getCookieParams(): array {
        return $this->cookieParams;
    }

    /**
      * {@inheritdoc}
      */
    public function withCookieParams(array $cookies): ServerRequestInterface {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
      * {@inheritdoc}
      */
    public function getQueryParams(): array {
        return $this->queryParams;
    }

    /**
      * {@inheritdoc}
      */
    public function withQueryParams(array $query): ServerRequestInterface {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
      * {@inheritdoc}
      */
    public function getUploadedFiles(): array {
        return isset($this->uploadedFiles['files'])
            ? $this->uploadedFiles['files']
            : $this->uploadedFiles;
    }

    /**
      * {@inheritdoc}
      */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface {
        self::validateUploadedFiles($uploadedFiles);
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
      * {@inheritdoc}
      */
    public function getParsedBody() {
        return $this->parsedBody;
    }

    /**
      * {@inheritdoc}
      */
    public function withParsedBody($data): ServerRequestInterface {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    /**
      * {@inheritdoc}
      */
    public function getAttributes(): array {
        return $this->attributes;
    }

    /**
      * {@inheritdoc}
      */
    public function getAttribute(string $name, $default = null) {
        return isset($this->attributes[$name])
            ? $this->attributes[$name]
            : $default;
    }

    /**
      * {@inheritdoc}
      */
    public function withAttribute(string $name, $value): ServerRequestInterface {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    /**
      * {@inheritdoc}
      */
    public function withoutAttribute(string $name): ServerRequestInterface {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
## Fin de Miembros de ServerRequestInterface

## Miembros de Psr\Http\Message\RequestInterface
    /**
      * Retrieves the message's request target.
      *
      * Retrieves the message's request-target either as it will appear (for
      * clients), as it appeared at request (for servers), or as it was
      * specified for the instance (see withRequestTarget()).
      *
      * In most cases, this will be the origin-form of the composed URI,
      * unless a value was provided to the concrete implementation (see
      * withRequestTarget() below).
      *
      * If no URI is available, and no request-target has been specifically
      * provided, this method MUST return the string "/".
      *
      * @return string
      */
    public function getRequestTarget(): string {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (empty($target)) {
            $target = '/';
        }

        return $target;
    }

    /**
      * Create a new instance with a specific request-target.
      *
      * If the request needs a non-origin-form request-target — e.g., for
      * specifying an absolute-form, authority-form, or asterisk-form —
      * this method may be used to create an instance with the specified
      * request-target, verbatim.
      *
      * This method MUST be implemented in such a way as to retain the
      * immutability of the message, and MUST return a new instance that has the
      * changed request target.
      *
      * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
      *     request-target forms allowed in request messages)
      * @param mixed $requestTarget
      * @return static
      * @throws InvalidArgumentException if the request target is invalid.
      */
    public function withRequestTarget(string $requestTarget): RequestInterface {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
      * Proxy to receive the request method.
      *
      * This overrides the parent functionality to ensure the method is never
      * empty; if no method is present, it returns 'GET'.
      *
      * @return string
      */
    public function getMethod(): string {
        return $this->method ?: 'GET';
    }

    /**
      * Set the request method.
      *
      * Unlike the regular Request implementation, the server-side
      * normalizes the method to uppercase to ensure consistency
      * and make checking the method simpler.
      *
      * This methods returns a new instance.
      *
      * @param string $method
      * @return self
      */
    public function withMethod(string $method): RequestInterface {
        $this->validateMethod($method);
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request, if any.
     */
    public function getUri(): UriInterface {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method will update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header will be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, the returned request will not update the Host header of the
     * returned message -- even if the message contains no Host header. This
     * means that a call to `getHeader('Host')` on the original request MUST
     * equal the return value of a call to `getHeader('Host')` on the returned
     * request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $new;
        }

        if (! $uri->getHost()) {
            return $new;
        }

        $host = $uri->getHost();
        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        $new->headerNames['host'] = 'Host';

        // Remove an existing host header if present, regardless of current
        // de-normalization of the header name.
        // @see https://github.com/zendframework/zend-diactoros/issues/91
        foreach (array_keys($new->headers) as $header) {
            if (strtolower($header) === 'host') {
                unset($new->headers[$header]);
            }
        }

        $new->headers['Host'] = [$host];

        return $new;
    }
## Fin de Miembros de RequestInterface

## Miembros de Psr\Http\Message\MessageInterface

    /**
      * Retrieves the HTTP protocol version as a string.
      *
      * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
      *
      * @return string HTTP protocol version.
      */
    public function getProtocolVersion() : string {
        return $this->protocol;
    }

    /**
      * Return an instance with the specified HTTP protocol version.
      *
      * The version string MUST contain only the HTTP version number (e.g.,
      * "1.1", "1.0").
      *
      * This method MUST be implemented in such a way as to retain the
      * immutability of the message, and MUST return an instance that has the
      * new protocol version.
      *
      * @param string $version HTTP protocol version
      * @return static
      */
    public function withProtocolVersion(string $version): MessageInterface {
        self::validateProtocolVersion($version);
        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    /**
      * Retrieves all message headers.
      *
      * The keys represent the header name as it will be sent over the wire, and
      * each value is an array of strings associated with the header.
      *
      *     // Represent the headers as a string
      *     foreach ($message->getHeaders() as $name => $values) {
      *         echo $name . ": " . implode(", ", $values);
      *     }
      *
      *     // Emit headers iteratively:
      *     foreach ($message->getHeaders() as $name => $values) {
      *         foreach ($values as $value) {
      *             header(sprintf('%s: %s', $name, $value), false);
      *         }
      *     }
      *
      * @return array Returns an associative array of the message's headers. Each
      *     key MUST be a header name, and each value MUST be an array of strings.
      */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
      * Checks if a header exists by the given case-insensitive name.
      *
      * @param  string  $header Case-insensitive header name.
      * @return bool            Returns true if any header names match the given header
      *                         name using a case-insensitive string comparison. Returns false if
      *                         no matching header name is found in the message.
      */
    public function hasHeader(string $name): bool {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
      * Retrieves a message header value by the given case-insensitive name.
      *
      * This method returns an array of all the header values of the given
      * case-insensitive header name.
      *
      * If the header does not appear in the message, this method MUST return an
      * empty array.
      *
      * @param  string      $header     Case-insensitive header field name.
      * @return string[]                An array of string values as provided for the given
      *                                 header. If the header does not appear in the message, this method MUST
      *                                 return an empty array.
      */
      public function getHeader(string $name) : array {
        if (! $this->hasHeader($name)) {
            return [];
        }

        $name = $this->headerNames[strtolower($name)];

        return $this->headers[$name];
    }

    /**
      * Retrieves a comma-separated string of the values for a single header.
      *
      * This method returns all of the header values of the given
      * case-insensitive header name as a string concatenated together using
      * a comma.
      *
      * NOTE: Not all header values may be appropriately represented using
      * comma concatenation. For such headers, use getHeader() instead
      * and supply your own delimiter when concatenating.
      *
      * If the header does not appear in the message, this method MUST return
      * an empty string.
      *
      * @param string $name Case-insensitive header field name.
      * @return string A string of values as provided for the given header
      *    concatenated together using a comma. If the header does not appear in
      *    the message, this method MUST return an empty string.
      */
    public function getHeaderLine(string $name): string {
        $value = $this->getHeader($name);
        return empty($value)
            ? ''
            : implode(', ', $value);
    }

    /**
      * Return an instance with the provided header, replacing any existing
      * values of any headers with the same case-insensitive name.
      *
      * While header names are case-insensitive, the casing of the header will
      * be preserved by this function, and returned from getHeaders().
      *
      * This method MUST be implemented in such a way as to retain the
      * immutability of the message, and MUST return an instance that has the
      * new and/or updated header and value.
      *
      * @param string $header Case-insensitive header field name.
      * @param string|string[] $value Header value(s).
      * @return static
      * @throws \InvalidArgumentException for invalid header names or values.
      */
    public function withHeader(string $name, $value): MessageInterface {
        $this->assertHeader($header);

        $normalized = strtolower($header);

        $new = clone $this;
        if ($new->hasHeader($header)) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $value = $this->filterHeaderValue($value);

        $new->headerNames[$normalized] = $header;
        $new->headers[$header]         = $value;

        return $new;
    }

    /**
      * Return an instance with the specified header appended with the
      * given value.
      *
      * Existing values for the specified header will be maintained. The new
      * value(s) will be appended to the existing list. If the header did not
      * exist previously, it will be added.
      *
      * This method MUST be implemented in such a way as to retain the
      * immutability of the message, and MUST return an instance that has the
      * new header and/or value.
      *
      * @param string $header Case-insensitive header field name to add.
      * @param string|string[] $value Header value(s).
      * @return static
      * @throws \InvalidArgumentException for invalid header names or values.
      */
    public function withAddedHeader(string $name, $value): MessageInterface {
        $this->assertHeader($header);

        if (! $this->hasHeader($header)) {
            return $this->withHeader($header, $value);
        }

        $header = $this->headerNames[strtolower($header)];

        $new = clone $this;
        $value = $this->filterHeaderValue($value);
        $new->headers[$header] = array_merge($this->headers[$header], $value);
        return $new;
    }

    /**
      * Return an instance without the specified header.
      *
      * Header resolution MUST be done without case-sensitivity.
      *
      * This method MUST be implemented in such a way as to retain the
      * immutability of the message, and MUST return an instance that removes
      * the named header.
      *
      * @param string $header Case-insensitive header field name to remove.
      * @return static
      */
    public function withoutHeader(string $name): MessageInterface {
        if (! $this->hasHeader($name)) {
            return clone $this;
        }

        $normalized = strtolower($name);
        $original   = $this->headerNames[$normalized];

        $new = clone $this;
        unset($new->headers[$original], $new->headerNames[$normalized]);
        return $new;
    }

    /**
      * Gets the body of the message.
      *
      * @return StreamInterface Returns the body as a stream.
      */
    public function getBody(): StreamInterface {
        return $this->stream;
    }

    /**
      * Return an instance with the specified message body.
      *
      * The body MUST be a StreamInterface object.
      *
      * This method MUST be implemented in such a way as to retain the
      * immutability of the message, and MUST return a new instance that has the
      * new body stream.
      *
      * @param StreamInterface $body Body.
      * @return static
      * @throws \InvalidArgumentException When the body is not valid.
      */
    public function withBody(StreamInterface $body): MessageInterface {
        $new = clone $this;
        $new->stream = $body;
        return $new;
    }
## Fin de Miembros de MessageInterface

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * @param array $uploadedFiles
     * @throws InvalidArgumentException if any leaf is not an UploadedFileInterface instance.
     */
    private static function validateUploadedFiles(array $uploadedFiles) {
        require_once 'Psr/Http/Message/UploadedFileInterface.php';
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                self::validateUploadedFiles($file);
                continue;
            }

            if (! $file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException('Invalid leaf in uploaded files structure');
            }
        }
    }

    private function getStream($stream, $modeIfNotInstance) {
        if ($stream instanceof StreamInterface) {
            return $stream;
        }

        if (! is_string($stream) && ! is_resource($stream)) {
            throw new InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }

        return new Stream($stream, $modeIfNotInstance);
    }

    /**
     * Filter a set of headers to ensure they are in the correct internal format.
     *
     * Used by message constructors to allow setting all initial headers at once.
     *
     * @param array $originalHeaders Headers to filter.
     */
    private function setHeaders(array $originalHeaders) {
        $headerNames = $headers = [];

        foreach ($originalHeaders as $header => $value) {
            $value = $this->filterHeaderValue($value);

            $this->assertHeader($header);

            $headerNames[strtolower($header)] = $header;
            $headers[$header] = $value;
        }

        $this->headerNames = $headerNames;
        $this->headers = $headers;
    }

    /**
     * Validate the HTTP protocol version
     *
     * @param string $version
     * @throws InvalidArgumentException on invalid HTTP protocol version
     */
    private static function validateProtocolVersion(string $version) {
        if (empty($version)) {
            throw new InvalidArgumentException(
                'HTTP protocol version can not be empty'
            );
        }
        if (! is_string($version)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP protocol version; must be a string, received %s',
                (is_object($version) ? get_class($version) : gettype($version))
            ));
        }

        // HTTP/1 uses a "<major>.<minor>" numbering scheme to indicate
        // versions of the protocol, while HTTP/2 does not.
        if (! preg_match('#^(1\.[01]|2)$#', $version)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP protocol version "%s" provided',
                $version
            ));
        }
    }

    /**
     * @param mixed $values
     * @return string[]
     */
    private function filterHeaderValue($values) {
        if (! is_array($values)) {
            $values = [$values];
        }

        return array_map(function ($value) {
            HeaderSecurity::assertValid($value);

            return (string) $value;
        }, $values);
    }

    /**
     * Ensure header name and values are valid.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     */
    private static function assertHeader(string $name) {
        HeaderSecurity::assertValidName($name);
    }

    /**
     * Initialize request state.
     *
     * Used by constructors.
     *
     * @param ?string|UriInterface $uri URI for the request, if any.
     * @param ?string $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @throws InvalidArgumentException for any invalid value.
     */
    private function initialize(?string $uri = null, ?string $method = null, $body = 'php://memory', array $headers = []) {
        $this->validateMethod($method);

        $this->method = $method ?: '';
        $this->uri    = $this->createUri($uri);
        $this->stream = $this->getStream($body, 'wb+');

        $this->setHeaders($headers);

        // per PSR-7: attempt to set the Host header from a provided URI if no
        // Host header is provided
        if (! $this->hasHeader('Host') &&
            $this->uri->getHost()) {
            $this->headerNames['host'] = 'Host';
            $this->headers['Host'] = [$this->getHostFromUri()];
        }
    }

    /**
     * Create and return a URI instance.
     *
     * If `$uri` is a already a `UriInterface` instance, returns it.
     *
     * If `$uri` is a string, passes it to the `Uri` constructor to return an
     * instance.
     *
     * If `$uri is null, creates and returns an empty `Uri` instance.
     *
     * Otherwise, it raises an exception.
     *
     * @param ?string|UriInterface $uri
     * @return UriInterface
     * @throws InvalidArgumentException
     */
    private function createUri($uri) {
        if ($uri instanceof UriInterface) {
            return $uri;
        }
        if (is_string($uri)) {
            return new Uri($uri);
        }
        if ($uri === null) {
            return new Uri();
        }
        throw new InvalidArgumentException(
            'Invalid URI provided; must be null, a string, or a Psr\Http\Message\UriInterface instance'
        );
    }

    /**
     * Validate the HTTP method
     *
     * @param ?string $method
     * @throws InvalidArgumentException on invalid HTTP method.
     */
    private function validateMethod(?string $method) {
        if (null === $method) {
            return;
        }

        if (! is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        if (! preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }
    }

    /**
     * Retrieve the host from the URI instance
     *
     * @return string
     */
    private function getHostFromUri() {
        $host  = $this->uri->getHost();
        $host .= $this->uri->getPort()
            ? ':' . $this->uri->getPort()
            : '';
        return $host;
    }

}
