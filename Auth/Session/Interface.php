<?php

interface Kansas_Auth_Session_Interface {
    public function initialize($force = FALSE, $lifetime = 0, $domain = NULL);
    public function getIdentity();
    public function setIdentity($user, $lifetime = 0, $domain = NULL);
    public function clearIdentity();
}