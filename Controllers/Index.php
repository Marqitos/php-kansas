<?php

class Kansas_Controllers_Index
	extends Kansas_Controller_Abstract {

	public function Index($vars = []) {
		$template = $this->getParam('template', 'page.default.tpl');
		$view = $this->createView();
		$view->assign($vars);
		return $this->createResult($view, $template);
	}
	
	public function Redirection() {
		$gotoUrl = $this->getParam('gotoUrl');
		$redirection = new Kansas_View_Result_Redirect();
		$redirection->setGotoUrl($gotoUrl);
		return $redirection;
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


	public function Sass() {
		$file				= $this->getParam('file');
		return new Kansas_View_Result_Sass($file);
	}
	
	public function Scss() {
		$file				= $this->getParam('file');
		return new Kansas_View_Result_Scss($file);
	}
	
	public function File() {
		$file				= $this->getParam('file');
		return new Kansas_View_Result_File($file);
	}
  
  public function Javascript() {
    $components = (array)$this->getParam('component');
    return new Kansas_View_Result_Javascript($components);
  }		
	
	public function clearCache() {
		$ru = $this->getParam('ru', '/');
		$view = $this->createView();
		$view->getEngine()->clearAllCache();
		
		$redirection = new Kansas_View_Result_Redirect();
		$redirection->setGotoUrl($ru);
		return $redirection;
	}
	
	public function phpInclude() {
		return new Kansas_View_Result_Include($this->getParam('file'));
	}
	
}