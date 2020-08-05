<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/Test/classes/class.ilTestWorkingTimeRecordList.php';
require_once 'Modules/Test/classes/class.ilTestPassWorkingTimeRecord.php';
/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilTestPassWorkingTimeRecordList extends ilTestWorkingTimeRecordList
{
	public function newRecord()
	{
		return $this->initRecord(
			ilTestPassWorkingTimeRecord::getInstance()
		);
	}
	
	
}