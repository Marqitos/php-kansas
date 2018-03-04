<?php

class Kansas_Media_Image
	extends Kansas_Core_GuidItem_Model
	implements Kansas_Media_Image_Interface {
		
	const VALIDATION_NAME				= 0x01;
	const VALIDATION_SLUG				= 0x02;
	
	const FILL_SCALE						= 'scale';
	const FILL_CROP							= 'crop';
	
	private static $_404;
	
	private $_sources;
	private $_tags;
	
	private $_albumId;
	private $_album;

	private $_sourceId;
	private $_source;
	
	protected function init() {
		parent::init();
		if($this->row['Album'] != null)
			$this->_albumId		= new System_Guid($this->row['Album']);
		if($this->row['DefaultSource'] != null)
			$this->_sourceId	= new System_Guid($this->row['DefaultSource']);
	}
	
	/* Metodos de Kansas_Media_Image_Interface */
	public function getName() {
		return $this->row['Name'];
	}
	public function getDescription() {
		return $this->row['Description'];
	}
	public function getSlug() {
		return $this->row['slug'];
	}

	public function getDefaultSourceId() {
		return $this->_sourceId;
	}
	public function getDefaultSource() {
		global $application;
		if($this->_source == null || $this->_source->getId() != $this->_sourceId)
			$this->_source = $application->getProvider('Image')->getSource($this->_sourceId);
		return $this->_source;
	}

	public function getAlbumId() {
		return $this->_albumId;
	}
	public function getAlbum() {
		global $application;
		if($this->_album == null)
			$this->_album = $application->getProvider('Image')->getAlbum($this->_albumId);
		return $this->_album;
	}

	public function getSources() {
		global $application;
		if($this->_sources == null)
			$this->_sources = $this->getId()->isEmpty()?
				new Kansas_Core_GuidItem_Collection():
				$application->getProvider('Image')->getSources($this->getId());
			
		if(isset($this->row['sourcesRemoved']))
			foreach($this->row['sourcesRemoved'] as $remove)
				unset($this->_sources[$remove]);
				
		if(isset($this->row['sourcesEdit']))
			foreach($this->row['sourcesEdit'] as $sourceEdit)
				$this->_sources->add($sourceEdit);
				
		return $this->_sources;
	}
	
	public function getSource($format) {
		global $application;
		if(isset($this->_sources)) {
			foreach($this->_sources as $source)
				if($source->getFormat() == $format)
					return $source;
		}
		return $application->getProvider('Image')->getSourceByImageFormat($this->getId(), $format);
	}
	public function addSource(Kansas_Media_Image_Source_Interface $source) {
		if(!isset($this->row['sourcesEdit']))
			$this->row['sourcesEdit'] = new Kansas_Core_GuidItem_Collection();
		
		$this->row['sourcesEdit']->add($source);
		return $source;
	}
	public function removeSource(System_Guid $sourceId) {
		if(!isset($this->row['sourcesRemoved']))
			$this->row['sourcesRemoved'] = new Kansas_Core_GuidItem_GuidCollection();
		
		$this->row['sourcesRemoved']->add($sourceId);

		if(isset($this->row['sourcesEdit']))
			unset($this->row['sourcesEdit'][$sourceId]);
		
	}
	protected function createSource($path, $format) {
		$row		= array(
			'Id'			=> System_Guid::NewGuid()->getHex(),
			'Image'		=> $this,
			'Path'		=> $path,
			'Format'	=> $format
		);
		return new Kansas_Media_Image_Source($row);
	}
	public function hasSourceNew() {
		return isset($this->row['newSource']);
	}
	public function getSourceNew() {
		return isset($this->row['newSource'])?
			$this->row['newSource']:
			null;
	}
	
	public function getTags() {
		global $application;
		if($this->_tags == null)
			$this->_tags = $this->getId()->isEmpty()?
				new Kansas_Core_GuidItem_Collection():
				$application->getProvider('Image')->getTagGroupsByImageId($this->getId());

		if(isset($this->row['tagsRemoved']))
			foreach($this->row['tagsRemoved'] as $remove)
				unset($this->_tags[$remove]);

		if(isset($this->row['tagsEdit']))
			foreach($this->row['tagsEdit'] as $tag)
				if(!isset($this->_tags[$tag]))
					$this->_tags->add($application->getProvider('Image')->getTagGroup($tag));

		return $this->_tags;
	}

	public function addTag($tag) {
		global $application;
		if(!isset($this->row['tagsEdit']))
			$this->row['tagsEdit'] = new Kansas_Core_GuidItem_GuidCollection();
			
		$this->row['tagsEdit']->add($tag);
		
		if(isset($this->_tags))
			$this->_tags->add($application->getProvider('Image')->getTagGroup($tag));
	}
	public function removeTag($tag) {
		if(!isset($this->row['tagsRemoved']))
			$this->row['tagsRemoved'] = new Kansas_Core_GuidItem_GuidCollection();
			
		$this->row['tagsRemoved']->add($tag);
		
		if(isset($this->_tags))
			unset($this->_tags[$tag]);
			
		if(isset($this->row['tagsEdit']))
			unset($this->row['tagsEdit'][$tag]);
	}
	public function hasTagNew() {
		return isset($this->row['newTag']) && $this->row['newTag'] != null;
	}
	public function getTagNew() {
		return isset($this->row['newTag'])?
			$this->row['newTag']:
			null;
	}

	/* Metodos Estaticos */
	public static function getModel(&$mId, Kansas_Controller_Interface $controller) {
		$model = parent::getModel($mId);
		if($model == null) {
			global $application, $environment;
			$request			= $environment->getRequest();
			$router				= $controller->getParam('router');
			$image				= new System_Guid($controller->getParam('image'));
			$model				= $application->getProvider('Image')->getById($image);
			$model->setReturnUrl($controller->getParam(Kansas_Core_Model::KEY_RETURN_URL, $router->assemble()));
			$mId					= $application->getProvider('Model')->createModel($model);
		} else
			$model->fill($controller);
		return $model;
	}
	
	public static function getErrorInstace() {
		if(self::$_404 == null)
			self::$_404 = new self(array(
				'Album'					=>	null,
				'DefaultSource'	=>	null,
				'Source'				=>	'error-404.jpg'
			));
		return self::$_404;
	}
	
	public function fill(Kansas_Controller_Interface $controller) {
		parent::fill($controller);
		
		$this->row['Name'] = trim($this->row['Name']);
		
		if(empty($this->row['slug']) && !empty($this->row['Name']))
			$this->row['slug'] = Kansas_Core_Slug_Slugify($this->row['Name']);
		
		$tag = $controller->getParam('tag');
		$this->row['newTag']= empty($tag)?
			null:
			new System_Guid($tag);

		$sourcePath							= $controller->getParam('source-path');
		$sourceFormat						=	$controller->getParam('source-format');
		if(isset($this->row['newSource'])) {
			$this->row['newSource']->setPath($sourcePath);
			$this->row['newSource']->setFormat($sourceFormat);
		} else
			$this->row['newSource']	= $this->createSource($sourcePath, $sourceFormat);
		
		$this->init();
	}
	
	public function save() {
		global $application;
		// Validar datos
		$result = self::VALIDATION_SUCCESS;
		
		if(empty($this->row['Name']))
			$result |= self::VALIDATION_NAME;

		if(empty($this->row['slug']) || $this->row['slug'] == 'n-a')
			$result |= self::VALIDATION_THUMBNAIL;
		
		// Guardar en BBDD
		if($result != self::VALIDATION_SUCCESS)
			return $result;
			
		$application->getProvider('Image')->saveImage($this->row);

		return self::VALIDATION_SUCCESS;
	}
	
	public function addSourceNew() {
		if(!isset($this->row['newSource']))
			return self::VALIDATION_EMPTY;
			
		// Validar datos
		$result = $this->row['newSource']->validate();
		
		if($result == self::VALIDATION_SUCCESS) {
			$this->addSource($this->row['newSource']);
			unset($this->row['newSource']);
		}
		return $result;
	}
	
	public function addTagNew() {
		if(!isset($this->row['newTag']))
			return self::VALIDATION_EMPTY;
			
		$this->addTag($this->row['newTag']);
		unset($this->row['newTag']);
		return self::VALIDATION_SUCCESS;
	}
	
	public function getSourcePath() {
		return empty($this->row['Source']) ?
			$this->row['slug'] . '.jpg':
			$this->row['Source'];
	}
	
	public function getPath($type = 'jpg', array $params = array()) {
		return '/img/' . $this->row['slug'] . '.' . $type . Kansas_Response::buildQueryString($params);
	}
	
}
