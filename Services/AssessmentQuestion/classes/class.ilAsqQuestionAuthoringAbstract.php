<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqQuestionAuthoringAbstract
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package		Services/AssessmentQuestion
 */
abstract class ilAsqQuestionAuthoringAbstract implements ilAsqQuestionAuthoring
{
	/**
	 * Constants for Command Methods
	 */
	const CMD_SHOW_CONFIG_FORM = 'showQuestionConfig';
	
	/**
	 * @var ilAsqQuestion
	 */
	protected $question;
	
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
	public function getQuestion() : ilAsqQuestion
	{
		return $this->question;
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getEditQuestionConfigLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameter($this, 'qid', $this->getQuestion()->getId());
		
		$url = $DIC->ctrl()->getLinkTarget(
			array('ilAsqQuestionAuthoringGUI', get_class($this)), self::CMD_SHOW_CONFIG_FORM
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_edit_question_config_link'), $url
		);
	}
}
