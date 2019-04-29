<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqQuestionAbstract
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
abstract class ilAsqQuestionAbstract implements ilAsqQuestion
{
	/**
	 * @var int
	 */
	protected $parentId;
	
	/**
	 * @var int
	 */
	protected $id;
	
	/**
	 * @var ilAsqQuestionType
	 */
	protected $questionType;
	
	/**
	 * @var string
	 */
	protected $title;
	
	/**
	 * @var string
	 */
	protected $comment;
	
	/**
	 * @var int
	 */
	protected $owner;
	
	/**
	 * @var string
	 */
	protected $questionText;
	
	/**
	 * @param int $parentId
	 */
	public function setParentId(int $parentId)
	{
		$this->parentId = $parentId;
	}
	
	/**
	 * @return int
	 */
	public function getParentId(): int
	{
		return $this->parentId;
	}
	
	/**
	 * @param int $questionId
	 */
	public function setId(int $questionId)
	{
		$this->id = $questionId;
	}
	
	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}
	
	/**
	 * @param ilAsqQuestionType $questionType
	 */
	public function setQuestionType(ilAsqQuestionType $questionType)
	{
		$this->questionType = $questionType;
	}
	
	/**
	 * @return ilAsqQuestionType
	 */
	public function getQuestionType(): ilAsqQuestionType
	{
		return $this->questionType;
	}
	
	/**
	 * @param string $title
	 */
	public function setTitle(string $title)
	{
		$this->title = $title;
	}
	
	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}
	
	/**
	 * @param string $comment
	 */
	public function setComment(string $comment)
	{
		$this->comment = $comment;
	}
	
	/**
	 * @return string
	 */
	public function getComment(): string
	{
		return $this->comment;
	}
	
	/**
	 * @param int $owner
	 */
	public function setOwner(int $owner)
	{
		$this->owner = $owner;
	}
	
	/**
	 * @return int
	 */
	public function getOwner(): int
	{
		return $this->owner;
	}
	
	/**
	 * @param string $questionText
	 */
	public function setQuestionText(string $questionText)
	{
		$this->questionText = $questionText;
	}
	
	/**
	 * @return string
	 */
	public function getQuestionText(): string
	{
		return $this->questionText;
	}
}
