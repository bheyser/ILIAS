<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqQuestionSolutionAbstract
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
abstract class ilAsqQuestionSolutionAbstract implements ilAsqQuestionSolution
{
	/**
	 * @var ilAsqQuestion
	 */
	protected $question;
	
	/**
	 * @var int
	 */
	protected $id;
	
	/**
	 * @param ilAsqQuestion $question
	 */
	public function setQuestion(ilAsqQuestion $question)
	{
		$this->question = $question;
	}
	
	/**
	 * @return ilAsqQuestion
	 */
	public function getQuestion(): ilAsqQuestion
	{
		return $this->question;
	}
	
	/**
	 * @param int $id
	 */
	public function setId(int $id)
	{
		$this->id = $id;
	}
	
	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}
	
	/**
	 * Loads soluton data
	 */
	abstract public function load();
}
