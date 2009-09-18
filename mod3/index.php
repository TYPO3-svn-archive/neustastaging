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

$GLOBALS['LANG']->includeLLFile('EXT:neustastaging/mod3/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$GLOBALS['BE_USER']->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.

require_once(t3lib_extMgm::extPath('neustastaging') . 'class.tx_neustastaging_logging.php');



/**
 * Module 'Staging Log' for the 'neustastaging' extension.
 *
 * @author	Susanne Moog - NEUSTA GmbH <s.moog@neusta.de>
 * @package	TYPO3
 * @subpackage	tx_neustastaging
 */
class  tx_neustastaging_module3 extends t3lib_SCbase {
				var $pageinfo;

				/**
				 * Initializes the Module
				 * @return	void
				 */
				function init()	{
					parent::init();
                    $this->staginglog = t3lib_div::makeInstance('tx_neustastaging_logging');
				}


				/**
				 * Main function of the module. Write the content to $this->content
				 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
				 *
				 * @return	[type]		...
				 */
				function main()	{
							// Draw the header.
						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $GLOBALS['BACK_PATH'];
						$this->doc->form='<form action="" method="POST">';

							// JavaScript
						$this->doc->JScode = '
							<script language="javascript" type="text/javascript">
								script_ended = 0;
								function jumpToUrl(URL)	{
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
						if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
							$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
						

						$this->content.=$this->doc->spacer(10);

				        }

                }
                
				/**
				 * Prints out the module HTML
				 *
				 * @return	void
				 */
				function printContent()	{

					$this->content.=$this->doc->endPage();
					echo $this->content;
				}

				/**
				 * Generates the module content
				 *
				 * @return	void
				 */
				function moduleContent()	{                    
                    $content .= $this->doc->header($GLOBALS['LANG']->getLL('notstagedyetfiles')); 
                    $content .= $this->staginglog->getNotYetStagedFiles();                   
                     
                    $content .= $this->doc->header($GLOBALS['LANG']->getLL('notstagedyetpages')); 
                    $content .= $this->staginglog->getNotYetStagedPages();   

                    $content .= $this->doc->header($GLOBALS['LANG']->getLL('log'));
                    $timeframe = 7*24*60*60;
                    $content .= $this->staginglog->getLoggedData($GLOBALS['BE_USER']->user['uid'], $timeframe);
                        
                    $this->content.=$content;
                }
    }
        



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/neustastaging/mod3/index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/neustastaging/mod3/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_neustastaging_module3');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>