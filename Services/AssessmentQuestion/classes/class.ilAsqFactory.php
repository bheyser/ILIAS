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
	
	public function questionPresentation() : ilAsqQuestionPresentationFactory
	{
		return new ilAsqQuestionPresentationFactory();
	}
	
	public function solutionPresentation() : ilAsqSolutionPresentationFactory
	{
		return new ilAsqSolutionPresentationFactory();
	}
	
	public function feedbackPresentation() : ilAsqFeedbackPresentationFactory
	{
		return new ilAsqFeedbackPresentationFactory();
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
		$questionType = $this->getQuestionTypeByQuestionId($questionId);
		
		$classnameProvider = $this->getClassnameProvider($questionType);
		$classname = $classnameProvider->getQuestionClassname();
		
		/* @var ilAsqQuestion $questionInstance */
		$questionInstance = new $classname();
		
		$questionInstance->setQuestionType($questionType);
		
		$questionInstance->setId($questionId);
		$questionInstance->load();
		
		return $questionInstance;
	}
	
	/**
	 * @param ilAsqQuestionType $questionType
	 * @param int $parentId
	 * @param string $additionalContentEditingMode
	 * @return ilAsqQuestion
	 * @throws ilAsqInvalidArgumentException
	 */
	public function getEmptyQuestionInstance(ilAsqQuestionType $questionType, int $parentId, string $additionalContentEditingMode) : ilAsqQuestion
	{
		$classnameProvider = $this->getClassnameProvider($questionType);
		$classname = $classnameProvider->getQuestionClassname();
		
		/* @var ilAsqQuestion $questionInstance */
		$questionInstance = new $classname();
		
		$questionInstance->setQuestionType($questionType);
		$questionInstance->setParentId($parentId);
		
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
	 * @param ilAsqQuestion $question
	 * @param integer $solutionId
	 * @return ilAsqQuestionSolution
	 * @throws ilAsqInvalidArgumentException
	 */
	public function getQuestionSolutionInstance(ilAsqQuestion $question, $solutionId) : ilAsqQuestionSolution
	{
		$classnameProvider = $this->getClassnameProvider($question->getQuestionType());
		$classname = $classnameProvider->getQuestionClassname();
		
		/* @var ilAsqQuestionSolution $solutionInstance */
		$solutionInstance = new $classname($question);
		$solutionInstance->setSolutionId($solutionId);
		$solutionInstance->load();
		
		return $solutionInstance;
	}
	
	/**
	 * @param ilAsqQuestion $question
	 * @return ilAsqQuestionSolution
	 */
	public function getEmptyQuestionSolutionInstance($question) : ilAsqQuestionSolution
	{
		$classnameProvider = $this->getClassnameProvider($question->getQuestionType());
		$classname = $classnameProvider->getQuestionClassname();

		$emptySolutionInstance = new $classname();
		$emptySolutionInstance->setQuestion($question);
		
		return $emptySolutionInstance;
	}
	
	/**
	 * @param ilAsqQuestionSolution $solutionInstance
	 * @return ilAsqResultCalculator
	 */
	public function getResultCalculator(ilAsqQuestionSolution $solutionInstance) : ilAsqResultCalculator
	{
		$resultCalculator; /* @var ilAsqResultCalculator $resultCalculator */
		
		/**
		 * initialise $resultCalculator as an instance of the question type corresponding object class
		 * that implements ilAsqResultCalculator depending on the given $questionInstance and $solutionInstance
		 */
		
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
		
		switch( $questionType->getTag() )
		{
			case 'assSingleChoice': return new ilAsqSingleChoiceClassnameProvider();
		}
		
		throw new ilAsqInvalidArgumentException(
			"invalid question type given: '{$questionType->getTag()}'"
		);
	}
	
	/**
	 * @param int $questionId
	 * @return ilAsqQuestionType
	 * @throws ilAsqInvalidArgumentException
	 */
	protected function getQuestionTypeByQuestionId($questionId)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$query = "
			SELECT qt.question_type_id type_id, qt.type_tag type_tag, qt.plugin is_plugin, qt.plugin_name plugin_name
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
			$questionType->setId($row['type_id']);
			$questionType->setTag($row['type_tag']);
			$questionType->setPluginType($row['is_plugin']);
			$questionType->setPluginName($row['plugin_name']);
			
			return $questionType;
		}
		
		throw new ilAsqInvalidArgumentException(
			"invalid question id given: '{$questionId}'"
		);
	}
	
	/**
	 * @param string $typeIdentifier
	 * @return ilAsqQuestionType
	 * @throws ilAsqInvalidArgumentException
	 */
	public function getQuestionTypeByTypeIdentifier($typeIdentifier)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$query = "
			SELECT qt.question_type_id type_id, qt.type_tag type_tag, qt.plugin is_plugin, qt.plugin_name plugin_name
			FROM qpl_qst_type qt
			WHERE qt.type_tag = %s
		";
		
		$res = $DIC->database()->queryF(
			$query, array('text'), array($typeIdentifier)
		);
		
		while( $row = $DIC->database()->fetchAssoc($res) )
		{
			$questionType = new ilAsqQuestionType();
			$questionType->setId($row['type_id']);
			$questionType->setTag($row['type_tag']);
			$questionType->setPluginType($row['is_plugin']);
			$questionType->setPluginName($row['plugin_name']);
			
			return $questionType;
		}
		
		throw new ilAsqInvalidArgumentException(
			"invalid qst type identifier given: '{$typeIdentifier}'"
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