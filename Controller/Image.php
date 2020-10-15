<?php

class Kansas_Controller_Image
	extends Kansas_Controller_Abstract {

	public function gallery() {
		global $application;
		$auth = $application->getPlugin('Auth');
		$view = $this->createView();
		$gallery = $this->getParam('gallery');
		$view->setCacheId('galery-' . $gallery->getName());
		if(!$this->isCached($view, 'page.image-gallery.tpl')) {
			$router = $this->getParam('router');
			$view->assign('viewAlbums', $router->assemble(array(
					'action'	=> 'viewAlbums',
					'albums'	=> $gallery->getSlugCollection()
				)));
		}
		if($auth->hasIdentity()) {
			global $application;
			$images = $application->getProvider('Image')->getAll();
			$view->assign('body_class',		'gallery-full');
			$view->assign('createAlbum',	$router->assemble(array(
				'action'	=> 'createAlbum'
			)));
			$view->assign('createImage',	$router->assemble(array(
				'action'	=> 'createImage'
			)));
			$view->assign('images',				$images);
			$view->assign('editImages',		$router->assemble(array(
				'action'	=> 'editImages',
				'images'	=> $images
			)));
		}
		
		return new Kansas_View_Result_Template($view, 'page.image-gallery.tpl');
	}

	public function album() {
		require_once('Kansas/Core/Collection/Interface.php');
		global $application;
		$auth = $application->getPlugin('Auth');
		$template = $this->getParam('template',	'page.image-album.tpl');
		$view = $this->createView();
		$router = $this->getParam('router');
		$album = $this->getParam('album');
		$first = Kansas_Core_Collection_First($album);
		if($first == null) {
			$view->setCacheId('album-' . $album->getId()->__toString());
			if(!$view->isCached($template)) {
				$gallery = $this->getParam('gallery');
				$galleryRouter = $this->getParam('galleryRouter');
				$view->assign('viewAlbums', $galleryRouter->assemble(array(
					'action'	=> 'viewAlbums',
					'albums'	=> $gallery
				)));
				$view->assign('viewImages',	$router->assemble(array(
					'action'	=> 'viewImages',
					'images'	=> $album
				)));
				$view->assign('pathImages',	$router->assemble(array(
					'action'	=> 'pathImages',
					'images'	=> $album,
					'format'	=> 'jpg',
					'params'	=> array(
						'size'	=> 150,
						'fill'	=> 'crop'
					)
				)));
			}
			return $this->createResult($view, $template);
		} else {
			$url	= $router->assemble(array(
				'action'	=> 'viewImage',
				'image'		=> $first
			));
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl($url);
			return $result;
		}
	}
	
	public function image() {
		global $application;
		$auth = $application->getPlugin('Auth');
		$template = $this->getParam('template',	'page.image-image.tpl');
		$view = $this->createView();
		$image = $this->getParam('image');
		$view->setCacheId('image-' . $image->getId()->__toString());
		if(!$view->isCached($template)) {
			$router = $this->getParam('router');
			$gallery = $this->getParam('gallery');
			$galleryRouter = $this->getParam('galleryRouter');
			$album = $this->getParam('album');
			$view->assign('image490',	$image->getPath('jpg', array(
				'size'	=>	490,
				'fill'	=>	Kansas_Media_Image::FILL_SCALE
			)));
			$view->assign('imageDzi',	$image->getPath('dzi'));
			$view->assign('viewAlbums', $galleryRouter->assemble(array(
					'action'	=> 'viewAlbums',
					'albums'	=> $gallery
				)));
			$view->assign('viewImages',	$router->assemble(array(
				'action'	=> 'viewImages',
				'images'	=> $album
			)));
			$view->assign('pathImages',	$router->assemble(array(
				'action'	=> 'pathImages',
				'images'	=> $album,
				'format'	=> 'jpg',
				'params'	=> array(
					'size'	=> 150,
					'fill'	=> 'crop'
				)
			)));
		}
		return $this->createResult($view, $template);
	}

	public function createImage() {
		global $application;
		$auth = $application->getPlugin('Auth');
		if($auth->hasIdentity()) {
			global $application;
			$router 			= $this->getParam('router');
			$model				= $application->getProvider('Image')->createImage();
			$model->setReturnUrl($this->getParam(Kansas_Core_Model::KEY_RETURN_URL, $router->assemble()));
			$modelId			= $application->getProvider('Model')->createModel($model);
			$url					= $router->assemble(array(
												'action'	=>	'editImage',
												'model'		=>	$modelId
											));
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl($url);
		} else {
			
		}
		return $result;
	}
	
	public function editImage() {
		global $application;
		$view 				= $this->createView();
		$router 			= $this->getParam('router');
		$model 				= Kansas_Media_Image::getModel($mId);
		$view->assign('model', 			$model);
		$view->assign('id', 				$mId);
		$view->assign('gallery',		new Kansas_Media_Group_Image_Gallery('Albunes'));
		$view->assign('tagTypes',		$application->getProvider('Image')->getTagTypes());
		
		if($model->hasSourceNew())
			$view->assign('newSource',	$model->getSourceNew());
		$view->assign('addSource', 		$router->assemble(array(
			'action'	=> 'addSource',
			'model'		=> $mId
		)));
		$view->assign('removeSource', 		$router->assemble(array(
			'action'	=> 'removeSource',
			'model'		=> $mId,
			'sources'	=> $model->getSources()
		)));
		if($model->hasTagNew())
			$view->assign('newTag',			$model->getTagNew());
		$view->assign('addTag', 			$router->assemble(array(
			'action'	=> 'addTag'
		)));
		$view->assign('removeTag', 		$router->assemble(array(
			'action'	=> 'removeTag',
			'model'		=> $mId,
			'tags'		=> $model->getTags()
		)));
		$view->assign('saveImage', 		$router->assemble(array(
			'action'	=> 'saveImage',
			'model'		=> $mId
		)));
		return new Kansas_View_Result_Template($view, 'page.image-edit.tpl');
	}
	
	public function saveImage() {
		global $application;
		$model 				= Kansas_Media_Image::getModel($mId);
		$application->getProvider('Model')->updateModel($mId, $model);
		
		$validation		= $model->save();
		
		if($validation == Kansas_Core_Model::VALIDATION_SUCCESS)
			$url 				= $model->getReturnUrl();
		else {
			$router 		= $this->getParam('router');
			$url				= $router->assemble(array(
											'action'					=> 'editImage',
											'model'						=> $mId,
											'validationError'	=> $validation
										));
		}
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl($url);
		return $result;
	}
	
	public function addSource() {
		global $application;
		$model 				= Kansas_Media_Image::getModel($mId);
		$validation		= $model->addSourceNew();
		$application->getProvider('Model')->updateModel($mId, $model);
		$router 		= $this->getParam('router');
		$url				= $router->assemble(array(
										'action'								=> 'editImage',
										'model'									=> $mId,
										'sourceValidationError'	=> $validation
									));
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl($url);
		return $result;
	}
	public function removeSource() {
		global $application;
		$model 				= Kansas_Media_Image::getModel($mId);
		$source				= $this->getParam('source');
		$sId					= new System_Guid($source);
		$model->removeSource($sId);
		$application->getProvider('Model')->updateModel($mId, $model);
		$router 		= $this->getParam('router');
		$url				= $router->assemble(array(
										'action'								=> 'editImage',
										'model'									=> $mId,
										'sourceValidationError'	=> $validation
									));
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl($url);
		return $result;
	}
	public function addTag() {
		global $application;
		$model 				= Kansas_Media_Image::getModel($mId);
		$validation		= $model->addTagNew();
		$application->getProvider('Model')->updateModel($mId, $model);
		$router 			= $this->getParam('router');
		$url					= $router->assemble([
										'action'								=> 'editImage',
										'model'									=> $mId,
										'tagValidationError'		=> $validation
									]);
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl($url);
		return $result;
	}
	public function removeTag() {
		global $application;
		$model 				= Kansas_Media_Image::getModel($mId);
		$tag					= $this->getParam('tag');
		$tId					= new System_Guid($tag);
		$model->removeTag($tId);
		$application->getProvider('Model')->updateModel($mId, $model);
		$router 		= $this->getParam('router');
		$url				= $router->assemble(array(
										'action'								=> 'editImage',
										'model'									=> $mId,
										'tagValidationError'		=> $validation
									));
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl($url);
		return $result;
	}
	
	public function resize() {
		$format = $this->getParam('format', 'jpg');
		$image	= $this->getParam('image');
		$filename = realpath(FILES_PATH . '/img/' . $image->getSourcePath());
		$header		= $this->getParam('header');
		if($header == 404 || !$filename)
			header("HTTP/1.0 404 Not Found");
		if(!$filename)
			$filename = realpath(FILES_PATH . '/img/error-404.jpg');
		list($width, $height) = getimagesize($filename);
		
		switch($format) {
			case 'dzi':
				$view = $this->createView();
				$view->assign('width', $width);
				$view->assign('height', $height);
				header('Content-type: application/xml');
				return $this->createResult($view, 'page.dzi.tpl');
			case 'jpg':
				$size		= $this->getParam('size');
				$maxWidth	= intval($this->getParam('width',	$size));
				$maxHeight	= intval($this->getParam('height', $size));
				if($maxWidth == 0 && $maxHeight == 0) {
					$dst = imagecreatefromjpeg($filename);
				} else {
					$fill		= $this->getParam('fill',		'crop');
					$r = $width / $height;
					$rdif = ($maxWidth != 0 && $maxHeight != 0)?
						$maxWidth/$maxHeight - $r:
						0;
					$x = 0;
					$y = 0;
					$dstWidth = $maxWidth;
					$dstHeight = $maxHeight;
					if($fill == 'scale' || $maxHeight == 0 || $maxWidth == 0) {
						if($rdif < 0 || $maxWidth == 0) //  ajustar algo
							$dstHeight = ceil($maxWidth/$r);
						elseif($rdif > 0 || $maxHeight == 0) // ajustar ancho
							$dstWidth = ceil($maxHeight*$r);
					} else {
						if ($rdif < 0) { // cortas x los lados
							$x = abs(ceil($width * $rdif / 2));
							$width = $width - (int)abs(ceil($width * $rdif));
						} elseif($rdif > 0) { // cortar x arriba y abajo
							$y = ceil($height * $rdif / 2);
							$height = $height -(int)ceil($height * $rdif);
						}
					}
					$src = imagecreatefromjpeg($filename);
					$dst = imagecreatetruecolor($dstWidth, $dstHeight);
					imagecopyresampled($dst, $src, 0, 0, $x, $y, $dstWidth, $dstHeight, $width, $height);
				}
				return  new Kansas_View_Result_Image($dst, $format);
		}
		
		
		var_dump($filename, FILES_PATH, $image->getSourcePath(), $format);
		exit;
		
	}
	
}