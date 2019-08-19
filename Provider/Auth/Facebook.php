<?php

namespace Kansas\Db\Auth;

use System\Guid;
use Kansas\Db\AbstractDb;

class Facebook extends AbstractDb {
        
    public function __construct(Zend_Db_Adapter_Abstract $db) {
        parent::__construct($db);
    }
    
    public function createUser($fbUser, $name, $email) {
        global $application;
        $usersProvider = $application->getProvider('Users');
        $user = $usersProvider->getByEmail($email);
        $this->db->beginTransaction();
        try {
            if($user == null) {
                $user = new Kansas_User(array(
                    'Id'				=> Guid::NewGuid(),
                    'Name'				=> $name, 
                    'Email'				=> $email,
                    'Subscriptions' 	=> 0,
                    'IsApproved'		=> 1,
                    'IsLockedOut'		=> 0,
                    'LastLockOutDate'	=> null,
                    'Role'				=> Kansas_User::ROLE_GUEST,
                    'Comment'			=> 'Facebook user'
                ));
                $id = $usersProvider->createUser($user);
            } else {
                if(!$user->IsApproved()) {
                    $user->setApproved(true);	
                    $usersProvider->updateUser($user);
                }
                $id = $user->getId();
            }
            $sql = "REPLACE INTO `FbUsers` (`Id`, `User`, `Email`) VALUES (?, UNHEX(?), ?)";
            $this->db->query($sql, array(
                $fbUser,
                $id->getHex(),
                strtolower($email)
            ));
            $this->db->commit();
        } catch(Exception $e) {
            $this->db->rollBack();
            echo($e->getMessage());
        }
        return $id;
    }
    
    public function getUser($userId) {
        $sql = 'SELECT USR.* FROM `Users` AS USR INNER JOIN `FbUsers` AS FBU ON USR.Id = FBU.User WHERE FBU.Id = ?;';
        $row = $this->db->fetchRow($sql, [$userId]);
        return ($row == null) ?
            null:
            new Kansas_User($row);
    }
    
}