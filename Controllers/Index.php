<?php

class Kansas_Controllers_Index
	extends Kansas_Controller_Abstract {

	public function Index(array $vars = []) {
		return $this->createViewResult('page.default.tpl');
	}
	
	public function Redirection(array $vars = []) {
    return Kansas_View_Result_Redirect::gotoUrl($this->getParam('gotoUrl'));
	}
	
	public function Css() {
		global $application;
		$files = $this->getParam('files');
		$cssResult = new Kansas_View_Result_Css($files);
		$backendCache = $application->getModule('BackendCache');
		if($backendCache) {
			$cacheId = $cssResult->getCacheId();
			if($backendCache->test($cacheId)) {
				$content = $backendCache->load($cacheId);
			} else {
				$content = $cssResult->getResult();
				$backendCache->save($content, $cacheId);
			}
			$contentResult = new Kansas_View_Result_Content($content);
			$contentResult->setMimeType('text/css');
			return $contentResult;
		} else
			return $cssResult;
	}


	public function Sass(array $vars = []) {
		return new Kansas_View_Result_Sass($this->getParam('file'));
	}
	
	public function Scss(array $vars = []) {
		return new Kansas_View_Result_Scss($this->getParam('file'));
	}
	
	public function File(array $vars = []) {
		return new Kansas_View_Result_File($this->getParam('file'));
	}
  
  public function Javascript() {
    return new Kansas_View_Result_Javascript((array)$this->getParam('component'));
  }		
	
	public function clearCache() {
    global $application;
    $application->getView()->getEngine()->clearAllCache();

    return Kansas_View_Result_Redirect::gotoUrl($this->getParam('ru', '/'));
	}
	
	public function phpInclude() {
		return new Kansas_View_Result_Include($this->getParam('file'));
	}
	
}