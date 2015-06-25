<#1>
<?php
if(!$ilDB->tableExists('hist_answer_progress'))
{
	$fields = array(
		'row_id'          => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true
		),
		'hist_version'    => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 1
		),
		'hist_historic'   => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0
		),
		'creator_user_id' => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true
		),
		'created_ts'      => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0
		),
		'solution_id'     => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true
		),
		'active_fi'       => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true
		),
		'question_fi'     => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true
		),
		'points'          => array(
			'type' => 'float'
		),
		'pass'            => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true
		),
		'tstamp'          => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true
		),
		'value1'          => array(
			'type' => 'clob'
		),
		'value2'          => array(
			'type' => 'clob'
		)
	);
	$ilDB->createTable('hist_answer_progress', $fields);
	$ilDB->addPrimaryKey('hist_answer_progress', array('row_id'));
	$ilDB->createSequence('hist_answer_progress');
}
?>