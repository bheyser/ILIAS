<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface ilAsqQuestionSolution
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package    Services/AssessmentQuestion
 */
interface ilAsqQuestionSolution
{
	/**
	 * @return ilAsqQuestion
	 */
	public function getQuestion() : ilAsqQuestion;

	/**
	 * @return integer
	 */
	public function getId() : int;
	
	/**
	 * Saves solution data
	 */
	public function save();
	
	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 */
	public function initFromServerRequest(\Psr\Http\Message\ServerRequestInterface $request);
	
	/**
	 * @return bool
	 */
	public function isEmpty() : bool;
}