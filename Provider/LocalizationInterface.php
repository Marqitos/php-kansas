<?php declare(strict_types = 1 );
/**
 * Representa un proveedor para localización
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
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
	public function localize(array &$row, string $type, string $key, string $lang, string $country = null) : void;

}
