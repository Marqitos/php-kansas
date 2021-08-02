<?php declare(strict_types = 1);
/**
 * Representa un administrador del idioma actual
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use Kansas\Plugin\PluginInterface;

require_once 'Kansas/Plugin/PluginInterface.php';

interface LocalizationInterface extends PluginInterface {

	/**
	 * Obtiene los datos de localización actual
	 * 
	 * @return array Array con los indices 'lang', 'country' y 'q'
	 */
	public function getLocale() : array;

	/**
	 * Establece la localización actual
	 * 
	 * @param string $lang Código de idioma
	 * @param string $country Código de pais (opcional)
	 * @param float|bool $q Precisión del idioma establecido (opcional)
	 */
	public function setLocale(string $lang, string $country = null, $q = true);

	/**
	 * Devuelve la localización actual como una cadena de texto
	 */
	public function __toString() : string;
}
