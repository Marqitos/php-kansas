<?php
/**
  * Plugin el pago mediante redsys, utilizando la librería omnipay
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Kansas\Configurable;
use System\EnvStatus;
use System\NotSupportedException;
use System\Version;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Omnipay\Omnipay;
use Omnipay\Common\AbstractGateway;

require_once 'Kansas/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Omnipay/Common/AbstractGateway.php';

class Redsys extends Configurable implements PluginInterface {

    private $gateway;

    const CLASSNAME_REDIRECT    = 'Redsys_Redirect';
    const CLASSNAME_WEBSERVICE  = 'Redsys_Webservice';

/*
    public function __construct(array $options) {
        parent::__construct($options);
        // pruebas
        $gateway = $this->getGateway();
        var_dump($gateway->getName(), $gateway->supportsAuthorize(), $gateway->supportsCompleteAuthorize(), $gateway->supportsPurchase(), $gateway->supportsCompletePurchase());
        $request = $gateway->purchase([
            'amount' => '10.5',
            'currency' => 'EUR'
        ]);
        $request->setTransactionId('00002P');
        $request->setNotifyUrl('https://sinkolas.com/carrito/pago');
        $request->setReturnUrl('https://sinkolas.com/carrito/redsys');
        $request->setConsumerLanguage('es');
        $response = $request->send();

        // Process response
        if ($response->isSuccessful()) {
            // Payment was successful
            print_r($response);

        } elseif ($response->isRedirect()) {
            var_dump($response->getRedirectMethod(), $response->getRedirectUrl());
            $params = [];
            foreach ($response->getRedirectData() as $key => $value) {
                var_dump($key, $value);
                $params[$key] = $value;
            }
            var_dump($response->getRedirectUrl() . '?' . http_build_query($params));

            // Redirect to offsite payment gateway
            $response->redirect();

        } else {
            // Payment failed
            echo $response->getMessage();
        }
        var_dump($response->isSuccessful(), $response->isRedirect(), $response->getMessage());
    }
*/

    // Miembros de Kansas\Plugin\PluginInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        // TODO: Use dovent
        $data = [
            'redirect'          => true,
            'currency'          => 'EUR',
            'terminal_id'       => '001',
            'hmac_key'          => null,
            'merchant_id'       => null,
            'notify_url'        => null,
            'return_url'        => null];
        switch ($environment) {
            case EnvStatus::DEVELOPMENT:
            case EnvStatus::TEST:
                $data['hmac_key']     = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';
                $data['merchant_id']  = '999008881';
                $data['test_mode']    = true;
                break;
            case EnvStatus::PRODUCTION:
                $data['test_mode']    = false;
                break;
            default:
                require_once 'System/NotSupportedException.php';
                NotSupportedException::NotValidEnvironment($environment);
        }
        return $data;
    }

    public function getVersion() : Version {
        return Environment::getVersion();
    }

    public function getGateway() : AbstractGateway {
        if($this->gateway == null) {
            require_once 'Http/Discovery/Psr17FactoryDiscovery.php';
            require_once 'Http/Adapter/Guzzle7/Client.php';
            require_once 'Psr/Http/Client/ClientInterface.php';
            require_once 'Omnipay/Omnipay.php';
            if($this->options['redirect']) {
                require_once 'Omnipay/Redsys/RedirectGateway.php';
                $className = self::CLASSNAME_REDIRECT;
            } else {
                require_once 'Omnipay/Redsys/WebserviceGateway.php';
                $className = self::CLASSNAME_WEBSERVICE;
            }
            $this->gateway = Omnipay::create($className);
            $this->gateway->setMerchantId($this->options['merchant_id']);
            $this->gateway->setTerminalId($this->options['terminal_id']);
            $this->gateway->setHmacKey($this->options['hmac_key']);
            $this->gateway->setTestMode($this->options['test_mode']);
            $this->gateway->setCurrency($this->options['currency']);
        }
        return $this->gateway;
    }

}
