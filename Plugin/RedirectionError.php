<?php
/**
 * Plugin para causar una redirección en caso de error 404
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use Exception;
use System\Configurable;
use System\NotSupportedException;
use System\Version;
use Kansas\Plugin\PluginInterface;
use Kansas\View\Result\Redirect;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class RedirectionError extends Configurable implements PluginInterface {

	private $next;

	public function __construct(array $options) {
		parent::__construct($options);
		global $application;
		try {
			$this->next = $application->getOptions()['error'];
		} catch(Exception $ex) {
			$this->next = [$application, 'errorManager'];
		}
		$application->setOption('error', [$this, 'errorManager']);
	}
  
	/// Miembros de Kansas_Module_Interface
	public function getDefaultOptions($environment) : array {
		switch ($environment) {
		case 'production':
		case 'development':
		case 'test':
			return [
				'basePath' => '',
				'append'   => true];
		default:
			require_once 'System/NotSupportedException.php';
			throw new NotSupportedException("Entorno no soportado [$environment]");
		}
	}
  
	public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}
  
	public function errorManager($params) {
		if($params['code'] == 404 && $path = $this->options['basePath']) {
			global $environment;
			if($this->options['append']) {
				$path = rtrim($path, '/') . $environment->getRequest()->getRequestUri();
			}
			require_once 'Kansas/View/Result/Redirect.php';
			$result = Redirect::gotoUrl($path);
			$result->executeResult();
		} else {
			call_user_func($this->next, $params);
		}
	}
  
}