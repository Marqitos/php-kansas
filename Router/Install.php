<?php

class Kansas_Router_Install
	extends Kansas_Router_Abstract {

	public function match() {
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');

    if($path == '')
      $params = array_merge($this->options['params'], $this->options['path']['.'], ['router' => $this]);
    elseif(isset($this->options['path'][$path]))
      $params = array_merge($this->options['params'], $this->options['path'][$path], ['router' => $this]);

		return $params;
	}
      
	protected function getDefaultOptions() {
		return array_replace_recursive([
			'path'		  => [
				'.'   => [
					'controller'	=> 'Install',
					'action'			=> 'index']
			]
		], parent::getDefaultOptions());
	}	      
}