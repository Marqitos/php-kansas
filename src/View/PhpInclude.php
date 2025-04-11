<?php declare(strict_types = 1);
/**
  * Renderiza una plantilla, mediante un script en PHP
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\View;

use Kansas\View\ViewInterface;
use Kansas\Environment;
use Kansas\View\Template;
use System\Configurable;
use System\EnvStatus;
use function is_array;

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

## Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [];
    }
## -- ConfigurableInterface

## Miembros de Kansas\View\ViewInterface
    /**
      * @inheritDoc
      */
    public function getEngine() { }

    /**
      * @inheritDoc
      */
    public function setScriptPath(string $path): void {
        $this->scriptPaths = $path;
    }

    /**
      * @inheritDoc
      */
    public function getScriptPaths(): string {
        if($this->scriptPaths !== null) {
            return $this->scriptPaths;
        }
        require_once 'Kansas/Environment.php';
        return Environment::getSpecialFolder(Environment::SF_LAYOUT);
    }

    /**
      * @inheritDoc
      */
    public function __set(string $name, mixed $value): void {
        $this->data[$name] = $value;
    }

    /**
      * @inheritDoc
      */
    public function __isset(string $name): bool {
        return isset($this->data[$name]);
    }

    /**
      * @inheritDoc
      */
    public function __unset(string $name): void {
        unset($this->data[$name]);
    }

    /**
      * @inheritDoc
      */
    public function assign(string|array $spec, $value = null) {
        if(is_array($spec)) {
            $this->data = array_merge($this->data, $spec);
        } else {
            $this->data[$spec] = $value;
        }
    }

    /**
      * @inheritDoc
      */
    public function clearVars(): void {
        $this->data = $this->options;
    }

    /**
      * @inheritDoc
      */
    public function render(string $name): string {
        $template = $this->createTemplate($name, $this->data);
        echo $template->fetch();
    }
## -- ViewInterface

    public function getCaching(): bool {
        return $this->caching;
    }

    public function setCaching(bool $value): void {
        $this->caching = $value;
    }

    public function createTemplate(string $file, array $data = []): Template {
        require_once 'Kansas/View/Template.php';
        return new Template($this->getScriptPaths() . $file, $data);
    }

}
