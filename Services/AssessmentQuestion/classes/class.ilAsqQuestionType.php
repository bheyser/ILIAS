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
	 * @var int
	 */
	protected $id;
	
	/**
	 * @var string
	 */
	protected $tag;
	
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
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}
	
	/**
	 * @param int $id
	 */
	public function setId(int $id)
	{
		$this->id = $id;
	}
	
	/**
	 * @return string
	 */
	public function getTag() : string
	{
		return $this->tag;
	}
	
	/**
	 * @param string $tag
	 */
	public function setTag($tag)
	{
		$this->tag = $tag;
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