<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqSingleChoiceClassnameProvider
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
class ilAsqSingleChoiceClassnameProvider implements ilAsqQuestionClassnameProvider
{
	/**
	 * @return string
	 */
	public function getQuestionClassname() : string
	{
		return 'ilAsqSingleChoiceQuestion';
	}
	
	/**
	 * @return string
	 */
	public function getSolutionClassname() : string
	{
		return 'ilAsqSingleChoiceSolution';
	}
	
	/**
	 * @return string
	 */
	public function getAuthoringClassname() : string
	{
		return 'ilAsqSingleChoiceAuthoringGUI';
	}
	
	/**
	 * @return string
	 */
	public function getConfigFormClassname() : string
	{
		return 'ilAsqSingleChoiceConfigFormGUI';
	}
}
