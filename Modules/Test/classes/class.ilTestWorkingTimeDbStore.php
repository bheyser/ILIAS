<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilTestWorkingTimeDbStore
{
	const TEST_TIMES_TABLE = 'tst_times';
	const QUESTION_TIMES_TABLE = 'tst_times_qst';

	public function __construct()
	{
		$this->db = $GLOBALS['DIC'] ? $GLOBALS['DIC']['ilDB'] : $GLOBALS['ilDB'];
	}
	
	public function loadTestTimes(ilTestPassWorkingTimeRecordList $recordList)
	{
		$this->loadTimes($recordList, self::TEST_TIMES_TABLE);
	}
	
	public function loadQuestionTimes(ilTestQuestionWorkingTimeRecordList $recordList)
	{
		$this->loadTimes($recordList, self::QUESTION_TIMES_TABLE);
	}
	
	protected function loadTimes(ilTestWorkingTimeRecordList $recordList, $tableName)
	{
		$query = "SELECT * FROM {$tableName} WHERE active_fi = %s ";
		$values = array($recordList->getActiveId());
		$types = array('integer');
		
		$res = $this->db->queryF($query, $types, $values);
		
		while($rec = $this->db->fetchAssoc($res))
		{
			$workingTimeRecord = $recordList->newRecord();
			$workingTimeRecord->assignFromDbRecord($rec);
			$recordList->addRecord($workingTimeRecord);
		}
	}
	
	public function savePassTimeRecord(ilTestWorkingTimeRecord $record)
	{
		$this->saveWorkingTimeRecord($record, self::TEST_TIMES_TABLE);
	}
	
	public function saveQuestionTimeRecord(ilTestWorkingTimeRecord $record)
	{
		$this->saveWorkingTimeRecord($record, self::QUESTION_TIMES_TABLE);
	}
	
	protected function saveWorkingTimeRecord(ilTestWorkingTimeRecord $record, $tableName)
	{
		if( $record->getRecordId() )
		{
			$this->db->update(
				$tableName, $record->buildDbUpdateFields(), $record->buildDbUpdateCondition()
			);
		}
		else
		{
			$this->db->insert( $tableName, $record->buildDbInsertFields(
				$this->requestNextRecordId($tableName)
			));
		}
	}
	
	protected function requestNextRecordId($tableName)
	{
		return $this->db->nextId($tableName);
	}
}