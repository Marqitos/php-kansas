<?php

abstract class Kansas_User_Abstract
	extends Kansas_Core_GuidItem_Model 
	implements Kansas_User_Interface {
		
	// Roles predeterminadas
	const ROLE_ADMIN			= 'admin'; // Usuario con todos los permisos
	const ROLE_SUSCRIBER	= 'suscriber'; // Usuario autenticado, sin permisos especiales
	const ROLE_GUEST			= 'guest'; // Usuario no autenticado

	public function getName() {
		return $this->row['name'];
	}
	
	public function getEmail() {
		return $this->row['email'];
	}
	
	public function isApproved() {
		return $this->row['isApproved'] != 0;
	}
	
	public function isLockedOut() {
		return $this->row['isLockedOut'] != 0;
	}
	
	public abstract function getRoles();
	
	public function isInRole($roleName) {
		return in_array($roleName, array_values($this->getRoles()));
	}
	
	public function getSubscriptions() {
		return intval($this->row['subscriptions']);
	}
	
	public function getLastLockOutDate() {
		return $this->row['lastLockOutDate'];
	}

	public function getComment() {
		return $this->row['comment'];
	}
	
	public function getAvatar($default, $size = 80) {
		return "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $this->getEmail() ) ) ) . "?d=" . urlencode( $default ) . "&s=" . $size;		
	}
}