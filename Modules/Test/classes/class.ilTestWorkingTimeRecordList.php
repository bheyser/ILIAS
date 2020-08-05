<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
abstract class ilTestWorkingTimeRecordList implements Iterator
{
	/**
	 * @var integer
	 */
	protected $activeId;
	
	/**
	 * @var integer
	 */
	protected $passIndex;

	/**
	 * @var array[ilTestWorkingTime]
	 */
	protected $records;
	
	/**
	 * ilTestWorkingTimeRecordList constructor.
	 */
	public function __construct()
	{
		$this->resetRecords();
	}
	
	/**
	 * @return int
	 */
	public function getActiveId()
	{
		return $this->activeId;
	}
	
	/**
	 * @param int $activeId
	 */
	public function setActiveId($activeId)
	{
		$this->activeId = $activeId;
	}
	
	/**
	 * @return int
	 */
	public function getPassIndex()
	{
		return $this->passIndex;
	}
	
	/**
	 * @param int $passIndex
	 */
	public function setPassIndex($passIndex)
	{
		$this->passIndex = $passIndex;
	}
	
	/**
	 * @return array
	 */
	public function getRecords()
	{
		return $this->records;
	}
	
	/**
	 * @param array $records
	 */
	public function setRecords($records)
	{
		$this->records = $records;
	}
	
	/**
	 * @param array $records
	 */
	public function resetRecords()
	{
		$this->setRecords(array());
	}
	
	/**
	 * @param ilTestWorkingTimeRecord $workingTimeRecord
	 */
	public function addRecord(ilTestWorkingTimeRecord $workingTimeRecord)
	{
		$this->records[$workingTimeRecord->getRecordId()] = $workingTimeRecord;
	}
	
	
	/**
	 * @param integer $workingTimeRecordId
	 */
	public function getRecord($workingTimeRecordId)
	{
		return $this->records[$workingTimeRecordId];
	}
	
	/**
	 * @return ilTestWorkingTimeRecord $workingTimeRecord
	 */
	abstract public function newRecord();
	
	/**
	 * @param ilTestWorkingTimeRecord $record
	 * @return ilTestWorkingTimeRecord
	 */
	protected function initRecord(ilTestWorkingTimeRecord $record)
	{
		$record->setActiveId($this->getActiveId());
		$record->setPassIndex($this->getPassIndex());
		
		return $record;
	}
	
	/**
	 * @return ilTestWorkingTimeRecord
	 */
	public function current()
	{
		return current($this->records);
	}
	
	/**
	 * @return ilTestWorkingTimeRecord
	 */
	public function next()
	{
		return next($this->records);
	}
	
	/**
	 * @return string
	 */
	public function key()
	{
		return key($this->records);
	}
	
	/**
	 * @return bool
	 */
	public function valid()
	{
		return key($this->records) !== null;
	}
	
	/**
	 * @return ilTestWorkingTimeRecord
	 */
	public function rewind()
	{
		return reset($this->records);
	}
}