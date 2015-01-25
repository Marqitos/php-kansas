<?php

interface Kansas_TitleBuilder_Interface {
	public function getSeparator();
	public function setSeparator($separator);
	
	public function getAttachOrder();
	public function setAttachOrder($order);
	
	public function attach($title);
	public function setTitle($title);
}