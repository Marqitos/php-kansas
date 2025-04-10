<?php declare(strict_types = 1);
/**
  * Devuelve el menú de forma localizada, y con artefactos
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas\Plugin;

interface LocalizationMenuInterface {

    /**
      * Devuelve el menú de forma localizada, para el rol solicitado
      *
      * @param int $role Rol del usuario.
      * @param string $cultureCode Opcional, por referencia. Idioma preferido para el menú. Devuelve el idioma del menú.
      * @return array Estructura del menú.
      */
    function getMenu(int $role, string &$cultureCode = null) : array;
    /*
     * Para cada elemento: (icon o img_icon)
     * 'id' => 1,
     * 'name' => 'Texto del menú',
     * 'href' => '/ruta',
     * 'icon' => 'fa-solid fa-user-plus',
     * 'img_icon' => '/ruta/img.png',
     * 'submenus' => []
     */

}
