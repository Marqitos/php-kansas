<?php declare(strict_types = 1);
/**
  * Roles básicos para asignar permisos
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas;

class Roles {
    public const ROOT           = 1;
    public const ADMIN          = 2;
    public const USER           = 4;

    public const GUEST          = 128;
}
