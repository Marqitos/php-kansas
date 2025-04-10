<?php
/**
  * Proporciona el envío de correo electronico mediante mb_send_mail, y el uso de plantillas
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Email;

use function mb_language;
use function mb_send_mail;
use function wordwrap;

function serverSend($from, $to, $title, $htmlFileTemplate, $textFileTemplate, array $templateData) {
    global $application;
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    $headers .= 'From: ' . $from;
    $view = $application->getView();
    $htmlTemplate = $view->createTemplate($htmlFileTemplate, $templateData);
    //$textTemplate = $view->createTemplate($textFileTemplate, $templateData);
    $message = wordwrap($htmlTemplate->fetch(), 70);
    mb_language('uni');
    return mb_send_mail($to, $title, $message, $headers);
}
