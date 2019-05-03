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
 * @ilCtrl_Calls ilAsqQuestionAuthoringGUI: ilAssQuestionPreviewGUI
 * @ilCtrl_Calls ilAsqQuestionAuthoringGUI: ilAssQuestionPageGUI
 * @ilCtrl_Calls ilAsqQuestionAuthoringGUI: ilAssQuestionFeedbackEditingGUI
 * @ilCtrl_Calls ilAsqQuestionAuthoringGUI: ilAssQuestionHintsGUI
 * @ilCtrl_Calls ilAsqQuestionAuthoringGUI: ilCommonActionDispatcherGUI
 * @ilCtrl_Calls ilAsqQuestionAuthoringGUI: ilFormPropertyDispatchGUI
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
	const TAB_ID_HINTS = 'qst_hints_tab';
	const TAB_ID_SUGGESTED_SOLUTION = 'qst_suggested_solution_tab';
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
	 * @var \ILIAS\UI\Component\Link\Standard
	 */
	protected $parentBackLink;
	
	/**
	 * ilAsqQuestionAuthoringGUI constructor.
	 * @param int $parentObjId
	 * @param int $parentRefId
	 * @param int[] $parentTaxonomyIds
	 * @param \ILIAS\UI\Component\Link\Link $parentBackLink
	 */
	public function __construct(int $parentObjId, int $parentRefId,
		array $parentTaxonomyIds, \ILIAS\UI\Component\Link\Standard $parentBackLink)
	{
		$this->setParentObjId($parentObjId);
		$this->setParentRefId($parentRefId);
		$this->setParentTaxonomyIds($parentTaxonomyIds);
		$this->setParentBackLink($parentBackLink);
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
	 * @return \ILIAS\UI\Component\Link\Standard
	 */
	public function getParentBackLink(): \ILIAS\UI\Component\Link\Standard
	{
		return $this->parentBackLink;
	}
	
	/**
	 * @param \ILIAS\UI\Component\Link\Standard $parentBackLink
	 */
	public function setParentBackLink(\ILIAS\UI\Component\Link\Standard $parentBackLink)
	{
		$this->parentBackLink = $parentBackLink;
	}
	
	/**
	 * @throws ilCtrlException
	 * @throws ilAsqInvalidArgumentException
	 */
	public function executeCommand()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$question = $DIC->question()->getQuestionInstance($_GET['qid']);
		$question->setParentId($this->getParentObjId());
		
		$questionAuthoringGUI = $DIC->question()->getAuthoringCommandInstance($question);
		$questionAuthoringGUI->setTaxonomies($this->getParentTaxonomyIds());
		$questionAuthoringGUI->setBackLink($this->getParentBackLink());

		$this->initTabs($questionAuthoringGUI);
		$this->initHeaderAction($questionAuthoringGUI);
		
		switch( $DIC->ctrl()->getNextClass($this) )
		{
			case strtolower(get_class($questionAuthoringGUI)):
				
				$DIC->ctrl()->forwardCommand($questionAuthoringGUI);
				break;
			
			case 'ilcommonactiondispatchergui':

				$DIC->ctrl()->forwardCommand(ilCommonActionDispatcherGUI::getInstanceFromAjaxCall());
				break;
				
			case 'ilformpropertydispatchgui':
				
				$form = $DIC->question()->getQuestionConfigForm($questionAuthoringGUI);
				$form_prop_dispatch = new ilFormPropertyDispatchGUI();
				$form_prop_dispatch->setItem($form->getItemByPostVar(ilUtil::stripSlashes($_GET['postvar'])));
				return $DIC->ctrl()->forwardCommand($form_prop_dispatch);
				break;
			
			
			case 'ilasqquestionpreviewgui':
				
				$DIC->tabs()->activateTab(self::TAB_ID_PREVIEW);
				
				$gui = new ilAssQuestionPreviewGUI(
					$DIC->ctrl(), $DIC->tabs(), $DIC->ui()->mainTemplate(),
					$DIC->language(), $DIC->database(), $DIC->user()
				);
				
				$gui->initQuestion((int)$_GET['qid'], $this->getParentObjId());
				$gui->initPreviewSettings($this->getParentRefId());
				$gui->initPreviewSession($DIC->user()->getId(), (int)$_GET['qid']);
				$gui->initHintTracking();
				$gui->initStyleSheets();
				
				$ilHelp = $DIC['ilHelp'];
				$ilHelp->setScreenIdComponent("qpl");
				
				$DIC->ctrl()->forwardCommand($gui);
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
			
			case 'ilassquestionfeedbackeditinggui':
				
				$DIC->tabs()->activateTab(self::TAB_ID_FEEDBACK);

				// set return target
				$this->ctrl->setReturn($this, "questions");
				
				// set context tabs
				require_once 'Modules/TestQuestionPool/classes/class.assQuestionGUI.php';
				$questionGUI = assQuestionGUI::_getQuestionGUI($q_type, $_GET['q_id']);
				$questionGUI->object->setObjId($this->object->getId());
				$questionGUI->setQuestionTabs();
				global $DIC;
				$ilHelp = $DIC['ilHelp'];
				$ilHelp->setScreenIdComponent("qpl");
				
				if( $this->object->getType() == 'qpl' && $writeAccess )
				{
					$questionGUI->addHeaderAction();
				}
				
				// forward to ilAssQuestionFeedbackGUI
				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionFeedbackEditingGUI.php';
				$gui = new ilAssQuestionFeedbackEditingGUI($questionGUI, $ilCtrl, $ilAccess, $tpl, $ilTabs, $lng);
				$ilCtrl->forwardCommand($gui);
				
				break;
			
			case 'ilassquestionhintsgui':
				
				$DIC->tabs()->activateTab(self::TAB_ID_HINTS);

				// set return target
				$this->ctrl->setReturn($this, "questions");
				
				// set context tabs
				require_once 'Modules/TestQuestionPool/classes/class.assQuestionGUI.php';
				$questionGUI = assQuestionGUI::_getQuestionGUI($q_type, $_GET['q_id']);
				$questionGUI->object->setObjId($this->object->getId());
				$questionGUI->setQuestionTabs();
				global $DIC;
				$ilHelp = $DIC['ilHelp'];
				$ilHelp->setScreenIdComponent("qpl");
				
				if( $this->object->getType() == 'qpl' && $writeAccess )
				{
					$questionGUI->addHeaderAction();
				}
				
				// forward to ilAssQuestionHintsGUI
				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionHintsGUI.php';
				$gui = new ilAssQuestionHintsGUI($questionGUI);
				
				$gui->setEditingEnabled(
					$DIC->access()->checkAccess('write', '', $this->object->getRefId())
				);
				
				$ilCtrl->forwardCommand($gui);
				
				break;
				
			default:
				
				$command = $DIC->ctrl()->getCmd();
				$this->$command();
		}
	}
	
	/**
	 * @param ilAsqQuestionAuthoring $authoringGUI
	 */
	protected function redrawHeaderAction(ilAsqQuestionAuthoring $authoringGUI)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		echo $this->getHeaderAction($authoringGUI) . $DIC->ui()->mainTemplate()->getOnLoadCodeForAsynch();
		exit;
	}
	
	/**
	 * @param ilAsqQuestionAuthoring $authoringGUI
	 */
	protected function initHeaderAction(ilAsqQuestionAuthoring $authoringGUI)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ui()->mainTemplate()->setVariable(
			'HEAD_ACTION', $this->getHeaderAction($authoringGUI)
		);
		
		$notesUrl = $DIC->ctrl()->getLinkTargetByClass(
			array('ilCommonActionDispatcherGUI', 'ilNoteGUI'), '', '', true, false
		);
		
		ilNoteGUI::initJavascript($notesUrl,IL_NOTE_PUBLIC, $DIC->ui()->mainTemplate());
		
		$redrawActionsUrl = $DIC->ctrl()->getLinkTarget($this, 'redrawHeaderAction', '', true);
		$DIC->ui()->mainTemplate()->addOnLoadCode("il.Object.setRedrawAHUrl('$redrawActionsUrl');");
	}
	
	/**
	 * @param ilAsqQuestionAuthoring $authoringGUI
	 * @return string
	 */
	protected function getHeaderAction(ilAsqQuestionAuthoring $authoringGUI)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		/* @var ilObjectDataCache $ilObjDataCache */
		$ilObjDataCache = $DIC['ilObjDataCache'];
		
		$parentObjType = $ilObjDataCache->lookupType($this->getParentObjId());
		
		$dispatcher = new ilCommonActionDispatcherGUI(
			ilCommonActionDispatcherGUI::TYPE_REPOSITORY,
			$DIC->access(), $parentObjType, $this->getParentRefId(), $this->getParentObjId()
		);
		
		$dispatcher->setSubObject('quest', $authoringGUI->getQuestion()->getId());
		
		$ha = $dispatcher->initHeaderAction();
		$ha->enableComments(true, false);
		
		return $ha->getHeaderAction($DIC->ui()->mainTemplate());
	}
	
	/**
	 * @param ilAsqQuestionAuthoring $questionAuthoringGUI
	 */
	protected function initTabs(ilAsqQuestionAuthoring $questionAuthoringGUI)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$writeAccess = $DIC->rbac()->system()->checkAccess('write', $this->getParentRefId());
		
		$DIC->tabs()->clearTargets();
		
		$DIC->tabs()->setBackTarget($this->getParentBackLink()->getLabel(), $this->getParentBackLink()->getAction());
		
		if( $writeAccess )
		{
			$link = $questionAuthoringGUI->getEditQuestionPageLink();
			$DIC->tabs()->addTab(self::TAB_ID_PAGEVIEW, $link->getLabel(), $link->getAction());
		}
		
		$link = $questionAuthoringGUI->getPreviewLink();
		$DIC->tabs()->addTab(self::TAB_ID_PREVIEW, $link->getLabel(), $link->getAction());
		
		if( $writeAccess )
		{
			$link = $questionAuthoringGUI->getEditQuestionConfigLink();
			$DIC->tabs()->addTab(self::TAB_ID_CONFIG, $link->getLabel(), $link->getAction());
		}
		
		$link = $questionAuthoringGUI->getEditFeedbacksLink();
		$DIC->tabs()->addTab(self::TAB_ID_FEEDBACK, $link->getLabel(), $link->getAction());
		
		$link = $questionAuthoringGUI->getEditHintsLink();
		$DIC->tabs()->addTab(self::TAB_ID_HINTS, $link->getLabel(), $link->getAction());
		
		$link = $questionAuthoringGUI->getEditSuggestedSolutionLink();
		$DIC->tabs()->addTab(self::TAB_ID_SUGGESTED_SOLUTION, $link->getLabel(), $link->getAction());
		
		$link = $questionAuthoringGUI->getStatisticLink();
		$DIC->tabs()->addTab(self::TAB_ID_STATISTIC, $link->getLabel(), $link->getAction());
	}
}
