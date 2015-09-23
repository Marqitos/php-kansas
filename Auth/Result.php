<?php
/**
 * Zend Framework
 * @package    Zend_Auth
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Kansas_Auth_Result {
    /**
     * General Failure
     */
    const FAILURE                        =  0;

    /**
     * Failure due to identity not being found.
     */
    const FAILURE_IDENTITY_NOT_FOUND     = -1;

    /**
     * Failure due to identity being ambiguous.
     */
    const FAILURE_IDENTITY_AMBIGUOUS     = -2;

    /**
     * Failure due to invalid credential being supplied.
     */
    const FAILURE_CREDENTIAL_INVALID     = -3;

    /**
     * Failure due to uncategorized reasons.
     */
    const FAILURE_UNCATEGORIZED          = -4;

    /**
     * Authentication success.
     */
    const SUCCESS                        =  1;

    /**
     * Authentication result code
     *
     * @var int
     */
    protected $_code;

    /**
     * The identity used in the authentication attempt
     *
     * @var Kansas_User_Interface
     */
    protected $_identity;

    /**
     * An array of string reasons why the authentication attempt was unsuccessful
     *
     * If authentication was successful, this should be an empty array.
     *
     * @var array
     */
    protected $_messages;

    /**
     * Sets the result code, identity, and failure messages
     *
     * @param  int     $code
     * @param  mixed   $identity
     * @param  array   $messages
     * @return void
     */
    public static function Success(Kansas_User_Interface $identity) {
      $result = new self();
      $result->_code     = self::SUCCESS;
      $result->_identity = $identity;
    }

    public static function Failure($code = self::FAILURE, array $messages = []) {
      $result = new self();
      if ($code < self::FAILURE_UNCATEGORIZED || $code > self::FAILURE)
          $code = self::FAILURE;
      $result->_code     = $code;
      $result->_messages = $messages;
    }
    
    /**
     * Returns whether the result represents a successful authentication attempt
     *
     * @return boolean
     */
    public function isValid() {
        return ($this->_code > 0) ? true : false;
    }

    /**
     * getCode() - Get the result code for this authentication attempt
     *
     * @return int
     */
    public function getCode() {
        return $this->_code;
    }

    /**
     * Returns the identity used in the authentication attempt
     *
     * @return mixed
     */
    public function getIdentity() {
        return $this->_identity;
    }

    /**
     * Returns an array of string reasons why the authentication attempt was unsuccessful
     *
     * If authentication was successful, this method returns an empty array.
     *
     * @return array
     */
    public function getMessages() {
        return $this->_messages;
    }
}
