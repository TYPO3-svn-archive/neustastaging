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
 * Logging: Loglevel Labels: notice, warning, error
 *
 * @author Nils Seinschedt <n.seinschedt@neusta.de>, 10/2008
 */

class Logging {

	/**
	 * constructor startLogging
	 */
	public function __construct() {
		$this->logConf = @array_merge($GLOBALS['LOG']['default'], is_array($GLOBALS['LOG'][$GLOBALS['TASK']]) ? $GLOBALS['LOG'][$GLOBALS['TASK']] : array());
		$this->logConf['path'] = $this->logConf['path'] ? $this->logConf['path'] : dirname($_SERVER['PHP_SELF']) . '/logs';
		$this->logConf['fileName'] .= $GLOBALS['THREAD'];
	}

	/**
	 * build a log-row from given string-parameters
	 *
	 * @param string $levelString
	 * @param string $msg
	 * @param array $prefixArr
	 */
	public function setLog($levelString, $msg='', $prefixArr=array()) {
		$this->log[] = array($levelString => '[' . date('y.m.d-H:i:s') . '][' . $GLOBALS['TASK'] . ']' . (count($prefixArr) ? '[' . implode('][', $prefixArr) . ']' : '') . ($msg ? ' : ' . $msg : ''));
	}

	/**
	 * returns all log entries filtered by given logLevel
	 *
	 * @param int $logLevel
	 */
	public function getLog($logLevel='') {
		if($logLevel === '') {
			$logLevel = $this->logConf['level'];
		}
		$logLevelArr = array(
			'notice' => array('spacer' => 0, 'bin' => 100),
			'warn' => 	array('spacer' => 2, 'bin' => 10),
			'error' => 	array('spacer' => 1, 'bin' => 1)
		);
		for($i=0; $i<count($this->log); $i++) {
			foreach($this->log[$i] as $key => $msg) {
				if(decbin($logLevel) & $logLevelArr[$key]['bin']) {
					$ret .= "[" . $key . "]";
					for($j=0; $j<$logLevelArr[$key]['spacer']; $j++) {
						$ret .= " ";
					}
					$ret .= ": " . $msg . "\n";
				}
			}
		}
		return $ret;
	}

	/**
	 * Finishes logging, i.e. writes log messages to file
	 *
	 * @return boolean
	 */
	public function writeLog() {
		if($this->getLog()) {
			if($fp = fopen($this->logConf['path'] . '/' .  $this->logConf['fileName'] . '_' . date('y_m_d-H_i_s')  . '.log', 'w')) {
				if(fwrite($fp, $this->getLog()) !== FALSE) {
					return true;
				}
				fclose($fp);
			}
		}
		return false;
	}

	/**
	 * sends mail with given msg-text
	 *
	 * @return boolean
	 */
	function mailLog() {
		if($this->getLog($this->logConf['mail']['level'])) {
			$mailHeaders = "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
			$mailHeaders .= "Content-Transfer-Encoding:8bit\r\n";
			$mailHeaders .= "X-Mailer: PHP\r\n";
			$mailHeaders .= "From: " . mb_encode_mimeheader($this->logConf['mail']['sndName'],'ISO-8859-1') . " <" . $this->logConf['mail']['sndMail'] . ">\r\n";
			$mailHeaders .= "Reply-To: " . mb_encode_mimeheader($this->logConf['mail']['sndName'],'ISO-8859-1') . " <" . $this->logConf['mail']['sndMail'] . ">\r\n";
			$mailHeaders .= "Sender: " . mb_encode_mimeheader($this->logConf['mail']['sndName'],'ISO-8859-1') . " <" . $this->logConf['mail']['sndMail'] . ">\r\n";
			return mail(mb_encode_mimeheader($this->logConf['mail']['rcvName'],'ISO-8859-1') . " <" . $this->logConf['mail']['rcvMail'] . ">", $this->logConf['mail']['subject'], $this->getLog($this->logConf['mail']['level']), $mailHeaders);
		}
	}
}

?>