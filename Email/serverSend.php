<?php

namespace Kansas\Email;

use function mb_language;
use function mb_send_mail;
use function wordwrap;

function serverSend($from, $to, $title, $htmlTemplate, $textTemplate, array $templateData) {
    global $application;
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    $headers .= 'From: ' . $from;
    $view = $application->getView();
    $template = $view->createTemplate($htmlTemplate, $templateData);
    $message = wordwrap($template->fetch(), 70);
    mb_language('uni');
    return mb_send_mail($to, $title, $message, $headers);
}