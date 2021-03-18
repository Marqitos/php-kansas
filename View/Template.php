<?php
/**
 * Representa una plantilla
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\View;

/**
 * Representa una plantilla
 */
class Template {

    private $data;
    private $script;
    private static $datacontext;

    /**
     * Crea una instancia del objeto, indicando el script a ejecutar y los datos con los que debe rellenar la plantilla
     */
    public function __construct($script, array $data) {
        $this->script   = $script;
        $this->data     = $data;
    }
    
    /**
     * Renderiza la plantilla y devuelve el resultado como una cadena de texto
     */
    public function fetch() {
        self::$datacontext = $this->data;
        try {
            ob_start();
            include $this->script;
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Obtiene el contexto en el que se debe rellenar la plantilla (Los datos para rellenarla).
     * @param $index string Indice opcional, si se especifica se devolverá el dato guardado con esa clave, si existe o false si no existe. Si no se espedicica devuelve el array con todos los valores.
     * @return array|false|mixed Un array si no se especifica $index, y false o un valor si se especifica $index.
     */
    public static function getDatacontext($index = null, $default = false) {
        if($index == null) {
            return self::$datacontext;
        } else if(isset(self::$datacontext[$index])) {
            return (is_array($default) && is_string(self::$datacontext[$index]))
                ? [self::$datacontext[$index]]
                : self::$datacontext[$index];
        } else {
            return $default;
        }
    }

    /**
     * Establece un valor en el contexto de la plantilla.
     */
    public static function setDatacontext($index, $value) {
        self::$datacontext[$index] = $value;
    }

    /**
     * Obtiene un objeto Kansas\TitleBuilder\TitleBuilderInterface, con el titulo en contexto de la plantilla
     */
    public static function getTitle() {
        global $application;
        $title = $application->createTitle();
        if(isset(self::$datacontext['title'])) {
            $title->setTitle(self::$datacontext['title']);
        }
        return $title;     
    }

    public static function parse($input, $outFormat, $inFormat = 'txt') {
        if(is_array($input)) { // procesamos si input es un array ['format', 'value']
            if(isset($input['format'])) {
                $inFormat = $input['format'];
            }
            if(isset($input['value'])) {
                $input = $input['value'];
            }
        }
        if($inFormat == $outFormat) {
            $output = $input;
        }
        if($inFormat == 'txt' &&
           $outFormat == 'html') {
            $output = nl2br(htmlentities($input));
        }
        if($inFormat == 'html' &&
           $outFormat == 'txt') {
            $output = strip_tags($input);
        }
        return $output;
    }

}