<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
		
	t3lib_extMgm::addModule('web','txneustastagingM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}


if (TYPO3_MODE == 'BE')	{
		
	t3lib_extMgm::addModule('file','txneustastagingM2','',t3lib_extMgm::extPath($_EXTKEY).'mod2/');
}


if (TYPO3_MODE == 'BE')	{
		
	t3lib_extMgm::addModule('help','txneustastagingM3','',t3lib_extMgm::extPath($_EXTKEY).'mod3/');
}

$tempColumns = Array (
	"tx_neustastaging_staging" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:neustastaging/locallang_db.xml:pages.tx_neustastaging_staging",		
		"config" => Array (
			"type" => "check",
		)
	),
);


t3lib_div::loadTCA("pages");
t3lib_extMgm::addTCAcolumns("pages",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("pages","tx_neustastaging_staging;;;;1-1-1");

$TCA["tx_neustastaging_files"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:neustastaging/locallang_db.xml:tx_neustastaging_files',		
		'label'     => 'uid',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_neustastaging_files.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, path",
	)
);

$TCA["tx_neustastaging_log"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:neustastaging/locallang_db.xml:tx_neustastaging_log',		
		'label'     => 'uid',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_neustastaging_log.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "time, user, pages, files",
	)
);
?>