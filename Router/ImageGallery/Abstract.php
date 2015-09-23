<?php

abstract class Kansas_Router_ImageGallery_Abstract
	extends Kansas_Router_Abstract {
		
	public function match() {
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    
    if(Kansas_String::startWith($this->getBasePath(), $path))
      $path = substr($this->getBasePath(), strlen($this->getBasePath()));
    else
			return false;

		if($path === false)
			return false;
			
		if($path == '') {
			return array_merge($this->getDefaultParams(),
				array(
					'gallery'		=> $this->getGallery(),
					'router'		=> $this
				));
		}
		
		foreach($this->getGallery() as $album) {
			$router = $this->getAlbumRouter($album);
			$router->setGallery($this->getGallery());
			$router->setBasePath($this->getBasePath() . '/' . $album->getSlug());
			$router->setGallery($this->getGallery());
			if($params = $router->match($request))
				return $params;
		}
		
		return false;
	}
	
	public abstract function getGallery();
	public abstract function getAlbumRouter(Kansas_Media_Group_Image_Album_Interface $album);
	
	protected function getDefaultOptions() {
		return parent::getDefaultOptions()->merge(new Zend_Config(array(
			'params'	=> array(
				'controller'	=> 'image',
				'action'			=> 'gallery',
				'template'		=> 'page.image-gallery.tpl')
		)));
	}
	
  public function assemble($data = array(), $reset = false, $encode = false) {
		$path = parent::assemble($data, $reset, $encode);
	 	if(isset($data['action'])) {
			switch($data['action']) {
				case 'createAlbum':
					return $path . '/album/create';
				case 'viewAlbums':
					$result = array();
					foreach($data['albums'] as $album) {
						$result[$album->getId()->__toString()] = $path . '/' . $album->getSlug();
					}
					return $result;
				case 'createImage':
					$queryData = array(
						'ru'	=> $path
					);
					return $path . '/create' . Kansas_Response::buildQueryString($queryData);
				case 'saveImage':
					$queryData = array(
						'm' => $data['model']
					);
					return $path . '/save' . Kansas_Response::buildQueryString($queryData);
				case 'addSource':
					$queryData = array(
						'm' => $data['model']
					);
					return $path . '/source/add' . Kansas_Response::buildQueryString($queryData);
				case 'removeSource':
					$result = array();
					foreach($data['sources'] as $source) {
						$queryData = array(
							'm' 			=> $data['model'],
							'source'	=> $source->getId()
						);
						$result[$source->getId()->__toString()] = $path . '/source/remove' . Kansas_Response::buildQueryString($queryData);
					}
					return $result;
				case 'addTag':
					return $path . '/tag/add';
				case 'removeTag':
					$result = array();
					foreach($data['tags'] as $tag) {
						$queryData = array(
							'm' 			=> $data['model'],
							'tag'			=> $tag->getId()
						);
						$result[$tag->getId()->__toString()] = $path . '/tag/remove' . Kansas_Response::buildQueryString($queryData);
					}
					return $result;
				case 'editImage':
					$queryData = array();
					if(isset($data['model']))
						$queryData[Kansas_Core_Model::KEY_MODEL]	= $data['model'];
					if(isset($data['photo']))
						$queryData['photo']	= $data['photo'];
					return $path . '/edit' . Kansas_Response::buildQueryString($queryData);
				case 'editImages':
					$result = array();
					foreach($data['images'] as $image) {
						$queryData = array(
							'image'	=> $image->getId()
						);
						$result[$image->getId()->__toString()] = $path . '/edit' . Kansas_Response::buildQueryString($queryData);
					}
					return $result;
			}
		}
	}
	
}