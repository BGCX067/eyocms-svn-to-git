<?php
$GLOBALS['SITECONF']['DBCONF']['SERNAME'] = 'http://localhost/';
$GLOBALS['SITECONF']['DBCONF']['username'] = 'eyocms';
$GLOBALS['SITECONF']['DBCONF']['password'] = 'eyocms';
$GLOBALS['SITECONF']['DBCONF']['db'] = 'eyocms';

//Configuration du site
$GLOBALS['SITECONF'] = array(
	'CONFIG' => array(
		'lang' => 'fr',
		'urlRewrite' => true,
		'baseUrl' => 'http://localhost/eyocms/',
		'charset' => 'utf-8',
	),
	'HEADER' => array(
		'css' => array(
			'style' => 'public/style.css',
		),
		'js' => array(
			'prototype' => 'public/js/prototype.js',
			'rounded-corners.js' => 'public/js/rounded-corners.js',
		),
	),
);


$GLOBALS['CONF']['addMarker'] = array(
	'menu' => 'm_menu_addmarker->main',
	'bonjour' => 'm_content_addmarker->getBonjourContent',
	'type_content' => 'm_content_addmarker->getTypeContent',
);
?>