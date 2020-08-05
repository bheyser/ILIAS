<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/Test/classes/class.ilTestWorkingTimeRecordList.php';
require_once 'Modules/Test/classes/class.ilTestQuestionWorkingTimeRecord.php';
/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilTestQuestionWorkingTimeRecordList extends ilTestWorkingTimeRecordList
{
	public function newRecord()
	{
		return $this->initRecord(
			ilTestQuestionWorkingTimeRecord::getInstance()
		);
	}
	
	public function getFirstStartTime($questionId)
	{
		$firstStartTime = null;
		
		foreach($this as $record)
		{
			/* @var ilTestQuestionWorkingTimeRecord $record */
			
			if( $this->getPassIndex() && $record->getPassIndex() != $this->getPassIndex() )
			{
				continue;
			}
			
			if( $record->getQuestionId() != $questionId )
			{
				continue;
			}
			
			$trackedStartTime = $record->getStartTime()->get(IL_CAL_UNIX);
			
			if( $firstStartTime !== null && $firstStartTime < $trackedStartTime )
			{
				continue;
			}
			
			$firstStartTime = $trackedStartTime;
		}
		
		return $firstStartTime;
	}
}