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
 * Init Script-Routines
 *
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 10/2008
 */

include_once 'config.inc.php';
include_once 'Logging.class.php';
include_once 'Locking.class.php';
include_once 'DBConnection.class.php';

$GLOBALS['LOGGING'] = new Logging();
$GLOBALS['LOCKING'] = new Locking();

?>