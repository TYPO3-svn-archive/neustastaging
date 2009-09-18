<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_neustastaging_files"] = array (
	"ctrl" => $TCA["tx_neustastaging_files"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,path"
	),
	"feInterface" => $TCA["tx_neustastaging_files"]["feInterface"],
	"columns" => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"path" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:neustastaging/locallang_db.xml:tx_neustastaging_files.path",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "hidden;;1;;1-1-1, path")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_neustastaging_log"] = array (
	"ctrl" => $TCA["tx_neustastaging_log"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "time,user,pages,files"
	),
	"feInterface" => $TCA["tx_neustastaging_log"]["feInterface"],
	"columns" => array (
		"time" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:neustastaging/locallang_db.xml:tx_neustastaging_log.time",		
			"config" => Array (
				"type"     => "input",
				"size"     => "12",
				"max"      => "20",
				"eval"     => "datetime",
				"checkbox" => "0",
				"default"  => "0"
			)
		),
		"user" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:neustastaging/locallang_db.xml:tx_neustastaging_log.user",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "be_users",	
				"foreign_table_where" => "ORDER BY be_users.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"pages" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:neustastaging/locallang_db.xml:tx_neustastaging_log.pages",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"files" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:neustastaging/locallang_db.xml:tx_neustastaging_log.files",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "time;;;;1-1-1, user, pages, files")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);
?>