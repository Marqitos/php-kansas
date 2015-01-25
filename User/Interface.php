<?php

interface Kansas_User_Interface
	extends Kansas_Core_GuidItem_Interface {

	public function getName();
	public function getEmail();
	public function getSubscriptions();
	public function isApproved();
	public function isLockedOut();
	public function getRoles();
	public function isInRole($roleName);
	public function getLastLockOutDate();
	public function getComment();
}
