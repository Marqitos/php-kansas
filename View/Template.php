<?php declare(strict_types = 1);
/**
 * Representa una plantilla
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2024, Marcos Porto
 * @since v0.4
 */

namespace Kansas\View;

use Kansas\TextParser\Plain;
use Kansas\TextParser\Markdown;
use Kansas\TitleBuilder\TitleBuilderInterface;

use function is_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function strip_tags;

require_once 'Kansas/TitleBuilder/TitleBuilderInterface.php';

/**
 * Representa una plantilla
 */
class Template {

  private $data;
  private $script;
  private static $datacontext = [];

  /**
    * Crea una instancia del objeto, indicando el script a ejecutar y los datos con los que debe rellenar la plantilla
    */
  public function __construct(string $script, array $data) {
    $this->script   = $script;
    $this->data     = $data;
  }
    
  /**
    * Renderiza la plantilla y devuelve el resultado como una cadena de texto
    */
  public function fetch() {
    self::$datacontext = array_merge(self::$datacontext, $this->data);
    try {
      ob_start();
      include $this->script;
      return ob_get_clean();
    } finally {
      ob_end_clean();
    }
  }

  /**
    * Obtiene el contexto en el que se debe rellenar la plantilla (Los datos para rellenarla).
    * @param $index string Indice opcional, si se especifica se devolverá el dato guardado con esa clave, si existe o false si no existe. Si no se especifica devuelve el array con todos los valores.
    * @return array|false|mixed Un array si no se especifica $index, y false o un valor si se especifica $index.
    */
  public static function getDatacontext(string $index = null, $default = false) {
    if ($index == null) {
      return self::$datacontext;
    } elseif (isset(self::$datacontext[$index])) {
      return (is_array($default) && !is_array(self::$datacontext[$index]))
        ? [self::$datacontext[$index]]
        : self::$datacontext[$index];
    } else {
      return $default;
    }
  }

  /**
    * Establece un valor en el contexto de la plantilla.
    */
  public static function setDatacontext(string $index, $value) {
    self::$datacontext[$index] = $value;
  }

  /**
    * Obtiene un objeto Kansas\TitleBuilder\TitleBuilderInterface, con el titulo en contexto de la plantilla
    */
  public static function getTitle() : TitleBuilderInterface {
    global $application;
    $title = $application->createTitle();
    if(isset(self::$datacontext['title'])) {
      $title->setTitle(self::$datacontext['title']);
    }
    return $title;
  }

  public static function parse($input, string $outFormat, string $inFormat = 'txt') {
    if (is_array($input)) { // procesamos si input es un array ['format', 'value']
      if (isset($input['format'])) {
          $inFormat = $input['format'];
      }
      if (isset($input['value'])) {
          $input = $input['value'];
      }
    }
    if ($inFormat == $outFormat) {
        $output = $input;
    } elseif ($inFormat == 'txt' &&
              $outFormat == 'html') {
      require_once 'Kansas/TextParser/Plain.php';
      $parser = new Plain($input);
      $output = $parser->__toString();
    } elseif ($inFormat == 'md' &&
              $outFormat == 'html') {
      require_once 'Kansas/TextParser/Markdown.php';
      $parser = new Markdown($input);
      $output = $parser->__toString();
    } elseif ($inFormat == 'html' &&
              $outFormat == 'txt') {
      $output = strip_tags($input);
    }
    return $output;
  }

}
