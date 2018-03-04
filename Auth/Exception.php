<?php

class Kansas_Auth_Exception
    extends Exception {
    /**
     * Usuario no encontrado
     */
    const FAILURE_IDENTITY_NOT_FOUND    = 1;

    /**
     * Credenciales no válidos
     */
    const FAILURE_CREDENTIAL_INVALID    = 2;

    /**
     * Cuenta de usuario no verificada
     */
    const FAILURE_IDENTITY_NOT_APPROVED = 4;

    /**
     * Cuenta de usuario bloqueada
     */
    const FAILURE_IDENTITY_NOT_ENABLED  = 8;

    /**
     * Cuenta de usuario bloqueada temporalmente
     */
    const FAILURE_IDENTITY_LOCKEDOUT    = 16;

    /**
     * Otro tipo de error
     */
    const FAILURE_UNCATEGORIZED         = 32;

    private $errorCode;

    public function __construct($errorCode) {
        $this->errorCode = $errorCode;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

}