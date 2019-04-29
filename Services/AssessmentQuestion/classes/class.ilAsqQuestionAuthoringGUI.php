<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqQuestionAuthoringGUI
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 *
 * @ilCtrl_Calls ilAsqQuestionAuthoringGUI: ilAsqSingleChoiceAuthoringGUI
 */
class ilAsqQuestionAuthoringGUI
{
	/**
	 * Constants for UI Tabs
	 */
	const TAB_ID_PREVIEW = 'qst_preview_tab';
	const TAB_ID_PAGEVIEW = 'qst_pageview_tab';
	const TAB_ID_CONFIG = 'qst_config_tab';
	const TAB_ID_FEEDBACK = 'qst_feedback_tab';
	const TAB_ID_STATISTIC = 'qst_statistic_tab';
	
	/**
	 * @var int
	 */
	protected $parentObjId;
	
	/**
	 * @var int
	 */
	protected $parentRefId;
	
	/**
	 * @var int[]
	 */
	protected $parentTaxonomyIds;
	
	/**
	 * ilAsqQuestionAuthoringGUI constructor.
	 * @param int $parentObjId
	 * @param int $parentRefId
	 * @param int[] $parentTaxonomyIds
	 */
	public function __construct(int $parentObjId, int $parentRefId, array $parentTaxonomyIds)
	{
		$this->setParentObjId($parentObjId);
		$this->setParentRefId($parentRefId);
		$this->setParentTaxonomyIds($parentTaxonomyIds);
	}
	
	/**
	 * @return int
	 */
	public function getParentObjId(): int
	{
		return $this->parentObjId;
	}
	
	/**
	 * @param int $parentObjId
	 */
	public function setParentObjId(int $parentObjId)
	{
		$this->parentObjId = $parentObjId;
	}
	
	/**
	 * @return int
	 */
	public function getParentRefId(): int
	{
		return $this->parentRefId;
	}
	
	/**
	 * @param int $parentRefId
	 */
	public function setParentRefId(int $parentRefId)
	{
		$this->parentRefId = $parentRefId;
	}
	
	/**
	 * @return int[]
	 */
	public function getParentTaxonomyIds(): array
	{
		return $this->parentTaxonomyIds;
	}
	
	/**
	 * @param int[] $parentTaxonomyIds
	 */
	public function setParentTaxonomyIds(array $parentTaxonomyIds)
	{
		$this->parentTaxonomyIds = $parentTaxonomyIds;
	}
	
	/**
	 * @throws ilCtrlException
	 * @throws ilAsqInvalidArgumentException
	 */
	public function executeCommand()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$questionAuthoringGUI = $DIC->question()->getAuthoringCommandInstance(
			$DIC->question()->getQuestionInstance($_GET['qid'])
		);

		$questionAuthoringGUI->setTaxonomies($this->getParentTaxonomyIds());

		$this->initTabs($questionAuthoringGUI);
		
		switch( $DIC->ctrl()->getNextClass($this) )
		{
			case strtolower(get_class($questionAuthoringGUI)):
				
				$DIC->ctrl()->forwardCommand($questionAuthoringGUI);
				break;
			
			
			case 'ilassquestionpagegui':
				
				$DIC->tabs()->activateTab(self::TAB_ID_PAGEVIEW);
				
				$DIC->ui()->mainTemplate()->setCurrentBlock("ContentStyle");
				$DIC->ui()->mainTemplate()->setVariable("LOCATION_CONTENT_STYLESHEET", ilObjStyleSheet::getContentStylePath(0));
				$DIC->ui()->mainTemplate()->parseCurrentBlock();
				
				// syntax style
				$DIC->ui()->mainTemplate()->setCurrentBlock("SyntaxStyle");
				$DIC->ui()->mainTemplate()->setVariable("LOCATION_SYNTAX_STYLESHEET", ilObjStyleSheet::getSyntaxStylePath());
				$DIC->ui()->mainTemplate()->parseCurrentBlock();
				
				include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
				$q_gui = assQuestionGUI::_getQuestionGUI("", $_GET["q_id"]);
				$q_gui->setRenderPurpose(assQuestionGUI::RENDER_PURPOSE_PREVIEW);
				$q_gui->setQuestionTabs();
				$q_gui->outAdditionalOutput();
				$q_gui->object->setObjId($this->getParentObjId());
				
				$q_gui->setTargetGuiClass(null);
				$q_gui->setQuestionActionCmd(null);
				
				if( $parentQuestionType == 'qpl' )
				{
					$q_gui->addHeaderAction();
				}
				
				$question = $q_gui->object;
				$DIC->ctrl()->saveParameter($this, "q_id");
				include_once("./Modules/TestQuestionPool/classes/class.ilAssQuestionPageGUI.php");
				$DIC->language()->loadLanguageModule("content");
				$DIC->ctrl()->setReturnByClass("ilAssQuestionPageGUI", "view");
				$DIC->ctrl()->setReturn($this, "questions");
				
				$pageGUI = new ilAssQuestionPageGUI($_GET["q_id"]);
				$pageGUI->obj->addUpdateListener(
					$question,
					'updateTimestamp'
				);
				$pageGUI->setEditPreview(true);
				$pageGUI->setEnabledTabs(false);
				if (strlen($DIC->ctrl()->getCmd()) == 0 && !isset($_POST["editImagemapForward_x"])) // workaround for page edit imagemaps, keep in mind
				{
					$DIC->ctrl()->setCmdClass(get_class($pageGUI));
					$DIC->ctrl()->setCmd("preview");
				}
				$pageGUI->setQuestionHTML(array($q_gui->object->getId() => $q_gui->getPreview(TRUE)));
				$pageGUI->setTemplateTargetVar("ADM_CONTENT");
				$pageGUI->setOutputMode("edit");
				$pageGUI->setHeader($question->getTitle());
				$pageGUI->setPresentationTitle($question->getTitle());
				
				$ret = $DIC->ctrl()->forwardCommand($pageGUI);
				$DIC->ui()->mainTemplate()->setContent($ret);
				
				break;
		}
	}
	
	public function initTabs(ilAsqQuestionAuthoring $questionAuthoringGUI)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$writeAccess = $DIC->rbac()->system()->checkAccess('write', $this->getParentRefId());
		
		$DIC->tabs()->clearTargets();
		
		if( $writeAccess )
		{
			$DIC->ctrl()->setParameterByClass(
				'ilAssQuestionPageGUI', 'qid', $questionAuthoringGUI->getQuestion()->getId()
			);
			
			$DIC->tabs()->addTab(self::TAB_ID_PAGEVIEW, $DIC->language()->txt(self::TAB_ID_PAGEVIEW),
				$DIC->ctrl()->getLinkTargetByClass('ilAssQuestionPageGUI', 'edit')
			);
		}
			
		//$this->addTab_QuestionPreview($ilTabs);
		
		if( $writeAccess )
		{
			$DIC->ctrl()->setParameter(
				$questionAuthoringGUI, 'qid', $questionAuthoringGUI->getQuestion()->getId()
			);
			
			$DIC->tabs()->addTab(self::TAB_ID_CONFIG, $DIC->language()->txt(self::TAB_ID_CONFIG),
				$DIC->ctrl()->getLinkTarget($questionAuthoringGUI,
					ilAsqQuestionAuthoringAbstract::CMD_SHOW_CONFIG_FORM
				)
			);
		}
	}
}
