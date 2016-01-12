<?php

class Kansas_Controller_Error
	extends Kansas_Controller_Abstract {
	
	public function Index(array $params) {
		global $application;
		global $environment;
		$application->getView()->setCaching(false);
		
    switch($this->getParam('code')) {
			case 403:
				header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
				break;
			case 404:
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
				break;
			default:
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		}
		
		return $this->createViewResult('page.error.tpl', [
      'title'       => 'Error',
      'env'         => $application->getEnvironment(),
      'pageTitle'   => $this->getParam('message'),
      'requestUri'  => $environment->getRequest()->getUriString(),
      'sugestions'  => []
    ]);
	}
	
  // Visor de errores
  public function adminError(array $vars = []) {
    global $application;
    if($vars['requestType'] == 'smarty') {
      $cache = $application->hasModule('BackendCache');
      $ids = $cache->getIdsMatchingTags(['error']);
      $errors = [];
      foreach($ids as $id) {
        $errors[$id] = unserialize($cache->load($id));
        $errors[$id]['log'] = count($errors[$id]['log']);
        $errors[$id]['basename'] = $errors[$id]['httpCode'] == 404 ? $errors[$id]['file'] : '[' . $errors[$id]['line'] . '] ' . basename($errors[$id]['file']);
      }
      $clearResult = $this->getParam('clearResult');
      return $this->createViewResult('part.error-admin.tpl', [
        'errors'      => $errors,
        'clearResult' => $clearResult
      ]);
    } else
      return $application->dispatch(array_merge($vars, [
        'controller'    => 'admin',
        'action'        => 'dispatch',
        'dispatch'      => [
          'controller'      => 'error',
          'action'          => 'adminError']]));
  }
  
  // Detalles de un error
  public function adminErrorDetail(array $vars = []) {
    global $application;
    if($vars['requestType'] == 'smarty') {
      $cache = $application->hasModule('BackendCache');
      $error = $this->getParam('error');
      $selected = $this->getParam('selected');
      $error['basename'] = $error['httpCode'] == 404 ? $error['file'] : '[' . $error['line'] . '] ' . basename($error['file']);
      $ids = $error['log'];
      $error['log'] = [];
      $trace;
      foreach($ids as $id) {
        $current = unserialize($cache->load('error-' . $error['id'] . $id ));
        if($current) {
          $error['log'][$id] = $current;
          if($selected == null)
            $selected = $id;
        }
        if($selected == $id)
          $trace = $error['log'][$id]['trace'];
        unset($error['log'][$id]['trace']);
      }

      $trace = array_map(function($item) {
        $item['basename'] = basename($item['file']);
        $item['args'] = array_map(function($arg) {
          ob_start();
          var_dump($arg); 
          $result = ob_get_contents(); 
          ob_end_clean();
          return $result; 
        }, $item['args']);
        return $item;
      }, $trace);
      

      return $this->createViewResult('part.error-admin-detail.tpl', [
        'error'     => $error,
        'selected'  => $selected,
        'trace'     => $trace
      ]);
    } else
      return $application->dispatch(array_merge($vars, [
        'controller'    => 'admin',
        'action'        => 'dispatch',
        'dispatch'      => [
          'controller'      => 'error',
          'action'          => 'adminErrorDetail']]));
  }
  
  // Eliminar registros de errores
  public function adminErrorClear(array $vars = []) {
    global $application;
    $cache = $application->hasModule('BackendCache');
    $result = $cache->clean(Kansas_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['error']);
    return Kansas_View_Result_Redirect::gotoUrl('/admin/alerts/errores?clearResult=' .  $result);
  }  
  
}