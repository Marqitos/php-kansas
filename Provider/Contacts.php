<?php

namespace Kansas\Provider;

use Kansas\Provider\AbstractDb;
use System\Guid;
use Exception;
use function array_merge;
use function substr;
use function strpos;
use function strlen;

require_once 'Kansas/Provider/AbstractDb.php';

class Contacts extends AbstractDb {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Obtiene un usuario
	 *
	 * @param System\Guid $id Id del usuario
	 * @return mixed array con los datos de contacto del usuario o false en caso de que no exista
	 */
	public function getUser($id) {
		require_once 'System/Guid.php';
		$statement = $this->db->query(
			'SELECT ' .
				'HEX(`CNT`.`id`) AS `id`, ' .
				'`CNT`.`kind`, ' .
				'`USR`.`name`, ' .
				'`USR`.`email`, ' .
				'`USR`.`isApproved`, ' .
				'`USR`.`isEnabled` ' .
			'FROM `Contacts` AS `CNT` ' .
			'INNER JOIN `Users` AS `USR` ON ' .
				'`CNT`.`id` = `USR`.`id` ' .
			'WHERE `CNT`.`id` = UNHEX(?);'
		);
		if($id instanceof Guid)
			$row = $statement->execute([$id->getHex()])->current();
		else
			$row = $statement->execute([$id])->current();

		if($row !== false) { // Cargamos propiedades
			$user = [
				'id' => $row['id'],
				'kind'	=> $row['kind'],
				'name'	=> $row['name'],
				'email' => $row['email'],
				'isApproved' => $row['isApproved'],
				'isEnabled' => $row['isEnabled'],
				'contact' => []
			];
			$statement = $this->db->query(
				'SELECT HEX(`CTP`.`id`) AS `id`, `CTP`.`key`, `CTP`.`value` FROM `ContactProperties` AS `CTP` WHERE `CTP`.`contact` = UNHEX(?);'
			);
			if($id instanceof Guid) {
				$rows = $statement->execute([$id->getHex()]);
			} else {
				$rows = $statement->execute([$id]);
			}
			while($row = $rows->current()) {
				$user['contact'][$row['id']] = [
					'key' 	=> $row['key'],
					'value'	=> $row['value']
				];
				$rows->next();
			}
			return $user;
		}
		return false;
	}

	/**
	 * Crea un nuevo contacto y usuario con los datos falicitados.
	 * Del tipo persona física
	 *
	 * @param string $email email del usuario
	 * @param string $name Nombre del usuario
	 * @param string $surnames Apellidos del usuario
	 * @return void
	 */
    public function createUser($email, $name, $surnames) {
		global $application;
		// Comprobamos que no haya ningun usuario con esa dirección de email
		$usersProvider = $application->getProvider('users');
		$user = $usersProvider->getByEmail($email);
		if($user !== false)
			return; // thrown
		

		// Creamos los valores a insertar
		require_once 'System/Guid.php';
		$fn = $name . " " . $surnames;
		// The structured property value corresponds, in
		// sequence, to the Family Names (also known as surnames), Given
		// Names, Additional Names, Honorific Prefixes, and Honorific
		// Suffixes.  The text components are separated by the SEMICOLON
		// character (U+003B).  Individual text components can include
		// multiple text values separated by the COMMA character (U+002C).
		// This property is based on the semantics of the X.520 individual
		// name attributes [CCITT.X520.1988].  The property SHOULD be present
		// in the vCard object when the name of the object the vCard
		// represents follows the X.520 model.
		$n = $surnames . ";" . $name . ";;;";
		$userName = $name . " " . self::getSurname($surnames);
		$user = [
			'name' 			=> $userName,
			'email'			=> $email,
			'contact'		=> [
				Guid::newGuid()->getHex() => [
					'key'			=> 'N',
					'value'			=> $n],
				Guid::newGuid()->getHex() => [
					'key'			=> 'FN',
					'value'			=> $fn],
				Guid::newGuid()->getHex() => [
					'key'			=> 'EMAIL',
					'value'			=> $email],
		]];
		return self::insertUser($user);
    }

	/**
	 * Obtiene el primer apellido, las cadenas de hasta 3 caracteres no cuenta como un apellido
	 *
	 * @param string $surnames Apellidos a analizar
	 * @return string Primer apellido
	 */
    protected static function getSurname($surnames) {
		$init = 0;
		$pos = true;
		do {
			$text = substr($surnames, $init);
			$pos1 = strpos($surnames, " ", $init);
			$pos2 = strpos($surnames, "'", $init);
			if($pos1 === false) {
				if($pos2 === false)
					$pos = false;
				else
					$pos = $pos2;
			} else {
				if($pos2 === false)
					$pos = $pos1;
				else
					$pos = min($pos1, $pos2);
			}
			if($pos !== false)
				$init += $pos;
		} while ($pos !== false && $pos <= 3);
		if($pos === false)
			return $surnames;
		return substr($surnames, 0, $init);
	}
	
	/**
	 * Inserta un nuevo usuario en la base de datos
	 *
	 * @param array $user Datos del usuario
	 * @return array Datos guardados
	 */
	protected function insertUser(array $user) {
		require_once 'System/Guid.php';
		$user = array_merge([
			'id'			=> Guid::newGuid(),
			'isApproved'	=> false,
			'isEnabled'		=> true,
			'kind'			=> 'individual',
		], $user);
		$contactId = ($user['id'] instanceof Guid) 
			? $user['id']->getHex()
			: $user['id'];
		try {
			$connection = $this->db->driver->getConnection();
			$connection->beginTransaction();
			$statement = $this->db->query(
				'INSERT INTO `Contacts` (`id`, `kind`)' .
				'VALUES (UNHEX(?), ?);'
			);
			$statement->execute([
				$contactId,
				$user['kind']
			]);

			$statement = $this->db->query(
				'INSERT INTO `Users` (`id`, `name`, `email`, `isApproved`, `isEnabled`)' .
				'VALUES (UNHEX(?), ?, ?, ?, ?);'
			);
			$statement->execute([
				$contactId,
				$user['name'],
				$user['email'],
				$user['isApproved'],
				$user['isEnabled']
			]);

			foreach($user['contact'] as $id => $property) {
				$statement = $this->db->query(
					'INSERT INTO `ContactProperties` (`id`, `contact`, `key`, `value`)' .
					'VALUES (UNHEX(?), UNHEX(?), ?, ?);'
				);
				$statement->execute([
					$id,
					$contactId,
					$property['key'],
					$property['value']
				]);
	
			}
			$connection->commit();
			return $user;
		} catch(Exception $e) {
			$connection->rollback();
			throw $e;
		}
	}

    
}