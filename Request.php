<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

use Zend\Http\Headers;
use Zend\Http\Header\Cookie;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\ParametersInterface;
use Zend\Uri\Http as HttpUri;
use Zend\Validator\Hostname as HostnameValidator;

class Kansas_Request implements Kansas_Request_Interface {

    /**#@+
     * @const string Version constant numbers
     */
    const VERSION_10 = '1.0';
    const VERSION_11 = '1.1';
    /**#@-*/
	
    /**#@+
     * @const string METHOD constant names
     */
    const METHOD_OPTIONS  = 'OPTIONS';
    const METHOD_GET      = 'GET';
    const METHOD_HEAD     = 'HEAD';
    const METHOD_POST     = 'POST';
    const METHOD_PUT      = 'PUT';
    const METHOD_DELETE   = 'DELETE';
    const METHOD_TRACE    = 'TRACE';
    const METHOD_CONNECT  = 'CONNECT';
    const METHOD_PATCH    = 'PATCH';
    const METHOD_PROPFIND = 'PROPFIND';
    /**#@-*/

    /**
     * @var string
     */
    protected $version = self::VERSION_11;

    /**
     * @var Headers|null
     */
    protected $headers = null;

    /**
     * @var string
     */
    protected $method = self::METHOD_GET;

    /**
     * @var string|HttpUri
     */
    protected $uri = null;

    /**
     * @var ParametersInterface
     */
    protected $queryParams = null;

    /**
     * @var ParametersInterface
     */
    protected $postParams = null;

    /**
     * @var ParametersInterface
     */
    protected $fileParams = null;
	
    /**
     * Base URL of the application.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Base Path of the application.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Actual request URI, independent of the platform.
     *
     * @var string
     */
    protected $requestUri;

    /**
     * PHP server params ($_SERVER)
     *
     * @var ParametersInterface
     */
    protected $serverParams = null;

    /**
     * PHP environment params ($_ENV)
     *
     * @var ParametersInterface
     */
    protected $envParams = null;

    /**
     * Construct
     * Instantiates request.
     */
    public function __construct() {
			if ($_COOKIE)
				$this->setCookies(new Parameters($_COOKIE));
			$this->setServer(new Parameters($_SERVER));
    }

    /**
     * Set the HTTP version for this object, one of 1.0 or 1.1
     * (AbstractMessage::VERSION_10, AbstractMessage::VERSION_11)
     *
     * @param  string $version (Must be 1.0 or 1.1)
     * @return AbstractMessage
     * @throws Exception\InvalidArgumentException
     */
    public function setVersion($version) {
        if ($version != self::VERSION_10 && $version != self::VERSION_11)
					throw new System_OutOfRangeException('Not valid or not supported HTTP version: ' . $version);

        $this->version = $version;
        return $this;
    }

    /**
     * Return the HTTP version for this request
     *
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Set the method for this request
     *
     * @param  string $method
     * @return Request
     * @throws Exception\InvalidArgumentException
     */
    public function setMethod($method) {
        $method = strtoupper($method);
        if (!defined('static::METHOD_' . $method)) {
            throw new Exception\InvalidArgumentException('Invalid HTTP method passed');
        }
        $this->method = $method;
        return $this;
    }
    /**
     * Return the method for this request
     *
     * @return string
     */
    public function getMethod() {
			return $this->method;
    }

    /**
     * Set the URI/URL for this request, this can be a string or an instance of Zend\Uri\Http
     *
     * @throws Exception\InvalidArgumentException
     * @param string|HttpUri $uri
     * @return Request
     */
    public function setUri($uri)
    {
        if (is_string($uri)) {
            try {
                $uri = new HttpUri($uri);
            } catch (UriException\InvalidUriPartException $e) {
                throw new Exception\InvalidArgumentException(
                    sprintf('Invalid URI passed as string (%s)', (string) $uri),
                    $e->getCode(),
                    $e
                );
            }
        } elseif (!($uri instanceof HttpUri)) {
            throw new Exception\InvalidArgumentException(
                'URI must be an instance of Zend\Uri\Http or a string'
            );
        }
        $this->uri = $uri;

        return $this;
    }

    /**
     * Return the URI for this request object
     *
     * @return HttpUri
     */
    public function getUri() {
        if ($this->uri === null || is_string($this->uri)) {
            $this->uri = new HttpUri($this->uri);
        }
        return $this->uri;
    }

    /**
     * Return the URI for this request object as a string
     *
     * @return string
     */
    public function getUriString() {
        if ($this->uri instanceof HttpUri) {
            return $this->uri->toString();
        }
        return $this->uri;
    }

    /**
     * Get raw request body
     *
     * @return string
     */
    public function getContent() {
			if (empty($this->content)) {
				$requestBody = file_get_contents('php://input');
				if (strlen($requestBody) > 0)
					$this->content = $requestBody;
			}

			return $this->content;
    }

    /**
     * Set cookies
     *
     * Instantiate and set cookies.
     *
     * @param $cookie
     * @return Request
     */
    public function setCookies($cookie) {
			$this->getHeaders()->addHeader(new Cookie((array) $cookie));
			return $this;
    }

    /**
     * Set the request URI.
     *
     * @param  string $requestUri
     * @return self
     */
    public function setRequestUri($requestUri) {
			$this->requestUri = $requestUri;
			return $this;
    }

    /**
     * Get the request URI.
     *
     * @return string
     */
    public function getRequestUri() {
			if ($this->requestUri === null)
				$this->requestUri = $this->detectRequestUri();
			return $this->requestUri;
    }

    /**
     * Set the base URL.
     *
     * @param  string $baseUrl
     * @return self
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if ($this->baseUrl === null) {
            $this->setBaseUrl($this->detectBaseUrl());
        }
        return $this->baseUrl;
    }

    /**
     * Set the base path.
     *
     * @param  string $basePath
     * @return self
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        return $this;
    }

    /**
     * Get the base path.
     *
     * @return string
     */
    public function getBasePath()
    {
        if ($this->basePath === null) {
            $this->setBasePath($this->detectBasePath());
        }

        return $this->basePath;
    }

    /**
     * Provide an alternate Parameter Container implementation for server parameters in this object,
     * (this is NOT the primary API for value setting, for that see getServer())
     *
     * @param  ParametersInterface $server
     * @return Request
     */
    public function setServer(ParametersInterface $server)
    {
        $this->serverParams = $server;

        // This seems to be the only way to get the Authorization header on Apache
        if (function_exists('apache_request_headers')) {
            $apacheRequestHeaders = apache_request_headers();
            if (!isset($this->serverParams['HTTP_AUTHORIZATION'])) {
                if (isset($apacheRequestHeaders['Authorization'])) {
                    $this->serverParams->set('HTTP_AUTHORIZATION', $apacheRequestHeaders['Authorization']);
                } elseif (isset($apacheRequestHeaders['authorization'])) {
                    $this->serverParams->set('HTTP_AUTHORIZATION', $apacheRequestHeaders['authorization']);
                }
            }
        }

        // set headers
        $headers = array();

        foreach ($server as $key => $value) {
            if ($value || (!is_array($value) && strlen($value))) {
                if (strpos($key, 'HTTP_') === 0) {
                    if (strpos($key, 'HTTP_COOKIE') === 0) {
                        // Cookies are handled using the $_COOKIE superglobal
                        continue;
                    }

                    $headers[strtr(ucwords(strtolower(strtr(substr($key, 5), '_', ' '))), ' ', '-')] = $value;
                } elseif (strpos($key, 'CONTENT_') === 0) {
                    $name = substr($key, 8); // Remove "Content-"
                    $headers['Content-' . (($name == 'MD5') ? $name : ucfirst(strtolower($name)))] = $value;
                }
            }
        }

        $this->getHeaders()->addHeaders($headers);

        // set method
        if (isset($this->serverParams['REQUEST_METHOD'])) {
            $this->setMethod($this->serverParams['REQUEST_METHOD']);
        }

        // set HTTP version
        if (isset($this->serverParams['SERVER_PROTOCOL'])
            && strpos($this->serverParams['SERVER_PROTOCOL'], self::VERSION_10) !== false
        ) {
            $this->setVersion(self::VERSION_10);
        }

        // set URI
        $uri = new HttpUri();

        // URI scheme
        if ((!empty($this->serverParams['HTTPS']) && strtolower($this->serverParams['HTTPS']) !== 'off')
            || (!empty($this->serverParams['HTTP_X_FORWARDED_PROTO']) && $this->serverParams['HTTP_X_FORWARDED_PROTO'] == 'https')
        ) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }
        $uri->setScheme($scheme);

        // URI host & port
        $host = null;
        $port = null;

        // Set the host
        if ($this->getHeaders()->get('host')) {
            $host = $this->getHeaders()->get('host')->getFieldValue();

            // works for regname, IPv4 & IPv6
            if (preg_match('|\:(\d+)$|', $host, $matches)) {
                $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
                $port = (int) $matches[1];
            }

            // set up a validator that check if the hostname is legal (not spoofed)
            $hostnameValidator = new HostnameValidator(array(
                'allow'       => HostnameValidator::ALLOW_ALL,
                'useIdnCheck' => false,
                'useTldCheck' => false,
            ));
            // If invalid. Reset the host & port
            if (!$hostnameValidator->isValid($host)) {
                $host = null;
                $port = null;
            }
        }

        if (!$host && isset($this->serverParams['SERVER_NAME'])) {
            $host = $this->serverParams['SERVER_NAME'];
            if (isset($this->serverParams['SERVER_PORT'])) {
                $port = (int) $this->serverParams['SERVER_PORT'];
            }
            // Check for missinterpreted IPv6-Address
            // Reported at least for Safari on Windows
            if (isset($this->serverParams['SERVER_ADDR']) && preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)) {
                $host = '[' . $this->serverParams['SERVER_ADDR'] . ']';
                if ($port . ']' == substr($host, strrpos($host, ':')+1)) {
                    // The last digit of the IPv6-Address has been taken as port
                    // Unset the port so the default port can be used
                    $port = null;
                }
            }
        }
        $uri->setHost($host);
        $uri->setPort($port);

        // URI path
        $requestUri = $this->getRequestUri();
        if (($qpos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $qpos);
        }

        $uri->setPath($requestUri);

        // URI query
        if (isset($this->serverParams['QUERY_STRING'])) {
            $uri->setQuery($this->serverParams['QUERY_STRING']);
        }

        $this->setUri($uri);

        return $this;
    }

    /**
     * Return the parameter container responsible for server parameters or a single parameter value.
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @see http://www.faqs.org/rfcs/rfc3875.html
     * @return \Zend\Stdlib\ParametersInterface|mixed
     */
    public function getServer($name = null, $default = null)
    {
        if ($name === null)
            return $this->serverParams;

        return $this->serverParams->get($name, $default);
    }

    /**
     * Provide an alternate Parameter Container implementation for env parameters in this object,
     * (this is NOT the primary API for value setting, for that see env())
     *
     * @param  ParametersInterface $env
     * @return Request
     */
    public function setEnv(ParametersInterface $env) {
        $this->envParams = $env;
        return $this;
    }

    /**
     * Return the parameter container responsible for env parameters or a single parameter value.
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.     * @return \Zend\Stdlib\ParametersInterface
     * @return \Zend\Stdlib\ParametersInterface|mixed
     */
    public function getEnv($name = null, $default = null) {
        if ($this->envParams === null)
            $this->envParams = new Parameters($_ENV);

        if ($name === null)
            return $this->envParams;

        return $this->envParams->get($name, $default);
    }

    /**
     * Convert PHP superglobal $_FILES into more sane parameter=value structure
     * This handles form file input with brackets (name=files[])
     *
     * @return array
     */
    protected function mapPhpFiles()
    {
        $files = array();
        foreach ($_FILES as $fileName => $fileParams) {
            $files[$fileName] = array();
            foreach ($fileParams as $param => $data) {
                if (!is_array($data)) {
                    $files[$fileName][$param] = $data;
                } else {
                    foreach ($data as $i => $v) {
                        $this->mapPhpFileParam($files[$fileName], $param, $i, $v);
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @param array        $array
     * @param string       $paramName
     * @param int|string   $index
     * @param string|array $value
     */
    protected function mapPhpFileParam(&$array, $paramName, $index, $value)
    {
        if (!is_array($value)) {
            $array[$index][$paramName] = $value;
        } else {
            foreach ($value as $i => $v) {
                $this->mapPhpFileParam($array[$index], $paramName, $i, $v);
            }
        }
    }

    /**
     * Detect the base URI for the request
     *
     * Looks at a variety of criteria in order to attempt to autodetect a base
     * URI, including rewrite URIs, proxy URIs, etc.
     *
     * @return string
     */
    protected function detectRequestUri()
    {
        $requestUri = null;
        $server     = $this->getServer();

        // Check this first so IIS will catch.
        $httpXRewriteUrl = $server->get('HTTP_X_REWRITE_URL');
        if ($httpXRewriteUrl !== null) {
            $requestUri = $httpXRewriteUrl;
        }

        // Check for IIS 7.0 or later with ISAPI_Rewrite
        $httpXOriginalUrl = $server->get('HTTP_X_ORIGINAL_URL');
        if ($httpXOriginalUrl !== null) {
            $requestUri = $httpXOriginalUrl;
        }

        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = $server->get('IIS_WasUrlRewritten');
        $unencodedUrl    = $server->get('UNENCODED_URL', '');
        if ('1' == $iisUrlRewritten && '' !== $unencodedUrl) {
            return $unencodedUrl;
        }

        // HTTP proxy requests setup request URI with scheme and host [and port]
        // + the URL path, only use URL path.
        if (!$httpXRewriteUrl) {
            $requestUri = $server->get('REQUEST_URI');
        }

        if ($requestUri !== null) {
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }

        // IIS 5.0, PHP as CGI.
        $origPathInfo = $server->get('ORIG_PATH_INFO');
        if ($origPathInfo !== null) {
            $queryString = $server->get('QUERY_STRING', '');
            if ($queryString !== '') {
                $origPathInfo .= '?' . $queryString;
            }
            return $origPathInfo;
        }

        return '/';
    }

    /**
     * Auto-detect the base path from the request environment
     *
     * Uses a variety of criteria in order to detect the base URL of the request
     * (i.e., anything additional to the document root).
     *
     *
     * @return string
     */
    protected function detectBaseUrl()
    {
        $filename       = $this->getServer()->get('SCRIPT_FILENAME', '');
        $scriptName     = $this->getServer()->get('SCRIPT_NAME');
        $phpSelf        = $this->getServer()->get('PHP_SELF');
        $origScriptName = $this->getServer()->get('ORIG_SCRIPT_NAME');

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            // 1and1 shared hosting compatibility.
            $baseUrl = $origScriptName;
        } else {
            // Backtrack up the SCRIPT_FILENAME to find the portion
            // matching PHP_SELF.

            $baseUrl  = '/';
            $basename = basename($filename);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        // Does the base URL have anything in common with the request URI?
        $requestUri = $this->getRequestUri();

        // Full base URL matches.
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        // Directory portion of base path matches.
        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $truncatedRequestUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        // No match whatsoever
        if (empty($basename) || false === strpos($truncatedRequestUri, $basename)) {
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of the base path. $pos !== 0 makes sure it is not matching a
        // value from PATH_INFO or QUERY_STRING.
        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }

    /**
     * Autodetect the base path of the request
     *
     * Uses several criteria to determine the base path of the request.
     *
     * @return string
     */
    protected function detectBasePath()
    {
        $filename = basename($this->getServer()->get('SCRIPT_FILENAME', ''));
        $baseUrl  = $this->getBaseUrl();

        // Empty base url detected
        if ($baseUrl === '') {
            return '';
        }

        // basename() matches the script filename; return the directory
        if (basename($baseUrl) === $filename) {
            return str_replace('\\', '/', dirname($baseUrl));
        }

        // Base path is identical to base URL
        return $baseUrl;
    }
		
		
    /**
     * Provide an alternate Parameter Container implementation for query parameters in this object,
     * (this is NOT the primary API for value setting, for that see getQuery())
     *
     * @param \Zend\Stdlib\ParametersInterface $query
     * @return Request
     */
    public function setQuery(ParametersInterface $query) {
        $this->queryParams = $query;
        return $this;
    }

    /**
     * Return the parameter container responsible for query parameters or a single query parameter
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @return \Zend\Stdlib\ParametersInterface|mixed
     */
    public function getQuery($name = null, $default = null) {
        if ($this->queryParams === null)
            $this->queryParams = ($_GET) ? new Parameters($_GET) : new Parameters();

        if ($name === null)
            return $this->queryParams;

        return $this->queryParams->get($name, $default);
    }
		
		    /**
     * Provide an alternate Parameter Container implementation for post parameters in this object,
     * (this is NOT the primary API for value setting, for that see getPost())
     *
     * @param \Zend\Stdlib\ParametersInterface $post
     * @return Request
     */
    public function setPost(ParametersInterface $post) {
        $this->postParams = $post;
        return $this;
    }

    /**
     * Return the parameter container responsible for post parameters or a single post parameter.
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @return \Zend\Stdlib\ParametersInterface|mixed
     */
    public function getPost($name = null, $default = null) {
        if ($this->postParams === null)
            $this->postParams = ($_POST) ? new Parameters($_POST) : new Parameters();

        if ($name === null)
            return $this->postParams;

        return $this->postParams->get($name, $default);
    }
		
		/**
     * Return the Cookie header, this is the same as calling $request->getHeaders()->get('Cookie');
     *
     * @convenience $request->getHeaders()->get('Cookie');
     * @return Header\Cookie|bool
     */
    public function getCookie()
    {
        return $this->getHeaders()->get('Cookie');
    }

    /**
     * Provide an alternate Parameter Container implementation for file parameters in this object,
     * (this is NOT the primary API for value setting, for that see getFiles())
     *
     * @param  ParametersInterface $files
     * @return Request
     */
    public function setFiles(ParametersInterface $files)
    {
        $this->fileParams = $files;
        return $this;
    }

    /**
     * Return the parameter container responsible for file parameters or a single file.
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @return ParametersInterface|mixed
     */
    public function getFiles($name = null, $default = null)
    {
        if ($this->fileParams === null) {
					if ($_FILES) {
							$files = $this->mapPhpFiles();
							$this->fileParams = new Parameters($files);
					} else
            $this->fileParams = new Parameters();
        }

        if ($name === null)
            return $this->fileParams;

        return $this->fileParams->get($name, $default);
    }
		
    /**
     * Return the header container responsible for headers or all headers of a certain name/type
     *
     * @see \Zend\Http\Headers::get()
     * @param string|null           $name            Header name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the requested header is missing.
     * @return \Zend\Http\Headers|bool|\Zend\Http\Header\HeaderInterface|\ArrayIterator
     */
    public function getHeaders($name = null, $default = false) {
			if ($this->headers === null || is_string($this->headers)) {
					// this is only here for fromString lazy loading
					$this->headers = (is_string($this->headers)) ? Headers::fromString($this->headers) : new Headers();
			}

			if ($name === null)
					return $this->headers;

			return $this->headers->has($name) ? $this->headers->get($name) : $default;
    }
		
    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    public function isOptions()
    {
        return ($this->method === self::METHOD_OPTIONS);
    }

    /**
     * Is this a PROPFIND method request?
     *
     * @return bool
     */
    public function isPropFind()
    {
        return ($this->method === self::METHOD_PROPFIND);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    public function isGet()
    {
        return ($this->method === self::METHOD_GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    public function isHead()
    {
        return ($this->method === self::METHOD_HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    public function isPost()
    {
        return ($this->method === self::METHOD_POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    public function isPut()
    {
        return ($this->method === self::METHOD_PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    public function isDelete()
    {
        return ($this->method === self::METHOD_DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    public function isTrace()
    {
        return ($this->method === self::METHOD_TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    public function isConnect()
    {
        return ($this->method === self::METHOD_CONNECT);
    }

    /**
     * Is this a PATCH method request?
     *
     * @return bool
     */
    public function isPatch()
    {
        return ($this->method === self::METHOD_PATCH);
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        $header = $this->getHeaders()->get('X_REQUESTED_WITH');
        return false !== $header && $header->getFieldValue() == 'XMLHttpRequest';
    }

    /**
     * Is this a Flash request?
     *
     * @return bool
     */
    public function isFlashRequest()
    {
        $header = $this->getHeaders()->get('USER_AGENT');
        return false !== $header && stristr($header->getFieldValue(), ' flash');
    }

	public static function getParam($var, $defaultValue = null) {
		return isset($_REQUEST[$var])?
			$_REQUEST[$var]:
			$defaultValue;
	}
	
	public static function getReturnUrl($var = 'ru', $defaultValue = null) {
		return isset($_REQUEST[$var]) 					?	$_REQUEST[$var]:
			     !empty($defaultValue)						?	$defaultValue:
				   isset($_SERVER['HTTP_REFERER'])	?	$_SERVER['HTTP_REFERER']:
																							'/';
	}
	
	public static function fillModel(array &$model) {
		$result = array();
		foreach($model as $key => $value)
			$model[$key] = self::getParam($key, $value);
	}

}
