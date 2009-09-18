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
 * Locking
 *
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 10/2008
 */

class Locking {

	/**
	 * constructor startLocking
	 */
	public function __construct() {
		$this->lockConf = @array_merge($GLOBALS['LOCK']['default'], is_array($GLOBALS['LOCK'][$GLOBALS['TASK']]) ? $GLOBALS['LOCK'][$GLOBALS['TASK']] : array());
		$this->lockConf['path'] = $this->lockConf['path'] ? $this->lockConf['path'] : dirname($_SERVER['PHP_SELF']);
		$this->lockConf['fileName'] .= $GLOBALS['THREAD'];
	}

	/**
	 * check if lock-file exists and its older than maxAge
	 *
	 * @return boolean
	 */
	public function checkLock() {
		if(file_exists($this->lockConf['path'] . '/' . $this->lockConf['fileName'])) {
			if(filectime($this->lockConf['path'] . '/' . $this->lockConf['fileName']) < (time() - $this->lockConf['maxAge'])) {
				$GLOBALS['LOGGING']->setLog('warn', 'found lock-file: "' . $this->lockConf['path'] . '/' . $this->lockConf['fileName'] . '" older than ' . $this->lockConf['maxAge'] . ' seconds...', array('checkLock'));
				$GLOBALS['LOGGING']->setLog('warn', 'processing the job anyway', array('checkLock'));
				return true;
			} else {
				$GLOBALS['LOGGING']->setLog('warn', 'found lock-file: "' . $this->lockConf['path'] . '/' . $this->lockConf['fileName'] . '" created at ' . date('y.m.d-H:i:s', filectime(dirname($_SERVER['PHP_SELF']) . '/' . $this->lockConf['fileName'])) . ', stopped processing', array('checkLock'));
				return false;
			}
		}
		return true;
	}

	/**
	 * create lock-file to prevent more than one process
	 *
	 * @return boolean
	 */
	public function createLock() {
		if($fp = fopen($this->lockConf['path'] . '/' . $this->lockConf['fileName'], 'w')) {
			if(fwrite($fp, 1) !== false) {
				return true;
			} else {
				$GLOBALS['LOGGING']->setLog('error', 'can not write to lock-file: "' . $this->lockConf['path'] . '/' . $this->lockConf['fileName'] . '"', array('createLockFile'));
			}
			fclose($fp);
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'can not create lock-file: "' . $this->lockConf['path'] . '/' . $this->lockConf['fileName'] . '"', array('createLockFile'));
		}
		return false;
	}

	/**
	 * remove lock-file
	 */
	public function removeLock() {
		if(!@unlink($this->lockConf['path'] . '/' . $this->lockConf['fileName'])) {
			$GLOBALS['LOGGING']->setLog('error', 'can not delete lock-file: "' . $this->lockConf['path'] . '/' . $this->lockConf['fileName'] . '"', array('deleteLock'));
		}
	}
}

?>