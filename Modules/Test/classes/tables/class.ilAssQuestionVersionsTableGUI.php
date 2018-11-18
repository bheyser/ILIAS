<?php

class ilAssQuestionVersionsTableGUI extends ilTable2GUI
{
	public function __construct($a_parent_obj, string $a_parent_cmd = "", string $a_template_context = "")
	{
		$this->setRowTemplate('tpl.question_versions_row.html', 'Modules/TestQuestionPool');
		$this->setId('qst_versions');
		
		$this->setTitle('Question Versions');
		
		$this->addColumn('Version', '', '100');
		$this->addColumn('Title');
		$this->addColumn('Author');
		$this->addColumn('', '', '1%');
		
		
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
	}
}