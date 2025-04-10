<?php declare(strict_types = 1);
/**
  * Proporciona un creador de titulos
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\TitleBuilder;

use System\EnvStatus;

require_once 'Kansas/TitleBuilder/DefaultTitleBuilder.php';

class FullTitleBuilder extends DefaultTitleBuilder {

    public function setFullTitle($title) {
        $this->options['full_title'] = $title;
    }

    public function getFullTitle() {
        return empty($this->options['full_title'])
            ? $this->options['title']
            : $this->options['full_title'];
    }

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return array_merge(parent::getDefaultOptions($environment), ['full_title' => '']);
    }

    public function __toString() {
        return count($this->items) == 0
            ? (string) $this->getFullTitle()
            : parent::__toString();
    }
}
