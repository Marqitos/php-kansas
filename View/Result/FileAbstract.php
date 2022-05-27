<?php 
/**
 * Proporciona la funcionalidad básica para la devolución de archivos, como resultado de una solicitud
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultAbstract;
use function basename;
use function header;

require_once 'Kansas/View/Result/ViewResultAbstract.php';

abstract class FileAbstract extends ViewResultAbstract {
		
	protected $download = false;
    protected $size		= false;
		
	protected function sendHeaders($noCache = false) {
        $result = parent::sendHeaders($noCache);
		if($this->download) { // Si se quiere indicar al navegador que debe guardar el archivo
			//$basename = basename($this->download);
			//if(mb_check_encoding($basename, ))
			header('Content-Disposition: attachment; filename="' . basename($this->download) . '"');
			header("Content-Transfer-Encoding: binary");
		} else {
			header('Content-Disposition: inline');
		}
        if($this->size !== false &&
            $result) {
            header('Content-Length: ' . $this->size);
        }
        return $result;
	}

}
