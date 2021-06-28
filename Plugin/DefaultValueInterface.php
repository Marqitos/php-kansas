<?php declare(strict_types = 1);
/**
 * Representa un objeto que presenta valores predeterminados.
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

interface DefaultValueInterface {
    public function getDefault(string $key);
    public function tryGetDefault(string $key, &$value = null); // : bool;
}
