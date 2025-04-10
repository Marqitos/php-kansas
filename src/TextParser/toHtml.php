<?php declare(strict_types = 1);
/**
  * Convierte texto a formato Html
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
 */

namespace Kansas\TextParser;

use Kansas\TextParser\BbCode;
use Kansas\TextParser\HtmlSanitize;
use Kansas\TextParser\Markdown;
use Kansas\TextParser\Plain;
use System\ArgumentOutOfRangeException;
use function strtolower;


const PARSER_BB_CODE    = 'BbCode';
const PARSER_HTML       = 'Html';
const PARSER_MARKDOWN   = 'Markdown';
const PARSER_PLAIN      = 'Txt';

function toHtml(string $text, string $parser = PARSER_PLAIN) {
    return match(strtolower($parser)) {
        strtolower(PARSER_BB_CODE)  => bbCodeToHtml($text),
        strtolower(PARSER_HTML)     => htmlSanitizer($text),
        strtolower(PARSER_MARKDOWN) => markdownToHtml($text),
        strtolower(PARSER_PLAIN)    => plainToHtml($text),
        default                     => invalidParser()
    };
}

function bbCodeToHtml(string $text) : string {
    require_once 'Kansas/TextParser/BbCode.php';
    $parser = new BbCode($text);
    return $parser->__toString();
}

function htmlSanitizer(string $text) : string {
    require_once 'Kansas/TextParser/HtmlSanitize.php';
    $parser = new HtmlSanitize($text);
    return $parser->__toString();
}

function markdownToHtml(string $text) : string {
    require_once 'Kansas/TextParser/Markdown.php';
    $parser = new Markdown($text);
    return $parser->__toString();
}

function plainToHtml(string $text) : string {
    require_once 'Kansas/TextParser/Plain.php';
    $parser = new Plain($text);
    return $parser->__toString();
}

function invalidParser() {
    require_once 'System/ArgumentOutOfRangeException.php';
    throw new ArgumentOutOfRangeException('parser');
}
