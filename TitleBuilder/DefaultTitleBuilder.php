<?php

namespace Kansas\TitleBuilder;

use System\Configurable;
use System\NotSupportedException;
use Kansas\TitleBuilder\TitleBuilderInterface;

use function array_merge;
use function array_unshift;
use function count;
use function implode;

require_once 'System/Configurable.php';

class DefaultTitleBuilder extends Configurable implements TitleBuilderInterface {
		
	const APPEND	= 'APPEND';
	const SET		= 'SET';
	const PREPEND	= 'PREPEND';

	protected $_items = [];

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions($environment) : array {
		switch ($environment) {
			case 'production':
			case 'development':
			case 'test':
				return [
					'separator'		=> ' : ',
					'attachOrder'	=> self::PREPEND,
					'title'			=> ''
				];
			default:
				require_once 'System/NotSupportedException.php';
				throw new NotSupportedException("Entorno no soportado [$environment]");
		}
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