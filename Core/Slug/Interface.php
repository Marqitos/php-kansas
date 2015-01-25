<?php

interface Kansas_Core_Slug_Interface {
	public function getSlug();
}

/**
 * Modifies a string to remove all non ASCII characters and spaces.
 */
function Kansas_Core_Slug_Slugify($text) {
	// replace non letter or digits by -
	$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

	// trim
	$text = trim($text, '-');

	// transliterate
	if (function_exists('iconv'))
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

	// lowercase
	$text = strtolower($text);

	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);

	return empty($text)?
		'n-a':
		$text;
}