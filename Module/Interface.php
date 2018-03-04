<?php
require_once 'System/Configurable/Interface.php';

interface Kansas_Module_Interface
  extends System_Configurable_Interface {
	public function getVersion();
}