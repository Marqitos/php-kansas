<?php

interface Kansas_Application_Module_Interface {
  public function getDefaultOptions();
  public function getOptions($key = NULL);
  public function setOptions($options);
	public function getVersion();
}