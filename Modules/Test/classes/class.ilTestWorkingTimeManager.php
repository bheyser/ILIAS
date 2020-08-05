<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test
 */
class ilTestWorkingTimeManager
{
	/**
	 * @var ilTestPassWorkingTimeRecordList
	 */
	protected $passWorkingTimeRecordList;
	
	/**
	 * @var ilTestQuestionWorkingTimeRecordList
	 */
	protected $qstWorkingTimeRecordList;
	
	/**
	 * @var ilTestWorkingTimeDbStore
	 */
	protected $dataStore;
	
	/**
	 * ilTestWorkingTime constructor.
	 */
	public function __construct()
	{
	}
	
	protected function getNowDateTime()
	{
		return new ilDateTime(time(), IL_CAL_UNIX);
	}
	
	// TODO BEGIN: handling of IDs for time tracking records in php session
	
	const SESSION_STORAGE_KEY = 'ilTestWorkingTimeTrackingRecordsRegister'; // __CLASS__;
	const TRACKING_KEY_PARTS_SEPARATOR = '_';
	
	protected function ensureInitialisedTrackingRecordIdSessionStorage()
	{
		if( !isset($_SESSION[self::SESSION_STORAGE_KEY]) )
		{
			$_SESSION[self::SESSION_STORAGE_KEY] = array();
		}
	}
	
	protected function sessionStoredTrackingRecordIdExists($trackingKey)
	{
		return isset($_SESSION[self::SESSION_STORAGE_KEY][$trackingKey]);
	}
	
	protected function getSessionStoredTrackingRecordId($trackingKey)
	{
		return $_SESSION[self::SESSION_STORAGE_KEY][$trackingKey];
	}
	
	protected function setSessionStoredTrackingRecordId($trackingKey, $trackingRecordId)
	{
		$_SESSION[self::SESSION_STORAGE_KEY][$trackingKey] = $trackingRecordId;
	}
	
	protected function buildTrackingKey($activeId, $passIndex, $questionId = null)
	{
		$keyParts = array($activeId, $passIndex);
		
		if( $questionId )
		{
			$keyParts[] = $questionId;
		}
		
		return implode(self::TRACKING_KEY_PARTS_SEPARATOR, $keyParts);
	}
	
	// TODO END: handling of IDs for time tracking records in php session
	
	/**
	 * @return ilTestPassWorkingTimeRecordList
	 */
	public function getPassWorkingTimeRecordList()
	{
		return $this->passWorkingTimeRecordList;
	}
	
	/**
	 * @param ilTestPassWorkingTimeRecordList $passWorkingTimeRecordList
	 */
	public function setPassWorkingTimeRecordList($passWorkingTimeRecordList)
	{
		$this->passWorkingTimeRecordList = $passWorkingTimeRecordList;
	}
	
	/**
	 * @return ilTestQuestionWorkingTimeRecordList
	 */
	public function getQstWorkingTimeRecordList()
	{
		return $this->qstWorkingTimeRecordList;
	}
	
	/**
	 * @param ilTestQuestionWorkingTimeRecordList $qstWorkingTimeRecordList
	 */
	public function setQstWorkingTimeRecordList($qstWorkingTimeRecordList)
	{
		$this->qstWorkingTimeRecordList = $qstWorkingTimeRecordList;
	}
	
	/**
	 * @return ilTestWorkingTimeDbStore
	 */
	public function getDataStore()
	{
		return $this->dataStore;
	}
	
	/**
	 * @param ilTestWorkingTimeDbStore $dataStore
	 */
	public function setDataStore(ilTestWorkingTimeDbStore $dataStore)
	{
		$this->dataStore = $dataStore;
	}
	
	protected function registerNewPassWorkingTimeId(ilTestPassWorkingTimeRecord $record)
	{
		$trackingKey = $this->buildTrackingKey(
			$this->getPassWorkingTimeRecordList()->getActiveId(),
			$this->getPassWorkingTimeRecordList()->getPassIndex()
		);
		
		$this->setSessionStoredTrackingRecordId($trackingKey, $record->getRecordId());
	}
	
	protected function lookupCurrentPassWorkingTimeId()
	{
		$trackingKey = $this->buildTrackingKey(
			$this->getPassWorkingTimeRecordList()->getActiveId(),
			$this->getPassWorkingTimeRecordList()->getPassIndex()
		);
		
		if( !$this->sessionStoredTrackingRecordIdExists($trackingKey) )
		{
			return null;
		}
		
		return $this->getSessionStoredTrackingRecordId($trackingKey);
	}
	
	protected function hasCurrentPassWorkingTimeId()
	{
		return $this->lookupCurrentPassWorkingTimeId() !== null;
	}
	
	protected function registerNewQuestionWorkingTimeId(ilTestQuestionWorkingTimeRecord $record)
	{
		$trackingKey = $this->buildTrackingKey(
			$this->getPassWorkingTimeRecordList()->getActiveId(),
			$this->getPassWorkingTimeRecordList()->getPassIndex(),
			$record->getQuestionId()
		);
		
		$this->setSessionStoredTrackingRecordId($trackingKey, $record->getRecordId());
	}
	
	protected function lookupCurrentQuestionWorkingTimeId($questionId)
	{
		$trackingKey = $this->buildTrackingKey(
			$this->getPassWorkingTimeRecordList()->getActiveId(),
			$this->getPassWorkingTimeRecordList()->getPassIndex(),
			$questionId
		);
		
		if( !$this->sessionStoredTrackingRecordIdExists($trackingKey) )
		{
			return null;
		}
		
		return $this->getSessionStoredTrackingRecordId($trackingKey);
	}
	
	protected function hasCurrentQuestionWorkingTimeId($questionId)
	{
		return $this->lookupCurrentQuestionWorkingTimeId($questionId) !== null;
	}
	
	protected function getPassWorkingTimeRecord()
	{
		if( !$this->hasCurrentPassWorkingTimeId() )
		{
			$record = $this->getPassWorkingTimeRecordList()->newRecord();
			$record->setStartTime($this->getNowDateTime());
			
			$this->registerNewPassWorkingTimeId($record);
			$this->getPassWorkingTimeRecordList()->addRecord($record);
		}
		
		return $this->getPassWorkingTimeRecordList()->getRecord(
			$this->lookupCurrentPassWorkingTimeId()
		);
	}
	
	protected function getQuestionWorkingTimeRecord($questionId)
	{
		if( !$this->hasCurrentQuestionWorkingTimeId($questionId) )
		{
			$record = $this->getQstWorkingTimeRecordList()->newRecord();
			$record->setQuestionId($questionId);
			$record->setStartTime($this->getNowDateTime());
			
			$this->registerNewQuestionWorkingTimeId($record);
			$this->getQstWorkingTimeRecordList()->addRecord($record);
		}
		
		return $this->getQstWorkingTimeRecordList()->getRecord(
			$this->lookupCurrentQuestionWorkingTimeId($questionId)
		);
	}
	
	public function loadPassTimes()
	{
		$this->getDataStore()->loadTestTimes(
			$this->getPassWorkingTimeRecordList()
		);
	}
	
	public function loadQuestionTimes()
	{
		$this->getDataStore()->loadQuestionTimes(
			$this->getQstWorkingTimeRecordList()
		);
	}
	
	public function loadTimes()
	{
		$this->loadPassTimes();
		$this->loadQuestionTimes();
	}
	
	public function trackPassWorkingAccess()
	{
		$workingTimeRecord = $this->getPassWorkingTimeRecord();
		$workingTimeRecord->setLastAccessTime($this->getNowDateTime());
		$this->dataStore->savePassTimeRecord($workingTimeRecord);
	}
	
	public function trackQuestionWorkingAccess($questionId)
	{
		$workingTimeRecord = $this->getQuestionWorkingTimeRecord($questionId);
		$workingTimeRecord->setLastAccessTime($this->getNowDateTime());
		$this->dataStore->saveQuestionTimeRecord($workingTimeRecord);
	}
	
	public function hasQuestionWorkingTimeRemaining(assQuestion $question)
	{
		$remainingTime = $this->getRemainingQuestionWorkingTime($question);
		return $remainingTime > 0;
	}
	
	public function getRemainingQuestionWorkingTime(assQuestion $question)
	{ 
		$allowedTime = $question->getWorkingTimeLimitation();
		$firstAccess = $this->getQstWorkingTimeRecordList()->getFirstStartTime($question->getId());
		
		$now = $this->getNowDateTime();
		$limit = $firstAccess + $allowedTime;
		
		if( $limit < $now->get(IL_CAL_UNIX) )
		{
			return 0;
		}
		
		return $limit - $now;
	}
	
	public function getQuestionStartingTime(assQuestion $question)
	{
		return $this->getQstWorkingTimeRecordList()->getFirstStartTime($question->getId());
	}
}