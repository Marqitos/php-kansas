<?php declare(strict_types = 1);
/**
  * Plain Text Parser Class
  *
  * Plain Text Parser with HTML5 support.
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @version    0.1
  */

namespace Kansas\TextParser;

use function nl2br;

require_once 'Kansas/TextParser/TextParserAbstract.php';

class Plain extends TextParserAbstract {

  # Miembros de TextParserAbstract

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
  # -- Miembros de TextParserAbstract

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
