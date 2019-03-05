<?php /* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'include/inc.header.php';

/* @var ilAccess $ilAccess */
if( !$ilAccess->checkAccess('read', '', SYSTEM_FOLDER_ID) )
{
	die('administrative privileges only!');
}

/* @var \ILIAS\DI\Container $DIC */
try
{
	// use $DIC->database() for doing updates
	
	if( !$DIC->database()->tableExists('qpl_mc_opt_excluded') )
	{
		$DIC->database()->createTable('qpl_mc_opt_excluded', array(
			'active_fi' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
			'pass_index' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
			'question_fi' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
			'option_index' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0)
		));
		
		$DIC->database()->addPrimaryKey('qpl_mc_opt_excluded', array(
			'active_fi', 'pass_index', 'question_fi', 'option_index'
		));
	}
	
	echo "[ OK ]";
}
catch(ilException $e)
{
	echo "<pre>{$e}</pre>";
}
