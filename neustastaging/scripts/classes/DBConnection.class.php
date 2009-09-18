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
 * Wrapper for database connection
 *
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 12/2008
 */

class DBConnection extends mysqli {

	/**
	 * constructor init dbconnection
	 *
	 * @param String $connArea
	 * @param boolean $simpleConn
	 * @return boolean
	 */
	function __construct($connArea='ctest', $simpleConn=false) {
		$this->connArea = $connArea;
		if(array_key_exists($this->connArea, $GLOBALS['DB'])) {
			if($simpleConn) {
				if (!$this->simpleConnect()) {
					throw new Exception('Could not connect to database.');
				}
			} else {
				parent::__construct($GLOBALS['DB'][$this->connArea]['host'], $GLOBALS['DB'][$this->connArea]['user'], $GLOBALS['DB'][$this->connArea]['pass'], $GLOBALS['DB'][$this->connArea]['db']);
				if(mysqli_connect_error()) {
					$GLOBALS['LOGGING']->setLog('error', 'connection failed: ' . mysqli_connect_error(), array('DBConnection', $this->connArea));
				} else {
					return true;
				}
			}
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'connection failed, cant find connection-data with name "'. $this->connArea . '"', array('DBConnection', $this->connArea));
		}

		throw new Exception('Could not connect to database.');
	}

	/**
	 * init simple-mysql-connection
	 *
	 * @return boolean
	 */
	function simpleConnect() {
		if($this->connId = @mysql_connect($GLOBALS['DB'][$this->connArea]['host'], $GLOBALS['DB'][$this->connArea]['user'], $GLOBALS['DB'][$this->connArea]['pass'])) {
			if(@mysql_select_db($GLOBALS['DB'][$this->connArea]['db'], $this->connId)) {
				return true;
			} else {
				$GLOBALS['LOGGING']->setLog('error', 'db-select failed', array('DBConnection', $this->connArea));
			}
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'connection failed', array('DBConnection', $this->connArea));
		}
		return false;
	}

	/**
	 * kill simple-mysql-connection
	 *
	 * @return boolean
	 */
	function simpleDisconnect() {
		if(@mysql_close($this->connId)) {
			unset($this->connId);
			return true;
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'disconnect failed', array('DBConnection', $this->connArea));
		}
		return false;
	}
}
?>