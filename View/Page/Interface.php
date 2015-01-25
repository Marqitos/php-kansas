<?php

interface Kansas_View_Page_Interface {
	public function getRouter();
	public function getParent();
	public function getKeywords();
	public function getTitle();
	public function getDescription();
	public function getUrl();
}