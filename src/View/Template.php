<?php declare(strict_types = 1);
/**
  * Representa una plantilla
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\View;

use Kansas\TextParser\Plain;
use Kansas\TextParser\Markdown;
use Kansas\TitleBuilder\TitleBuilderInterface;
use System\EnvStatus;

use function is_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_status;
use function ob_start;
use function strip_tags;
use function System\String\isNullOrEmpty as StringIsNullOrEmpty;

require_once 'Kansas/TitleBuilder/TitleBuilderInterface.php';

/**
  * Representa una plantilla
  */
class Template {

    private static $datacontext = [];

    /**
      * Crea una instancia del objeto, indicando el script a ejecutar
      * y los datos con los que debe rellenar la plantilla
      */
    public function __construct(
        private string $script,
        private array $data) {}

    /**
      * Renderiza la plantilla y devuelve el resultado como una cadena de texto
      */
    public function fetch(): string {
        self::$datacontext = [...self::$datacontext, ...$this->data];
        try {
            ob_start();
            include $this->script;
            return ob_get_clean();
        } catch (Throwable $th) {
            $buffer = ob_end_clean();
            if (Environment::getStatus() == EnvStatus::DEVELEPMENT) {
                var_dump($th);
                echo $buffer;
            }
            throw $th;
        } finally {
            if (!empty(ob_get_status())) {
                ob_clean();
            }
        }
    }

    /**
      * Obtiene el contexto en el que se debe rellenar la plantilla (Los datos para rellenarla).
      * @param  ?string $index      (Opcional) Indice, si se especifica se devolverá el dato guardado con esa clave, o $default si no existe;
      *                             si no se especifica devolverá todos los datos almacenados.
      *                             Si no se especifica devuelve el array con todos los valores.
      * @param  mixed   $default    (Opcional) Valor por defecto si no se encuentra el valor con la clave especificada.
      * @return mixed               Un array si no se especifica $index,
      *                             o el valor guardado con la clave especificada por $index,
      *                             o $default si no se encuentra.
      */
    public static function getDatacontext(?string $index = null, mixed $default = false): mixed {
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
    public static function setDatacontext(string $index, mixed $value): void {
        self::$datacontext[$index] = $value;
    }

    /**
      * Obtiene la descripción,
      * o una cadena vacía si no se ha establecido
      *
      * @return string  Valor de $datacontext['description'], o una cadena vacía
      */
    public static function getDescription() : string {
        return isset(self::$datacontext['description'])
            ? self::$datacontext['description']
            : '';
    }

    /**
      * Devuelve si se ha establecido una descripción
      *
      * @return  boolean TRUE, si tiene descripción; FALSE, en caso contrario
      */
    public static function hasDescription() : bool {
        require_once 'System/String/isNullOrEmpty.php';
        return isset(self::$datacontext['description']) &&
               ! StringIsNullOrEmpty(self::$datacontext['description']);
    }

    /**
      * Establece la descripción
      */
    public static function setDescription(string $value): void {
        self::setDatacontext('description', $value);
    }

    public static function getKeywords(): array {
        return (isset(self::$datacontext['keywords']) &&
                is_array(self::$datacontext['keywords']))
            ? self::$datacontext['keywords']
            : self::parseKeywords();
    }

    public static function hasKeywords(): bool {
        $keywords = self::parseKeywords();
        return count($keywords) > 0;
    }

    public static function getKeywordsAsString(): string {
        return htmlentities(implode(', ', self::getKeywords()));
    }

    protected static function parseKeywords(string|array|null $values = null): array {
        if ($values === null &&
            isset(self::$datacontext['keywords'])) {
            $values = self::$datacontext['keywords'];
        }
        if (is_array($values)) {
            $keywords = $values;
        } elseif (is_string($values) &&
                  ! StringIsNullOrEmpty($values)) {
            $keywords = [];
            foreach (explode(',', $values) as $keyword) {
                $keyword = trim((string) $keyword);
                $keywords[] = $keyword;
            }
        } else {
            $keywords = [];
        }
        self::$datacontext['keywords'] = $keywords;
        return $keywords;
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

    public static function parse(string|array $input, string $outFormat, string $inFormat = 'txt') {
        if (is_array($input) &&
            isset($input['value'])) { // procesamos si input es un array ['format', 'value']
            if (isset($input['format'])) {
                $inFormat = $input['format'];
            }
            $input = $input['value'];
        }
        if (! is_string($input)) {
            throw ArgumentOutOfRangeException('input', $input);
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
