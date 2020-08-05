<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/Test/classes/class.ilTestPassWorkingTimeRecord.php';

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test
 */
class ilTestQuestionWorkingTimeRecord extends ilTestPassWorkingTimeRecord
{
	protected $questionId;
	
	public function getQuestionId()
	{
		return $this->questionId;
	}
	
	public function setQuestionId($questionId)
	{
		$this->questionId = $questionId;
	}
	
	public function assignFromDbRecord($dbRecord)
	{
		parent::assignFromDbRecord($dbRecord);
		
		foreach($dbRecord as $field => $value)
		{
			switch($field)
			{
				case 'question_fi': $this->setQuestionId($value); break;
			}
		}
	}
	
	public function buildDbInsertFields($recordId)
	{
		return array(
			'record_id' => array('integer', $recordId),
			'active_fi' => array('integer', $this->getActiveId()),
			'pass_index' => array('integer', $this->getPassIndex()),
			'question_fi' => array('integer', $this->getQuestionId()),
			'starting_time' => array('timestamp', $this->getStartTime()->get(IL_CAL_DATETIME)),
			'ending_time' => array('timestamp', $this->getLastAccessTime()->get(IL_CAL_DATETIME))
		);
	}
	
	public function buildDbUpdateFields()
	{
		return array(
			'active_fi' => array('integer', $this->getActiveId()),
			'pass_index' => array('integer', $this->getPassIndex()),
			'question_fi' => array('integer', $this->getQuestionId()),
			'starting_time' => array('timestamp', $this->getStartTime()->get(IL_CAL_DATETIME)),
			'ending_time' => array('timestamp', $this->getLastAccessTime()->get(IL_CAL_DATETIME))
		);
	}
	
	public function buildDbUpdateCondition()
	{
		return array(
			'record_id' => array('integer', $this->getRecordId())
		);
	}
	
	public static function getInstance()
	{
		return new self();
	}
}


