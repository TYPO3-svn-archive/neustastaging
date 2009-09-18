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
 * Start Staging
 *  
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 10/2008
 */
 
include_once 'classes/DBStaging.class.php';
include_once 'classes/FSStaging.class.php';

if($argv[1] == '?') {
	echo "Usage: php staging.php [logLevel] [threadLevel]\n";
	echo "Allowed LogLevels are: 1-7\n";
	echo "Allowed ThreadLevels are 1 or 2\n";
} else {
	$GLOBALS['TASK'] = 'staging';
	$GLOBALS['THREAD'] = $argv[2] == 2 ? 2 : 1;
	include_once 'classes/common.inc.php';	
	
	if($GLOBALS['LOCKING']->checkLock()) {
		$GLOBALS['LOCKING']->createLock();

		$fsStaging = new FSStaging();				
		$dbStaging = new DBStaging();
		
		$GLOBALS['LOCKING']->removeLock();
	}
	
	$GLOBALS['LOGGING']->writeLog();
	$GLOBALS['LOGGING']->mailLog();

	echo $GLOBALS['LOGGING']->getLog($argv[1]);
}

?>