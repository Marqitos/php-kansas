<?php 

function smarty_block_slidebar($params, $content, Smarty_Internal_Template $template, &$repeat){
	if(!$repeat && isset($content)) {
		$layout = ColumnsLayout::search($template);
		$layout->slidebar($params, $content);
	}
}