<?php

interface Kansas_Application_Interface {
	public function getDb();
	public function getProviderLoader();
	public function getProvider($name);
	public function getRequest();
	public function getRouter();
	public function getControllerLoader();
	public function dispatch($params);
	public function run();
	public function createView();
	public function getEnviroment();
}