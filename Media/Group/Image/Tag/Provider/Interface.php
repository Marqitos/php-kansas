<?php

interface Kansas_Media_Group_Image_Tag_Provider_Interface {
	public function getTagTypes();
//	public function getTagGroups();
	public function getTagGroup(System_Guid $id);
	public function getTagGroupsByImageId(System_Guid $id);
	public function getTagGroupsByType($type);
}
