<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAssExcludedMcOptionsStorage
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Modules/Test
 */
class ilAssExcludedMcOptionsStorage
{
	/**
	 * @var int
	 */
	protected $activeId;
	
	/**
	 * @var int
	 */
	protected $passIndex;
	
	/**
	 * @var int
	 */
	protected $questionId;
	
	/**
	 * ilAssExcludedMcOptionsStorage constructor.
	 * @param $activeId
	 * @param $passIndex
	 * @param $questionId
	 */
	public function __construct($activeId, $passIndex, $questionId)
	{
		$this->activeId = $activeId;
		$this->passIndex = $passIndex;
		$this->questionId = $questionId;
	}
	
	/**
	 * @return int[]
	 */
	public function getExcludedOptions()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$query = "SELECT option_index FROM qpl_mc_opt_excluded
			WHERE active_fi = %s AND pass_index = %s AND question_fi = %s
		";
		
		$res = $DIC->database()->queryF($query, array('integer', 'integer', 'integer'), array(
			$this->activeId, $this->passIndex, $this->questionId
		));
		
		$excludedOptions = array();
		
		while($row = $DIC->database()->fetchAssoc($res))
		{
			$excludedOptions[] = $row['option_index'];
		}
		
		return $excludedOptions;
	}
	
	/**
	 * @param int[] $excludedOptions
	 */
	public function replaceExcludedOptions($excludedOptions)
	{
		if( !is_array($excludedOptions) )
		{
			$excludedOptions = array();
		}
		
		if( count($excludedOptions) )
		{
			foreach($excludedOptions as $k => $excludedOption)
			{
				$excludedOptions[$k] = (int)$excludedOption;
			}
		}
		
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		foreach($excludedOptions as $excludedOption)
		{
			$DIC->database()->replace('qpl_mc_opt_excluded', array(
				'active_fi' => array('integer', $this->activeId),
				'pass_index' => array('integer', $this->passIndex),
				'question_fi' => array('integer', $this->questionId),
				'option_index' => array('integer', $excludedOption)
			), array());
		}
		
		if( count($excludedOptions) )
		{
			$AND_NOT_IN_excludedOptions = 'AND ' . $DIC->database()->in('option_index', $excludedOptions, true, 'integer');
		}
		else
		{
			$AND_NOT_IN_excludedOptions = '';
		}
		
		$query = "
			DELETE FROM qpl_mc_opt_excluded
			WHERE active_fi = %s AND pass_index = %s AND question_fi = %s
			$AND_NOT_IN_excludedOptions
		";
		
		$DIC->database()->queryF($query, array('integer', 'integer', 'integer'), array(
			$this->activeId, $this->passIndex, $this->questionId
		));
	}
}
