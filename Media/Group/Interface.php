<?php

interface Kansas_Media_Group_Interface
	extends Kansas_Core_GuidItem_Interface,
					Kansas_Core_Collection_Interface {
	
	public function getType();
	public function getSlugCollection();
}