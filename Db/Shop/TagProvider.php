<?php

class Kansas_Db_Shop_TagProvider
	extends Kansas_Db
	implements Kansas_Media_Group_Image_Tag_Provider_Interface {
		
	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
		
	public function getTagTypes() {
		$result = new Kansas_Media_Group_Image_TagType_Collection();
		$result->add(new Kansas_Media_Group_Image_TagType(array(
			'Type'	=> 'shop',
			'Name'	=> 'Tienda'
		)));
		return $result;
	}
	
	public function getTagGroups() {
		$result = new ArrayIterator();
		$products = Kansas_Application::getInstance()->getProvider('shop')->getProducts();
		foreach($products as $product)
			$result[] = Kansas_Db_Shop_TagProvider_ProductToImageTagGroup($product);
		return $result;
	}
	// ArrayIterator
	public function getTagGroupsByType($type) {
		if($type == 'shop')
			return $this->getTagGroups();
	}

	// ArrayIterator
	public function getTagGroupsByImageId(System_Guid $id) {
		$sql = "SELECT SPR.* FROM `ImageTags` AS IMT INNER JOIN `ShopProducts` AS SPR ON IMT.`Tag` = SPR.`Id` WHERE IMT.`Image` = UNHEX(?) ORDER BY SPR.`Name`;";
		$rows = $this->db->fetchAll($sql, $id->getHex());
		$result = new ArrayIterator();
		foreach($rows as $row)
			$result[] = Kansas_Db_Shop_TagProvider_ProductToImageTagGroup(
				new Kansas_Shop_Product($row)
			);
		return $result;
	}

	// ImageTag
	public function getTagGroup(System_Guid $id) {
		$sql = "SELECT * FROM `ShopProducts` WHERE `Id` = UNHEX(?);";
		$row = $this->db->fetchRow($sql, $id->getHex());
		if($row == null)
			return null;
		return Kansas_Db_Shop_TagProvider_ProductToImageTagGroup(
			new Kansas_Shop_Product($row)
		);
	}
	
}

function Kansas_Db_Shop_TagProvider_ProductToImageTagGroup(Kansas_Shop_Product_Interface $product) {
	return new Kansas_Media_Group_Image_Tag(array(
		'Id'					=> $product->getId(),
		'Name'				=> $product->getName(),
		'Description'	=> $product->getDescription(),
		'Type'				=> 'shop',
		'slug'				=> $product->getFullSlug(),
		'Thumbnail'		=> '/img/' . $product->getFullSlug() . '/th_128.jpg'
	));
}
