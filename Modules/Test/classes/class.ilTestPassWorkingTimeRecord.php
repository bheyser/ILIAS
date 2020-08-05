<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/Test/interfaces/interface.ilTestWorkingTimeRecord.php';
/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test
 */
class ilTestPassWorkingTimeRecord implements ilTestWorkingTimeRecord
{
	protected $recordId;
	
	protected $activeId;
	
	protected $passIndex;
	
	/**
	 * @var ilDateTime
	 */
	protected $startTime;
	
	/**
	 * @var ilDateTime
	 */
	protected $lastAccessTime;
	
	public function getRecordId()
	{
		return $this->recordId;
	}
	
	public function setRecordId($recordId)
	{
		$this->recordId = $recordId;
	}
	
	public function getActiveId()
	{
		return $this->activeId;
	}
	
	public function setActiveId($activeId)
	{
		$this->activeId = $activeId;
	}
	
	public function getPassIndex()
	{
		return $this->passIndex;
	}
	
	public function setPassIndex($passIndex)
	{
		$this->passIndex = $passIndex;
	}
	
	public function getStartTime()
	{
		return $this->startTime;
	}
	
	public function setStartTime($startTime)
	{
		$this->startTime = $startTime;
	}
	
	public function getLastAccessTime()
	{
		return $this->lastAccessTime;
	}
	
	public function setLastAccessTime($lastAccessTime)
	{
		$this->lastAccessTime = $lastAccessTime;
	}
	
	public function assignFromDbRecord($dbRecord)
	{
		foreach($dbRecord as $field => $value)
		{
			switch($field)
			{
				case 'active_fi':
					$this->setActiveId($value);
					break;
				
				case 'pass':
				case 'pass_index':
					$this->setPassIndex($value);
					break;

				case 'times_id':
				case 'record_id':
					$this->setRecordId($value);
					break;
				
				case 'started':
				case 'starting_time':
					$value = new ilDateTime($value, IL_CAL_DATETIME);
					$this->setStartTime($value);
					break;
				
				case 'finished':
				case 'access_time':
					$value = new ilDateTime($value, IL_CAL_DATETIME);
					$this->setLastAccessTime($value);
					break;
			}
		}
	}
	
	public function buildDbInsertFields($recordId)
	{
		return array(
			'times_id' => array('integer', $recordId),
			'active_fi' => array('integer', $this->getActiveId()),
			'pass' => array('integer', $this->getPassIndex()),
			'started' => array('timestamp', $this->getStartTime()->get(IL_CAL_DATETIME)),
			'finished' => array('timestamp', $this->getLastAccessTime()->get(IL_CAL_DATETIME)),
			'tstamp' => array('timestamp', $this->getLastAccessTime()->get(IL_CAL_DATETIME))
		);
	}
	
	public function buildDbUpdateFields()
	{
		return array(
			'active_fi' => array('integer', $this->getActiveId()),
			'pass' => array('integer', $this->getPassIndex()),
			'started' => array('timestamp', $this->getStartTime()->get(IL_CAL_DATETIME)),
			'finished' => array('timestamp', $this->getLastAccessTime()->get(IL_CAL_DATETIME)),
			'tstamp' => array('timestamp', $this->getLastAccessTime()->get(IL_CAL_DATETIME))
		);
	}
	
	public function buildDbUpdateCondition()
	{
		return array(
			'times_id' => array('integer', $this->getRecordId())
		);
	}
	
	public static function getInstance()
	{
		return new self();
	}
}