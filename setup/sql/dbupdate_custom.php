<#1>
<?php
if (!$ilDB->tableColumnExists("qpl_questions", "auding_nr_of_sends")) {
	$atts = array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	);
	$ilDB->addTableColumn("qpl_questions", "auding_nr_of_sends", $atts);
}
?>
<#2>
<?php
if (!$ilDB->tableColumnExists("qpl_questions", "auding_file")) {
	$atts = array(
		'type' => 'text',
		'length' => 4000,
		'notnull' => false,
		'default' => null
	);
	$ilDB->addTableColumn("qpl_questions", "auding_file", $atts);
}
?>
<#3>
<?php
if (!$ilDB->tableColumnExists("qpl_questions", "auding_activate")) {
	$atts = array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	);
	$ilDB->addTableColumn("qpl_questions", "auding_activate", $atts);
}
?>
<#4>
<?php
if (!$ilDB->tableColumnExists("qpl_questions", "auding_mode")) {
	$atts = array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
	);
	$ilDB->addTableColumn("qpl_questions", "auding_mode", $atts);
}
?>
<#5>
<?php
if (!$ilDB->tableExists("qpl_auding_log")) {
	$ilDB->createTable("qpl_auding_log", array(
			"id" => array(
				"type" => "integer", "length" => 4, "notnull" => true
			),
			"q_id" => array(
				"type" => "integer", "length" => 4, "notnull" => true
			),
			"pass" => array(
				"type" => "integer", "length" => 4, "notnull" => true
			),
			"active_fi" => array(
				"type" => "integer", "length" => 4, "notnull" => true
			),
			"timestamp" => array(
				"type" => "integer", "length" => 4, "notnull" => true
			)
		)
	);

	$ilDB->addPrimaryKey("qpl_auding_log", array("id"));

	$ilDB->createSequence("qpl_auding_log");
	$ilDB->addIndex("qpl_auding_log", array("q_id", "pass", "active_fi"), "i1");
}
?>
<#6>
<?php
if (!$ilDB->tableColumnExists("qpl_qst_imagemap", "is_multiple_choice")) {
	$atts = array(
		'type' => 'boolean',
	);
	$ilDB->addTableColumn("qpl_qst_imagemap", "is_multiple_choice", $atts);
}
?>
<#7>
<?php
if (!$ilDB->tableColumnExists("qpl_a_imagemap", "points_unchecked")) {
	$atts = array(
		'type' => 'float',
		'notnull' => true,
		'default' => 0
	);
	$ilDB->addTableColumn("qpl_a_imagemap", "points_unchecked", $atts);
}
?>
<#8>
<?php
if ($ilDB->tableColumnExists("qpl_questions", "auding_nr_of_sends")) {
	$ilDB->modifyTableColumn('qpl_questions', 'auding_nr_of_sends',
		array("type" => "integer", "length" => 4, "notnull" => false));
}
?>
<#9>
<?php
if ($ilDB->tableColumnExists("qpl_questions", "auding_activate")) {
	$ilDB->modifyTableColumn('qpl_questions', 'auding_activate',
		array("type" => "integer", "length" => 1, "notnull" => false));
}
?>
<#10>
<?php
if ($ilDB->tableColumnExists("qpl_questions", "auding_mode")) {
	$ilDB->modifyTableColumn('qpl_questions', 'auding_mode',
		array("type" => "integer", "length" => 4, "notnull" => false));
}
?>
<#11>
<?php
if(!$ilDB->tableExists('seat_number')){
	$fields = array(
		'seatnr' => array(
			'type' => 'integer',
			'length' => 4
		),
		'sector' => array(
			'type' => 'text',
			'length' => 50
		),		
		'ip' => array(
			'type' => 'text',
			'length' => 50
		)
	);
	$ilDB->createTable("seat_number", $fields);
	$ilDB->createSequence("seat_number");
}
?>
<#12>
<?php
if(!$ilDB->tableExists('tst_auding_settings'))
{
	$fields = array(
		'question_fi' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'active_fi' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),		
		'pass' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'nr_of_sends' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'auding_mode' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		)
	);
	$ilDB->createTable('tst_auding_settings', $fields);
}
?>
<#13>
<?php
$ilDB->addPrimaryKey('tst_auding_settings', array('question_fi', 'active_fi', 'pass'));
?>
<#14>
<?php
$query = "SELECT * FROM rbac_operations WHERE operation = %s AND class = %s";
$res = $ilDB->queryF(
	$query,
	array('text', 'text'),
	array('man_scoring_access', 'object')
);
if(!$ilDB->numRows($res))
{
	$new_ops_id = $ilDB->nextId('rbac_operations');
	$res = $ilDB->manipulateF('
		INSERT INTO rbac_operations (ops_id, operation, description, class, op_order)
		VALUES(%s, %s, %s, %s, %s)',
		array('integer','text', 'text', 'text', 'integer'),
		array($new_ops_id, 'man_scoring_access', 'manual scoring access', 'object', 9000)
	);
}
?>
<#15>
<?php
$query = "SELECT ops_id FROM rbac_operations WHERE operation = %s AND class = %s";
$res = $ilDB->queryF(
	$query,
	array('text', 'text'),
	array('man_scoring_access', 'object')
);
if($ilDB->numRows($res))
{
	$data       = $ilDB->fetchAssoc($res);
	$new_ops_id = $data['ops_id'];

	$res = $ilDB->queryF(
		'SELECT obj_id FROM object_data WHERE type = %s AND title = %s',
		array('text', 'text'),
		array('typ', 'tst')
	);

	$row    = $ilDB->fetchAssoc($res);
	$typ_id = $row['obj_id'];
	if($typ_id)
	{
		$query = "SELECT * FROM rbac_ta WHERE typ_id = %s AND ops_id = %s";
		$res   = $ilDB->queryF(
			$query,
			array('integer','integer'),
			array($typ_id, $new_ops_id)
		);
		if(!$ilDB->numRows($res))
		{
			$query = $ilDB->manipulateF(
				'INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
				array('integer','integer'),
				array($typ_id, $new_ops_id)
			);
		}
	}
}
?>
<#16>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "show_examview_html_hash")) {
	$res = $ilDB->addTableColumn("tst_tests", "show_examview_html_hash", array("type" => "integer", "length" => 1));
}
?>
<#17>
<?php
if(!$ilDB->tableExists('tst_active_files')) {
	$ilDB->createTable("tst_active_files", array(
			"active_id" => array(
				"type"		=> "integer",
				"length"	=> 4

			),
			"file_path" => array(
				"type"		=> "text",
				"length"	=> 255,
				"fixed"		=> true
			),
			"file_hash" => array(
				"type"		=> "text",
				"length"	=> 64,
				"fixed"		=> true
			)));
	$ilDB->addPrimaryKey("tst_active_files", array("active_id"));
}
?>
<#18>
<?php
if(!$ilDB->tableExists('tst_tests_files')) {
	$ilDB->createTable("tst_tests_files", array(
			"test_fi" => array(
				"type"		=> "integer",
				"length"	=> 4
			),
			"file_path" => array(
				"type"		=> "text",
				"length"	=> 255,
				"fixed"		=> true 
			),
			"file_hash" => array(
				"type"		=> "text",
				"length"	=> 64,
				"fixed"		=> true
			)));
}
?>
<#19>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "signature_list_type")) {
	$ilDB->addTableColumn("tst_tests", "signature_list_type", array("type" => "integer", "length" => 2));
}
?>
<#20>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "show_examview_html_hash")) {
	$ilDB->addTableColumn("tst_tests", "show_examview_html_hash", array("type" => "integer", "length" => 1));
}
?>
<#21>
<?php
if (!$ilDB->tableColumnExists("qpl_questions", "transfer_id")) {
	$ilDB->addTableColumn('qpl_questions', 'transfer_id', array(
		'type' => 'text',
		'length' => 32,
		'notnull' => false,
		'default' => null
	));
}
?>
<#22>
<?php
if( !$ilDB->tableColumnExists('qpl_qst_mc', 'selection_limit') )
{
	$ilDB->addTableColumn('qpl_qst_mc', 'selection_limit', array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false,
		'default' => null
	));
}
?>
<#23>
<#24>
<#25>
<#26>
<#27>
<#28>
<#29>
<?php
if( !$ilDB->tableColumnExists('tst_tests', 'screenshots_client') )
{
	$ilDB->addTableColumn("tst_tests", "screenshots_client", array(
		"type" => "integer", 
		"length" => 1
	));
}
?>
<#30>
<?php
if( !$ilDB->tableColumnExists('tst_tests', 'examview_printview') )
{
	$ilDB->addTableColumn("tst_tests", "examview_printview", array(
		"type" => "integer",
		"length" => 1
	));
}
?>
<#31>
<?php
if( !$ilDB->tableColumnExists('tst_tests', 'timeout_autofinish') )
{
	$ilDB->addTableColumn("tst_tests", "timeout_autofinish", array(
		"type" => "integer",
		"length" => 1
	));
}
?>
<#32>
<?php
if(!$ilDB->tableColumnExists('qpl_qst_type', 'plugin_name'))
{
	$ilDB->addTableColumn('qpl_qst_type', 'plugin_name', array(
		'type'    => 'text',
		'length'  => 40,
		'notnull' => false,
		'default' => null
	));
}
?>
<#33>
<?php
if(!$ilDB->tableColumnExists('tst_manual_fb', 'finalized_evaluation'))
{
	$ilDB->addTableColumn('tst_manual_fb', 'finalized_evaluation', array(
		"type" => "integer",
		"length" => 1
	));
}
?>
<#34>
<?php
if(!$ilDB->tableColumnExists('tst_manual_fb', 'finalized_by_usr_id'))
{
	$ilDB->addTableColumn('tst_manual_fb', 'finalized_by_usr_id', array(
		"type" => "integer",
		"length" => 4
	));
}
?>
<#35>
<?php
if(!$ilDB->tableColumnExists('tst_manual_fb', 'finalized_tstamp'))
{
	$ilDB->addTableColumn('tst_manual_fb', 'finalized_tstamp', array(
		"type" => "integer",
		"length" => 4
	));
}
?>	
<#36>
<?php
if(!$ilDB->tableColumnExists('qpl_qst_lome', 'identical_scoring'))
{
	$ilDB->addTableColumn('qpl_qst_lome', 'identical_scoring', array(
		'type'    => 'integer',
		'length'  => 1,
		'default' => 1
	));
}
?>