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
 * DBStaging
 *
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 10/2008
 */

class DBStaging {

	/**
	 * constructor startDBStaging
	 */
	public function __construct() {
		$this->updateStack = array();
		$this->srcDB = new DBConnection('ctest', 1);
		$this->dstDB = new DBConnection('prod', 1);
		if($this->srcDB->connId && $this->dstDB->connId) {
			$this->getDBUpdates($GLOBALS['STAGING']['db']['rootTable'], $GLOBALS['THREAD']);
			if(count($this->updateStack)) {
				if($this->processDBUpdates()) {
					$this->removeDBCache();
					$this->removeDBStagingFlags();
				}
			}
			$this->srcDB->simpleDisconnect();
			$this->dstDB->simpleDisconnect();
		}
	}

	/**
	 * get all needed mysql-updates from source-db
	 *
	 * @param array $tblSettings
	 * @param string $whereValue
	 * @param boolean $step
	 */
	private function getDBUpdates($tblSettings, $whereValue=1, $step=false) {
		if($res = @mysql_query('SELECT * FROM ' . $tblSettings['name'] . ' WHERE ' . $tblSettings['whereField'] . ' = ' . $whereValue, $this->srcDB->connId)) {
			if(@mysql_num_rows($res)) {
				while($row = @mysql_fetch_assoc($res)) {
					if(!$step) {
						unset($row[$tblSettings['whereField']]);
						$this->detectMissingParents($row['pid']);
					}
					foreach($row as $key => $value) {
						$row[$key] = mysql_escape_string($value);
					}
					$this->updateStack[$tblSettings['name']][] = $row;
					$GLOBALS['LOGGING']->setLog('notice', 'found data: "' . $row[$tblSettings['title']] . ' (' . $row[$tblSettings['uidField']] . ')"', array('db', 'srcDB', 'getDBUpdates', $tblSettings['name']));
					if(count($tblSettings['extTables'])) {
						for($i=0; $i<count($tblSettings['extTables']); $i++) {
							$this->getDBUpdates($tblSettings['extTables'][$i], $row[$tblSettings['uidField']], true);
						}
					}
				}
			} else {
				$GLOBALS['LOGGING']->setLog('notice', 'no data-updates found in table', array('db', 'srcDB', 'getDBUpdates', $tblSettings['name']));
			}
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'getting data from table failed, this error occurred: "' . mysql_error() . '"', array('db', 'srcDB', 'getDBUpdates', $tblSettings['name']));
		}
	}

	/**
	 * detect missing parent datasets from the given id
	 *
	 * @param int $id
	 */
	private function detectMissingParents($id) {
		$row = @mysql_fetch_assoc(@mysql_query('SELECT uid FROM ' . $GLOBALS['STAGING']['db']['rootTable']['name'] . ' WHERE ' . $GLOBALS['STAGING']['db']['rootTable']['uidField'] . ' = ' . $id, $this->dstDB->connId));
		if(!$row['uid'] && $id) {
			$row = @mysql_fetch_assoc(@mysql_query('SELECT * FROM ' . $GLOBALS['STAGING']['db']['rootTable']['name'] . ' WHERE ' . $GLOBALS['STAGING']['db']['rootTable']['uidField'] . ' = ' . $id, $this->srcDB->connId));
			foreach($row as $key => $value) {
				$row[$key] = mysql_escape_string($value);
			}
			unset($row[$GLOBALS['STAGING']['db']['rootTable']['whereField']]);
			$this->detectMissingParents($row['pid']);
			$this->updateStack[$GLOBALS['STAGING']['db']['rootTable']['name']][] = $row;
			$GLOBALS['LOGGING']->setLog('notice', 'found missing parent data: "' . $row[$GLOBALS['STAGING']['db']['rootTable']['title']] . ' (' . $row[$GLOBALS['STAGING']['db']['rootTable']['uidField']] . ')"', array('db', 'dstDB', 'detectMissingParents', $GLOBALS['STAGING']['db']['rootTable']['name']));
		}
	}

	/**
	 * proceed db-updates from updateStack
	 *
	 * @return boolean
	 */
	private function processDBUpdates() {
		$error = false;
		@mysql_query('BEGIN', $this->dstDB->connId);
		foreach($this->updateStack as $table => $dataArr) {
			$destFields = array();
			$qry = 'SHOW COLUMNS FROM ' . $table;
			if(!@mysql_query($qry, $this->dstDB->connId)) {
				$error = !$this->createTable($table);
			}
			$res = @mysql_query($qry, $this->dstDB->connId);
			while ($row = @mysql_fetch_assoc($res)) {
				$destFields[] = $row['Field'];
			}
			if($missingFields = array_values(array_diff(array_keys($dataArr[0]), $destFields))) {
				for($i=0; $i<count($missingFields); $i++) {
					$error = !$this->createField($table, $missingFields[$i]);
				}
			}
			if(!$error) {
				$tablePrefs = $this->findTablePrefs($table, $GLOBALS['STAGING']['db']['rootTable']);
				if(preg_match('/\_mm$/', $table)) {
					$baseArr = array();
					for($i=0; $i<count($dataArr); $i++) {
						$baseArr[$dataArr[$i][$tablePrefs['uidField']]][] = $dataArr[$i];
					}
					foreach($baseArr as $compareBaseUid => $compareBaseArr) {
						$compareCompArr = array();
						$res = mysql_query('SELECT * FROM ' . $table . ' WHERE ' . $tablePrefs['uidField'] . ' = ' . $compareBaseUid, $this->dstDB->connId);
						while ($row = @mysql_fetch_assoc($res)) {
							foreach($row as $key => $value) {
								$row[$key] = mysql_escape_string($value);
							}
							$compareCompArr[] = $row;
						}
						for($i=0; $i<count($compareCompArr); $i++) {
							if(!in_array($compareCompArr[$i], $compareBaseArr)) {
								$deleteWhereArr = array();
								foreach($compareCompArr[$i] as $key => $value) {
									$deleteWhereArr[] = $key . ' = "' . $value . '"';
								}
								@mysql_query('DELETE FROM ' . $table . ' WHERE ' . implode(' AND ', $deleteWhereArr), $this->dstDB->connId);
							}
						}
					}
				}
				for($i=0; $i<count($dataArr); $i++) {
					$deleteQueryExt = preg_match('/\_mm$/', $table) ? ' AND ' . $tablePrefs['whereField'] . ' = ' . $dataArr[$i][$tablePrefs['whereField']] : '';
					if(@mysql_query('DELETE FROM ' . $table . ' WHERE ' . $tablePrefs['uidField'] . ' = ' . $dataArr[$i][$tablePrefs['uidField']] . $deleteQueryExt, $this->dstDB->connId)) {
						$GLOBALS['LOGGING']->setLog('notice', 'deleted data with id: "(' . $dataArr[$i][$tablePrefs['uidField']] . ')"', array('db', 'dstDB', 'processDBUpdates', $table));
					} else {
						$GLOBALS['LOGGING']->setLog('warn', 'can not delete data with id: "(' . $dataArr[$i][$tablePrefs['uidField']] . ')"', array('db', 'dstDB', 'processDBUpdates', $table));
						$error = true;
					}
					if(@mysql_query('INSERT INTO ' . $table . ' (' . implode(',', array_keys($dataArr[$i])) . ') VALUES (\'' . implode('\',\'', array_values($dataArr[$i])) . '\')', $this->dstDB->connId)) {
						$GLOBALS['LOGGING']->setLog('notice', 'inserted data: "' . $dataArr[$i][$tablePrefs['title']] . ' (' . $dataArr[$i][$tablePrefs['uidField']] . ')"', array('db', 'dstDB', 'processDBUpdates', $table));
					} else {
						$GLOBALS['LOGGING']->setLog('error', 'can not insert data: "' . $dataArr[$i][$tablePrefs['title']] . ' (' . $dataArr[$i][$tablePrefs['uidField']] . ')", this error occurred: "' . mysql_error() . '"', array('db', 'dstDB', 'processDBUpdates', $table));
						$error = true;
					}
				}
			}
		}
		if($error) {
			@mysql_query('ROLLBACK', $this->dstDB->connId);
		} else {
			@mysql_query('COMMIT', $this->dstDB->connId);
		}
		return !$error;
	}

	/**
	 * find settings for given table missing table
	 *
	 * @param string $table
	 * @return array
	 */
	private function findTablePrefs($table, $settings) {
		if($settings['name'] == $table) {
			unset($settings['extTables']);
			return $settings;
		} else {
			for($i=0; $i<count($settings['extTables']); $i++) {
				if(!$retArr) {
					$retArr = $this->findTablePrefs($table, $settings['extTables'][$i]);
					unset($retArr['extTables']);
				}
			}
			return $retArr;
		}
	}

	/**
	 * create missing table
	 *
	 * @param string $table
	 * @return boolean
	 */
	private function createTable($table) {
		$GLOBALS['LOGGING']->setLog('warn', 'found non existing table : "' . $table . '" trying to create it...', array('db', 'dstDB', 'createTable', $table));
		$res = @mysql_query('SHOW COLUMNS FROM ' . $table, $this->srcDB->connId);
		while ($row = @mysql_fetch_assoc($res)) {
			$colArr[] = '`' . $row['Field'] . '` ' . $row['Type'] . ($row['Null'] == 'NO' ? ' NOT' : '') . ' NULL' . ($row['Default'] ? ' DEFAULT "' . $row['Default'] . '"' : '') . ($row['Extra'] ? ' ' . $row['Extra'] : '');
		}
		$keyArr = array();
		$res = @mysql_query('SHOW INDEX FROM ' . $table, $this->srcDB->connId);
		while ($row = @mysql_fetch_assoc($res)) {
			$keyArr[$row['Key_name']][] = array('cname' => $row['Column_name'], 'unique' => !$row['Non_unique']);
		}
		foreach($keyArr as $key => $valueArr) {
			if($key == 'PRIMARY') {
				$colArr[] = 'PRIMARY KEY  (`' . $valueArr[0]['cname']. '`)';
			} else {
				$cnameArr = array();
				for($i=0; $i<count($valueArr); $i++) {
					$cnameArr[] = $valueArr[$i]['cname'];
				}
				$colArr[] = ($valueArr[0]['unique'] ? 'UNIQUE ' : '') . 'KEY `' . $key . '` (`' . implode('`,`', $cnameArr) . '`)';
			}
		}
		if(@mysql_query('CREATE TABLE ' . $table . ' (' . implode(',', $colArr) . ') ENGINE InnoDB', $this->dstDB->connId)) {
			$GLOBALS['LOGGING']->setLog('warn', 'successfully created table : "' . $table . '"', array('db', 'dstDB', 'createTable', $table));
			return true;
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'can not create table : "' . $table . '", this error occurred: "' . mysql_error() . '"', array('db', 'dstDB', 'createTable', $table));
		}
		return false;
	}

	/**
	 * create missing table-fields
	 *
	 * @param string $table
	 * @param string $field
	 * @return boolean
	 */
	private function createField($table, $field) {
		$GLOBALS['LOGGING']->setLog('warn', 'found non existing field : "' . $field . '" trying to create it...', array('db', 'dstDB', 'createField', $table));
		$fieldSettings = @mysql_fetch_assoc(@mysql_query('SHOW COLUMNS FROM ' . $table . ' WHERE Field = "' . $field . '"', $this->srcDB->connId));
		if(@mysql_query('ALTER TABLE ' . $table . ' ADD `' . $fieldSettings['Field'] . '` ' . $fieldSettings['Type'] . ($fieldSettings['Null'] == 'NO' ? ' NOT' : '') . ' NULL' . ($fieldSettings['Default'] ? ' DEFAULT "' . $fieldSettings['Default'] . '"' : ''), $this->dstDB->connId)) {
			$GLOBALS['LOGGING']->setLog('warn', 'successfully created field : "' . $field . '"', array('db', 'dstDB', 'createField', $table));
			return true;
		} else {
			$GLOBALS['LOGGING']->setLog('error', 'can not create field : "' . $field . '", this error occurred: "' . mysql_error() . '"', array('db', 'dstDB', 'createField', $table));
		}
		return false;
	}

	/**
	 * remove cache entrys from dstDB
	 */
	private function removeDBCache() {
		$pidArr = array();
		for($i=0; $i<count($GLOBALS['STAGING']['db']['rootTable']['extTables']); $i++) {
			if($GLOBALS['STAGING']['db']['rootTable']['extTables'][$i]['name'] == 'tt_content') {
				$irStartTable = $GLOBALS['STAGING']['db']['rootTable']['extTables'][$i];
			}
		}
		for($i=0; $i<count($this->updateStack[$GLOBALS['STAGING']['db']['rootTable']['name']]); $i++) {
			$pid = $this->updateStack[$GLOBALS['STAGING']['db']['rootTable']['name']][$i][$GLOBALS['STAGING']['db']['rootTable']['uidField']];
			$pidArr[] = $pid;
			if($irStartTable) {
				if($res = mysql_query('SELECT ' . $irStartTable['uidField'] . ', ' . $irStartTable['whereField'] . ' FROM ' . $irStartTable['name'] . ' WHERE ' . $irStartTable['whereField'] . ' = ' . $pid, $this->srcDB->connId)) {
					$subPidArr = array();
					if(mysql_num_rows($res)) {
						while($row = mysql_fetch_assoc($res)) {
							$subPidArr[] = $row[$irStartTable['whereField']];
						}
					}
					$pidArr = array_merge($pidArr, array_unique($subPidArr));
				}
			}
		}
		$pidArr = array_unique($pidArr);
		sort($pidArr);
		for($i=0; $i<count($GLOBALS['STAGING']['db']['cacheTables']); $i++) {
			for($j=0; $j<count($pidArr); $j++) {
				if(@mysql_query('DELETE FROM ' . $GLOBALS['STAGING']['db']['cacheTables'][$i]['name'] . ' WHERE ' . $GLOBALS['STAGING']['db']['cacheTables'][$i]['whereField'] . ' = ' . $pidArr[$j], $this->dstDB->connId)) {
					$GLOBALS['LOGGING']->setLog('notice', 'deleted cache-entry with id: "(' . $pidArr[$j] . ')"', array('db', 'dstDB', 'removeDBCache', $GLOBALS['STAGING']['db']['cacheTables'][$i]['name']));
				} else {
					$GLOBALS['LOGGING']->setLog('warn', 'can not delete cache-entry with id: "(' . $pidArr[$j] . ')"', array('db', 'dstDB', 'removeDBCache', $GLOBALS['STAGING']['db']['cacheTables'][$i]['name']));
				}
			}
		}
	}

	/**
	 * reset staging-flags from srcDB
	 */
	private function removeDBStagingFlags() {
		foreach($this->updateStack as $table => $dataArr) {
			if($table == $GLOBALS['STAGING']['db']['rootTable']['name']) {
				for($i=0; $i<count($dataArr); $i++) {
					if(@mysql_query('UPDATE ' . $GLOBALS['STAGING']['db']['rootTable']['name'] . ' SET ' . $GLOBALS['STAGING']['db']['rootTable']['whereField'] . ' = ' . ($dataArr[$i]['deleted'] ? 2 : 0) . ' WHERE ' . $GLOBALS['STAGING']['db']['rootTable']['uidField'] . ' = ' . $dataArr[$i]['uid'], $this->srcDB->connId)) {
						$GLOBALS['LOGGING']->setLog('notice', 'updated staging-flag for data: "' . $dataArr[$i][$GLOBALS['STAGING']['db']['rootTable']['title']] . ' (' . $dataArr[$i][$GLOBALS['STAGING']['db']['rootTable']['uidField']] . ')"', array('db', 'srcDB', 'removeStagingFlags', $table));
					} else {
						$GLOBALS['LOGGING']->setLog('error', 'can not update staging-flag for data: "' . $dataArr[$i][$GLOBALS['STAGING']['db']['rootTable']['title']] . ' (' . $dataArr[$i][$GLOBALS['STAGING']['db']['rootTable']['uidField']] . ')"', array('db', 'srcDB', 'removeStagingFlags', $table));
					}
				}
			}
		}
	}
}

?>