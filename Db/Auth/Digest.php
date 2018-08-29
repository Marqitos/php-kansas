<?php

namespace Kansas\Db\Auth;

use Kansas\Db\AbstractDb;
use function strtolower;

require_once 'Kansas/AbstractDb.php';

/*
CREATE TABLE IF NOT EXISTS `Digest` (
  `Id` binary(16) NOT NULL,
  `Realm` binary(16) NOT NULL,
  `A1` binary(16) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `U_REALM` (`Id`,`Realm`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Autenticación HttpDigest';
*/

/*
REPLACE INTO Digest
 (Id, Realm, A1)
 SELECT Id, UNHEX(MD5('API')), UNHEX(MD5(CONCAT_WS(":", '{marcosarnoso@msn.com}', '{API}', '{34878854}')))
 FROM `Users`
 WHERE Email = 'marcosarnoso@msn.com'
*/ 
//		$sql = 'REPLACE INTO Digest INNER JOIN `Users` AS USR ON DGS.Id = USR.Id INNER JOIN `Membership` AS MBS ON USR.Id = MBS.Id (Id, Realm, A1) VALUES (USR.Id, ?, MD5(CONCAT_WS(":", USR.Email, ?, ?))) WHERE USR.Email = ? AND MBS.Password = UNHEX(SHA1(?))';
        
class Digest extends AbstractDb {
        
    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function getA1($realm, $username) {
        $sql = 'SELECT HEX(DGS.A1) FROM `Digest` AS DGS INNER JOIN `Users` AS USR ON DGS.Id = USR.Id WHERE DGS.Realm = UNHEX(MD5(?)) AND USR.email = ?;';
        return strtolower($this->db->fetchOne($sql, [$realm, strtolower($username)]));
    }

    
}