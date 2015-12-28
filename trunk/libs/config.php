<?php
if (function_exists('spl_autoload_register')) {
	spl_autoload_register(array('div', 'dynClassLoad'));
}

//Lib à charger tous le temps
$GLOBALS['LIB_REGISTER'] = array(
	'headmgr' => PATH_LIBS.'headmgr.php',
	'template' => PATH_LIBS.'template.php',
	'magicmarkers' => PATH_LIBS.'magicmarkers.php',
	'base' => PATH_LIBS.'base.php',
	'user' => PATH_LIBS.'user.php',
);

//configuration des modules
$GLOBALS['LIBCONF'] = array(
	'template' => array(
		'magicMarkers' => array(
			'FORMAT_SIZE' => 'magicmarkers->formatSizeMarkers',
			'DATE'   => 'magicmarkers->processDateSubpart',
			'LINK' => 'magicmarkers->link',
			'ADDJS' => 'magicmarkers->addJS',
			'ADDCSS' => 'magicmarkers->addCSS',
			'PATH' => 'magicmarkers->path',
			'CALENDAR' => 'magicmarkers->addCalendar',
			'ADDCSSFILE' => 'magicmarkers->addCSSFile',
			'ADDJSFILE' => 'magicmarkers->addJSFile',
			'ODD_EVEN_TABLE_ROW' => 'magicmarkers->oddEvenTableRow',
		),
		'includeCalendar' => array(
			'css' => array(
				'public/js/calendar/css/jscal2.css',
				'public/js/calendar/css/border-radius.css',
			),
			'js' => array(
				'public/js/calendar/js/jscal2.js',
			),
			'js_lang' => array(
				'ru' => 'public/js/calendar/js/lang/ru.js',
				'de' => 'public/js/calendar/js/lang/de.js',
				'fr' => 'public/js/calendar/js/lang/fr.js',
				'ro' => 'public/js/calendar/js/lang/ro.js',
				'es' => 'public/js/calendar/js/lang/es.js',
				'cz' => 'public/js/calendar/js/lang/cz.js',
				'pl' => 'public/js/calendar/js/lang/pl.js',
				'pt' => 'public/js/calendar/js/lang/pt.js',
				'jp' => 'public/js/calendar/js/lang/jp.js',
				'cn' => 'public/js/calendar/js/lang/cn.js',
				'en' => 'public/js/calendar/js/lang/en.js',
			),
		),
		'themeCalendar' => array(
			'win2k'	=> 'public/js/calendar/css/win2k/win2k.css', 
			'steel'	=> 'public/js/calendar/css/steel/steel.css', 
			'gold'	=> 'public/js/calendar/css/gold/gold.css', 
			'matrix' => 'public/js/calendar/css/matrix/matrix.css',
		),
	),

	'webtod' => array(
		'cache' => array(
			'ServiceLireAdherent' => false,
			'ServiceListerReservationsParAdherent' => false,
			'ServiceListerArrets' => true,
			'ServiceListerLignes' => true,
			'ServiceAuthentifierAdherent' => false,
			'ServiceAnnulerReservation' => false,
			'ServiceActiverVoyagesReguliers' => false,
		),
		'urlServiceCorrespondance' => array(
			'ServiceListerLignes' => 'ServiceListerLignesGeo'
		),
	),
);
?>