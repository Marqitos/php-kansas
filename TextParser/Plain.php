<?php
/**
 * @title            Plain Text Parser Class
 * @desc             Plain Text Parser with HTML5 support.
 *
 * @author           Marcos Porto Mariño <marcosarnoso@msn.com>
 * @version          0.1
 */
 
namespace Kansas\TextParser;

use function nl2br;

require_once 'Kansas/TextParser/TextParserAbstract.php';

class Plain extends TextParserAbstract {

  /**
    * @access public
    * @param string $sText
    */
  public function __construct($sText) {
    $this->sText = $sText;
    parent::__construct();
  }

  /**
    * @access public
    * @return string The code parsed
    */
  public function __toString() {
    return $this->sText;
  }

  /**
    * Run the parse methods
    *
    * @access protected
    * @return void
    */
  protected function run() : void {
    $this->paragraph();
    $this->lineBreaks();
  }

  /**
    * Parse text and processing
    *
    * @access protected
    * @return void
    */
  protected function lineBreaks() : void {
      // Line breaks
      $this->sText = nl2br($this->sText);
  }

}
