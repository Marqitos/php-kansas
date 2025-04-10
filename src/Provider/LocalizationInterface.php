<?php declare(strict_types = 1);
/**
  * Representa un proveedor para localización
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Provider;

interface LocalizationInterface {

  /**
   * Localiza un tupla
   *
   * @param array $row (Por referencia) Datos a localizar
   * @param string $type Tipo de datos
   * @param string $key Clave especifica del elemento
   * @param string $lang Código de idioma a localizar
   * @param string $country (Opcional) Código de región especifica del idioma
   */
  public function localize(array &$row, string $type, string $key, string $lang, ?string $country = null) : void;

  /**
   * Devuelve los idiomas disponibles para localización
   *
   * @return array Elementos con las claves 'lang' y 'country'
   */
  public function getLanguages() : array;

}
