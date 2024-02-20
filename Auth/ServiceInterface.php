<?php

namespace Kansas\Auth;

interface ServiceInterface {
    // Obtiene las acciones de autenticación del servicio
    public function getActions();
    // Obtiene el tipo de autenticación
    public function getAuthType();
    // Obtiene el nombre del servicio de autenticación
    public function getName();
}
