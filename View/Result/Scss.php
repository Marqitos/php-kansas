<?php
/**
 * Representa el resultado de una solicitud, en la que se va a devolver codigo css a partir de scss
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\View\Result;

use Kansas\View\Result\StringAbstract;

require_once 'Kansas/View/Result/StringAbstract.php';

/**
 * Representa una respuesta a una solicitud css a partir de un archivo scss
 */
class Scss extends StringAbstract {
    
    private $file;
    
    public function __construct($file) {
        parent::__construct('text/css; charset= UTF-8');
        $this->file = $file;
    }
    
    public function getResult(&$noCache) {
        global $application;
        $noCache = true;
        return $application->getPlugin('Scss')->toCss($this->file, $noCache);
    }  

}