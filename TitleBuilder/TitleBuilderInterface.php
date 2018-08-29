<?php

namespace Kansas\TitleBuilder;

interface TitleBuilderInterface {
	public function getSeparator();
	public function setSeparator($separator);
	
	public function getAttachOrder();
	public function setAttachOrder($order);
	
	public function attach($title);
	public function setTitle($title);
}