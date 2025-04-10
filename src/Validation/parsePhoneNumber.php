<?php declare(strict_types = 1);
/**
  * Comprueba que un teléfono es válido
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño <lib-kansas@marcospor.to>
  * @copyright  2025, Marcos Porto
  * @since      v0.6
  */

namespace Kansas\Validation;

use libphonenumber\CountryCodeToRegionCodeMap;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;

require_once 'libphonenumber/PhoneNumber.php';

function parsePhoneNumber(string $phoneNumber, PhoneNumber &$phoneNumberProto = null, string &$phoneCountry = null) {
    global $application, $options;
    require_once 'libphonenumber/PhoneNumberUtil.php';
    require_once 'libphonenumber/NumberParseException.php';
    require_once 'libphonenumber/CountryCodeToRegionCodeMap.php';
    try {
        if($phoneCountry == null) {
            $phoneCountry = $options['country'];
        }
        $phoneUtil          = PhoneNumberUtil::getInstance();
        $phoneNumberProto   = $phoneUtil->parse($phoneNumber, $phoneCountry);
        $countryCode        = $phoneUtil->getRegionCodeForNumber($phoneNumberProto);
        if(isset(CountryCodeToRegionCodeMap::$countryCodeToRegionCodeMap[$countryCode]) &&
            count(CountryCodeToRegionCodeMap::$countryCodeToRegionCodeMap[$countryCode]) == 1) {
            $phoneCountry   = CountryCodeToRegionCodeMap::$countryCodeToRegionCodeMap[$countryCode][0];
        }
        return true;
    } catch (NumberParseException $ex) {
        if($logger = $application->hasPlugin('Logger')) {
            $logger->debug($ex->__toString(), ['exception' => $ex]);
        }
    }
    return false;
}
