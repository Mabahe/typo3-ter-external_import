<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
*
* $Id$
***************************************************************/

unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH . 'init.php');
require_once($BACK_PATH . 'template.php');

$LANG->includeLLFile('EXT:external_import/mod1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF, 1);	// This checks permissions and exits if the users has no permission for entry.

/**
 * Module 'External Data Import' for the 'external_import' extension.
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_externalimport
 */
class tx_externalimport_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $periods = array('minutes', 'hours', 'days', 'weeks', 'months', 'years'); // List of possible periods for auto sync

	/**
	 * Initialise the module
	 * @return	void
	 */
	function init()	{
		parent::init();
	}

	/**
	 * Add items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		$this->MOD_MENU = array(
			'function' => array(
				'sync' => $GLOBALS['LANG']->getLL('function_sync'),
				'nosync' => $GLOBALS['LANG']->getLL('function_nosync'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$BACK_PATH;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id) || ($BE_USER->user['uid'] && !$this->id))	{

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';

// Add JavaScript for AJAX call to synchronise method
// When the call returns, the code also handles the display of the response messages
// Additionnally an animated icon and a message are displayed with the sync is running to provide visual feedback

// Code for TYPO3 4.2

			if (t3lib_div::compat_version('4.2')) {
				$this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
				$this->doc->loadJavascriptLib('js/common.js');
				$this->doc->JScode .= '
					<script language="javascript" type="text/javascript">
						var syncRunningIcon = \'<img src="../res/icons/refresh_animated.gif" alt="'.$GLOBALS['LANG']->getLL('running_synchronisation').'" border="0" />\';
						var syncStoppedIcon = \'<img '.(t3lib_iconWorks::skinImg($BACK_PATH,'gfx/refresh_n.gif')).' alt="'.$GLOBALS['LANG']->getLL('synchronise').'" border="0" />\';
						function syncTable(theID, theTable, theIndex) {
							$("result" + theID).update("'.$GLOBALS['LANG']->getLL('running').'");
							$("link" + theID).update(syncRunningIcon);
							new Ajax.Request("'.$BACK_PATH.'ajax.php", {
								method: "get",
								parameters: {
									"ajaxID": "externalimport::synchronizeExternalTable",
									"table" : theTable,
									"index": theIndex
								},
								onComplete: function(xhr) {
										var response = xhr.responseText.evalJSON();
										var messages = "";
										if (response["error"]) {
											for (i = 0; i < response["error"].length; i++) {
												messages = messages + "<p style=\"padding: 4px; background-color: #f00; color: #fff;\">'.$GLOBALS['LANG']->getLL('error').': " + response["error"][i] + "</p>";
											}
										}
										if (response["warning"]) {
											for (i = 0; i < response["warning"].length; i++) {
												messages = messages + "<p style=\"padding: 4px; background-color: #f60; color: #fff;\">'.$GLOBALS['LANG']->getLL('warning').': " + response["warning"][i] + "</p>";
											}
										}
										if (response["success"]) {
											for (i = 0; i < response["success"].length; i++) {
												messages = messages + "<p style=\"padding: 4px; background-color: #0f0; color: #000;\">" + response["success"][i] + "</p>";
											}
										}
										$("result" + theID).update(messages);
										$("link" + theID).update(syncStoppedIcon);
								}.bind(this),
								onT3Error: function(xhr) {
									$("result" + theID).update("'.$GLOBALS['LANG']->getLL('failed').'");
								}.bind(this)
							});
						}
					</script>
				';
			}

// Code for TYPO3 4.1

			else {
				$ajaxURL = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . t3lib_div::getThisUrl() . '../tx_externalimport_ajaxhandler.php';
				$this->doc->JScode .= '<script type="text/javascript" src="../res/prototype.js"></script>'."\n";
				$this->doc->JScode .= '
					<script language="javascript" type="text/javascript">
						var syncRunningIcon = \'<img src="../res/icons/refresh_animated.gif" alt="'.$GLOBALS['LANG']->getLL('running_synchronisation').'" border="0" />\';
						var syncStoppedIcon = \'<img '.(t3lib_iconWorks::skinImg($BACK_PATH,'gfx/refresh_n.gif')).' alt="'.$GLOBALS['LANG']->getLL('synchronise').'" border="0" />\';
						function syncTable(theID, theTable, theIndex) {
							$("result" + theID).update("'.$GLOBALS['LANG']->getLL('running').'");
							$("link" + theID).update(syncRunningIcon);
							new Ajax.Request("'.$ajaxURL.'",
								{
									method: "get",
									parameters: {
										"function": "synchronizeExternalTable",
										"table": theTable,
										"index": theIndex
									},
									onSuccess: function(transport) {
										var response = transport.responseText.evalJSON();
										var messages = "";
										if (response["error"]) {
											for (i = 0; i < response["error"].length; i++) {
												messages = messages + "<p style=\"padding: 4px; background-color: #f00; color: #fff;\">'.$GLOBALS['LANG']->getLL('error').': " + response["error"][i] + "</p>";
											}
										}
										if (response["warning"]) {
											for (i = 0; i < response["warning"].length; i++) {
												messages = messages + "<p style=\"padding: 4px; background-color: #f60; color: #fff;\">'.$GLOBALS['LANG']->getLL('warning').': " + response["warning"][i] + "</p>";
											}
										}
										if (response["success"]) {
											for (i = 0; i < response["success"].length; i++) {
												messages = messages + "<p style=\"padding: 4px; background-color: #0f0; color: #000;\">" + response["success"][i] + "</p>";
											}
										}
										$("result" + theID).update(messages);
									},
									onFailure: function() {$("result" + theID).update("'.$GLOBALS['LANG']->getLL('failed').'");},
									onComplete: function() {$("link" + theID).update(syncStoppedIcon);}
								}
							);
						}
					</script>
				';
			}

			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= '<form name="menuForm" action="" method="POST">'.$this->doc->section('',$this->doc->funcMenu('', t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']))).'</form>';
			$this->content .= $this->doc->divider(5);


			// Render content:
			$this->moduleContent();


			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content .= $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content .= $this->doc->spacer(10);
		}
		else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{

		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generate the module's content
	 *
	 * @return	void
	 */
	function moduleContent() {
		switch((string)$this->MOD_SETTINGS['function'])	{

// List tables that receive external data from the outside
// (i.e. cannot be synchronised from the BE)

			case 'nosync':
				$this->listOtherTables();
				break;

// Default view is the list of all external tables that can be synchronised

			default:
				$this->listSynchronizedTables();
				break;
		}
	}

	/**
	 * This method lists all the tables that can be synchronised from the BE
	 * These are the tables that have a "external" part defined in the "ctrl" section of their TCA
	 * and a "connector" defined in this "external" part
	 *
	 * @return	void
	 */
	function listSynchronizedTables() {
		global $BACK_PATH;

// Get list of all synchronisable tables and extract general information about them

		$externalTables = array();
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
			foreach ($sections as $sectionKey => $sectionData) {
				if ($sectionKey == 'ctrl' && isset($sectionData['external'])) {
					foreach ($sectionData['external'] as $index => $externalConfig) {
						if (!empty($externalConfig['connector'])) {
							if (isset($externalConfig['priority'])) {
								$priority = $externalConfig['priority'];
							}
							else {
								$priority = 1000; // If priority is not defined, set to very low
	                        }
							if (isset($externalConfig['description'])) {
								$description = $GLOBALS['LANG']->sL($externalConfig['description']);
							}
							else {
								$description = '';
	                        }
							$externalTables[] = array('tablename' => $tableName, 'index' => $index, 'priority' => $priority, 'description' => $description);
						}
                    }
				}
			}
		}

// Sort tables by priority (lower number is highest priority)

		usort($externalTables, array('tx_externalimport_module1','prioritySort'));


// Prepare table to display list of external tables

		if (count($externalTables) == 0) {
			$tableList = '<p>'.$GLOBALS['LANG']->getLL('external_tables_none').'</p>';
		}
		else {

// First initialise the table layout

			$tableLayout = array (
								'table' => array ('<table border="0" cellspacing="1" cellpadding="2" style="width:auto;">', '</table>'),
								'0' => array (
									'tr' => array('<tr class="bgColor2">','</tr>'),
								),
								'defRow' => array (
									'tr' => array('<tr class="bgColor3-20" valign="top">','</tr>'),
									'defCol' => array('<td>','</td>'),
								)
							);

			$table = array();

// First row is header row

			$tr = 0;
			$table[$tr] = array();
			$table[$tr][] = '&nbsp;'; // Table icon
			$table[$tr][] = $GLOBALS['LANG']->getLL('table'); // Table name
			$table[$tr][] = $GLOBALS['LANG']->getLL('description'); // Sync description
			$table[$tr][] = $GLOBALS['LANG']->getLL('priority'); // Priority
			$table[$tr][] = '&nbsp;'; // Action icons
			$table[$tr][] = '&nbsp;'; // Action result

// Generate table row for each table

			foreach ($externalTables as $tableData) {
				$tr++;
				$tableName = $tableData['tablename'];
				$ctrlData = $GLOBALS['TCA'][$tableName]['ctrl'];
				$tableIndex = $tableData['index'];
				$table[$tr] = array();
				$tableTitle = $GLOBALS['LANG']->sL($ctrlData['title']);
				$table[$tr][] = t3lib_iconWorks::getIconImage($tableName, array(), $BACK_PATH);
				$table[$tr][] = $tableTitle.' ('.$tableName.')';
				$table[$tr][] = '['.$tableIndex.']'.((empty($tableData['description'])) ? '' : ' '.$tableData['description']);
				$table[$tr][] = $tableData['priority'];
				$table[$tr][] = '<a href="javascript:syncTable(\''.$tr.'\', \''.$tableName.'\', \''.$tableIndex.'\')" id="link'.$tr.'" title="'.$GLOBALS['LANG']->getLL('manual_sync').'"><img '.(t3lib_iconWorks::skinImg($BACK_PATH,'gfx/refresh_n.gif')).' alt="'.$GLOBALS['LANG']->getLL('synchronise').'" border="0" /></a>'; // Action icons
				$table[$tr][] = '<div id="result'.$tr.'"></div>'; // Action result
			}

// Render the table

			$tableList = $this->doc->table($table, $tableLayout);
		}

// Assemble content

		$content = '<p>'.$GLOBALS['LANG']->getLL('external_tables_intro').'</p>';
		$content .= $this->doc->spacer(10);
		$content .= $tableList;
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('external_tables'),$content,0,1);
		$this->content .= $this->doc->divider(5);

// Display form for automatic synchronisation

		$this->displayAutoSyncSection();
	}

	/**
	 * This method lists tables that have an external section, but that do not rely on a connector.
	 * In such cases data is not fetched by external_import and stored into those tables. On the contrary,
	 * data is pushed towards those tables using the external_import API
	 *
	 * @return	void
	 */
	function listOtherTables() {
		global $BACK_PATH;

// Get list of all non-synchronisable tables and extract general information about them

		$externalTables = array();
		foreach ($GLOBALS['TCA'] as $tableName => $sections) {
			foreach ($sections as $sectionKey => $sectionData) {
				if ($sectionKey == 'ctrl' && isset($sectionData['external'])) {
					foreach ($sectionData['external'] as $index => $externalConfig) {
						if (empty($externalConfig['connector'])) {

// Table's full name and index will be used as key for sorting the tables

							$tableTitle = $GLOBALS['LANG']->sL($sectionData['title']).':'.$index;
							if (isset($externalConfig['description'])) {
								$description = $GLOBALS['LANG']->sL($externalConfig['description']);
							}
							else {
								$description = '';
	                        }
							$externalTables[$tableTitle] = array('tablename' => $tableName, 'description' => $description);
						}
                    }
				}
			}
		}
		ksort($externalTables);

// Prepare the list of tables

		if (count($externalTables) == 0) {
			$tableList = '<p>'.$GLOBALS['LANG']->getLL('nosync_tables_none').'</p>';
		}
		else {

// Initialise the table layout

			$tableLayout = array (
								'table' => array ('<table border="0" cellspacing="1" cellpadding="2" style="width:auto;">', '</table>'),
								'0' => array (
									'tr' => array('<tr class="bgColor2">','</tr>'),
								),
								'defRow' => array (
									'tr' => array('<tr class="bgColor-20">','</tr>'),
									'defCol' => array('<td>','</td>'),
								)
							);

			$table = array();

// First row is header row

			$tr = 0;
			$table[$tr] = array();
			$table[$tr][] = '&nbsp;'; // Table icon
			$table[$tr][] = $GLOBALS['LANG']->getLL('table'); // Table name
			$table[$tr][] = $GLOBALS['LANG']->getLL('description'); // Sync description

// Generate table row for each table

			foreach ($externalTables as $key => $tableData) {
				$tr++;
				list($tableTitle, $tableIndex) = t3lib_div::trimExplode(':', $key, 1);
				$tableName = $tableData['tablename'];
				$ctrlData = $GLOBALS['TCA'][$tableName]['ctrl'];
				$table[$tr] = array();
				$table[$tr][] = t3lib_iconWorks::getIconImage($tableName, array(), $BACK_PATH);
				$table[$tr][] = $tableTitle.' ('.$tableName.')';
				$table[$tr][] = '['.$tableIndex.']'.((empty($tableData['description'])) ? '' : ' '.$tableData['description']);
			}

// Render the table

			$tableList = $this->doc->table($table, $tableLayout);
		}

// Assemble content

		$content = '<p>'.$GLOBALS['LANG']->getLL('nosync_tables_intro').'</p>';
		$content .= $this->doc->spacer(10);
		$content .= $tableList;
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('nosync_tables'),$content,0,1);
		$this->content .= $this->doc->divider(5);
	}

	/**
	 * This method displays information and input form for setting a schedule for automatic synchronisation
	 *
	 * @return	void
	 */
	function displayAutoSyncSection() {
		$content = '';
		if (t3lib_extMgm::isLoaded('gabriel', false) || t3lib_extMgm::isLoaded('scheduler', false)) {

// Instantiate a Gabriel or Scheduler object

			if (t3lib_extMgm::isLoaded('gabriel', false)) {
				$scheduler = t3lib_div::getUserObj('EXT:gabriel/class.tx_gabriel.php:&tx_gabriel');
				$autoSyncClass = 'tx_externalimport_autosync_gabriel';
			}
			else {
				$scheduler = t3lib_div::makeInstance('tx_scheduler');
				$autoSyncClass = 'tx_externalimport_autosync_scheduler';
			}

// If there was an input, set gabriel event

			$syncInput = t3lib_div::_GP('sync');
			if (isset($syncInput) && is_array($syncInput)) {
				$hasError = false;

// Check validity of input

				$period = intval($syncInput['period_value']);
				$startdate = (empty($syncInput['start'])) ? time() : strtotime($syncInput['start']);
				if ($startdate === false || $startdate === -1) {
					$errorMessage = $GLOBALS['LANG']->getLL('error_invalid_start_date');
					$hasError = true;
				}
				elseif ($period < 1) {
					$errorMessage = $GLOBALS['LANG']->getLL('error_value_below_0');
					$hasError = true;
				}

// If input was invalid, issue error and do nothing more

				if ($hasError) {
					$content .= '<p style="padding: 4px; background-color: #f00; color: #fff;">'.$errorMessage.'</p>';
					$content .= $this->doc->spacer(10);
				}
				else { // Input is valid

// Get interval and assemble as crontab frequency syntax

					$minutes = '*';
					$hours = '*';
					$day = '*';
					$month = '*';
					switch ($syncInput['period_type']) {
						case 'minutes':
							$minutes = '*/'.$period;
							$interval = 60 * $period;
							break;
						case 'hours':
							$hours = '*/'.$period;
							$interval = 3600 * $period;
							break;
						case 'days':
							$day = '*/'.$period;
							$interval = 24 * 3600 * $period;
							break;
						case 'weeks':
							$day = '*/'.(7 * $period);
							$interval = 7 * 24 * 3600 * $period;
							break;
						case 'months':
							$month = '*/'.$period;
							$interval = 30 * 7 * 24 * 3600 * $period;
							break;
						case 'years':
							$day = '*/'.(12 * $period);
							$interval = 12 * 30 * 7 * 24 * 3600 * $period;
							break;
					}
					$crontab = "$minutes $hours $day $month *";

// If uid is given, get the corresponding event
// The point is to ensure that there's ever only a single tx_externalimport:all event registered
// (unless someone has manually introduced such an event in the Gabriel BE module)

					$event = null;
					if (!empty($syncInput['uid'])) {
						$event = $scheduler->fetchEvent($syncInput['uid']);
					}

// If there's an event, update it

					if (is_object($event)) {
						$event->stop(); // Stop any existing execution(s)
						$event->registerRecurringExecution($startdate, $interval, 0);
						$scheduler->saveEvent($event);
						$successMessage = $GLOBALS['LANG']->getLL('autosync_updated');
					}

// If there was no event, create a new one

					else {
						if (t3lib_extMgm::isLoaded('gabriel', false)) {
							$event = t3lib_div::getUserObj('EXT:external_import/class.tx_externalimport_autosync_gabriel.php:' . $autoSyncClass);
						} else {
							$event = t3lib_div::makeInstance($autoSyncClass);
						}
						$event->registerRecurringExecution($startdate, $interval, 0);
						$scheduler->addEvent($event, $autoSyncClass . '::sync=all');
						$successMessage = $GLOBALS['LANG']->getLL('autosync_activated');
					}
					$content .= '<p style="padding: 4px; background-color: #0f0;">'.$successMessage.'</p>';
					$content .= $this->doc->spacer(10);
				}
			}

// Check for existing event

			$existingEvents = $scheduler->fetchEventsByCRID($autoSyncClass . '::sync=all');
			if (count($existingEvents) == 0) { // No existing event, display a message to that effect
				$content .= '<p><strong>'.$GLOBALS['LANG']->getLL('no_autosync').'</strong></p>';
			}
			else { // An event exists (and there should be only one), display next execution time
				$content .= '<p><strong>'.sprintf($GLOBALS['LANG']->getLL('next_autosync'), date('d.m.Y H:i:s', $existingEvents[0]->executionTime)).'</strong></p>';
			}
			$content .= $this->doc->spacer(10);

// Display auto sync set up form

			if (count($existingEvents) == 0) {
				$content .= '<p>'.$GLOBALS['LANG']->getLL('autosync_nosync_intro').'</p>';
			}
			else {
				$content .= '<p>'.sprintf($GLOBALS['LANG']->getLL('autosync_update_intro'), $existingEvents[0]->executionPool[0]->interval).'</p>';
			}
			$content .= $this->doc->spacer(5);
			$content .= '</form><form name="syncForm" method="POST" action="">';
			if (count($existingEvents) > 0) $content .= '<input type="hidden" name="sync[uid]" value="'.$existingEvents[0]->eventUid.'" />';
			$content .= '<p>'.$GLOBALS['LANG']->getLL('start_date').'&nbsp;<input type="text" name="sync[start]" size="20" value="" />&nbsp;'.$GLOBALS['LANG']->getLL('start_date_help').'</p>';
			$content .= '<p>'.$GLOBALS['LANG']->getLL('period').'&nbsp;<input type="text" name="sync[period_value]" size="4" value="" />&nbsp;';
			$content .= '<select name="sync[period_type]">';
			foreach ($this->periods as $aPeriod) {
				$content .= '<option value="'.$aPeriod.'">'.$GLOBALS['LANG']->getLL($aPeriod).'</option>';
			}
			$content .= '</select></p>';
			$content .= '<p><input type="submit" name="sync[submit]" value="'.$GLOBALS['LANG']->getLL('set_sync').'" /></p>';
			$content .= '</form>';
		}

// Gabriel was not installed, issue error

		else {
			$content .= '<p style="padding: 4px; background-color: #f00; color: #fff;">'.$GLOBALS['LANG']->getLL('autosync_error').'</p>';
		}
		$content .= $this->doc->spacer(10);

// Add to module's output

		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('autosync'),$content,0,1);
	}

	/**
	 * Utility method used to sort ctrl sections according to the priority value in the external information block
	 *
	 * @param	array	$a: first ctrl section to compare
	 * @param	array	$b: second ctrl section to compare
	 *
	 * @return	int		1, 0 or -1 if a is smaller, equal or greater than b, respectively
	 */
	function prioritySort($a, $b) {
		if ($a['priority'] == $b['priority']) {
			return 0;
		}
		else {
			return ($a['priority'] < $b['priority']) ? -1 : 1;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/external_import/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/external_import/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_externalimport_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>