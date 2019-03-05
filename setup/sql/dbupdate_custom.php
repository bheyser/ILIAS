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
<#2>
<?php
	include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
	ilDBUpdateNewObjectType::addAdminNode('pdfg', 'PDFGeneration');
?>
<#3>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#4>
<?php
if( !$ilDB->tableExists('qpl_mc_opt_excluded') )
{
	$ilDB->createTable('qpl_mc_opt_excluded', array(
		'active_fi' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'pass_index' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'question_fi' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'option_index' => array('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0)
	));
	
	$ilDB->addPrimaryKey('qpl_mc_opt_excluded', array(
		'active_fi', 'pass_index', 'question_fi', 'option_index'
	));
}
?>