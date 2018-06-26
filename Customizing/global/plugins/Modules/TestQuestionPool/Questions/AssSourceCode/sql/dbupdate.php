<#1>
<?php // register question type

$typeTag = 'assSourceCode';
$pluginName = 'AssSourceCode';

$isRegistered = (bool)current( $ilDB->fetchAssoc($ilDB->queryF(
	"SELECT COUNT(*) cnt FROM qpl_qst_type WHERE type_tag = %s",  array('text'), array($typeTag)
)) );

if( !$isRegistered )
{
	$nextId = (int)current( $ilDB->fetchAssoc($ilDB->query(
		"SELECT (MAX(question_type_id) + 1) nextid FROM qpl_qst_type"
	)) ); 
	
	$ilDB->insert('qpl_qst_type', array(
		'question_type_id' => array('integer', $nextId),
		'type_tag' => array('text', $typeTag),
		'plugin' => array('integer', 1),
		'plugin_name' => array('text', $pluginName)
	));
}

?>
<#2>
<?php

if( !$ilDB->tableExists('qpl_qst_sourcecode') )
{
	$ilDB->createTable('qpl_qst_sourcecode', array(
		'question_fi' => array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0
		),
		'code_lang' => array(
			'type'    => 'text',
			'length'  => 64,
			'notnull' => false,
			'default' => null
		)
	));
}

?>