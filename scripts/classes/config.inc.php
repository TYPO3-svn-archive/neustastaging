<?php

/*********************************************************
 * Copyright notice
 *
 * (c) 2008 NEUSTA GmbH, Nils Seinschedt (n.seinschedt@neusta.de)
 * All rights reserved
 *
 *********************************************************
 *
 * $Id$
 *
 * General Config File
 *
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 10/2008
 */

$GLOBALS['DB'] = array(
	'sourceDbIdentifier' => array (
		'host' => 'db-host',
		'user' => 'db-user',
		'pass' => 'db-pass',
		'db' => 'db'
	),
	'destinationDbIdentifier' => array (
		'host' => 'db-host',
		'user' => 'db-user',
		'pass' => 'db-pass',
		'db' => 'db'
	)
);

$GLOBALS['LOG'] = array(
	'default' => array (
		'level' => 3,
		'fileName' => 'gen',
		'path' => '',
		'mail' => array (
			'level' => 3,
			'subject' => 'DEFAULT-LOGS',
			'sndMail' => 'errors@example.com',
			'rcvMail' => 'errors@example.com',
			'sndName' => 'DEFAULT',
			'rcvName' => 'DEFAULT'
		)
	),
	'staging' => array (
		'fileName' => 'staging',
		'mail' => array (
			'level' => 3,
			'subject' => 'STAGING-LOGS',
			'sndMail' => 'errors@example.com',
			'rcvMail' => 'errors@example.com',
			'sndName' => 'STAGING',
			'rcvName' => 'STAGING'
		)
	)
);

$GLOBALS['LOCK'] = array (
	'default' => array (
		'maxAge' => 1800,
		'fileName' => 'lock'
	),
	'staging' => array(
		'maxAge' => 3600
	)
);

/*********************************************************
 * STAGING CONFIGS
 */
$GLOBALS['STAGING'] = array (
	'db' => array (
		'fileTable'	=> array (
			'name' => 'tx_neustastaging_files',
			'title' => 'path',
			'uidField' => 'uid',
			'pathField' => 'path',
			'whereField' => 'thread_prio'
		),
		'cacheTables' => array (
			0 => array (
				'name' => 'cache_pages',
				'whereField' => 'page_id'
			),
			1 => array (
				'name' => 'cache_pagesection',
				'whereField' => 'page_id'
			)
		),
		'rootTable' => array (
			'name' => 'pages',
			'title' => 'title',
			'uidField' => 'uid',
			'whereField' => 'tx_neustastaging_staging',
			'extTables' => array (
				0 => array (
					'name' => 'sys_template',
					'title' => 'title',
					'uidField' => 'uid',
					'whereField' => 'pid'
				),
				1 => array (
					'name' => 'tt_content',
					'title' => 'header',
					'uidField' => 'uid',
					'whereField' => 'pid'
				)
			)
		)
	),
	'fs' => array (
		'rsyncBin' => 'rsync',
		'rsyncParams' => 'avR --delete -e ssh',
		'srcFS' => array (
			'mountPoint' => '/path/to/fileadmin/',
			'rootPath' => '/fileadmin/'
		),
		'dstFS' => array (
			0 => array (
				'sshPrefix' => 'user@server',
				'rootPath' => '/path/to/destination/fileadmin/'
			)
		)
	)
);
?>