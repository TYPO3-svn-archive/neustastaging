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
$GLOBALS['LANG']->includeLLFile('EXT:neustastaging/locallang.xml');

/**
 * Logging class for the 'neustastaging' extension.
 *
 * @author  Susanne Moog - NEUSTA GmbH <s.moog@neusta.de>
 * @package TYPO3
 * @subpackage  tx_neustastaging
 */
class  tx_neustastaging_logging {
    
    /**
     * Writes the log with given data
     *
     * @param     int         User ID
     * @param     string      comma-separated values to put in the database
     * @param     string      type of data, possible (known) values are 'pages' and 'files'
     * @return    boolean     success = true, failure = false!
     */
    function writeLog($user, $data, $type) {

            if ($type == 'pages') {

                $insert = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_neustastaging_log',array('user' => $user, 'time' => time(), 'crdate' => time(), 'tstamp' => time(), 'pages' => $data));
            
            
            } else if ($type == 'files') {
            
                $insert = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_neustastaging_log',array('user' => $user, 'time' => time(), 'crdate' => time(), 'tstamp' => time(), 'files' => $data));
                
            }
        
            return (boolean)$insert;
    }

    /**
     * Fetches the log of the corresponding user and given timeframe
     * Calls in a second step displayLog() to display the fetched data
     *
     * @param     int         User ID
     * @param     int         timeframe in seconds
     * @return    string      html with a table presentation of the data
     */
    function getLoggedData($user, $timeframe) {
    
        global $BE_USER;
            
        $time = time() - $timeframe;
 
        if ($BE_USER->isAdmin()) {
        
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('user, time, pages, files', 'tx_neustastaging_log', 'time >' . $time , 'time DESC');
        
        } else {
        
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('user, time, pages, files', 'tx_neustastaging_log', 'time >' . $time . ' AND user = ' . $user);
        
        }
        
        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $changes[] = $row;
        } 
     
        return $this->displayLog($changes);        
    }
    
    /**
     * Generates the HTML to display the log data (after some pre-processing like converting timestamps and fetching page names)
     *
     * @param     array       the log data to represent
     * @return    string      html with a table presentation of the data
     */
    function displayLog($changes) {
        $tableheader = '<table width=1000px;><tr><th style="font-size:10px;">%1s</th><th style="font-size:10px;">%2s</th><th style="font-size:10px;">%3s</th><th style="font-size:10px;">%4s</th>';
        
        $tableheader = sprintf($tableheader, $GLOBALS['LANG']->getLL('user'), $GLOBALS['LANG']->getLL('time'), $GLOBALS['LANG']->getLL('pages'), $GLOBALS['LANG']->getLL('files'));
        
        if($changes) {
            foreach($changes as $dataarrays) {
            
                $tablerows .= '<tr style="background-color:#fff;">';
            
                foreach($dataarrays as $key => $element) {
                
                    switch ($key) {
                        case 'files':
                            $files = explode(',', $element);
                            
                            $tablerows .= '<td>';
                            
                            foreach($files as $value) {
                            
                                if(is_dir($value)) {
                                
                                    $tablerows .=  'd: ' . $value  . '<br />';
                                    
                                } else if ($value) {
                                
                                    $tablerows .=  'f: ' . $value . '<br />';
                                
                                } else {
                                
                                    $tablerows .= ''; 
                                
                                }
                                
                            
                            }
                            
                            $tablerows .= '</td>';
                            break;
                            
                        case 'user':
                                
                                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'be_users', 'uid = ' . $element);
                                $row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
                                $tablerows .= '<td>' . $row[0] . '</td>';
                                
                            break;
                            
                        case 'time':
                            
                                $tablerows .= '<td>' . strftime('%d.%m.%Y %H:%M',$element) . '</td>';
                            
                            break;
                            
                        case 'pages':
                        
                            if($element) {
                                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title', 'pages', 'uid IN ( ' . $element . ')');
                                
                                $tablerows .= '<td>';
                                
                                while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){ 

                                   $tablerows .= '[' . $row['uid'] . '] ' . $row['title'] . '<br />';
                                }
                                
                                $tablerows .= '</td>';
                                

                            } else {
                            
                                $tablerows .= '<td></td>';
                                
                            }
                            
                            break;
                    } 
                    
                }

            $tablerows .= '</tr>';


            }
        }
        
        $tableend = '</table>';
        
        $table = $tableheader . $tablerows . $tableend;
     
        return $table;

    }
 
    /**
     * Gets all pages with the staging flag set
     *
     * 
     * @return    string      html with a table presentation of the data
     */ 
    function getNotYetStagedPages() {
    
        $table .= '<table width="600px;">'; 

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, tstamp, title', 'pages', 'tx_neustastaging_staging = 1');
            
        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){ 

           $table .= '<tr style="background-color:#fff;"><td>[' . $row['uid'] . '] ' . $row['title'] . '</td></tr>';
        }
        
        $table .= '</table>';
    
        return $table;

    }

    /**
     * Gets all files in the staging_files table
     *
     * 
     * @return    string      html with a table presentation of the data
     */ 
    function getNotYetStagedFiles() {
    
        $table .= '<table width="600px";>'; 

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp, path', 'tx_neustastaging_files', '1 = 1');
            
        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){ 

           $table .= '<tr style="background-color:#fff;"><td>' . $row['path'] . '</td></tr>';
        }
        
        $table .= '</table>';
    
        return $table;

    }
    
}
?>