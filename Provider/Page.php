<?php

class Kansas_Db_Page
	extends Kansas_Db {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
	
	public function getKeywords(System_Guid $pageId) {
		$sql = "SELECT keyword FROM Keywords WHERE `Page` = UNHEX(?);";
		$rows = $this->db->fetchAll($sql, $pageId->getHex());
		$result = [];
		foreach($rows as $row)
			$result[] = $row['keyword'];
		return $result;
	}

}