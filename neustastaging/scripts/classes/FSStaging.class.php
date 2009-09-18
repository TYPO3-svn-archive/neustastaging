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
 * FSStaging
 *
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 10/2008
 */

class FSStaging {

	/**
	 * constructor startFSStaging
	 */
	public function __construct() {
		$this->updateStack = array();
		$this->errorUpdateStack = array();
		$this->srcDB = new DBConnection('ctest', 1);
		if($this->srcDB->connId) {
			$this->getFSUpdates();
			if(count($this->updateStack)) {
				$this->processFSUpdates();
				$this->removeFSStagingFlags();
			}
			$this->srcDB->simpleDisconnect();
		}
	}

	/**
	 * get all needed file-updates from source-db
	 */
	private function getFSUpdates() {
		if($res = @mysql_query('SELECT ' . $GLOBALS['STAGING']['db']['fileTable']['uidField'] . ', ' . $GLOBALS['STAGING']['db']['fileTable']['pathField'] . ' FROM ' . $GLOBALS['STAGING']['db']['fileTable']['name'] . ' WHERE ' . $GLOBALS['STAGING']['db']['fileTable']['whereField'] . ' = ' .  $GLOBALS['THREAD'], $this->srcDB->connId)) {
			if(@mysql_num_rows($res)) {
				while($row = @mysql_fetch_assoc($res)) {
					$this->updateStack[$row[$GLOBALS['STAGING']['db']['fileTable']['uidField']]] = $row[$GLOBALS['STAGING']['db']['fileTable']['pathField']];
				}
			} else {
				$GLOBALS['LOGGING']->setLog('notice', 'no file-updates found in table', array('fs', 'srcDB', 'getFSUpdates', $GLOBALS['STAGING']['db']['fileTable']['name']));
			}
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'getting file-data from table failed, this error occurred: "' . mysql_error() . '"', array('fs', 'srcDB', 'getFSUpdates', $GLOBALS['STAGING']['db']['fileTable']['name']));
		}
	}

	/**
	 * proceed db-updates from updateStack
	 *
	 * @return boolean
	 */
	private function processFSUpdates() {
		if(chdir($GLOBALS['STAGING']['fs']['srcFS']['mountPoint'])) {
			$GLOBALS['LOGGING']->setLog('notice', 'command: "cd ' . $GLOBALS['STAGING']['fs']['srcFS']['mountPoint'] . '" successful', array('fs', 'srcFS', 'processFSUpdates'));
			for($i=0; $i<count($GLOBALS['STAGING']['fs']['dstFS']); $i++) {
				foreach($this->updateStack as $uid => $path) {
					unset($out);
					unset($ret);
					$cmd = $GLOBALS['STAGING']['fs']['rsyncBin'] . ($GLOBALS['STAGING']['fs']['rsyncParams'] ? ' -' . $GLOBALS['STAGING']['fs']['rsyncParams'] : '') . ' ';
					$cmd .= '"' . str_replace($GLOBALS['STAGING']['fs']['srcFS']['rootPath'], '', $path) . '" ';
					$cmd .= '"' . $GLOBALS['STAGING']['fs']['dstFS'][$i]['sshPrefix'] . ':' . $GLOBALS['STAGING']['fs']['dstFS'][$i]['rootPath'] . '" >&1 2>/dev/null';
					exec($cmd, $out, $ret);
					if(!$ret) {
						$GLOBALS['LOGGING']->setLog('notice', 'command: "' . $cmd . '" successful', array('fs', 'srcFS', 'processFSUpdates'));
					} else {
						$GLOBALS['LOGGING']->setLog('error', 'command: "' . $cmd . '" failed, this error occurred: "' . implode(',', $out) . '"', array('fs', 'srcFS', 'processFSUpdates'));
						$this->errorUpdateStack[$uid] = $path;
					}
				}
			}
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'cd to "' . $GLOBALS['STAGING']['fs']['srcFS']['mountPoint'] . '" failed', array('srcFS', 'processFSUpdates'));
		}
	}

	/**
	 * reset all staging-files from srcDB
	 */
	private function removeFSStagingFlags() {
		$this->updateStack = array_diff($this->updateStack, $this->errorUpdateStack);
		foreach($this->updateStack as $uid => $path) {
			if(@mysql_query('DELETE FROM ' . $GLOBALS['STAGING']['db']['fileTable']['name'] . ' WHERE ' . $GLOBALS['STAGING']['db']['fileTable']['uidField'] . ' = ' . $uid, $this->srcDB->connId)) {
				$GLOBALS['LOGGING']->setLog('notice', 'deleted data with id: "(' . $uid . ')"', array('fs', 'srcDB', 'removeFSStagingFlags', $GLOBALS['STAGING']['db']['fileTable']['name']));
			} else {
				$GLOBALS['LOGGING']->setLog('warn', 'can not delete data with id: "(' . $dataArr[$i][$uidField] . ')"', array('fs', 'srcDB', 'removeFSStagingFlags', $GLOBALS['STAGING']['db']['fileTable']['name']));
			}
		}
	}
}

?>