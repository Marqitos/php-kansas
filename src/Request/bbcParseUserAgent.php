<?php declare(strict_types = 1);
/**
 * Devuelve un elemento que cumple unas reglas de USER_AGENT
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 * PHP 7 >= 7.2
 */

namespace Kansas\Request;

use function preg_match;
use function preg_replace;
use function substr;

/**
 * Devuelve un elemento que cumple unas reglas de USER_AGENT
 *
 * @param string $userAgent User agent a buscar
 * @param array $items Valores user agent de bbclone
 * @return mixed array en caso de encontrar una coincidencia, false en caso contrario
 */
function bbcParseUserAgent(string $userAgent, array $items) {
    foreach($items as $id => $item) {
        foreach($item['rule'] as $pattern => $note) {
            if (preg_match('~' . $pattern . '~i', $userAgent, $regs)) {
                $result = $item;
                $result['id'] = $id;
                if (preg_match(":\\\\[0-9]{1}:", $note)) {
                    $str = preg_replace(":\\\\([0-9]{1}):", "\$regs[\\1]", $note);
                    eval("\$str = \"$str\";");
                    $result['note'] = $str;
                } elseif (preg_match(":^text\:.*:", $note)) {
                    $result['note'] = substr($note, 5);
                }
                unset($result['rule']);
                return $result;
            }
        }
    }
    return false;
}