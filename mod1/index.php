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
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$GLOBALS['LANG']->includeLLFile('EXT:neustastaging/mod1/locallang.xml');
$GLOBALS['LANG']->includeLLFile('EXT:neustastaging/locallang_db.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);  // This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

require_once(t3lib_extMgm::extPath('neustastaging') . 'class.tx_neustastaging_logging.php');

/**
 * Module 'Page Staging' for the 'neustastaging' extension.
 *
 * @author  Susanne Moog - NEUSTA GmbH <s.moog@neusta.de>
 * @package TYPO3
 * @subpackage  tx_neustastaging
 */
class  tx_neustastaging_module1 extends t3lib_SCbase {
				var $pageinfo;

				/**
				 * Initializes the Module
				 * @return  void
				 */
				function init() {
					parent::init();
                    $this->staginglog = t3lib_div::makeInstance('tx_neustastaging_logging');
				}

				/**
				 * Main function of the module.
				 *
				 *
				 * @return  [type]    ...
				 */
				function main() {
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
					$access = is_array($this->pageinfo) ? 1 : 0;

					if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id))  {
						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $GLOBALS['BACK_PATH'];
						$this->doc->form='<form action="" method="POST">';
						$this->doc->styleSheetFile2 = '../typo3conf/ext/neustastaging/res/staging.css';

							// JavaScript
						$this->doc->JScode = '
							<script language="javascript" type="text/javascript">
								script_ended = 0;
								function jumpToUrl(URL) {
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


						// Render content:
						$this->moduleContent();


						// ShortCut
						if ($GLOBALS['BE_USER']->mayMakeShortcut())  {
							$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
						}

						$this->content.=$this->doc->spacer(10);
					} else {
							// If no access or if ID == zero

						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $BACK_PATH;

						$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL('title'));
						$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('title'));
						$this->content.=$this->doc->spacer(5);
						$this->content.=$this->doc->spacer(10);
					}
				}

				/**
				 * Prints out the module HTML
				 *
				 * @return  void
				 */
				function printContent() {

					$this->content.=$this->doc->endPage();
					echo $this->content;
				}

				/**
				 * Generates the module content
				 *
				 * @return  void
				 */
				function moduleContent()  {
				    global $BE_USER;
                    
					if(!$this->id) {
						$section = $GLOBALS['LANG']->getLL('stepone');
						$content = $GLOBALS['LANG']->getLL('selectpages');
					} else {
						if($_POST) {
							if(count($_POST['stagingpages'])) {
                            
								$section = $GLOBALS['LANG']->getLL('stepthree');
                                
								if($GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid IN(' . implode(',', $_POST['stagingpages']) . ')', array('tx_neustastaging_staging' => 1))) {
									
                                    
                                    // if logging problems appear: test and catch if log is true or false here
                                    $log = $this->staginglog->writeLog($BE_USER->user['uid'], implode(',', $_POST['stagingpages']), 'pages');    
                                    
                                    $content = '<p><strong>' . $GLOBALS['LANG']->getLL('success') . '</strong></p>';
                                    
								} else {
                                
									$content = '<p><strong>' . $GLOBALS['LANG']->getLL('failure') . '</strong></p>';
                                    
								}
                                
							} else {
								$section  = $GLOBALS['LANG']->getLL('steptwo');
								$pages    = isset($_POST['subpages']) ? $this->loadSubpages($this->id)  : array('_' . $this->id => $this->pageinfo['title']);

								$content  = '<p><strong>' . $GLOBALS['LANG']->getLL('currentselection') . '</strong></p>';
								$content .= '<p>' . $GLOBALS['LANG']->getLL('selectedpages') . '</p>';
								$content .= '<form method="post">';
                                
								foreach($pages as $key => $value) {
                                
									$content .= $value . '<br />';
									$content .= '<input type="hidden" name="stagingpages[]" value="' . str_replace('_', '', $key) . '" />';
								}
                                    
								$content .= $this->doc->spacer(20);
								$content .= '<strong>' . $GLOBALS['LANG']->getLL('areyousure') . '</strong>';
								$content .= $this->doc->spacer(10);
								$content .= '<a style="float:left; text-decoration:underline" href="javascript:history.back()" alt="' . $GLOBALS['LANG']->getLL('back') . '">' . $GLOBALS['LANG']->getLL('back') . '</a>';
								$content .= '<input style="float:right;" type="submit" value="'. $GLOBALS['LANG']->getLL('submit') . '" />';
								$content .= '</form>';
							}
						} else {
							$section  = $GLOBALS['LANG']->getLL('stepone');
							$content .= $GLOBALS['LANG']->getLL('selectpages');
							$content .= $this->doc->spacer(20);
							$content .= '<p><strong>' . $GLOBALS['LANG']->getLL('currentselection') . '</strong></p>';
							$content .= $this->pageinfo['title'];
							$content .= $this->doc->spacer(10);
							$content .= '<h3>' . $GLOBALS['LANG']->getLL('options') . '</h3>';
							$content .= '<form method="post">';
							$content .= '<input type="checkbox" value="1" name="subpages" id="subpages" /><label for="subpages">' . $GLOBALS['LANG']->getLL('subpages') . '</label>';
							$content .= $this->doc->spacer(10);
							$content .= '<input type="submit" value="submit" name="submit" /></form>';
						}
					}
					$this->content.=$this->doc->section($section,$content,0,1);
				}
				
				/*
				 * Selects subpages to given pid
				 *
				 * @param int	Parent ID of the page to fetch subpages from
				 * @param array	Array of subpages (needed to recursively select subpages)
				 * @param int	Recursion level (incremented on recursion)
				 * @return array	Array of (recursive) subpages to the page "$id"
				 */
				
				function loadSubpages($id, $pagesArr=array(), $level=0) {
                    $where = 'uid = ' . $id . ' AND NOT tx_neustastaging_staging = 2';
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title', 'pages', $where);
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					
					$pagesArr['_' . $row['uid']] = '<span class="level level' . $level . '"><img src="../res/arrow' . $level . '.gif" alt="arrow" />' . $row['title'] . '</span>';
											
					if($row['uid']) {
						$level++;
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid = ' . $row['uid'] . ' AND NOT tx_neustastaging_staging = 2');
						while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$pagesArr = array_merge($pagesArr, $this->loadSubpages($row['uid'], $pagesArr['ids'], $level));
						}
					} 
					return $pagesArr;
				}
				
		}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/neustastaging/mod1/index.php'])  {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/neustastaging/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_neustastaging_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>