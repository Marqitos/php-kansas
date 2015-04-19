<?php

interface Kansas_Request_Interface {
	public function getMethod();
	public function getUri();
	public function getUriString();
	public function getRequestUri();
	public function getQuery($name = null, $default = null);
	public function getPost($name = null, $default = null);
	public function getCookie();
	public function getFiles($name = null, $default = null);
	public function getServer($name = null, $default = null);
	public function getEnv($name = null, $default = null);
	public function getHeaders($name = null, $default = false);

	public function getContent();

	public function isOptions();
	public function isPropFind();
	public function isGet();
	public function isHead();
	public function isPost();
	public function isPut();
	public function isDelete();
	public function isTrace();
	public function isConnect();
	public function isPatch();

	public function isXmlHttpRequest();
	public function isFlashRequest();
}