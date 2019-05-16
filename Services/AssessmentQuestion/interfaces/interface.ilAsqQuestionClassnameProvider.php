<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqClassnameProvider
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
interface ilAsqQuestionClassnameProvider
{
	/**
	 * @return string
	 */
	public function getQuestionClassname() : string;
	
	/**
	 * @return string
	 */
	public function getSolutionClassname() : string;
	
	/**
	 * @return string
	 */
	public function getAuthoringClassname() : string;
	
	/**
	 * @return string
	 */
	public function getConfigFormClassname() : string;
}
