<?php
/**
 * Plugin para el envío de mensajes utilizando PHPMailer
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use System\Configurable;
use System\NotSupportedException;
use System\Version;
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
                'host'      => '',
                'port'      => 0,
                'auth'      => false,
                'username'  => '',
                'password'  => '',
                'fromEmail' => '',
                'fromName'  => ''
            ]
        ];
        default:
            require_once 'System/NotSupportedException.php';
            throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() : Version {
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

    /**
     * Envía un mensaje mediante el servidor smtp
     * 
     * @param array|string $to Destinatario del mensaje: array con el correo y el nombre; o string con el correo
     * @param string $subject Asunto del mensaje
     * @param string $htmlTemplate Nombre del archivo de la plantilla para el mensaje en formato html
     * @param string $textTemplate Nombre del archivo de la plantilla para el mensaje en formato texto
     * @param array $templateData Contexto para las plantillas
     * @param array $options (Opcional) Opciones adicionales ['replyTo' => responder a]
     * @return bool|string true en caso de exito, o un texto descriptivo en caso de error
     */
    public function serverSend($to, $subject, $htmlTemplate, $textTemplate, array $templateData, array $options = []) {
        require_once 'Kansas/View/Template.php';
        global $environment;
        $template = new Template($environment->getSpecialFolder(Environment::SF_LAYOUT) . $htmlTemplate, $templateData);
        $htmlMessage = $template->fetch();
        $template = new Template($environment->getSpecialFolder(Environment::SF_LAYOUT) . $textTemplate, $templateData);
        $txtMessage = $template->fetch();

        $mail = $this->getSMTP();
        if(is_array($to)) {
            $mail->addAddress($to[0], $to[1]);
        } else {
            $mail->addAddress($to);
        }
        foreach($options as $key => $value) {
            if($key == 'replyTo') {
                if(is_array($value)) {
                    $mail->addReplyTo($value[0], $value[1]);
                } else {
                    $mail->addReplyTo($value);
                }
            }
        }
        $mail->Subject  = $subject;
        $mail->Body     = $htmlMessage;
        $mail->AltBody  = $txtMessage;

        //Envía el mensaje y comprueba si hay errores
        return $mail->send()
            ? true
            : $mail->ErrorInfo;
    }    
  
}