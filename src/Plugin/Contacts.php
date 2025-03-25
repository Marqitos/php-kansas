<?php

namespace Kansas\Plugin;

use function explode;
use function trim;
/**
 * Proporciona métodos para trabajar con contactos
 */
class Contacts {

    /**
     * Devuelve el nombre completo de un contacto
     *
     * @param array $contact
     * @return mixed string con el nombre completo en caso de que lo tenga, false en caso contrario
     */
    public static function getCompleteName(array $contact) {
        $n = [];
        foreach ($contact['contact'] as $property) {
            if ($property['key'] == 'N') {
                $n = explode(';', $property['value']);
                break;
            }
        }
        if ($contact['kind'] == 'individual' &&
            count($n) >= 3) {
            $name = trim($n[3] . ' ' . $n[1]); //Prefijos, Nombre
            $name .= ' ' . $n[2]; // Nombre adicionales
            $name = trim($name);
            $name .= ' ' . $n[0]; // Apellidos
            $name = trim($name);
            $name .= ' ' . $n[4]; // Sufijos
            return trim($name);
        }
        return false;
    }

    /**
     * Devuelve el nombre formateado de un contacto
     *
     * @param array $contact
     * @return string con el nombre formateado
     */
    public static function getFormattedName(array $contact) {
        foreach($contact['contact'] as $property) {
            if($property['key'] == 'FN') {
                return $property['value'];
            }
        }
    }

    /**
     * Devuelve el nombre de un contacto
     *
     * @param array $contact
     * @return mixed string con el nombre del contacto en caso de que lo tenga, o false en caso contrario
     */
    public static function getGivenName(array $contact) {
        if($contact['kind'] == 'individual') {
            foreach($contact['contact'] as $property) {
                if($property['key'] == 'N') {
                    return explode(';', $property['value'])[1];
                }
            }
        }
        return false;
    }
}
