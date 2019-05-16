<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqQuestionPreviewGUI
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 *
 * @ilCtrl_Calls ilAsqQuestionPreviewGUI: ilAsqQuestionPreviewToolbarGUI
 * @ilCtrl_Calls ilAsqQuestionPreviewGUI: ilAsqQuestionRelatedNavigationBarGUI
 * @ilCtrl_Calls ilAsqQuestionPreviewGUI: ilAsqQuestionHintRequestGUI
 * @ilCtrl_Calls ilAsqQuestionPreviewGUI: ilAsqGenFeedbackPageGUI
 * @ilCtrl_Calls ilAsqQuestionPreviewGUI: ilAsqSpecFeedbackPageGUI
 * @ilCtrl_Calls ilAsqQuestionPreviewGUI: ilNoteGUI
 */
class ilAsqQuestionPreviewGUI
{
	const CMD_SHOW = 'show';
	const CMD_RESET = 'reset';
	const CMD_INSTANT_RESPONSE = 'instantResponse';
	const CMD_HANDLE_QUESTION_ACTION = 'handleQuestionAction';
	const CMD_GATEWAY_CONFIRM_HINT_REQUEST = 'gatewayConfirmHintRequest';
	const CMD_GATEWAY_SHOW_HINT_LIST = 'gatewayShowHintList';
	
	const FEEDBACK_FOCUS_ANCHOR = 'focus';
	
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	
	/**
	 * @var ilGlobalTemplate
	 */
	protected $tpl;
	
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	
	/**
	 * @var ilDBInterface
	 */
	protected $db;
	
	/**
	 * @var ilObjUser
	 */
	protected $user;
	
	/**
	 * @var ilAsqQuestionAuthoring
	 */
	protected $qstAuthoringGUI;
	
	/**
	 * @var ilAsqQuestionPreviewSettings
	 */
	protected $previewSettings;
	
	/**
	 * @var ilAsqQuestionPreviewSession
	 */
	protected $previewSession;
	
	/**
	 * @var ilAsqQuestionPreviewHintTracking
	 */
	protected $hintTracking;
	
	public function __construct(ilCtrl $ctrl, ilTabsGUI $tabs, ilGlobalTemplate $tpl, ilLanguage $lng, ilDBInterface $db, ilObjUser $user)
	{
		$this->ctrl = $ctrl;
		$this->tabs = $tabs;
		$this->tpl = $tpl;
		$this->lng = $lng;
		$this->db = $db;
		$this->user = $user;
	}
	
	/**
	 * @param $questionId
	 * @param $parentObjId
	 * @throws ilAsqInvalidArgumentException
	 */
	public function initQuestion($questionId, $parentObjId)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$this->qstAuthoringGUI = $DIC->question()->getAuthoringCommandInstance(
			$DIC->question()->getQuestionInstance($questionId)
		);
		
		#$this->questionGUI = assQuestion::instantiateQuestionGUI($questionId);
		#$this->questionOBJ = $this->questionGUI->object;
		
		#$this->questionGUI->populateJavascriptFilesRequiredForWorkForm($this->tpl);
		
		#$this->questionGUI->setTargetGui($this);
		#$this->questionGUI->setQuestionActionCmd(self::CMD_HANDLE_QUESTION_ACTION);
	}
	
	public function initPreviewSettings($parentRefId)
	{
		$this->previewSettings = new ilAsqQuestionPreviewSettings($parentRefId);
		
		$this->previewSettings->init();
	}
	
	public function initPreviewSession($userId, $questionId)
	{
		$this->previewSession = new ilAsqQuestionPreviewSession($userId, $questionId);
		
		$this->previewSession->init();
	}
	
	public function initHintTracking()
	{
		$this->hintTracking = new ilAsqQuestionPreviewHintTracking($this->db, $this->previewSession);
	}
	
	public function initStyleSheets()
	{
		include_once("./Services/Style/Content/classes/class.ilObjStyleSheet.php");
		
		$this->tpl->setCurrentBlock("ContentStyle");
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", ilObjStyleSheet::getContentStylePath(0));
		$this->tpl->parseCurrentBlock();
		
		$this->tpl->setCurrentBlock("SyntaxStyle");
		$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET", ilObjStyleSheet::getSyntaxStylePath());
		$this->tpl->parseCurrentBlock();
	}
	
	public function executeCommand()
	{
		$this->lng->loadLanguageModule('content');
		
		$nextClass = $this->ctrl->getNextClass($this);
		
		switch ($nextClass) {
			case 'ilasqquestionhintrequestgui':
				
				$gui = new ilAsqQuestionHintRequestGUI($this, self::CMD_SHOW, $this->questionGUI, $this->hintTracking);
				
				$this->ctrl->forwardCommand($gui);
				
				break;
			
			case 'ilasqspecfeedbackpagegui':
			case 'ilasqgenfeedbackpagegui':

				$forwarder = new ilAsqQuestionFeedbackPageObjectCommandForwarder($this->questionOBJ, $this->ctrl, $this->tabs, $this->lng);
				$forwarder->forward();
				break;
			
			case 'ilnotegui':
				
				$notesGUI = new ilNoteGUI($this->questionOBJ->getObjId(), $this->questionOBJ->getId(), 'quest');
				$notesGUI->enablePublicNotes(true);
				$notesGUI->enablePublicNotesDeletion(true);
				$notesPanelHTML = $this->ctrl->forwardCommand($notesGUI);
				$this->showCmd($notesPanelHTML);
				break;
			
			
			default:
				
				$cmd = $this->ctrl->getCmd(self::CMD_SHOW) . 'Cmd';
				
				$this->$cmd();
		}
	}
	
	/**
	 * @return string
	 */
	protected function buildPreviewFormAction()
	{
		return $this->ctrl->getFormAction($this, self::CMD_SHOW) . '#' . self::FEEDBACK_FOCUS_ANCHOR;
	}
	
	protected function isCommentingRequired()
	{
		global $DIC;
		/* @var ILIAS\DI\Container $DIC */
		
		if ($this->previewSettings->isTestRefId()) {
			return false;
		}
		
		return (bool)$DIC->rbac()->system()->checkAccess('write', (int)$_GET['ref_id']);
	}
	
	private function showCmd($notesPanelHTML = '')
	{
		$tpl = new ilTemplate('tpl.qpl_question_preview.html', true, true, 'Modules/TestQuestionPool');
		
		$tpl->setVariable('PREVIEW_FORMACTION', $this->buildPreviewFormAction());
		
		$this->populatePreviewToolbar($tpl);
		
		$this->populateQuestionOutput($tpl);
		
		$this->handleInstantResponseRendering($tpl);
		
		if ($this->isCommentingRequired()) {
			$this->populateNotesPanel($tpl, $notesPanelHTML);
		}
		
		$this->tpl->setContent($tpl->get());
	}
	
	protected function handleInstantResponseRendering(ilTemplate $tpl)
	{
		$renderHeader = false;
		$renderAnchor = false;
		
		if ($this->isShowReachedPointsRequired()) {
			$this->populateReachedPointsOutput($tpl);
			$renderAnchor = true;
			$renderHeader = true;
		}
		
		if ($this->isShowBestSolutionRequired()) {
			$this->populateSolutionOutput($tpl);
			$renderAnchor = true;
			$renderHeader = true;
		}
		
		if ($this->isShowGenericQuestionFeedbackRequired()) {
			$this->populateGenericQuestionFeedback($tpl);
			$renderAnchor = true;
			$renderHeader = true;
		}
		
		if ($this->isShowSpecificQuestionFeedbackRequired()) {
			$renderHeader = true;
			
			if ($this->questionGUI->hasInlineFeedback()) {
				$renderAnchor = false;
			} else {
				$this->populateSpecificQuestionFeedback($tpl);
				$renderAnchor = true;
			}
		}
		
		if ($renderHeader) {
			$this->populateInstantResponseHeader($tpl, $renderAnchor);
		}
	}
	
	private function resetCmd()
	{
		$this->previewSession->setRandomizerSeed(null);
		$this->previewSession->setParticipantsSolution(null);
		$this->previewSession->resetRequestedHints();
		$this->previewSession->setInstantResponseActive(false);
		
		ilUtil::sendInfo($this->lng->txt('qst_preview_reset_msg'), true);
		
		$this->ctrl->redirect($this, self::CMD_SHOW);
	}
	
	private function instantResponseCmd()
	{
		if ($this->saveQuestionSolution()) {
			$this->previewSession->setInstantResponseActive(true);
		} else {
			$this->previewSession->setInstantResponseActive(false);
		}
		
		$this->ctrl->redirect($this, self::CMD_SHOW);
	}
	
	private function handleQuestionActionCmd()
	{
		$this->questionOBJ->persistPreviewState($this->previewSession);
		$this->ctrl->redirect($this, self::CMD_SHOW);
	}
	
	private function populatePreviewToolbar(ilTemplate $tpl)
	{
		$toolbarGUI = new ilAsqQuestionPreviewToolbarGUI($this->lng);
		
		$toolbarGUI->setFormAction($this->ctrl->getFormAction($this, self::CMD_SHOW));
		$toolbarGUI->setResetPreviewCmd(self::CMD_RESET);
		
		$toolbarGUI->build();
		
		$tpl->setVariable('PREVIEW_TOOLBAR', $this->ctrl->getHTML($toolbarGUI));
	}
	
	private function populateQuestionOutput(ilTemplate $tpl)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		// FOR WHAT EXACTLY IS THIS USEFUL?
		#$this->ctrl->setReturnByClass('ilAsqQuestionPageGUI', 'view');
		#$this->ctrl->setReturnByClass('ilObjQuestionPoolGUI', 'questions');
		
		$pageGUI = new ilAsqQuestionPageGUI($this->qstAuthoringGUI->getQuestion()->getId());
		$pageGUI->setRenderPageContainer(false);
		$pageGUI->setEditPreview(true);
		$pageGUI->setEnabledTabs(false);
		
		// FOR WHICH SITUATION IS THIS WORKAROUND NECCESSARY? (sure .. imagemaps, but where this can be done?)
		if (strlen($this->ctrl->getCmd()) == 0 && !isset($_POST['editImagemapForward_x'])) // workaround for page edit imagemaps, keep in mind
		{
			$this->ctrl->setCmdClass(get_class($pageGUI));
			$this->ctrl->setCmd('preview');
		}
		
		#$this->questionGUI->setPreviewSession($this->previewSession);
		$this->qstAuthoringGUI->getQuestion()->setShuffler($this->getQuestionAnswerShuffler());
		
		$questionHtml = $this->questionGUI->getPreview(true, $this->isShowSpecificQuestionFeedbackRequired());
		$this->questionGUI->magicAfterTestOutput();
		
		if ($this->isShowSpecificQuestionFeedbackRequired() && $this->questionGUI->hasInlineFeedback()) {
			$questionHtml = $this->questionGUI->buildFocusAnchorHtml() . $questionHtml;
		}
		
		$questionHtml .= $this->getQuestionNavigationHtml();
		
		$pageGUI->setQuestionHTML(array($this->questionOBJ->getId() => $questionHtml));
		
		//$pageGUI->setHeader($this->questionOBJ->getTitle()); // NO ADDITIONAL HEADER
		$pageGUI->setPresentationTitle($this->questionOBJ->getTitle());
		
		//$pageGUI->setTemplateTargetVar("ADM_CONTENT"); // NOT REQUIRED, OR IS?
		
		$tpl->setVariable('QUESTION_OUTPUT', $pageGUI->preview());
	}
	
	protected function populateReachedPointsOutput(ilTemplate $tpl)
	{
		$reachedPoints = $this->questionOBJ->calculateReachedPointsFromPreviewSession($this->previewSession);
		$maxPoints = $this->questionOBJ->getMaximumPoints();
		
		$scoreInformation = sprintf(
			$this->lng->txt("you_received_a_of_b_points"), $reachedPoints, $maxPoints
		);
		
		$tpl->setCurrentBlock("reached_points_feedback");
		$tpl->setVariable("REACHED_POINTS_FEEDBACK", $scoreInformation);
		$tpl->parseCurrentBlock();
	}
	
	private function populateSolutionOutput(ilTemplate $tpl)
	{
		// FOR WHAT EXACTLY IS THIS USEFUL?
		$this->ctrl->setReturnByClass('ilAsqQuestionPageGUI', 'view');
		$this->ctrl->setReturnByClass('ilObjQuestionPoolGUI', 'questions');
		
		$pageGUI = new ilAsqQuestionPageGUI($this->questionOBJ->getId());
		
		$pageGUI->setEditPreview(true);
		$pageGUI->setEnabledTabs(false);
		
		// FOR WHICH SITUATION IS THIS WORKAROUND NECCESSARY? (sure .. imagemaps, but where this can be done?)
		if (strlen($this->ctrl->getCmd()) == 0 && !isset($_POST['editImagemapForward_x'])) // workaround for page edit imagemaps, keep in mind
		{
			$this->ctrl->setCmdClass(get_class($pageGUI));
			$this->ctrl->setCmd('preview');
		}
		
		$this->questionGUI->setPreviewSession($this->previewSession);
		
		$pageGUI->setQuestionHTML(array($this->questionOBJ->getId() => $this->questionGUI->getSolutionOutput(0, null, false, false, true, false, true, false, false)));
		
		//$pageGUI->setHeader($this->questionOBJ->getTitle()); // NO ADDITIONAL HEADER
		//$pageGUI->setPresentationTitle($this->questionOBJ->getTitle());
		
		//$pageGUI->setTemplateTargetVar("ADM_CONTENT"); // NOT REQUIRED, OR IS?
		
		$output = $this->questionGUI->getSolutionOutput(0, null, false, false, true, false, true, false, false);
		//$output = $pageGUI->preview();
		//$output = str_replace('<h1 class="ilc_page_title_PageTitle"></h1>', '', $output);
		
		$tpl->setCurrentBlock('solution_output');
		$tpl->setVariable('TXT_CORRECT_SOLUTION', $this->lng->txt('tst_best_solution_is'));
		$tpl->setVariable('SOLUTION_OUTPUT', $output);
		$tpl->parseCurrentBlock();
	}
	
	private function getQuestionNavigationHtml()
	{
		$navGUI = new ilAsqQuestionRelatedNavigationBarGUI($this->ctrl, $this->lng);
		
		$navGUI->setInstantResponseCmd(self::CMD_INSTANT_RESPONSE);
		$navGUI->setHintRequestCmd(self::CMD_GATEWAY_CONFIRM_HINT_REQUEST);
		$navGUI->setHintListCmd(self::CMD_GATEWAY_SHOW_HINT_LIST);
		
		$navGUI->setInstantResponseEnabled($this->previewSettings->isInstantFeedbackNavigationRequired());
		$navGUI->setHintProvidingEnabled($this->previewSettings->isHintProvidingEnabled());
		
		$navGUI->setHintRequestsPossible($this->hintTracking->requestsPossible());
		$navGUI->setHintRequestsExist($this->hintTracking->requestsExist());
		
		return $this->ctrl->getHTML($navGUI);
	}
	
	private function populateGenericQuestionFeedback(ilTemplate $tpl)
	{
		if ($this->questionOBJ->isPreviewSolutionCorrect($this->previewSession)) {
			$feedback = $this->questionGUI->getGenericFeedbackOutputForCorrectSolution();
			$cssClass = ilAsqQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT;
		} else {
			$feedback = $this->questionGUI->getGenericFeedbackOutputForIncorrectSolution();
			$cssClass = ilAsqQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG;
		}
		
		if (strlen($feedback)) {
			$tpl->setCurrentBlock('instant_feedback_generic');
			$tpl->setVariable('GENERIC_FEEDBACK', $feedback);
			$tpl->setVariable('ILC_FB_CSS_CLASS', $cssClass);
			$tpl->parseCurrentBlock();
		}
	}
	
	private function populateSpecificQuestionFeedback(ilTemplate $tpl)
	{
		$fb = $this->questionGUI->getSpecificFeedbackOutput(
			(array)$this->previewSession->getParticipantsSolution()
		);
		
		$tpl->setCurrentBlock('instant_feedback_specific');
		$tpl->setVariable('ANSWER_FEEDBACK', $fb);
		$tpl->parseCurrentBlock();
	}
	
	protected function populateInstantResponseHeader(ilTemplate $tpl, $withFocusAnchor)
	{
		if ($withFocusAnchor) {
			$tpl->setCurrentBlock('inst_resp_id');
			$tpl->setVariable('INSTANT_RESPONSE_FOCUS_ID', self::FEEDBACK_FOCUS_ANCHOR);
			$tpl->parseCurrentBlock();
		}
		
		$tpl->setCurrentBlock('instant_response_header');
		$tpl->setVariable('INSTANT_RESPONSE_HEADER', $this->lng->txt('tst_feedback'));
		$tpl->parseCurrentBlock();
	}
	
	private function isShowBestSolutionRequired()
	{
		if (!$this->previewSettings->isBestSolutionEnabled()) {
			return false;
		}
		
		return $this->previewSession->isInstantResponseActive();
	}
	
	private function isShowGenericQuestionFeedbackRequired()
	{
		if (!$this->previewSettings->isGenericFeedbackEnabled()) {
			return false;
		}
		
		return $this->previewSession->isInstantResponseActive();
	}
	
	private function isShowSpecificQuestionFeedbackRequired()
	{
		if (!$this->previewSettings->isSpecificFeedbackEnabled()) {
			return false;
		}
		
		return $this->previewSession->isInstantResponseActive();
	}
	
	private function isShowReachedPointsRequired()
	{
		if (!$this->previewSettings->isReachedPointsEnabled()) {
			return false;
		}
		
		return $this->previewSession->isInstantResponseActive();
	}
	
	public function saveQuestionSolution()
	{
		return $this->questionOBJ->persistPreviewState($this->previewSession);
	}
	
	public function gatewayConfirmHintRequestCmd()
	{
		if (!$this->saveQuestionSolution()) {
			$this->previewSession->setInstantResponseActive(false);
			$this->showCmd();
			return;
		}
		
		$this->ctrl->redirectByClass(
			'ilAsqQuestionHintRequestGUI', ilAsqQuestionHintRequestGUI::CMD_CONFIRM_REQUEST
		);
	}
	
	public function gatewayShowHintListCmd()
	{
		if (!$this->saveQuestionSolution()) {
			$this->previewSession->setInstantResponseActive(false);
			$this->showCmd();
			return;
		}
		
		$this->ctrl->redirectByClass(
			'ilAsqQuestionHintRequestGUI', ilAsqQuestionHintRequestGUI::CMD_SHOW_LIST
		);
	}
	
	/**
	 * @return ilArrayElementShuffler
	 */
	private function getQuestionAnswerShuffler()
	{
		require_once 'Services/Randomization/classes/class.ilArrayElementShuffler.php';
		$shuffler = new ilArrayElementShuffler();
		
		if (!$this->previewSession->randomizerSeedExists()) {
			$this->previewSession->setRandomizerSeed($shuffler->buildRandomSeed());
		}
		
		$shuffler->setSeed($this->previewSession->getRandomizerSeed());
		
		return $shuffler;
	}
	
	protected function populateNotesPanel(ilTemplate $tpl, $notesPanelHTML)
	{
		if (!strlen($notesPanelHTML)) {
			$notesPanelHTML = $this->questionGUI->getNotesHTML();
		}
		
		$tpl->setCurrentBlock('notes_panel');
		$tpl->setVariable('NOTES_PANEL', $notesPanelHTML);
		$tpl->parseCurrentBlock();
	}
}