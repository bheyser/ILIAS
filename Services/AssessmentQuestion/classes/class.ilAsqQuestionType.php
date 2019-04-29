<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilAsqQuestionType
 * 
 * @author        Björn Heyser <bh@bjoernheyser.de>
 *
 * @package       Services/AssessmentQuestion
 */
class ilAsqQuestionType
{
	/**
	 * @var string
	 */
	protected $identifier;
	
	/**
	 * @var bool
	 */
	protected $pluginType;
	
	/**
	 * @var string
	 */
	protected $pluginName;
	
	/**
	 * ilAssQuestionType constructor.
	 */
	public function __construct()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$this->pluginAdmin = $DIC['ilPluginAdmin'];
	}
	
	/**
	 * @return string
	 */
	public function getIdentifier() : string
	{
		return $this->identifier;
	}
	
	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier)
	{
		$this->identifier = $identifier;
	}
	
	/**
	 * @return bool
	 */
	public function isPluginType() : bool
	{
		return $this->pluginType;
	}
	
	/**
	 * @param bool $pluginType
	 */
	public function setPluginType($pluginType)
	{
		$this->pluginType = $pluginType;
	}
	
	/**
	 * @return string
	 */
	public function getPluginName() : string
	{
		return $this->pluginName;
	}
	
	/**
	 * @param string $pluginName
	 */
	public function setPluginName($pluginName)
	{
		$this->pluginName = $pluginName;
	}
}