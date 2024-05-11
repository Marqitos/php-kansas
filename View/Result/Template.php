<?php
/**
 * Representa una respuesta a una solicitud basada en una plantilla
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\View\Result;

use Kansas\View\Result\StringAbstract;
use Kansas\View\Template as DataTemplate;

require_once 'Kansas/View/Result/StringAbstract.php';
require_once 'Kansas/View/Template.php';

/**
 * Representa una respuesta a una solicitud basada en una plantilla
 */
class Template extends StringAbstract {
        
  /**
   * Crea una nueva instancia del objeto, especificando la plantilla, y el tipo de datos mime que se debe devolver
   */
  public function __construct(
    private DataTemplate $template,
    string $mimeType) {
    $dataContextMimeType = $template::getDatacontext('mimeType');
    if ($dataContextMimeType) {
      $mimeType = $dataContextMimeType;
    }
    parent::__construct($mimeType);
  }

  public function getResult(&$noCache) {
    $noCache = true;
    return $this->template->fetch();
  }
}
