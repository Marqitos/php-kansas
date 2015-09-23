<?php
require_once('Kansas/View/Page/Abstract.php');
require_once('Kansas/View/Page/Interface.php');
require_once('Kansas/Router/Interface.php');

class Kansas_View_Page_Post
	extends Kansas_View_Page_Db {
		
	private $_post;

	public function __construct(Kansas_Post_Interface $post, Kansas_View_Page_Interface $parent = null, Kansas_Router_Interface $router = null) {
		parent::__construct($post->getId(), $parent, $router);
		$this->_post = $post;
	}

	public function hasDescription() {
		return true;
	}
	public function getDescription() {
		return $this->_post->getSumary();
	}
	public function setDescription($description) {
		throw new System_NotImplementedException();
	}
	
	public function getUrl() {
		return $this->_post->getUrl();
	}
		
}