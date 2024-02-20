<?php declare(strict_types = 1);

namespace Kansas\Auth;

use Exception;

class AuthException extends Exception {

    // Errores en el nombre de usuario (1+)
    /**
     * Cuenta de usuario no especificada
     */
    const REQUIRE_USERNAME              = 1;
    /**
     * Cuenta de usuario no valida (+8)
     */
    const FAILURE_USERNAME              = 9;
    /**
     * Usuario no encontrado (+16)
     */
    const FAILURE_USERNAME_NOT_FOUND    = 17;

    // Errores en la contraseña (2+)
    /**
     * Contraseña no especificada
     */
    const REQUIRE_PASSWORD              = 2;

    /**
     * Contraseña no valida (+32)
     */
    const FAILURE_PASSWORD              = 34;

    /**
     * Credenciales no válidos (+64)
     */
    const FAILURE_CREDENTIAL_INVALID    = 66;

    // Errores con la identidad (4+)
    /**
     * Cuenta de usuario no verificada
     */
    const FAILURE_IDENTITY_NOT_APPROVED = 4;

    /**
     * Cuenta de usuario bloqueada (+128)
     */
    const FAILURE_IDENTITY_NOT_ENABLED  = 136;

    // Otros errores
    /**
     * Inicio de sesión bloqueado temporalmente
     */
    const FAILURE_LOCKEDOUT             = 256;

    /**
     * Otro tipo de error
     */
    const FAILURE_UNCATEGORIZED         = 512;

    private $errorCode;

    public function __construct($errorCode) {
        $this->errorCode = $errorCode;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

}
