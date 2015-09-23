<?php

class Kansas_TitleBuilder_FullTitle 
	extends Kansas_TitleBuilder_Default {

	public function setFullTitle($title) {
		$this->options['fullTitle'] = $title;
	}
	
	public function getFullTitle() {
		return empty($this->options['fullTitle']) ?	$this->options['title']:
																							  $this->options['fullTitle'];
	}
	
	protected function getDefaultOptions() {
    return array_merge(parent::getDefaultOptions(), ['fullTitle' => '']);
	}
	
	public function __toString() {
		return count($this->_items) == 0 ? (string) $this->getFullTitle():
																			          parent::__toString();
	}
}