<?php

namespace Kansas\View;

use System\Configurable;
use Kansas\View\ViewInterface;
use Kansas\Environment;
use Kansas\View\Template;

require_once 'System/Configurable.php';
require_once 'Kansas/View/ViewInterface.php';

class PhpInclude extends Configurable implements ViewInterface {

    private $data;
    private $scriptPaths;
    private $caching = false;

    /// Constructor
    public function __construct(array $options) {
        parent::__construct($options);
        $this->clearVars();
    }

    // Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions($environment) : array {
		return [];
    }
    
    // Miembros de Kansas\View\ViewInterface
    public function getEngine() {

    }

    public function setScriptPath($path) {
        $this->scriptPaths = $path;
    }

    public function getScriptPaths() {
        if($this->scriptPaths !== null) {
            return $this->scriptPaths;
        }
        require_once 'Kansas/Environment.php';
        global $environment;
        return $environment->getSpecialFolder(Environment::SF_LAYOUT);
    }

    public function __set($key, $val) {
        $this->data[$key] = $val;
    }

    public function __isset($key) {
        return isset($this->data[$key]);
    }

    public function __unset($key){
        unset($this->data[$key]);
    }


    public function assign($spec, $value = null) {
        if(\is_array($spec)) {
            $this->data = array_merge($this->data, $spec);
        } else {
            $this->data[$spec] = $value;
        }
    }

    public function clearVars() {
        $this->data = $this->options;
    }

    public function render($name) {
        $template = $this->createTemplate($name, $this->data);
        echo $template->fetch();
    }

    public function getCaching() {
        return $this->caching;
    }

    public function setCaching($value) {
        $this->caching = $value;
    }

    public function createTemplate($file, array $data = []) {
        require_once 'Kansas/View/Template.php';
        return new Template($this->getScriptPaths() . $file, $data);
    }

}