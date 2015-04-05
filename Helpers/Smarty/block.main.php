<?php 

function smarty_block_main($params, $content, Smarty_Internal_Template $template, &$repeat){
	if(!$repeat && isset($content)) {
		$layout = ColumnsLayout::search($template);
		$layout->main($params, $content);
	}
}