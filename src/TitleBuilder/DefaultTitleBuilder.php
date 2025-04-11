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

use Kansas\Configurable;
use System\EnvStatus;
use Kansas\TitleBuilder\TitleBuilderInterface;

use function array_merge;
use function array_unshift;
use function count;
use function implode;

require_once 'Kansas/Configurable.php';

class DefaultTitleBuilder extends Configurable implements TitleBuilderInterface {

    const APPEND    = 'APPEND';
    const SET       = 'SET';
    const PREPEND   = 'PREPEND';

    protected $items = [];

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'separator'     => ' : ',
            'attachOrder'   => self::PREPEND,
            'title'         => ''
        ];
    }


    public function getSeparator() {
        return $this->options['separator'];
    }
    public function setSeparator($separator) {
        $this->options['separator'] = (string) $separator;
    }

    public function getAttachOrder() {
        return $this->options['attachOrder'];
    }
    public function setAttachOrder($order) {
        $this->options['attachOrder'] = $order;
    }

    public function attach($title) {
        switch($this->options['attachOrder']) {
            case self::APPEND:
                $this->items[] = $title;
                break;
            case self::SET:
                $this->items = [$title];
                break;
            default:
                array_unshift($this->items, $title);
                break;
        }
    }
    public function setTitle($title) {
        $this->options['title'] = $title;
    }

    public function __toString() {
        if(count($this->items) == 0) {
            $result = [$this->options['title']];
        } elseif(empty($this->options['title'])) {
            $result = $this->items;
        } else {
            $result = ($this->options['attachOrder'] == self::APPEND)
                ? array_merge((array)$this->options['title'], $this->items)
                : array_merge($this->items, (array)$this->options['title']);
        }
        return implode($this->getSeparator(), $result);
    }
}
