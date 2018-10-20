<?php

namespace Kansas\Router;

use Kansas\Router;
use System\NotSuportedException;

require_once 'Kansas/Router.php';

class Auth extends Router {

  private $_actions = [];

  /// Miembros de System_Configurable_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
      case 'development':
      case 'test':
    return [
      'base_path'	=> 'cuenta',
      'params'	=> []
    ];
      default:
        require_once 'System/NotSuportedException.php';
        throw new NotSuportedException("Entorno no soportado [$environment]");
    }
  }
    
  public function match() {
    global $environment;
    $params = false;
    $path = trim($environment->getRequest()->getUri()->getPath(), '/');

    foreach($this->_actions as $action) {
      if(isset($action['path'])) {
        $actionPath = $this->getActionPath($action['path']);
        if(substr($actionPath, 1) == $path) {
          unset($action['path']);
          $params = $this->getParams($action);
          break;
        }
      }
    }
    return $params;
  }
  
  public function assemble($data = [], $reset = false, $encode = false) {
     if(isset($data['action'])) {
       if(isset($this->_actions[$data['action']]['path'])) {
        $path = $this->getActionPath($this->_actions[$data['action']]['path']);
        unset($data['action']);
        return count($data) == 0
          ? $path
          : $path . '?' . http_build_query($data);
      }	else
        return false;
    }
    return parent::assemble();
  }

  public function getActionPath($path) {
    return (substr($path, 0, 1) == '/')
      ? $path
      : rtrim(parent::assemble() . '/' . $path, '/');
  }
  
  public function addActions(array $actions = []) {
    foreach ($actions as $key => $value) {
      $this->_actions[$key] = $value;
    }
  }
}