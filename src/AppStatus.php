<?php declare(strict_types = 1);
/**
  * Indica el estado actual de la aplicación
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas;

enum AppStatus : int {
    case START      = 0x00; // Estado inicial de la aplicación
    case INIT       = 0x01; // Los plugIns se han cargado
    case ROUTE      = 0x02; // Se ha analizado la ruta solicitada a su respectiva acción
    case DISPATCH   = 0x04; // Se ha lanzado la acción solicitada
    case DISPOSED   = 0x08; // Se ha iniciado el proceso de limpieza de recursos
    case ERROR      = 0x10; // Se ha producido un error durante la ejecucción
}
