<?php

########################################################################
# Extension Manager/Repository config file for ext: "external_import"
#
# Auto generated 24-03-2009 09:25
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'External Data Import',
	'description' => 'This backend module can be used to import data from external sources into TYPO3 tables extended with special definitions in the TCA and connector services.',
	'category' => 'module',
	'author' => 'Francois Suter (Cobweb)',
	'author_email' => 'typo3@cobweb.ch',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.9.0',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:21:{s:9:"ChangeLog";s:4:"ae87";s:10:"README.txt";s:4:"7e81";s:32:"class.tx_externalimport_ajax.php";s:4:"9bde";s:36:"class.tx_externalimport_autosync.php";s:4:"12a0";s:36:"class.tx_externalimport_importer.php";s:4:"9ba2";s:21:"ext_conf_template.txt";s:4:"916e";s:12:"ext_icon.gif";s:4:"d913";s:17:"ext_localconf.php";s:4:"ab11";s:14:"ext_tables.php";s:4:"0fb5";s:13:"locallang.xml";s:4:"de2b";s:33:"tx_externalimport_ajaxhandler.php";s:4:"8a32";s:14:"doc/manual.sxw";s:4:"03fc";s:13:"mod1/conf.php";s:4:"f5bd";s:14:"mod1/index.php";s:4:"3426";s:18:"mod1/locallang.xml";s:4:"e06a";s:22:"mod1/locallang_mod.xml";s:4:"7a33";s:19:"mod1/moduleicon.gif";s:4:"b786";s:16:"res/prototype.js";s:4:"d3a5";s:26:"res/icons/preview_data.gif";s:4:"526a";s:30:"res/icons/refresh_animated.gif";s:4:"e1d4";s:51:"samples/class.tx_externalimport_transformations.php";s:4:"bbc9";}',
	'suggests' => array(
	),
);

?>