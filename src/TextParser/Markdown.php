<?php declare(strict_types = 1);
/**
 * Markdown Class
 *
 * Markdown Markup Parser.
 *
 * @author           Pierre-Henry Soria <ph7software@gmail.com>
 * @copyright        (c) 2012-2013, Pierre-Henry Soria. All Rights Reserved.
 * @license          Lesser General Public License; See LICENSE.txt in the root directory.
 * @link             http://github.com/pH-7
 * @package          PH7 / Parse / Text
 * @version          0.8
 */

namespace Kansas\TextParser;

use function preg_replace;
use function str_replace;

require_once 'Kansas/TextParser/TextParserAbstract.php';

class Markdown extends TextParserAbstract {

    # Miembros de TextParserAbstract
    /**
     * Run the transform methods
     *
     * @access protected
     * @return void
     */
    protected function run() : void {
        $this->strong();
        $this->italic();
        $this->code();
        $this->img();
        $this->link();
        $this->blockquote();
        $this->heading();
        $this->paragraph();
        $this->br();
        $this->hr();
    }
    # -- Miembros de TextParserAbstract

    /**
     * Strong
     *
     * @access protected
     * @return void
     */
    protected function strong() {
        // Strong emphasis
        $this->sText = preg_replace('/__(.+?)__/s', '<strong>\1</strong>', $this->sText);

        // Alternative syntax
        $this->sText = preg_replace('/\*\*(.+?)\*\*/s', '<strong>\1</strong>', $this->sText);
    }

    /**
     * Italic
     *
     * @access protected
     * @return void
     */
    protected function italic() {
        // Emphasis
        $this->sText = preg_replace('/_([^_]+)_/', '<em>\1</em>', $this->sText);

        // Alternative syntax
        $this->sText = preg_replace('/\*([^\*]+)\*/', '<em>\1</em>', $this->sText);
    }

    /**
     * Code
     *
     * @access protected
     * @return void
     */
    protected function code() {
        $this->sText = preg_replace('/`(.+?)`/s', '<code>\1</code>', $this->sText);
    }

    /**
     * Links
     *
     * @access protected
     * @return void
     */
    protected function link() {
        // [linked text](link URL)
        $this->sText = preg_replace('/\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\)/i', '<a href="\2">\1</a>', $this->sText);

        // [linked text][link URL] (alternative syntax)
        $this->sText = preg_replace('/\[([^\]]+)]\[([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\]/i', '<a href="\2">\1</a>', $this->sText);

        // [linked text]: link URL "title" (alternative syntax)
        $this->sText = preg_replace('/\[([^\]]+)]: ([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+) "([^"]+)"/i', '<a href="\2" title="\3">\1</a>', $this->sText);
    }

    /**
     *
     * Images
     *
     * @access protected
     * @return void
     */
    protected function img() {
        // With title ![alt image](url image) "title of image"
        $this->sText = preg_replace('/!\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\) "([^"]+)"/', '<img src="\2" alt="\1" title="\3" />', $this->sText);

        // Without title ![alt image](url image)
        $this->sText = preg_replace('/!\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\)/', '<img src="\2" alt="\1" />', $this->sText);
    }

    /**
     * Blockquote
     *
     * @access protected
     * @return void
     */
    protected function blockquote() {
        // Blockquotes
        $this->sText = preg_replace('/> "(.+?)"/', '<blockquotes><p>\1</p></blockquote>', $this->sText);
    }

    /**
     * Break line
     *
     * @access protected
     * @return void
     */
    protected function br() {
        // Line breaks
        $this->sText = str_replace("\n", '<br />', $this->sText);
    }

    /**
     * Thematic break
     *
     * @access protected
     * @return void
     */
    protected function hr() {
        $this->sText = preg_replace('/^(\s)*----+(\s*)$/m', '<hr />', $this->sText);
    }

    /**
     * Headings
     *
     * @access protected
     * @return void
     */
    protected function heading() {
        $this->sText = preg_replace('/##### (.+?)\n/', '<h5>\1</h5>', $this->sText); //h5
        $this->sText = preg_replace('/#### (.+?)\n/', '<h4>\1</h4>', $this->sText); //h4
        $this->sText = preg_replace('/### (.+?)\n/', '<h3>\1</h3>', $this->sText); //h3
        $this->sText = preg_replace('/## (.+?)\n/', '<h2>\1</h2>', $this->sText); //h2
        $this->sText = preg_replace('/# (.+?)\n/', '<h1>\1</h1>', $this->sText); //h1

        // Alternative syntax
        $this->sText = preg_replace('/=======(.+?)=======/s', '<h1>\1</h1>', $this->sText); //h1
        $this->sText = preg_replace('/======(.+?)======/s', '<h2>\1</h2>', $this->sText); //h2
        $this->sText = preg_replace('/=====(.+?)=====/s', '<h3>\1</h3>', $this->sText); //h3
        $this->sText = preg_replace('/====(.+?)====/s', '<h4>\1</h4>', $this->sText); //h4
        $this->sText = preg_replace('/===(.+?)===/s', '<h5>\1</h5>', $this->sText); //h5
    }

}
