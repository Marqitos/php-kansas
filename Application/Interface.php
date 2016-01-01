<?php

interface Kansas_Application_Interface {
	public function dispatch($params);
	public function getDb();
	public function getEnvironment();
	public function getLoader($loaderName);
	public function getView();
	public function run();
}