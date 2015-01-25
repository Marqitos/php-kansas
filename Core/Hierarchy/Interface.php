<?php

interface Kansas_Core_Hierarchy_Interface {
	public function getParentId();
	public function getParent();

	public function getParentIterator();

	public function getChildren();
}
