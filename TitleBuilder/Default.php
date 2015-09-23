<?php

class Kansas_TitleBuilder_Default
	implements Kansas_TitleBuilder_Interface {
		
  const APPEND	= 'APPEND';
	const SET			= 'SET';
	const PREPEND	= 'PREPEND';

	protected $options;
	protected $_items = [];

	public function __construct(array $options) {
		$this->options = array_merge($this->getDefaultOptions(), $options);
	}
	
	protected function getDefaultOptions() {
		return [
			'separator'		=> ' : ',
			'attachOrder'	=> self::PREPEND,
			'title'				=> ''
		];
	}

	public function getSeparator() {
		return $this->options['separator'];
	}
	public function setSeparator($separator) {
		$this->options['separator'] = (string) $separator;
	}
	
	public function getAttachOrder() {
		return $this->options['attachOrder'];
	}
	public function setAttachOrder($order) {
		$this->options['attachOrder'] = $order;
	}
	
	public function attach($title) {
		switch($this->options['attachOrder']) {
			case self::APPEND:
				$this->_items[] = $title;
				break;
			case self::SET:
				$this->_items = [$title];
				break;			
			default:
				array_unshift($this->_items, $title);
				break;
		}
	}
	public function setTitle($title) {
		$this->options['title'] = $title;
	}
	
	public function __toString() {
		if(count($this->_items) == 0)
			$result = [$this->options['title']];
		elseif(empty($this->options['title']))
			$result = $this->_items;
		else {
			switch($this->options['attachOrder']) {
				case self::APPEND:
					$result = array_merge((array)$this->options['title'], $this->_items);
					break;
				default:
					$result = array_merge($this->_items, (array)$this->options['title']);
					break;
			}
		}
    return implode($this->getSeparator(), $result);
	}
		
}