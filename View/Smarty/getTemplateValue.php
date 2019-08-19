<?php

namespace Kansas\View\Smarty;

use Smarty_Internal_Template;

require_once 'Smarty/sysplugins/smarty_internal_template.php';

/**
 * Obtiene un valor almacenado en $params o $template
 *
 * @param array $params Array de valores pasados a una función de smarty
 * @param Smarty_Internal_Template $template Plantilla pasada a una función de smarty
 * @param string $key Clave del valor a buscar
 * @param boolean $default Valor devuelto si no se encuentra nada, false de forma predeterminada
 * @return mixed Valor encontrado, o $default en caso contrario
 */
function getTemplateValue(array $params, Smarty_Internal_Template $template, $key, $default = false) {
	if(isset($params[$key]))
		return $params[$key];
	$templateVars = $template->getTemplateVars();
	if(isset($templateVars[$key]))
		return $templateVars[$key];
	return $default;
}