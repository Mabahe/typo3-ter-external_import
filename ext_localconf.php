<?php
	// Register method with generic BE ajax calls handler
	// (as from TYPO3 4.2)
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['externalimport::synchronizeExternalTable'] = 'typo3conf/ext/external_import/class.tx_externalimport_ajax.php:tx_externalimport_ajax->synchronizeExternalTable';

	// Register handler class for Gabriel
if (t3lib_extMgm::isLoaded('gabriel')) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['gabriel']['include'][$_EXTKEY] = 'autosync/class.tx_externalimport_autosync_gabriel_event.php';
}

	// Register handler calls for Scheduler
if (t3lib_extMgm::isLoaded('scheduler')) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_externalimport_autosync_scheduler_Task'] = array(
		'extension'			=> $_EXTKEY,
		'title'				=> 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.title',
		'description'		=> 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.description',
		'additionalFields'	=> 'tx_externalimport_autosync_scheduler_AdditionalFieldProvider'
	);
}
?>