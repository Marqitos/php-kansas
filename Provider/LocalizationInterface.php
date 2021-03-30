<?php
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
	 * @param array $row Datos a localizar
	 * @param string $type Tipo de datos
	 * @param string $key Clave especifica del elemento
	 * @param string $lang Código de idioma a localizar
	 * @param string $country Opcional, código de región especifica del idioma
	 */
	public function localize(array &$row, $type, $key, $lang, $country = null);

}
