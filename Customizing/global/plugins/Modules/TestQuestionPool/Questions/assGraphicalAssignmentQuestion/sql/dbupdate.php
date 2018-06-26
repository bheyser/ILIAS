<#1>
<?php
/*
 * Create the new question type
 */

$res = $ilDB->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'), array('assGraphicalAssignmentQuestion')
);
if ($res->numRows() == 0) {
	$res = $ilDB->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
	$data = $ilDB->fetchAssoc($res);
	$max = $data["maxid"] + 1;

	$affectedRows = $ilDB->manipulateF("INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)", array("integer", "text", "integer"), array($max, 'assGraphicalAssignmentQuestion', 1)
	);
}
?>
<#2>
<?php
if(!$ilDB->tableExists('qpl_qst_grasqst_data')){
	$fields = array(
		'question_fi' => array(
			'type' => 'integer',
			'length' => 4
		),
		'image' => array(
			'type' => 'text',
			'length' => 100
		),
		'canvas_size' => array(
			'type' => 'text',
			'length' => '10'
		)
	);

	$ilDB->createTable("qpl_qst_grasqst_data", $fields);
	$ilDB->addPrimaryKey("qpl_qst_grasqst_data", array("question_fi"));
}
?>
<#3>
<?php
if(!$ilDB->tableExists('qpl_qst_grasqst_answer')){
	$fields = array(
		'answer_id' => array(
			'type' => 'integer',
			'length' => 4
		),
		'question_fi' => array(
			'type' => 'integer',
			'length' => 4
		),
		'item_id' => array(
			'type' => 'integer',
			'length' => 4
		),
		'answertext' => array(
			'type' => 'text',
			'length' => 1000
		),
		'points' => array(
			'type' => 'float',
			'notnull' => true,
			'default' => 0
		),
		'aorder' => array(
			'type' => 'integer',
			'length' => '4',
		),
		'answer_type' => array(
			'type' => 'text',
			'length' => '25'
		),
		'shuffle' => array(
			'type' => 'boolean',
		),
		'destination_x' => array(
			'type' => "integer",
			'length' => '4',
		),
		'destination_y' => array(
			'type' => "integer",
			'length' => '4',
		),
		'target_x' => array(
			'type' => "integer",
			'length' => '4',
		),
		'target_y' => array(
			'type' => "integer",
			'length' => '4',
		),
	);

	$ilDB->createTable("qpl_qst_grasqst_answer", $fields);
	$ilDB->createSequence("qpl_qst_grasqst_answer");
}
?>
<#4>
<?php
if(!$ilDB->tableExists('qpl_qst_grasqst_fb')){
	$fields = array(
		'feedback_id' => array(
			'type' => 'integer',
			'length' => 4
		),
		'question_fi' => array(
			'type' => 'integer',
			'length' => 4
		),
		'answer' => array(
			'type' => 'integer',
			'length' => 4
		),
		'feedback' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'tstamp' => array(
			'type' => 'integer',
			'length' => 4
		)
	);
	$ilDB->createTable("qpl_qst_grasqst_fb", $fields);
	$ilDB->createSequence("qpl_qst_grasqst_fb");
}
?>
<#5>
<?php
if (!$ilDB->tableColumnExists("qpl_qst_grasqst_data", "color")){
	$atts = array(
		'type' => 'text',
		'length' => 4000,
		'notnull' => false
	);
	$ilDB->addTableColumn("qpl_qst_grasqst_data", "color", $atts);
}
?>