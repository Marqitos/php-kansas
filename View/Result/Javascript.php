<?php
/**
 * Representa el resultado de una solicitud, en la que se va a devolver codigo javascript
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\View\Result;

use Kansas\View\Result\StringAbstract;

require_once 'Kansas/View/Result/StringAbstract.php';

class Javascript extends StringAbstract {
        
    private $components;
    
    public function __construct($components) {
        parent::__construct('application/javascript; charset: UTF-8');
        $this->components	= $components;
    }

    public function getResult(&$cache) {
        global $application;
        return $application->getPlugin('Javascript')->build($this->components, $cache);
    }

}