<?php

while( !file_exists(getcwd().'/ilias.php') )
{
	chdir('../');
}

require_once './include/inc.header.php';

$rs = $GLOBALS['DIC'] ? $GLOBALS['DIC']['rbacsystem'] : $GLOBALS['rbacsystem'];
if(!$rs->checkAccess('visible,read', SYSTEM_FOLDER_ID))
{
	die('Sorry, this script requires administrative privileges!');
}

/* @var ilDB $db */
$db = $GLOBALS['DIC'] ? $GLOBALS['DIC']['ilDB'] : $GLOBALS['ilDB'];

// -----------------------------------------------------------------------------

if( !$db->tableColumnExists('qpl_questions', 'working_time_usage') )
{
	$db->addTableColumn('qpl_questions', 'working_time_usage', array(
		'type' => 'text', 'length' => 16, 'notnull' => false, 'default' => null
	));
	
	$db->manipulateF(
		"UPDATE qpl_questions SET working_time_usage = %s", array('text'), array('meta')
	);
}

// -----------------------------------------------------------------------------

if( !$db->tableColumnExists('tst_tests', 'use_qst_work_times') )
{
	$db->addTableColumn('tst_tests', 'use_qst_work_times', array(
		'type' => 'integer', 'length' => 1, 'notnull' => false, 'default' => 0
	));
	
	$db->manipulateF(
		"UPDATE tst_tests SET use_qst_work_times = %s", array('integer'), array(0)
	);
}

// -----------------------------------------------------------------------------

if( !$db->tableExists('tst_times_qst') )
{
	$db->createTable('tst_times_qst', array(
		'record_id' => array(
			'type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null
		),
		'active_fi' => array(
			'type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null
		),
		'pass_index' => array(
			'type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null
		),
		'question_fi' => array(
			'type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null
		),
		'starting_time' => array(
			'type' => 'timestamp', 'notnull' => false, 'default' => null
		),
		'ending_time' => array(
			'type' => 'timestamp', 'notnull' => false, 'default' => null
		)
	));
	
	$db->addPrimaryKey('tst_times_qst', array('record_id'));
	$db->createSequence('tst_times_qst');
}
else
{
	if(!$db->tableColumnExists('tst_times_qst', 'question_fi')) {
		$db->addTableColumn('tst_times_qst', 'question_fi', array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => null
		));
	}
}

// -----------------------------------------------------------------------------

echo '[ finished script ]';
exit;