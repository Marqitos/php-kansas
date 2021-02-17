<?php

namespace Kansas\View;

class Template {

    private $data;
    private $script;
    private static $datacontext;

    /// Constructor
    public function __construct($script, array $data) {
        $this->script   = $script;
        $this->data     = $data;
    }
    
    public function fetch() {
        self::$datacontext = $this->data;
        try {
            ob_start();
            include $this->script;
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public static function getDatacontext($index = null) {
        if($index == null) {
            return self::$datacontext;
        } else if(isset(self::$datacontext[$index])) {
            return self::$datacontext[$index];
        } else {
            return false;
        }
    }

    public static function setDatacontext($index, $value) {
        self::$datacontext[$index] = $value;
    }

    public static function getTitle() {
        global $application;
        $title = $application->createTitle();
        if(isset(self::$datacontext['title'])) {
            $title->setTitle(self::$datacontext['title']);
        }
        return $title;     
    }

}