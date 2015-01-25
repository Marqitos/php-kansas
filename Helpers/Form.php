<?php

class Kansas_Helpers_Form {
	static public function hidden($id, $value) {
		if(!empty($value))
			echo('<input id="' . $id . '" name="' . $id . '" type="hidden" value="' . mb_convert_encoding($value, "HTML-ENTITIES") . '" />');
	}
	static public function input($type, $id, $value, $attribs = array()) {
		echo('<input id="' . $id . '" name="' . $id . '" type="' . $type . '" ');
		if(!empty($value))
			echo('value="' . mb_convert_encoding($value, "HTML-ENTITIES") . '" ');
		if(is_string($attribs))
			$attribs = explode(" ", $attribs);
		foreach($attribs as $key => $value)
			if(is_int($key))
				echo($value . '="' . mb_convert_encoding($value, "HTML-ENTITIES") . '" ');
			else
				echo($key . '="' . mb_convert_encoding($value, "HTML-ENTITIES") . '" ');
		echo('/>');
	}

}