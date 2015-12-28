<?php
//Configuration du site
$GLOBALS['MODULECONF']['menu'] = array(
	'declic' => array(
		'Réseau' => array('reseau','declic'),
		'Réserver' => array('reservation','declic'),
		'Mes Réservation' => array('mesreservations','declic'),
		'Mes Voyages réguliers' => array('voyagesreguliers','declic'),
		'Déconnexion' => array('','','deconnexion=true'),
	),
	'pixel' => array(
		'Réseau' => array('reseau','pixel'),
		'Réserver' => array('reservation','pixel'),
		'Mes Réservation' => array('mesreservations','pixel'),
		'Mes Voyages réguliers' => array('voyagesreguliers','pixel'),
		'Déconnexion' => array('','','deconnexion=true'),
	),
);
?>