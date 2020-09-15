<?php

namespace Kansas\Plugin;

use Exception;
use System\Configurable;
use System\NotSuportedException;
use Kansas\Environment;
use Kansas\View\Template;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Phpmail extends Configurable implements PluginInterface {

	/// Constructor
	public function __construct(array $options) {
        require_once 'PHPMailer/PHPMailer/PHPMailer.php';
        require_once 'PHPMailer/PHPMailer/SMTP.php';
		parent::__construct($options);
    }
    

  
	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions($environment) : array {
        switch ($environment) {
        case 'production':
        case 'development':
        case 'test':
        return [
            'smtp' =>  [
                'charset'   => PHPMailer::CHARSET_UTF8,
                //Enable SMTP debugging
                // SMTP::DEBUG_OFF = off (for production use)
                // SMTP::DEBUG_CLIENT = client messages
                // SMTP::DEBUG_SERVER = client and server messages
                'debug'     => SMTP::DEBUG_OFF,
                'host'      => 'dns134198.phdns18.es',
                'port'      => 587,
                'auth'      => true,
                'username'  => 'reprogalicia@reprogalicia.com',
                'password'  => 'g2R7&y4v',
                'fromEmail' => 'reprogalicia@reprogalicia.com',
                'fromName'  => 'Reprogalicia'
            ]
        ];
        default:
            require_once 'System/NotSuportedException.php';
            throw new NotSuportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() {
        global $environment;
        return $environment->getVersion();
    }

    public function getSMTP() {
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->CharSet      = $this->options['smtp']['charset'];
        $mail->SMTPDebug    = $this->options['smtp']['debug'];
        $mail->Host         = $this->options['smtp']['host'];
        $mail->Port         = $this->options['smtp']['port'];
        $mail->SMTPAuth     = $this->options['smtp']['auth'];
        $mail->Username     = $this->options['smtp']['username'];
        $mail->Password     = $this->options['smtp']['password'];
        $mail->setFrom(
            $this->options['smtp']['fromEmail'],
            $this->options['smtp']['fromName']);
        return $mail;
    }

    public function serverSend($to, $subject, $htmlTemplate, $textTemplate, array $templateData) {
        require_once 'Kansas/View/Template.php';
        global $environment;
        $template = new Template($environment->getSpecialFolder(Environment::SF_LAYOUT) . $htmlTemplate, $templateData);
        $htmlMessage = $template->fetch();
        $template = new Template($environment->getSpecialFolder(Environment::SF_LAYOUT) . $textTemplate, $templateData);
        $txtMessage = $template->fetch();

        $mail = $this->getSMTP();
        //$mail->addAddress('informatica@reprogalicia.com', 'Marcos Porto');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->msgHTML($htmlMessage);
        $mail->AltBody = $txtMessage;

        //send the message, check for errors
        return $mail->send();
    }    
  
}