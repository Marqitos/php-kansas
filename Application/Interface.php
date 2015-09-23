<?php

interface Kansas_Application_Interface {
	public function getDb();
	public function getLoader($loaderName);
	public function dispatch($params);
	public function run();
	public function createView();
	public function getEnvironment();
}