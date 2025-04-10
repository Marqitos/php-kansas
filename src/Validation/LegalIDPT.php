<?php declare(strict_types = 1);
/**
  * Realiza comprobaciones documentos de identidad portugueses
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño <lib-kansas@marcospor.to>
  * @copyright  2025, Marcos Porto
  * @since      v0.6
  */

namespace Kansas\Validation;

use System\ArgumentOutOfRangeException;

// 1 a 3: Pessoa singular, a gama 3 começou a ser atribuída em junho de 2019;
// 45: Pessoa singular. Os algarismos iniciais "45" correspondem aos cidadãos não residentes que apenas obtenham em território português rendimentos sujeitos a retenção na fonte a título definitivo;
const T_PESSOA_SINGULAR             = 'pessoa-singular';
// 5: Pessoa colectiva obrigada a registo no Registo Nacional de Pessoas Colectivas;
const T_PESSOA_COLECTIVA            = 'pessoa-colectiva';
// 6: Organismo da Administração Pública Central, Regional ou Local;
const T_ADMINISTRACAO_PUBLICA       = 'administracao-publica';
// 70, 74 e 75: Herança Indivisa, em que o autor da sucessão não era empresário individual, ou Herança Indivisa em que o cônjuge sobrevivo tem rendimentos comerciais;
const T_HERANCA_INDIVISA            = 'heranca-indivisa';
// 71: Não residentes colectivos sujeitos a retenção na fonte a título definitivo;
const T_NAO_RESIDENTES_COLECTIVOS   = 'nao-residentes-colectivos';
// 72: Fundos de investimento;
const T_FUNDOS_INVESTIMENTO         = 'fundos-investimento';
// 77: Atribuição Oficiosa de NIF de sujeito passivo (entidades que não requerem NIF junto do RNPC);
const T_SUJEITO_PASSIVO             = 'sujeito-passivo';
// 78: Atribuição oficiosa a não residentes abrangidos pelo processo VAT REFUND;
const T_NAO_RESIDENTES_VAT_REFUND   = 'nao-residentes-vat-refund';
// 79: Regime excepcional - Expo 98;
const T_REGIME_EXCEPCIONAL          = 'regime-excepcional';
// 8: "empresário em nome individual" (actualmente obsoleto, já não é utilizado nem é válido);
// 90 e 91: Condomínios, Sociedade Irregulares, Heranças Indivisas cujo autor da sucessão era empresário individual;
const T_HERANCA_INDIVISA_EMPRESARIO = 'heranca-indivisa-empresario-individual';
// 98: Não residentes sem estabelecimento estável;
const T_NAO_RESIDENTES              = 'nao-residentes';
// 99: Sociedades civis sem personalidade jurídica.
const T_SOCIEDADES_CIVIS            = 'sociedades-civis';

require_once 'System/ArgumentOutOfRangeException.php';

function validateLegalID(string $legalID, string &$type = null) {

    // Comprobamos que tenga 9 caracteres
    if(strlen($legalID) != 9) {
        return false;
    }

    // Obtenemos el tipo de documento
    try {
        $type = getType($legalID);
    } catch(ArgumentOutOfRangeException $ex) {
        return false;
    }

    // Calculamos el dígito de control
    $nif_split = str_split($legalID);
    $check_digit = 0;
    for ($i = 0; $i < 8; $i++) {
        $check_digit += $nif_split[$i] * (10 - $i - 1);
    }
    $check_digit = 11 - ($check_digit % 11);
    $check_digit = $check_digit >= 10 ? 0 : $check_digit;
    if ($check_digit == $nif_split[8]) {
        return true;
    }

    return false;
}

function getType(string $legalCode) {
    if (preg_match('/^[\d]{9}$/', $legalCode)) { // Comprobamos que tenga 9 dígitos
        // Comprobamos que indique un tipo de documento válido
        if (in_array(substr($legalCode, 0, 1), ['1', '2', '3']) ||
           substr($legalCode, 0, 2) == '45') {
            return T_PESSOA_SINGULAR;
        } elseif(substr($legalCode, 0, 1) == '5') {
            return T_PESSOA_COLECTIVA;
        } elseif(substr($legalCode, 0, 1) == '6') {
            return T_ADMINISTRACAO_PUBLICA;
        } elseif(substr($legalCode, 0, 2) == '70') {
            return T_HERANCA_INDIVISA;
        } elseif(substr($legalCode, 0, 2) == '71') {
            return T_NAO_RESIDENTES_COLECTIVOS;
        } elseif(substr($legalCode, 0, 2) == '72') {
            return T_FUNDOS_INVESTIMENTO;
        } elseif(substr($legalCode, 0, 2) == '77') {
            return T_SUJEITO_PASSIVO;
        } elseif(substr($legalCode, 0, 2) == '78') {
            return T_NAO_RESIDENTES_VAT_REFUND;
        } elseif(substr($legalCode, 0, 2) == '79') {
            return T_REGIME_EXCEPCIONAL;
        } elseif(substr($legalCode, 0, 2) == '90' ||
                 substr($legalCode, 0, 2) == '91') {
            return T_HERANCA_INDIVISA_EMPRESARIO;
        } elseif(substr($legalCode, 0, 2) == '98') {
            return T_NAO_RESIDENTES;
        } elseif(substr($legalCode, 0, 2) == '99') {
            return T_SOCIEDADES_CIVIS;
        }
    }
    throw new ArgumentOutOfRangeException('legalCode', 'El código no es reconocible',  $legalCode);
}
