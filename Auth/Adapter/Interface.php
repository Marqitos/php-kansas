<?php
/**
 * Zend Framework
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

interface Kansas_Auth_Adapter_Interface {
    /**
     * Performs an authentication attempt
     * @return Zend_Auth_Result
     */
    public function authenticate();
}
