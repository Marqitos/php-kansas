<?php

namespace Kansas\Auth\Session;

interface SessionInterface {
    public function getIdentity();
    public function setIdentity(array $user, $lifetime = 0, $domain = null);
    public function clearIdentity();
    public function getId();
}