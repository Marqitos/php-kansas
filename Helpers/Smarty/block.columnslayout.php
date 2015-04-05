<?php

function smarty_block_columnslayout($params, $content, Smarty_Internal_Template $template, &$repeat){
	global $layouts;
	if($layouts == null)
		$layouts = [];
	$depth = count($template->_tag_stack) - 1;
	if($repeat)
		$layouts[$depth] = new ColumnsLayout($params);
	else if(isset($content)) {
		$output = $layouts[$depth]->render($content, $template);
		unset($layouts[$depth]);
		return $output;
	}
}

class ColumnsLayout {
	
	private $_params;
	private $_main;
	private $_slides;
	
	public $htmlGlobalAttributes = ['accesskey', 'class', 'contenteditable', 'contextmenu', 'dir', 'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck', 'style', 'tabindex', 'title', 'translate'];
	public $htmlEventAttributes = [];
	public $htmlTags = ['div', 'span', 'header', 'nav', 'section', 'article', 'aside', 'footer'];
	private $_output;
	
	public function __construct($params) {
		$this->_params = $params;
		$this->_slides = [];
	}
	
	public function slidebar($params, $content) {
		$position = isset($params['position']) ? $params['position'] : 'left';
		unset($params['position']);
		$this->_slides[$position] = [$params, $content];
	}
	
	public function main($params, $content) {
		$this->_main = [$params, $content];
	}
	
	public function render($content, Smarty_Internal_Template $template) {
		if($this->_main == null)
			$this->_main = [[], $content];
			
			
		// etiqueta d apertura
		$baseTag = $this->openTag($this->_params);
		
		// contenido
		if(count($this->_slides) + count($this->_main[0]) > 0)
			$this->renderBlock($this->_main);
		else
			echo $this->_main[1];
		
		//columnas adicionales
		foreach($this->_slides as $position => $slide) {
			$this->renderBlock($slide);
		}
		
		// etiqeta de cierre
		$this->_output .= '</' . $baseTag . '>';
		return $this->_output;
	}
	
	public static function search(Smarty_Internal_Template $template) {
		global $layouts;
		if($layouts == null) return null;
		$count = count($template->_tag_stack) - 1;
		while($count >= 0) {
			if($template->_tag_stack[$count][0] == "columnslayout")
				return $layouts[$count];
			$count --;
		}
	}
	
	protected function openTag(array &$params) {
		$tag = 'div';
		if(isset($params['tag']) && array_search($params['tag'], $this->htmlTags)) {
			$tag = $params['tag'];
			unset($params['tag']);
		}
		$this->_output .= '<' . $tag;
		foreach($this->htmlGlobalAttributes as $att) {
			if(isset($params[$att])) {
				$this->_output .= ' ' . $att . '="' . $params[$att] . '"';
				unset($params[$att]);
			}
		}
		foreach($params as $key => $value)
			$this->_output .= ' data-' .  str_replace('_', '-', $key) . '="' . $value . '"';
		$this->_output .= '>';
		return $tag;
	}
	
	protected function renderBlock(array $block) {
		$tag = $this->openTag($block[0]);
		$this->_output .= $block[1] . '</' . $tag . '>';
	}
	
}