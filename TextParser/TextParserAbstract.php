<?php declare(strict_types = 1);
/**
 * @title            Code Abstract Class
 * @desc             The Prototype for the extends code classes.
 *
 * @author           Pierre-Henry Soria <ph7software@gmail.com>
 * @copyright        (c) 2012-2013, Pierre-Henry Soria. All Rights Reserved.
 * @license          Lesser General Public License; See LICENSE.txt in the root directory.
 * @link             http://github.com/pH-7
 * @package          PH7 / Parse / Text
 * @version          1.0
 */

namespace Kansas\TextParser;

use function preg_replace;
use function str_replace;

abstract class TextParserAbstract {

  /**
    * @access public
    */
  public function __construct(
    protected string $sText
  ) {
    $this->sanitize();
    $this->convert();
    $this->run();
  }

  /**
    * @access protected
    * @return void
    */
  protected function sanitize() : void {
    $this->sText = preg_replace('/<script(.*?)>(.*?)<\/script>/is', '', $this->sText);
    $this->convert();
  }

  /**
    * Paragraph
    *
    * @access protected
    * @return void
    */
  protected function paragraph() : void {
    $this->sText = '<p>' . str_replace("\n\n", '</p><p>', $this->sText) . '</p>';
  }

  /**
    * Convert the space
    *
    * @access protected
    * @return void
    */
  protected function convert() : void {
    // Convert Windows (\r\n) to Unix (\n)
    $this->sText = str_replace("\r\n", "\n", $this->sText);

    // Convert Macintosh (\r) to Unix (\n)
    $this->sText = str_replace("\r", "\n", $this->sText);
  }

  /**
    * Displaying the text
    *
    * @access public
    * @abstract
    * @return string The code parsed
    */
  abstract public function __toString();

  /**
    * Run the parse methods
    *
    * @access protected
    * @abstract
    * @return void
    */
  abstract protected function run() : void;

}
