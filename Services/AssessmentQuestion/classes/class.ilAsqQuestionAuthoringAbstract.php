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
	const CMD_SHOW_SUGGESTED_SOLUTION_FORM = 'showSuggestedSolutionForm';
	const CMD_SHOW_STATISTICS = 'showStatistics';
	
	/**
	 * @var ilAsqQuestion
	 */
	protected $question;
	
	/**
	 * @var \ILIAS\UI\Component\Link\Standard
	 */
	protected $backLink;
	
	/**
	 * @var int[]
	 */
	protected $taxonomies;
	
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
	 * @param \ILIAS\UI\Component\Link\Standard $backLink
	 */
	public function setBackLink(\ILIAS\UI\Component\Link\Standard $backLink)
	{
		$this->backLink = $backLink;
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getBackLink()
	{
		return $this->backLink;
	}
	
	/**
	 * @param int[] $taxonomies
	 */
	public function setTaxonomies($taxonomies)
	{
		$this->taxonomies = $taxonomies;
	}
	
	/**
	 * @return int[]
	 */
	public function getTaxonomies()
	{
		return $this->taxonomies;
	}
	
	/**
	 * Execute Command
	 */
	public function executeCommand()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		switch($DIC->ctrl()->getNextClass($this))
		{
			default:
				
				$command = $DIC->ctrl()->getCmd(self::CMD_SHOW_CONFIG_FORM);
				$command = $this->manipulateCommand($command);
				
				$ret = $this->$command();
				break;
		}
		return $ret;
	}
	
	protected function manipulateCommand($command)
	{
		return $command;
	}
	
	/**
	 * @param ilAsqQuestionConfigForm
	 * @throws ilAsqInvalidArgumentException
	 */
	public function showQuestionConfig(ilAsqQuestionConfigForm $form = null)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->tabs()->activateTab(ilAsqQuestionAuthoringGUI::TAB_ID_CONFIG);
		
		if( $form === null )
		{
			$form = $DIC->question()->getQuestionConfigForm($this);
		}
		
		$DIC->ui()->mainTemplate()->setContent($form->getHTML());
	}
	
	public function addQuestionChangeListener(ilQuestionChangeListener $listener)
	{
		// TODO: Implement addQuestionChangeListener() method.
	}
	
	public function addNewIdListener($a_object, $a_method, $a_parameters = "")
	{
		// TODO: Implement addNewIdListener() method.
	}
	
	public function callNewIdListeners($a_new_question_id)
	{
		// TODO: Implement callNewIdListeners() method.
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getPreviewLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameterByClass(
			'ilAssQuestionPreviewGUI', 'qid', $this->getQuestion()->getId()
		);
		
		$url = $DIC->ctrl()->getLinkTargetByClass(
			array('ilAsqQuestionAuthoringGUI', 'ilAssQuestionPreviewGUI'), ilAssQuestionPreviewGUI::CMD_SHOW
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_question_preview_link'), $url
		);
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getEditQuestionPageLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameterByClass(
			'ilAssQuestionPageGUI', 'qid', $this->getQuestion()->getId()
		);
		
		$url = $DIC->ctrl()->getLinkTargetByClass(
			array('ilAsqQuestionAuthoringGUI', 'ilAssQuestionPageGUI'), 'edit'
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_edit_question_page_link'), $url
		);
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getEditQuestionConfigLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameter($this, 'qid', $this->getQuestion()->getId());
		
		$url = $DIC->ctrl()->getLinkTargetByClass(
			array('ilAsqQuestionAuthoringGUI', get_class($this)), self::CMD_SHOW_CONFIG_FORM
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_edit_question_config_link'), $url
		);
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getEditFeedbacksLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameterByClass(
			'ilAssQuestionFeedbackEditingGUI', 'qid', $this->getQuestion()->getId()
		);
		
		$url = $DIC->ctrl()->getLinkTargetByClass(
			array('ilAsqQuestionAuthoringGUI', 'ilAssQuestionFeedbackEditingGUI'), ilAssQuestionFeedbackEditingGUI::CMD_SHOW
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_question_feedback_link'), $url
		);
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getEditHintsLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameterByClass(
			'ilAssQuestionHintsGUI', 'qid', $this->getQuestion()->getId()
		);
		
		$url = $DIC->ctrl()->getLinkTargetByClass(
			array('ilAsqQuestionAuthoringGUI', 'ilAssQuestionHintsGUI'), ilAssQuestionHintsGUI::CMD_SHOW_LIST
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_question_hints_link'), $url
		);
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getEditSuggestedSolutionLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameter($this, 'qid', $this->getQuestion()->getId());
		
		$url = $DIC->ctrl()->getLinkTargetByClass(
			array('ilAsqQuestionAuthoringGUI', get_class($this)), self::CMD_SHOW_SUGGESTED_SOLUTION_FORM
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_question_suggested_solution_link'), $url
		);
	}
	
	/**
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getStatisticLink(): \ILIAS\UI\Component\Link\Standard
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameter($this, 'qid', $this->getQuestion()->getId());
		
		$url = $DIC->ctrl()->getLinkTargetByClass(
			array('ilAsqQuestionAuthoringGUI', get_class($this)), self::CMD_SHOW_STATISTICS
		);
		
		return $DIC->ui()->factory()->link()->standard(
			$DIC->language()->txt('asq_question_statistics_link'), $url
		);
	}
}
