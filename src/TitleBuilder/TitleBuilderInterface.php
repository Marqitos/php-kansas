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

interface TitleBuilderInterface {
    public function getSeparator();
    public function setSeparator($separator);

    public function getAttachOrder();
    public function setAttachOrder($order);

    public function attach($title);
    public function setTitle($title);
}
