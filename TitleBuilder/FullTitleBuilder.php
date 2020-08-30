<?php

namespace Kansas\TitleBuilder;

require_once 'Kansas/TitleBuilder/DefaultTitleBuilder.php';

class FullTitleBuilder extends DefaultTitleBuilder {

	public function setFullTitle($title) {
		$this->options['full_title'] = $title;
	}
	
	public function getFullTitle() {
		return empty($this->options['full_title']) 
			? $this->options['title']
			: $this->options['full_title'];
	}
	
	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions($environment) : array {
		return array_merge(parent::getDefaultOptions($environment), ['full_title' => '']);
	}
	
	public function __toString() {
		return count($this->_items) == 0
			? (string) $this->getFullTitle()
			: parent::__toString();
	}
}