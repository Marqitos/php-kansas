<?php
namespace Kansas\Db;

use System\Guid;
use Kansas\Db\AbstractDb;

require_once 'Kansas/AbstractDb.php';
require_once 'System/Guid.php';

class Users extends AbstractDb {
	
  /// Usuarios
	// Devuelve un usuario por su email
	public function getByEmail($email) {
		$email = trim(strtolower($email));

		$sql = 'SELECT HEX(id) as id, name, email, isApproved, isLockedOut, lastLockOutDate, comment FROM `users` WHERE `Email` = ?;';
		$row = $this->db->fetchRow($sql, $email);
    if($row == null)
      return false;
      
    if($this->cache)
      $this->cache->save(serialize($row), 'user-id-' . $row['id'], ['user']);

		return $row;
	}
	
	// Devuelve un usuario por su Id
	public function getById(Guid $id) {
		if(Guid::isEmpty($id))
			return false;
			
    if($this->cache && $this->cache->test('user-id-' . $id->getHex()))
      return unserialize($this->cache->load('user-id-' . $id->getHex()));
          
		$sql = 'SELECT HEX(id) as id, name, email, isApproved, isLockedOut, lastLockOutDate, comment FROM `users` WHERE `Id` = UNHEX(?);';
		$row = $this->db->fetchRow($sql, $id->getHex());
    if($row == null)
      return false;
    
    if($this->cache)
      $this->cache->save(serialize($row), 'user-id-' . $id->getHex(), ['user']);

		return $row;
	}
  
  // Devuelve todos los usuarios
  public function getAll() {
		$sql = 'SELECT HEX(id) as id, name, email, isApproved, isLockedOut, lastLockOutDate, comment FROM `users`;';
		$rows = $this->db->fetchAll($sql);
    foreach($rows as $row) {
      if($this->cache)
        $this->cache->save(serialize($row), 'user-id-' . $row['id'], ['user']);
      yield $row;      
    }
  }
	
  // Guarda los datos de un nuevo usuario, y establece su ID
	public function create(array &$row) {
    if(isset($row['id']))
      throw new System_NotSuportedException();
    
    $id = Guid::newGuid();
    $sql = "INSERT INTO `users` (`id`, `name`, `email`, `comment`) VALUES (UNHEX(?), ?, ?, ?)";
		$this->db->beginTransaction();
		try {
      $result = $this->db->query($sql, [
        $id->getHex(),
        $row['name'],
        $row['email'],
        $row['comment']
      ])->rowCount();
      if(isset($row['roles']))
        foreach($row['roles'] as $rol)
          $this->addRol($rol, $id);
	
			$this->db->commit();
		} catch(Exception $e) {
			$this->db->rollBack();
      throw $e;
		}
    $row['id'] = $id->getHex();
    return $result;
	}
  
  public function addRol(array $row, Guid $user) {
    if(!isset($row['scope']))
      throw new System_ArgumentOutOfRangeException('rol');    
    if(isset($row['rol'])){
      $sql = 'SELECT COUNT(*) FROM `lists` WHERE `id` = UNHEX(?) AND `list` = UNHEX(?);';
      $count = $this->db->fetchOne($sql, [
        $row['rol'],
        $row['scope']
      ]);
      if($count == 0) {
        if(isset($row['name'])) {
          $sql = "INSERT INTO `lists` (`id`, `list`, `value`) VALUES (UNHEX(?), UNHEX(?), ?)";
          $this->db->query($sql, [
            $row['rol'],
            $row['scope'],
            $row['name']
          ]);
        } else
          throw new System_ArgumentOutOfRangeException('rol');        
      }
    } elseif(isset($row['name'])) {
      $sql = 'SELECT HEX(id) FROM `lists` WHERE `list` = UNHEX(?) AND `value` = ?;';
      $row['rol'] = $this->db->fetchOne($sql, [
        $row['scope'],
        strtolower($row['name'])
      ]);
      if($row['rol'] == null)
        throw new System_ArgumentOutOfRangeException('rol');
    } else
      throw new System_ArgumentOutOfRangeException('rol');
    $sql = "INSERT INTO `roles` (`rol`, `scope`, `user`) VALUES (UNHEX(?), UNHEX(?), UNHEX(?))";
    return $this->db->query($sql, [
      $row['rol'],
      $row['scope'],
      $user->getHex()
    ])->rowCount();
  }
	
  public function changeUserName(Guid $id, $name) {
    if(Guid::isEmpty($id))
			return false;
      
    if($this->cache && $this->cache->test('user-id-' . $id->getHex()))
      $this->cache->remove('user-id-' . $id->getHex());
          
		$sql = "UPDATE `users` SET `name` = ? WHERE `id` = UNHEX(?)";
		return $this->db->query($sql, [
			$name,
			$id->getHex()
		])->rowCount();
	}
	
	public function approveUser(Guid $id) {
		
		
	}
	
	public function unlockOutUser(Guid $id, $date) {
		
	}
	
	public function lockOutUser(Guid $id) {
		
	}
  
  public function deleteUser(Guid $id) {
    if($this->cache && $this->cache->test('user-id-' . $id->getHex()))
      $this->cache->remove('user-id-' . $id->getHex());
          
    $sql = "DELETE FROM `users` WHERE `id` = UNHEX(?)";
    return $this->db->query($sql, $id->getHex())->rowCount();
  }
  
  ///Roles
	public function getRolesByScope(Guid $scope) {
		if(Guid::isEmpty($scope))
			return false;
		
		$sql = 'SELECT HEX(id) as rol, HEX(list) as scope, value as name FROM `lists` WHERE `list` = UNHEX(?);';
		$rows = $this->db->fetchAll($sql, [$scope->getHex()]);
    
    if($this->cache)
      $this->cache->save(serialize($rows), 'scope-roles-' . $scope->getHex(), ['roles-scope', 'roles', 'scope']);

		return $rows;
  }
	
  ///Roles
	public function getRolesByUser(Guid $user, Guid $scope = null) {
    if($scope == null) {
      if($this->cache && $this->cache->test('user-roles-' . $user->getHex()))
        return unserialize($this->cache->load('user-roles-' . $user->getHex()));
 
  		$sql = 'SELECT HEX(roles.rol) as rol, HEX(roles.scope) as scope, HEX(roles.user) as user, lists.value as name FROM `roles` INNER JOIN `lists` ON roles.scope = lists.list AND roles.rol = lists.id WHERE `roles`.user = UNHEX(?);';
      $rows = $this->db->fetchAll($sql, [$user->getHex()]);
      
      if($this->cache)
        $this->cache->save(serialize($rows), 'user-roles-' . $user->getHex(), ['roles-user', 'roles']);
      return $rows;
    } else {
      if($this->cache && $this->cache->test('user-roles-scope-' . $user->getHex() . $scope->getHex()))
        return unserialize($this->cache->load('user-roles-scope-' . $user->getHex() . $scope->getHex()));
        
  		$sql = 'SELECT HEX(roles.rol) as rol, HEX(roles.scope) as scope, HEX(roles.user) as user, lists.value as name FROM `roles` INNER JOIN `lists` ON roles.scope = lists.list AND roles.rol = lists.id WHERE `roles`.user = UNHEX(?) AND `roles`.scope = UNHEX(?);';
      $rows = $this->db->fetchAll($sql, [$user->getHex(), $scope->getHex()]);
      if($this->cache)
        $this->cache->save(serialize($rows), 'user-roles-scope-' . $user->getHex() . $scope->getHex(), ['roles-user', 'roles']);
      return $rows;
      
    }

    
    if($this->cache)
      $this->cache->save(serialize($rows), 'user-roles-scope-' . $scope->getHex(), ['roles-user']);

		return $rows;
  }  
  
	public function saveAddress(array $data) {
		
		$sql = "REPLACE INTO `Address` (`Id`, `User`, `Name`, `Address`, `PostalCode`, `City`, `State`, `Country`) VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?)";
		
		$sql = "REPLACE INTO `DefaultAddress` (`Id`, `User`, `Type`, `Address`) VALUES (UNHEX(?), UNHEX(?), ?, UNHEX(?))";
		
	}
	
}

/*	
CREATE TABLE IF NOT EXISTS `Users` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(250) DEFAULT NULL,
	`Email` varchar(250) NOT NULL,
	`Subscriptions` int(11) DEFAULT 0,
	`IsApproved` int(11) DEFAULT 0,
	`IsLockedOut` int(11) DEFAULT 0,
	`LastLockOutDate` datetime DEFAULT NULL,
	`Role` varchar(50) DEFAULT NULL,
	`Comment` text DEFAULT NULL, 
	PRIMARY KEY (`Id`),
	UNIQUE KEY `Email` (`Email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Usuarios';	

CREATE TABLE IF NOT EXISTS `Address` (
  `Id` binary(16) NOT NULL,
  `User` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
	`PostOfficeBox` varchar(200) DEFAULT NULL,
	`ExtendedAddress` varchar(200) DEFAULT NULL,
	`StreetAddress` varchar(200) NOT NULL,
  `PostalCode` varchar(100) DEFAULT NULL,
  `Locality` varchar(100) NOT NULL,
  `Region` varchar(100) NOT NULL,
  `Country` binary(16) NOT NULL,
	`Label` varchar(500) DEFAULT NULL,
	PRIMARY KEY (`Id`),
	UNIQUE KEY `Name` (`User`, `Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Direcciones';	

CREATE TABLE IF NOT EXISTS `DefaultAddress` (
  `Id` binary(16) NOT NULL,
  `User` binary(16) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Address` binary(16) NOT NULL,
	PRIMARY KEY (`Id`),
	UNIQUE KEY `Type` (`User`, `Type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Direcciones predeterminadas';	

*/
