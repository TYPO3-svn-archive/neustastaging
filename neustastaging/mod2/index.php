<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Susanne Moog - NEUSTA GmbH <s.moog@neusta.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($GLOBALS['BACK_PATH'].'init.php');
require_once($GLOBALS['BACK_PATH'].'template.php');

$GLOBALS['LANG']->includeLLFile('EXT:neustastaging/mod2/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');
require_once(PATH_t3lib.'class.t3lib_extfilefunc.php');
$GLOBALS['BE_USER']->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.

require_once(t3lib_extMgm::extPath('neustastaging') . 'class.tx_neustastaging_logging.php');

/**
 * Module 'File Staging' for the 'neustastaging' extension.
 *
 * @author    Susanne Moog - NEUSTA GmbH <s.moog@neusta.de>
 * @package    TYPO3
 * @subpackage    tx_neustastaging
 */
class  tx_neustastaging_module2 extends t3lib_SCbase {
	var $pageinfo;

				/**
				 * Initializes the Module
				 * @return    void
				 */
	function init()    {
		$this->staginglog = t3lib_div::makeInstance('tx_neustastaging_logging');
		parent::init();

	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return    void
	 */
	function menuConfig()    {
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return    [type]        ...
	 */
	function main()    {
		// Draw the header.
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form='<form action="" method="POST">';

		// JavaScript
		$this->doc->JScode = '
							<script language="javascript" type="text/javascript">
								script_ended = 0;
								function jumpToUrl(URL)    {
									document.location = URL;
								}
							</script>
						';
		$this->doc->postCode='
							<script language="javascript" type="text/javascript">
								script_ended = 1;
								if (top.fsMod) top.fsMod.recentIds["web"] = 0;
							</script>
						';

		$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

		$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('title'));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
		$this->content.=$this->doc->divider(5);

		// Add Sorting Combobox
		$this->addSortCombo();

		// Render content:
		$this->moduleContent();

		// ShortCut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())    {
			$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
		}


		$this->content.=$this->doc->spacer(10);
	}

	/**
	 * Adds the Sorting Combobox
	 *
	 * @return    void
	 */
	function addSortCombo() {
		$sortings = array(
			'name'=>$GLOBALS['LANG']->getLL('alphabetical'),
			'date'=>$GLOBALS['LANG']->getLL('dateOfChange')
		);
		$selectedSorting = $_POST['sorting'];

		$this->content.='	<form>' . $GLOBALS['LANG']->getLL('sorting') . ' <select name="sorting">';
		foreach($sortings as $key => $title) {
			$this->content.= '<option value="'.$key.'"';
			if($key == $selectedSorting) {
				$this->content.= ' selected';
			}
			$this->content.= '>'.$title.'</option><br>';
		}
		$this->content.='   </select>
							<input type="submit" value="' . $GLOBALS['LANG']->getLL('change') . '" />
						</form>';
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return    void
	 */
	function printContent()    {

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return    void
	 */
	function moduleContent()    {

		// initialize the fileFunctions used later to retrieve the directory listing
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$this->fileProcessor->init($FILEMOUNTS, $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->fileProcessor->init_actionPerms($GLOBALS['BE_USER']->user['fileoper_perms']);
		$this->fileProcessor->start($this->file);
		$this->fileProcessor->processData();

		// add the initialized filefunctions to the global backend array (so we don't need to change the existing functions)
		$GLOBALS['SOBE']->basicFF = $this->fileProcessor;

		if ($_POST['tx_neustastaging_mod2_submitsure']) {

			$headerText = $GLOBALS['LANG']->getLL('stepthree');

			foreach($_POST as $key => $path)

			if($key != 'tx_neustastaging_mod2_submitsure') {

				$fileArray[] = $path;

			}

			$errors = $this->writeToDatabase($fileArray);

			if (!($errors)) {

				$content .= '<strong>' . $GLOBALS['LANG']->getLL('success') . '</strong>';

			} else {

				$content .= '<strong>' . $GLOBALS['LANG']->getLL('failureheader') . '</strong>';
				$content .= $GLOBALS['LANG']->getLL('failure');
				$content .= $this->doc->spacer(10);
				$content .= $errors;

			}

		} else if ($_POST['tx_neustastaging_mod2_submit']) {

			$headerText = $GLOBALS['LANG']->getLL('steptwo');

			foreach($_POST as $key => $element) {

				if ($key != 'tx_neustastaging_mod2_submit') {
					$hiddenfields .= '<input type="hidden" name="' . $key . '" value="' . str_replace($GLOBALS['SOBE']->basicFF->webPath,'',$element) . '" />';
					$fields2update .= '- ' . str_replace($_GET['id'], '', str_replace($GLOBALS['SOBE']->basicFF->webPath,'',$element)) . $this->doc->spacer(5);
				}
			}
			$content .= '<strong>' . $GLOBALS['LANG']->getLL('areyousure') . '</strong>';
			$content .= $this->doc->spacer(5);
			$content .= $GLOBALS['LANG']->getLL('fields2update');
			$content .=$this->doc->spacer(5);
			$content .= $fields2update;
			$content .= '<form method="post">';
			$content .= $hiddenfields;
			$content .= '<a style="float:left; text-decoration:underline" href="javascript:history.back()" alt="' . $GLOBALS['LANG']->getLL('back') . '">' . $GLOBALS['LANG']->getLL('back') . '</a>';
			$content .= '<input type="submit" name="tx_neustastaging_mod2_submitsure" value="' . $GLOBALS['LANG']->getLL('submitsure') . '" style="float:right" /></form>';


		} else {
			$headerText = $GLOBALS['LANG']->getLL('stepone');
			$content .= $GLOBALS['LANG']->getLL('selectfiles');
			$content .=$this->doc->spacer(10);
			if($_GET['id']) {
				// get the files in the selected directory
				$filesToShow = $this->readDirectory($_GET['id']);

				$content .= '<form method="post">';

				$submittedFiles = $this->getSubmittedFiles($filesToShow);

				// generate the input fields
				foreach($filesToShow['files'] as $key => $value) {
					$fileText = $value['file'];
					$nameAndPath = $this->cutPath($value['path']) . $value['file'];

					// If the file has not been submitted yet or is newer than a submitted file of the same name, it is written bold
					if(!isset($submittedFiles[$nameAndPath]) || $submittedFiles[$nameAndPath] < $value['tstamp']) {
						$fileText = '<b>'.$fileText.'</b>';
					}

					$content .= '<input type="checkbox" value="' . $nameAndPath . '" name="' . $value['file'] . '" id="' . $value['file'] . '" /> <label for="' . $value['file'] . '" style="vertical-align:top;">' . $fileText . '</label>';
					$content .=$this->doc->spacer(5);

				}

				$content .= '<input type="submit" name="tx_neustastaging_mod2_submit" value="' . $GLOBALS['LANG']->getLL('submit') . '" /></form>';

			}

		}

		$this->content.=$this->doc->section($headerText,$content,0,1);
	}

	/**
	 * Retrieves the files that are already submitted from the tx_neustastaging_log
	 * table
	 *
	 * @return
	 * @param $filesToShow The files that are to be displayed in the filelist
	 */
	function getSubmittedFiles($filesToShow) {
		foreach($filesToShow['files'] as $file) {
			if(isset($whereClause)) {
				$whereClause .= ' OR ';
			}
			$whereClause .= "FILES LIKE '%".$this->cutPath($file['path']).$file['file']."'";
		}

		$dbRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'files, time',
						'tx_neustastaging_log',
			$whereClause);

		while($item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes)) {
			$dbArr[$item['files']] = $item['time'];
		}
		return $dbArr;
	}

	/**
	 * Retrieves a substring from the given path so that it starts with "/fileadmin";
	 *
	 * @return
	 * @param $path String
	 */
	function cutPath($path) {
		return substr($path, strpos($path, '/fileadmin'));
	}

	 /**
	 * Returns an array with file/dir items + an array with the sorted items
	 *
	 * @param    string        Path (absolute) to read
	 * @param    string        $extList: List of fileextensions to select. If empty, all are selected.
	 * @return    array        Array('files'=>array(), 'sorting'=>array());
	 */
	function readDirectory($path,$extList='')    {
		$items = Array('files'=>array(), 'sorting'=>array());
		$path = $GLOBALS['SOBE']->basicFF->is_directory($path);    // Cleaning name...

		if($path)    {

			$d = @dir($path);
			$tempArray=Array();
			if (is_object($d))    {
				while($entry=$d->read()) {

					if (count($tempArray)>=10000) break;
					if ($entry!='.' && $entry!='..')    {
						$wholePath = $path.'/'.$entry;        // Because of odd PHP-error where  <br />-tag is sometimes placed after a filename!!

						$tempArray[] = $wholePath;
					}
				}
				$d->close();
			}
			// Get fileinfo
			reset($tempArray);
			while (list(,$val)=each($tempArray))    {
				$temp = $GLOBALS['SOBE']->basicFF->getTotalFileInfo($val);
				$items['files'][] = $temp;


				usort($items['files'], array('tx_neustastaging_module2', 'compare'));
			}
		}

		return $items;
	}

	/**
	 * Used by the usort method to sort the listed items either by name
	 * or by creation date, depending on selected value in the "sorting"
	 * ComboBox
	 *
	 * @return
	 * @param $a Object
	 * @param $b Object
	 */
	function compare($a, $b) {
		$selectedSorting = $_POST['sorting'];

		if($selectedSorting == 'date') {
			$ret = $b['tstamp'] - $a['tstamp'];
		} else {
			$ret = strcmp($a['file'], $b['file']);
		}

		return $ret;
	}

	/**
	 * Writes data to database (if filepath is already present it just updates the timestamp)
	 * Additionally triggers logging function (logs user and changed files)
	 *
	 * @param     array         Data array with file paths to insert into database
	 * @return    boolean       success = true, failure = false!
	 */
	function writeToDatabase($data) {
		$errors = false;
		foreach ($data as $path) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, path', 'tx_neustastaging_files', 'path = "' . $path . '"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$update = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_neustastaging_files', 'uid = ' . $row['uid'],array('tstamp' => time()));
				if (!($update)) {
					$errors .= basename($path) . '<br />';
				}
			} else {
				if (is_dir($path)) {
					$path = $path;
				}
				$insert = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_neustastaging_files',array('path' => $path, 'tstamp' => time(), 'crdate' => time()));
				if (!($insert)) {
					$errors .= basename($path) . '<br />';
				}
			}
		}
		$paths = implode(',' , $data);

		// if logging problems appear: test and catch if log is true or false here
		$log = $this->staginglog->writeLog($GLOBALS['BE_USER']->user['uid'], $paths, 'files');

		return $errors;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/neustastaging/mod2/index.php'])    {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/neustastaging/mod2/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_neustastaging_module2');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)    include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>