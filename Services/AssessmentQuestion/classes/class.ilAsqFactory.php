<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilAsqQuestionAuthoringFactory
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package    Services/AssessmentQuestion
 */
class ilAsqFactory
{
	/**
	 * @return ilAsqService
	 */
	public function service() : ilAsqService
	{
		return new ilAsqService();
	}

	/**
	 * @param int $parentObjId
	 * @param int $parentRefId
	 * @param int[] $parentTaxonomyIds
	 * @param \ILIAS\UI\Component\Link\Link $parentBackLink
	 * @return ilAsqQuestionAuthoringGUI
	 */
	public function forwardAuthoringGUI(int $parentObjId, int $parentRefId,
		array $parentTaxonomyIds, \ILIAS\UI\Component\Link\Link $parentBackLink) : ilAsqQuestionAuthoringGUI
	{
		return new ilAsqQuestionAuthoringGUI($parentObjId, $parentRefId, $parentTaxonomyIds, $parentBackLink);
	}
	
	/**
	 * @param integer $parentObjectId
	 * @return array
	 */
	public function getQuestionDataArray($parentObjectId) : array
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		global $ilPluginAdmin; /* @var ilPluginAdmin $ilPluginAdmin */
		
		$list = new ilAssQuestionList($DIC->database(), $DIC->language(), $ilPluginAdmin);
		$list->setParentObjIdsFilter(array($parentObjectId));
		$list->load();
		
		return $list->getQuestionDataArray(); // returns an array of arrays containing the question data
		
		/**
		 * TBD: Should we return an iterator with ilAsqQuestion instances?
		 * Issue: ilTable(2) does not support this kind object structure.
		 */
	}
	
	/**
	 * @param integer $parentObjectId
	 * @return ilAsqQuestion[]
	 */
	public function getQuestionInstances($parentObjectId) : array
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		global $ilPluginAdmin; /* @var ilPluginAdmin $ilPluginAdmin */
		
		$list = new ilAssQuestionList($DIC->database(), $DIC->language(), $ilPluginAdmin);
		$list->setParentObjIdsFilter(array($parentObjectId));
		$list->load();
		
		$questionInstances = array();
		
		foreach($list->getQuestionDataArray() as $questionId => $questionData)
		{
			$questionInstances[] = $this->getQuestionInstance($questionId);
		}
		
		return $questionInstances;
	}
	
	/**
	 * @param ilAsqQuestion $questionInstance
	 * @return ilAsqQuestionAuthoring
	 * @throws ilAsqInvalidArgumentException
	 */
	public function getAuthoringCommandInstance($questionInstance) : ilAsqQuestionAuthoring
	{
		$classnameProvider = $this->getClassnameProvider($questionInstance->getQuestionType());
		$classname = $classnameProvider->getAuthoringClassname();
		
		/* @var ilAsqQuestionAuthoring $authoringGUI */
		$authoringGUI = new $classname();
		
		$authoringGUI->setQuestion($questionInstance);
		
		return $authoringGUI;
	}
	
	/**
	 * render purpose constants that are required to get corresponding presentation renderer
	 */
	const RENDER_PURPOSE_PLAYBACK = 'renderPurposePlayback'; // e.g. Test Player
	const RENDER_PURPOSE_DEMOPLAY = 'renderPurposeDemoplay'; // e.g. Page Editing View in Test
	const RENDER_PURPOSE_PREVIEW = 'renderPurposePreview'; // e.g. Preview Player
	const RENDER_PURPOSE_PRINT_PDF = 'renderPurposePrintPdf'; // When used for PDF rendering
	const RENDER_PURPOSE_INPUT_VALUE = 'renderPurposeInputValue'; // When used as RTE Input Content
	
	/**
	 * @param ilAsqQuestion $questionInstance
	 * @return ilAsqQuestionPresentation
	 */
	public function getQuestionPresentationInstance($questionInstance, $renderPurpose) : ilAsqQuestionPresentation
	{
		$presentationGUI; /* @var ilAsqQuestionPresentation $presentationGUI */
		
		/**
		 * initialise $presentationGUI as an instance of the question type corresponding presentation class
		 * that implements ilAsqQuestionPresentation depending on the given $questionInstance
		 * and depending on the given render purpose.
		 */
		
		$presentationGUI->setQuestion($questionInstance);
		
		return $presentationGUI;
	}
	
	/**
	 * @param integer $questionId
	 * @return ilAsqQuestion
	 * @throws ilAsqInvalidArgumentException
	 */
	public function getQuestionInstance($questionId) : ilAsqQuestion
	{
		$questionType = $this->getQuestionType($questionId);
		
		$classnameProvider = $this->getClassnameProvider($questionType);
		$classname = $classnameProvider->getInstanceClassname();
		
		/* @var ilAsqQuestion $questionInstance */
		$questionInstance = new $classname();
		
		$questionInstance->setQuestionType($questionType);
		
		$questionInstance->setId($questionId);
		$questionInstance->load();
		
		return $questionInstance;
	}
	
	/**
	 * @param string $questionId
	 * @return ilAsqQuestion
	 */
	public function getEmptyQuestionInstance($questionType) : ilAsqQuestion
	{
		$questionInstance; /* @var ilAsqQuestion $questionInstance */
		
		/**
		 * initialise $questionInstance as an instance of the question type corresponding object class
		 * that implements ilAsqQuestion depending on the given $questionType
		 */
		
		return $questionInstance;
	}
	
	/**
	 * @param integer $questionId
	 * @return ilAsqQuestion
	 */
	public function getOfflineExportableQuestionInstance($questionId, $a_image_path = null, $a_output_mode = 'presentation') : ilAsqQuestion
	{
		$questionInstance; /* @var ilAsqQuestion $questionInstance */
		
		/**
		 * initialise $questionInstance as an instance of the question type corresponding object class
		 * that implements ilAsqQuestion depending on the given $questionId
		 */
		
		$questionInstance->setId($questionId);
		$questionInstance->load();
		
		$questionInstance->setOfflineExportImagePath($a_image_path);
		$questionInstance->setOfflineExportPagePresentationMode($a_output_mode);
		
		return $questionInstance;
	}
	
	/**
	 * @param ilAsqQuestion $offlineExportableQuestionInstance
	 * @return ilAsqQuestionOfflinePresentationExporter
	 */
	public function getQuestionOfflinePresentationExporter(ilAsqQuestion $offlineExportableQuestionInstance)
	{
		$qstOffPresentationExporter; /* @var ilAsqQuestionOfflinePresentationExporter $qstOffPresentationExporter */
		
		/**
		 * initialise $qstOffPresentationExporter as an instance of the question type corresponding
		 * object class that implements ilAsqQuestionOfflinePresentationExporter
		 * depending on the given $offlineExportableQuestionInstance
		 */
		
		$qstOffPresentationExporter->setQuestion($offlineExportableQuestionInstance);
		
		return $qstOffPresentationExporter;
	}
	
	/**
	 * @return ilAsqQuestionResourcesCollector
	 */
	public function getQuestionResourcesCollector()
	{
		/**
		 * this collector is able to manage all kind resources that aredepencies
		 * for the offline presentation of a question (like js/css, media files, mobs).
		 */
		
		return new ilAsqQuestionResourcesCollector();
	}
	
	/**
	 * @param integer $questionId
	 * @param integer $solutionId
	 * @return ilAsqQuestionSolution
	 */
	public function getQuestionSolutionInstance($questionId, $solutionId) : ilAsqQuestionSolution
	{
		$questionSolutionInstance; /* @var ilAsqQuestionSolution $questionSolutionInstance */
		
		/**
		 * initialise $questionSolutionInstance as an instance of the question type corresponding object class
		 * that implements ilAsqQuestionSolution depending on the given $questionId and $solutionId
		 */
		$questionSolutionInstance->setQuestionId($questionId);
		$questionSolutionInstance->setSolutionId($solutionId);
		$questionSolutionInstance->load();
		
		return $questionSolutionInstance;
	}
	
	/**
	 * @param integer $questionId
	 * @return ilAsqQuestionSolution
	 */
	public function getEmptyQuestionSolutionInstance($questionId) : ilAsqQuestionSolution
	{
		$emptySolutionInstance; /* @var ilAsqQuestionSolution $questionSolutionInstance */
		
		/**
		 * initialise $emptySolutionInstance as an instance of the question type corresponding object class
		 * that implements ilAsqQuestionSolution depending on the given $questionId
		 */
		
		$emptySolutionInstance->setQuestionId($questionId);
		
		return $emptySolutionInstance;
	}
	
	/**
	 * @param ilAsqQuestion $questionInstance
	 * @param ilAsqQuestionSolution $solutionInstance
	 * @return ilAsqResultCalculator
	 */
	public function getResultCalculator(ilAsqQuestion $questionInstance, ilAsqQuestionSolution $solutionInstance) : ilAsqResultCalculator
	{
		$resultCalculator; /* @var ilAsqResultCalculator $resultCalculator */
		
		/**
		 * initialise $resultCalculator as an instance of the question type corresponding object class
		 * that implements ilAsqResultCalculator depending on the given $questionInstance and $solutionInstance
		 */
		
		$resultCalculator->setQuestion($questionInstance);
		$resultCalculator->setSolution($solutionInstance);
		
		return $resultCalculator;
	}
	
	
	/**
	 * @param ilAsqQuestionType $questionType
	 * @return ilAsqQuestionClassnameProvider
	 * @throws ilAsqInvalidArgumentException
	 */
	protected function getClassnameProvider(ilAsqQuestionType $questionType)
	{
		if( $questionType->isPluginType() )
		{
			// TODO: ask plugin object for class name provider and return
		}
		
		switch( $questionType->getIdentifier() )
		{
			case 'assSingleChoice': return new ilAsqSingleChoiceClassnameProvider();
		}
		
		throw new ilAsqInvalidArgumentException(
			"invalid question type given: '{$questionType->getIdentifier()}'"
		);
	}
	
	/**
	 * @param int $questionId
	 * @return ilAsqQuestionType
	 * @throws ilAsqInvalidArgumentException
	 */
	protected function getQuestionType($questionId)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$query = "
			SELECT qt.type_tag question_type, qt.plugin is_plugin
			FROM qpl_questions q
			INNER JOIN qpl_qst_type qt
			ON q.question_type_fi = qt.question_type_id
			WHERE q.question_id = %s
		";
		
		$res = $DIC->database()->queryF(
			$query, array('integer'), array($questionId)
		);
		
		while( $row = $DIC->database()->fetchAssoc($res) )
		{
			$questionType = new ilAsqQuestionType();
			$questionType->setIdentifier($row['question_type']);
			$questionType->setPluginType($row['is_plugin']);
			$questionType->setPluginName($row['plugin_name']);
			
			return $questionType;
		}
		
		throw new ilAsqInvalidArgumentException(
			"invalid question id given: '{$questionId}'"
		);
	}
	
	/**
	 * @param ilAsqQuestion $questionInstance
	 * @return ilAsqQuestionConfigForm
	 * @throws ilAsqInvalidArgumentException
	 */
	public function getQuestionConfigForm(ilAsqQuestionAuthoring $questionAuthoring)
	{
		$classnameProvider = $this->getClassnameProvider($questionAuthoring->getQuestion()->getQuestionType());
		$classname = $classnameProvider->getConfigFormClassname();
		
		/* @var ilAsqQuestionConfigForm $questionConfigForm */
		$questionConfigForm = new $classname($questionAuthoring->getQuestion(), $questionAuthoring->getTaxonomies());
		
		$questionConfigForm->init($questionAuthoring);
		
		return $questionConfigForm;
	}
}