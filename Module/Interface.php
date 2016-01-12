<?php

interface Kansas_Module_Interface {
  public function getDefaultOptions();
  public function getOptions($key = NULL);
  public function setOptions(array $options);
	public function getVersion();
}