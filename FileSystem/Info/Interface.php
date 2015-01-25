<?php

interface Kansas_FileSystem_Info_Interface {
	public function getFullPath();
	public function getName();
	public function getExists();
}