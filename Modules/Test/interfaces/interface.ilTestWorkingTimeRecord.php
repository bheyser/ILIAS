<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Created by PhpStorm.
 * User: bheyser
 * Date: 14.03.17
 * Time: 13:37
 */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
interface ilTestWorkingTimeRecord
{
	public function setRecordId($recordId);
	public function getRecordId();
	
	public function setActiveId($activeId);
	public function getActiveId();
	
	public function setPassIndex($passIndex);
	public function getPassIndex();
	
	public function setStartTime($startTime);
	public function getStartTime();
	
	public function setLastAccessTime($lastAccessTime);
	public function getLastAccessTime();
	
	/**
	 * @param array $dbRecord
	 */
	public function assignFromDbRecord($dbRecord);
	
	/**
	 * @param integer $recordId
	 * @return array $dbInsertFields
	 */
	public function buildDbInsertFields($recordId);
	
	/**
	 * @return array $dbUpdateFields
	 */
	public function buildDbUpdateFields();
	
	/**
	 * @return array $dbUpdateCondition
	 */
	public function buildDbUpdateCondition();
	
	/**
	 * @return ilTestWorkingTimeRecord
	 */
	public static function getInstance();
}