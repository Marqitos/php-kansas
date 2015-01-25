<?php
//require_once 'Kansas/Core/Object.php';

abstract class Kansas_Core_Model
	extends Kansas_Core_Object
	implements Serializable {
	
	const VALIDATION_EMPTY		= 0x88888888;
	const VALIDATION_SUCCESS 	= 0x0;
	const KEY_MODEL						= 'm';
	const KEY_RETURN_URL			= 'ru';
	
	protected $row;
	
	protected function __construct($row) {
		$this->row = $row;
	}
	
	/* Metodos de Serializable */
	public function serialize() {
		return serialize($this->row);
	}
	
	public function unserialize($serialized) {
		$this->row = unserialize($serialized);
	}
	
	/* Metodos estaticos */
	public static function getModel(&$mId) {
		global $application;
		try {
			if($mId == null && !System_Guid::tryParse(Kansas_Request::getParam(self::KEY_MODEL, null), $mId))
				return null;
			return $application->getProvider('Model')->getModel($mId);
		} catch(Exception $e) {
			return null;
		}
	}
	
	public static function cancel($mId) {
		global $application;
		$moldel = self::getModel($application, $mId);
		if($moldel != null) {
			$application->getProvider('Model')->delete($mId);
			$ru = $model->getReturnUrl();
		} else {
			$ru = '/';
		}
		
		Kansas_Response::Redirect($ru);
	}
	
	/** 
		* 	Rellena los datos del objeto, con los datos enviados en la peticion web
		*/
	public function fill($map = []) {
		global $application;
		$request = $application->getRequest();
		foreach($this->row as $key => $value) {
			if(isset($map[$key])) {
				if($map[$key] == false)
					continue;
				$this->row[$key] = $request->getParam($map[$key], $value);
			} else
				$this->row[$key] = $request->getParam($key, $value);
		}
		return $this;
	}
	
	public function getRow($key) {
		if(!is_string($key))
			throw new System_ArgumentOutOfRangeException('key', 'Se esperaba una cadena', $key);
		return isset($this->row[$key])? $this->row[$key]:
																		false;
	}
	
	public function getReturnUrl($default = '/') {
		return isset($this->row[self::KEY_RETURN_URL])	? $this->row[self::KEY_RETURN_URL]
																										:	$default;
	}
	
	public function setReturnUrl($value) {
		$this->row[self::KEY_RETURN_URL] = $value;
	}
}