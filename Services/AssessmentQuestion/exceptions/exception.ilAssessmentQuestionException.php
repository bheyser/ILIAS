<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAssessmentQuestionException
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
class ilAssessmentQuestionException extends ilException
{
	public function __construct($a_message)
	{
		parent::__construct($a_message);
	}
}
