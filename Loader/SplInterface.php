<?php
/**
 * @see       https://github.com/zendframework/zend-loader for the canonical source repository
 */

namespace Kansas\Loader;

use Traversable;
use System\Configurable\ConfigurableInterface;

require_once 'System\Configurable\ConfigurableInterface.php';

/**
 * Defines an interface for classes that may register with the spl_autoload
 * registry
 */
interface SplInterface extends ConfigurableInterface {
    /**
     * Constructor
     *
     * Allow configuration of the autoloader via the constructor.
     *
     * @param  null|array|Traversable $options
     */
    public function __construct($options = []);

    /**
     * Autoload a class
     *
     * @param   $class
     * @return  mixed
     *          False [if unable to load $class]
     *          get_class($class) [if $class is successfully loaded]
     */
    public function autoload($class);

    /**
     * Register the autoloader with spl_autoload registry
     *
     * Typically, the body of this will simply be:
     * <code>
     * spl_autoload_register(array($this, 'autoload'));
     * </code>
     *
     * @return void
     */
    public function register();
}
