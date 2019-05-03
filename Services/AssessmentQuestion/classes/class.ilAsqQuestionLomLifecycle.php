<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqQuestionLomLifecycle
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
class ilAsqQuestionLomLifecycle
{
	const DRAFT = 'draft';
	const FINAL = 'final';
	const REVISED = 'revised';
	const UNAVAILABLE = 'unavailable';
	
	/**
	 * @var string
	 */
	protected $identifier;
	
	/**
	 * ilAsqQuestionLomLifecycle constructor.
	 * @param string $identifier
	 * @throws ilAsqInvalidArgumentException
	 */
	public function __construct($identifier = '')
	{
		if( strlen($identifier) )
		{
			$identifier = strtolower($identifier);
			$this->validateIdentifier($identifier);
			$this->setIdentifier($identifier);
		}
	}
	
	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}
	
	/**
	 * @param string $identifier
	 * @throws ilAsqInvalidArgumentException
	 */
	public function setIdentifier($identifier)
	{
		$this->validateIdentifier($identifier);
		$this->identifier = $identifier;
	}
	
	/**
	 * @return string[]
	 */
	public function getValidIdentifiers()
	{
		return [self::DRAFT, self::FINAL, self::REVISED, self::UNAVAILABLE];
	}
	
	/**
	 * @param string $identifier
	 * @throws ilAsqInvalidArgumentException
	 */
	public function validateIdentifier($identifier)
	{
		if( !in_array($identifier, $this->getValidIdentifiers()) )
		{
			throw new ilAsqInvalidArgumentException(
				'invalid lom lifecycle given: '.$identifier
			);
		}
	}
	
	/**
	 * @return string
	 */
	public function getMappedIliasLifecycleIdentifer()
	{
		switch( $this->getIdentifier() )
		{
			case self::UNAVAILABLE:
				
				return ilAssQuestionLifecycle::OUTDATED;
			
			case self::REVISED:
			case self::FINAL:
				
				return ilAssQuestionLifecycle::FINAL;
			
			case self::DRAFT:
			default:
				
				return ilAssQuestionLifecycle::DRAFT;
			
		}
	}
}
