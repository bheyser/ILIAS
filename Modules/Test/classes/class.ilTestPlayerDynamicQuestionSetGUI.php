<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/Test/classes/class.ilTestPlayerAbstractGUI.php';

/**
 * Output class for assessment test execution
 *
 * The ilTestOutputGUI class creates the output for the ilObjTestGUI
 * class when learners execute a test. This saves some heap space because 
 * the ilObjTestGUI class will be much smaller then
 *
 * @extends ilTestPlayerAbstractGUI
 * 
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 * 
 * @package		Modules/Test
 *
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilAssGenFeedbackPageGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilAssSpecFeedbackPageGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilAssQuestionHintRequestGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilAssQuestionPageGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilTestDynamicQuestionSetStatisticTableGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilToolbarGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilTestSubmissionReviewGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilTestPasswordProtectionGUI
 * @ilCtrl_Calls ilTestPlayerDynamicQuestionSetGUI: ilFormPropertyDispatchGUI
 */
class ilTestPlayerDynamicQuestionSetGUI extends ilTestPlayerAbstractGUI
{
	/**
	 * @var ilObjTestDynamicQuestionSetConfig
	 */
	private $dynamicQuestionSetConfig = null;

	/**
	 * @var ilTestSequenceDynamicQuestionSet
	 */
	protected $testSequence;

	/**
	 * @var ilTestSessionDynamicQuestionSet
	 */
	protected $testSession;
	
	/**
	 * execute command
	 */
	function executeCommand()
	{
		global $DIC;
		$ilDB = $DIC['ilDB'];
		$lng = $DIC['lng'];
		$ilPluginAdmin = $DIC['ilPluginAdmin'];
		$ilTabs = $DIC['ilTabs'];
		$tree = $DIC['tree'];

		$ilTabs->clearTargets();
		
		$this->ctrl->saveParameter($this, "sequence");
		$this->ctrl->saveParameter($this, "active_id");

		$this->initAssessmentSettings();

		require_once 'Modules/Test/classes/class.ilObjTestDynamicQuestionSetConfig.php';
		$this->dynamicQuestionSetConfig = new ilObjTestDynamicQuestionSetConfig($tree, $ilDB, $ilPluginAdmin, $this->object);
		$this->dynamicQuestionSetConfig->loadFromDb();

		$testSessionFactory = new ilTestSessionFactory($this->object);
		$this->testSession = $testSessionFactory->getSession($_GET['active_id']);

		$this->ensureExistingTestSession($this->testSession);
		$this->initProcessLocker($this->testSession->getActiveId());
		
		$testSequenceFactory = new ilTestSequenceFactory($ilDB, $lng, $ilPluginAdmin, $this->object);
		$this->testSequence = $testSequenceFactory->getSequenceByTestSession($this->testSession);
		$this->testSequence->loadFromDb();

		if( $this->object->isInstantFeedbackAnswerFixationEnabled() )
		{
			$this->testSequence->setPreventCheckedQuestionsFromComingUpEnabled(true);
		}

		include_once 'Services/jQuery/classes/class.iljQueryUtil.php';
		iljQueryUtil::initjQuery();
		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initConnectionWithAnimation();
		if( $this->object->getKioskMode() )
		{
			include_once 'Services/UIComponent/Overlay/classes/class.ilOverlayGUI.php';
			ilOverlayGUI::initJavascript();
		}
		
		$this->handlePasswordProtectionRedirect();
		
		$cmd = $this->ctrl->getCmd();
		$nextClass = $this->ctrl->getNextClass($this);
		
		switch($nextClass)
		{
			case 'ilformpropertydispatchgui':
				include_once './Services/Form/classes/class.ilFormPropertyDispatchGUI.php';
				$form_prop_dispatch = new ilFormPropertyDispatchGUI();
				$form = $this->buildQuestionSelectionForm();
				$item = $form->getItemByPostVar($_GET["postvar"]);
				$form_prop_dispatch->setItem($item);
				return $this->ctrl->forwardCommand($form_prop_dispatch);

			case 'ilassquestionpagegui':

				$questionId = $this->testSession->getCurrentQuestionId();

				require_once "./Modules/TestQuestionPool/classes/class.ilAssQuestionPageGUI.php";
				$page_gui = new ilAssQuestionPageGUI($questionId);
				$ret = $this->ctrl->forwardCommand($page_gui);
				break;

			case 'ilassquestionhintrequestgui':

				$this->ctrl->saveParameter($this, 'pmode');
				
				$questionGUI = $this->object->createQuestionGUI(
					"", $this->testSession->getCurrentQuestionId()
				);

				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionHintTracking.php';
				$questionHintTracking = new ilAssQuestionHintTracking(
					$questionGUI->object->getId(), $this->testSession->getActiveId(), $this->testSession->getPass()
				);

				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionHintRequestGUI.php';
				$gui = new ilAssQuestionHintRequestGUI(
					$this, ilTestPlayerCommands::SHOW_QUESTION, $questionGUI, $questionHintTracking
				);
				
// fau: testNav - save the 'answer changed status' for viewing hint requests
				$this->setAnswerChangedParameter($this->getAnswerChangedParameter());
// fau.
				$this->ctrl->forwardCommand($gui);
				
				break;
				
			case 'ildynamicquestionsetstatistictablegui':
				
				$this->ctrl->forwardCommand( $this->buildQuestionSetFilteredStatisticTableGUI() );
				
				break;

			case 'iltestpasswordprotectiongui':
				require_once 'Modules/Test/classes/class.ilTestPasswordProtectionGUI.php';
				$gui = new ilTestPasswordProtectionGUI($this->ctrl, $this->tpl, $this->lng, $this, $this->passwordChecker);
				$ret = $this->ctrl->forwardCommand($gui);
				break;
			
			default:
				
				$cmd .= 'Cmd';
				$ret =& $this->$cmd();
				break;
		}
		
		return $ret;
	}
	
	/**
	 * @return integer
	 */
	protected function getCurrentQuestionId()
	{
		return $this->testSession->getCurrentQuestionId();
	}

	/**
	 * Resume a test at the last position
	 */
	protected function resumePlayerCmd()
	{
		if ($this->object->checkMaximumAllowedUsers() == FALSE)
		{
			return $this->showMaximumAllowedUsersReachedMessage();
		}
		
		$this->handleUserSettings();
		
		if( $this->dynamicQuestionSetConfig->isAnyQuestionFilterEnabled() )
		{
			$this->ctrl->redirect($this, 'showAnsweringStatistic');
		}
		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}
	
	protected function startTestCmd()
	{
		$this->testSession->setCurrentQuestionId(null); // no question "came up" yet
		
		$this->testSession->saveToDb();
		
		$this->ctrl->setParameter($this, 'active_id', $this->testSession->getActiveId());

		assQuestion::_updateTestPassResults($this->testSession->getActiveId(), $this->testSession->getPass(), false, null, $this->object->id);

		$_SESSION['active_time_id'] = $this->object->startWorkingTime(
				$this->testSession->getActiveId(), $this->testSession->getPass()
		);
		
		$this->ctrl->saveParameter($this, 'tst_javascript');
		
		if( $this->dynamicQuestionSetConfig->isAnyQuestionFilterEnabled() )
		{
			$this->ctrl->redirect($this, 'showAnsweringStatistic');
		}
		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}
	
	protected function showTestResultsCmd()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ctrl()->setParameterByClass('ilMyTestResultsGUI', 'context',
			$_GET['context']
		);
		
		$DIC->ctrl()->setParameterByClass('ilTestEvaluationGUI', 'pass', 0);
		
		$DIC->ctrl()->redirectByClass(
			array('ilTestResultsGUI', 'ilMyTestResultsGUI', 'ilTestEvaluationGUI'), 'outUserPassDetails'
		);
	}
	
	protected function buildQuestionSelectionForm()
	{
		$form = new ilPropertyFormGUI();
		
		$form->setTitle('Question Selection');
		
		$form->setFormAction($this->ctrl->getFormAction($this));
		
		if( $this->dynamicQuestionSetConfig->isTaxonomyFilterEnabled() )
		{
			require_once 'Services/Taxonomy/classes/class.ilTaxSelectInputGUI.php';
			
			foreach($this->dynamicQuestionSetConfig->getFilterTaxonomyIds() as $taxId)
			{
				$postvar = "tax_$taxId";
				
				$inp = new ilTaxSelectInputGUI($taxId, $postvar, true);
				
				$form->addItem($inp);
				
				if( $this->testSession->getQuestionSetFilterSelection()->hasSelectedTaxonomy($taxId) )
				{
					$inp->setValue($this->testSession->getQuestionSetFilterSelection()->getSelectedTaxonomy($taxId));
				}
			}
		}
		
		if( $this->dynamicQuestionSetConfig->isAnswerStatusFilterEnabled() )
		{
			require_once 'Services/Form/classes/class.ilSelectInputGUI.php';
			require_once 'Services/Form/classes/class.ilRadioOption.php';
			
			$inp = new ilRadioGroupInputGUI($this->lng->txt('tst_question_answer_status'), 'question_answer_status');
			$inp->addOption(new ilRadioOption(
				$this->lng->txt('tst_question_answer_status_all_non_correct'), ilAssQuestionList::ANSWER_STATUS_FILTER_ALL_NON_CORRECT
			));
			$inp->addOption(new ilRadioOption(
				$this->lng->txt('tst_question_answer_status_non_answered'), ilAssQuestionList::ANSWER_STATUS_FILTER_NON_ANSWERED_ONLY
			));
			$inp->addOption(new ilRadioOption(
				$this->lng->txt('tst_question_answer_status_wrong_answered'), ilAssQuestionList::ANSWER_STATUS_FILTER_WRONG_ANSWERED_ONLY
			));
			$form->addItem($inp);
			
			if( $this->testSession->getQuestionSetFilterSelection()->hasAnswerStatusSelection() )
			{
				$inp->setValue($this->testSession->getQuestionSetFilterSelection()->getAnswerStatusSelection());
			}
			else
			{
				$inp->setValue(ilAssQuestionList::ANSWER_STATUS_FILTER_ALL_NON_CORRECT);
			}
		}
		
		$form->addCommandButton('filterQuestionSelection', 'Save Question Selection');
		$form->addCommandButton('resetQuestionSelection', 'Reset Question Selection');
		
		return $form;
	}
	
	protected function setQuestionSelectionCmd()
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		$filterSelection = $this->testSession->getQuestionSetFilterSelection();

		if( $this->dynamicQuestionSetConfig->isAnswerStatusFilterEnabled() )
		{
			if( isset($_POST['filter_answerstatus']) )
			{
				$filterSelection->setAnswerStatusSelection($_POST['filter_answerstatus']);
			}
		}
		
		if( $this->dynamicQuestionSetConfig->isTaxonomyFilterEnabled() )
		{
			foreach ($this->dynamicQuestionSetConfig->getFilterTaxonomyIds() as $taxId)
			{
				$enabledPostvar = 'enabled_tax_'.$taxId;
				$filterPostvar = 'filter_tax_'.$taxId;
				
				if( isset($_POST[$enabledPostvar]) && $_POST[$enabledPostvar] && isset($_POST[$filterPostvar]) )
				{
					$selection = array();
					
					if(strlen($_POST[$filterPostvar]))
					{
						$selection = implode(':', $_POST[$filterPostvar]);
					}
					
					$filterSelection->setSelectedTaxonomy($taxId, $selection);
				}
			}
		}
		
		$this->testSession->saveToDb();
		
		$DIC->ctrl()->redirect($this, 'showAnsweringStatistic');
	}
	
	protected function questionSelectionRoundtripModalCmd()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$replaceSignal = new \ILIAS\UI\Implementation\Component\ReplaceSignal($_GET['signal']);
		$requestedPage = $_GET['requestPage'];
		
		$pages = $this->getQuestionSelectionModalPages();
		
		foreach($pages as $page)
		{
			if( isset($_GET[$page]) )
			{
				$_POST[$page] = $_GET[$page];
			}
			
			$page = str_replace('filter_', 'enabled_', $page);
			
			if( isset($_GET[$page]) )
			{
				$_POST[$page] = $_GET[$page];
			}
		}
		
		$form = new ilPropertyFormGUI();
		$form->setId('qstSel_'.$requestedPage);
		$form->setFormAction($DIC->ctrl()->getFormAction($this, 'setQuestionSelection'));
		
		$modalTitle = null;
		$nextPage = null;
		$previousPage = null;
		
		$loopsPreviousPage = null;
		$loopExpectNextPage = false;
		
		foreach($pages as $page)
		{
			if($loopExpectNextPage)
			{
				$nextPage = $page;
				$loopExpectNextPage = false;
			}
			
			if( $page == $requestedPage )
			{
				$modalTitle = $this->fillQuestionSelectionFormVisibleInputs($form, $page);
				$loopExpectNextPage = true;
				$previousPage = $loopsPreviousPage;
			}
			else
			{
				$this->fillQuestionSelectionFormHiddenInputs($form, $page);
			}
			
			$loopsPreviousPage = $page;
		}
		
		$form->setValuesByPost();
		
		$nextLabel = $nextPage ? 'Next' : 'Apply';
		$buttonNext = $DIC->ui()->factory()->button()->primary($nextLabel, '#');
		
		$buttonBack = $DIC->ui()->factory()->button()->standard('Back', '#');
		
		$modal = $DIC->ui()->factory()->modal()->roundtrip(
			$modalTitle, $DIC->ui()->factory()->legacy($form->getHTML())
		);
		
		if( $nextPage )
		{
			// previous button
			
			$DIC->ctrl()->setParameter($this, 'signal', $replaceSignal->getId());
			$DIC->ctrl()->setParameter($this, 'requestPage', $previousPage);
			$DIC->ctrl()->setParameter($this, 'fromPage', $requestedPage);
			
			$url = $DIC->ctrl()->getLinkTarget(
				$this, 'questionSelectionRoundtripModal', '', true
			);
			
			$replaceSignal = $replaceSignal->withAsyncRenderUrl($url);
			
			$buttonBack = $buttonBack->/*withOnClick($replaceSignal)->*/withOnLoadCode(
				function($id) use ($modal, $form, $replaceSignal, $url) {
					return "
						$('#{$id}').click(function(event) {
						
							var formData = $('#form_{$form->getId()}').serialize();
							
							var asyncUrl = '{$url}&'+formData;
							
							$(this).trigger('{$replaceSignal->getId()}',{
								id: '{$replaceSignal->getId()}',
								event: 'click',
								triggerer: $(this),
								options: {url: asyncUrl}
							});
							
						});
					";
				}
			);
			
			// next button
			
			$DIC->ctrl()->setParameter($this, 'signal', $replaceSignal->getId());
			$DIC->ctrl()->setParameter($this, 'requestPage', $nextPage);
			$DIC->ctrl()->setParameter($this, 'fromPage', $requestedPage);
			
			$url = $DIC->ctrl()->getLinkTarget(
				$this, 'questionSelectionRoundtripModal', '', true
			);
			
			$replaceSignal = $replaceSignal->withAsyncRenderUrl($url);
			
			$buttonNext = $buttonNext->/*withOnClick($replaceSignal)->*/withOnLoadCode(
				function($id) use ($modal, $form, $replaceSignal, $url) {
					return "
						$('#{$id}').click(function(event) {
						
							var formData = $('#form_{$form->getId()}').serialize();
							
							var asyncUrl = '{$url}&'+formData;
							
							$(this).trigger('{$replaceSignal->getId()}',{
								id: '{$replaceSignal->getId()}',
								event: 'click',
								triggerer: $(this),
								options: {url: asyncUrl}
							});
							
						});
					";
				}
			);
		}
		else
		{
			// previous button
			
			$DIC->ctrl()->setParameter($this, 'signal', $replaceSignal->getId());
			$DIC->ctrl()->setParameter($this, 'requestPage', $previousPage);
			$DIC->ctrl()->setParameter($this, 'fromPage', $requestedPage);
			
			$url = $DIC->ctrl()->getLinkTarget(
				$this, 'questionSelectionRoundtripModal', '', true
			);
			
			$replaceSignal = $replaceSignal->withAsyncRenderUrl($url);
			
			$buttonBack = $buttonBack/*->withOnClick($replaceSignal)*/->withOnLoadCode(
				function($id) use ($modal, $form, $replaceSignal, $url) {
					return "
						$('#{$id}').click(function(event) {
						
							var formData = $('#form_{$form->getId()}').serialize();
							
							var asyncUrl = '{$url}&'+formData;
							
							$(this).trigger('{$replaceSignal->getId()}',{
								id: '{$replaceSignal->getId()}',
								event: 'click',
								triggerer: $(this),
								options: {url: asyncUrl}
							});
							
						});
					";
				}
			);
			
			// submit button
			
			$buttonNext = $buttonNext->withOnLoadCode(
				function($id) use ($form) { return "
					$('#{$id}').click(function() {
						$('#form_{$form->getId()}').submit();
						return false;
					});
				";}
			);
		}
		
		if( $previousPage )
		{
			$modal = $modal->withActionButtons([$buttonBack, $buttonNext]);
		}
		else
		{
			$modal = $modal->withActionButtons([$buttonNext]);
		}
		
		$echo = $DIC->ui()->renderer()->renderAsync($modal);
		echo $echo;
		exit;
		
		//$DIC->ctrl()->redirect($this, 'showAnsweringStatistic');
	}
	
	protected function fillQuestionSelectionFormVisibleInputs(ilPropertyFormGUI $form, $page)
	{
		$matches = $modalTitle = null;
		
		if($page == 'filter_answerstatus')
		{
			$inp = new ilRadioGroupInputGUI($this->lng->txt('tst_question_answer_status'), 'filter_answerstatus');
			$inp->addOption(new ilRadioOption(
				$this->lng->txt('tst_question_answer_status_all_non_correct'), ilAssQuestionList::ANSWER_STATUS_FILTER_ALL_NON_CORRECT
			));
			$inp->addOption(new ilRadioOption(
				$this->lng->txt('tst_question_answer_status_non_answered'), ilAssQuestionList::ANSWER_STATUS_FILTER_NON_ANSWERED_ONLY
			));
			$inp->addOption(new ilRadioOption(
				$this->lng->txt('tst_question_answer_status_wrong_answered'), ilAssQuestionList::ANSWER_STATUS_FILTER_WRONG_ANSWERED_ONLY
			));
			$form->addItem($inp);
			
			if( $this->testSession->getQuestionSetFilterSelection()->hasAnswerStatusSelection() )
			{
				$inp->setValue($this->testSession->getQuestionSetFilterSelection()->getAnswerStatusSelection());
			}
			else
			{
				$inp->setValue(ilAssQuestionList::ANSWER_STATUS_FILTER_ALL_NON_CORRECT);
			}
			
			$modalTitle = $this->lng->txt('tst_question_answer_status');
		}
		elseif(preg_match('/^filter_tax_(\d+)$/', $page, $matches))
		{
			$taxId = $matches[1];
			$postvar = "tax_$taxId";

			$tax = new ilObjTaxonomy($taxId);
			$modalTitle = $tax->getTitle();
			
			$enabledInput = new ilRadioGroupInputGUI($modalTitle, 'enabled_'.$postvar);
			$form->addItem($enabledInput);

			$optAll = new ilRadioOption('All Questions', 0);
			$optFilter = new ilRadioOption('Filter by Taxonomy', 1);
			$enabledInput->addOption($optAll);
			$enabledInput->addOption($optFilter);
			
			#$inp = new ilTaxSelectInputGUI($taxId, 'filter_'.$postvar, true);
			
			#$optFilter->addSubItem($inp);
			
			#if( $this->testSession->getQuestionSetFilterSelection()->hasSelectedTaxonomy($taxId) )
			#{
			#	$inp->setValue($this->testSession->getQuestionSetFilterSelection()->getSelectedTaxonomy($taxId));
			#}
		}
		
		return $modalTitle;
	}
	
	protected function fillQuestionSelectionFormHiddenInputs(ilPropertyFormGUI $form, $page)
	{
		if($page == 'filter_answerstatus')
		{
			$inp = new ilHiddenInputGUI($page,
				isset($_POST[$page]) ? $_POST[$page] : null
			);
			
			$form->addItem($inp);
		}
		
		if(preg_match('/^filter_tax_(\d+)$/', $page, $matches))
		{
			$taxId = $matches[1];
			$postvar = "tax_$taxId";
			
			$inp = new ilHiddenInputGUI('enabled_'.$postvar,
				isset($_POST['enabled_'.$postvar]) ? $_POST['enabled_'.$postvar] : null
			);
			
			$form->addItem($inp);
			
			$inp = new ilHiddenInputGUI('filter_'.$postvar,
				isset($_POST['filter_'.$postvar]) ? $_POST['filter_'.$postvar] : null
			);
			
			$form->addItem($inp);
		}
	}
	
	protected function getQuestionSelectionModalPages()
	{
		$pages = array();
		
		if( $this->dynamicQuestionSetConfig->isAnswerStatusFilterEnabled() )
		{
			$pages[] = 'filter_answerstatus';
		}
		
		if( $this->dynamicQuestionSetConfig->isTaxonomyFilterEnabled() )
		{
			foreach ($this->dynamicQuestionSetConfig->getFilterTaxonomyIds() as $taxId)
			{
				$pages[] = 'filter_tax_'.$taxId;
			}
		}
		
		return $pages;
	}
	
	protected function getQuestionSelectionModal()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$pages = $this->getQuestionSelectionModalPages();
		
		$modal = $DIC->ui()->factory()->modal()->roundtrip('', []);
		
		$replaceSignalId = $modal->getReplaceSignal()->getId();
		
		$DIC->ctrl()->setParameter($this, 'signal', $replaceSignalId);
		$DIC->ctrl()->setParameter($this, 'requestPage', $pages[0]);
		$DIC->ctrl()->setParameter($this, 'fromPage', '');
		
		$modal = $modal->withAsyncRenderUrl($DIC->ctrl()->getLinkTarget(
			$this, 'questionSelectionRoundtripModal', '', true
		));
		
		return $modal;
	}
	
	protected function showAnsweringStatisticCmd()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$this->testSequence->loadQuestions(
			$this->dynamicQuestionSetConfig, $this->testSession->getQuestionSetFilterSelection()
		);
		
		$this->testSequence->cleanupQuestions($this->testSession);
		
		$this->testSequence->saveToDb();

		$filteredData = array($this->buildQuestionSetAnswerStatisticRowArray(
			$this->testSequence->getSelectedQuestionsData(), $this->testSequence->getTrackedQuestionList()
		)); #vd($filteredData);
		$filteredTableGUI = $this->buildQuestionSetFilteredStatisticTableGUI();
		$filteredTableGUI->setData($filteredData);
		
		$completeData = array($this->buildQuestionSetAnswerStatisticRowArray(
			$this->testSequence->getCompleteQuestionsData(), $this->testSequence->getTrackedQuestionList()
		)); #vd($completeData);
		$completeTableGUI = $this->buildQuestionSetCompleteStatisticTableGUI();
		$completeTableGUI->setData($completeData);
		
		require_once 'Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php';
		$toolbarGUI = new ilToolbarGUI();
		
		require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
		$button = ilLinkButton::getInstance();
		$button->setUrl($this->getStartTestFromQuestionSelectionLink());
		$button->setCaption($this->getEnterTestButtonLangVar());
		$button->setPrimary(true);
		$toolbarGUI->addButtonInstance($button);
		
		$modal = $this->getQuestionSelectionModal();

		$button = $DIC->ui()->factory()->button()->standard('Change Question Selection', "")->withOnClick(
			$modal->getShowSignal()
		);
		
		$toolbarGUI->addComponent($button);
		
		/*
		$button = ilLinkButton::getInstance();
		$button->setUrl($this->ctrl->getLinkTarget($this, 'showQuestionSelection'));
		$button->setCaption('Change Question Selection', false);
		$toolbarGUI->addButtonInstance($button);
		*/
		
		$this->ctrl->setParameter(
			$this, 'context', ilTestPassDeletionConfirmationGUI::CONTEXT_DYN_TEST_QUESTION_SELECTION
		);
		
		$btn = ilTestPlayerNavButton::getInstance();
		$btn->setNextCommand('showTestResults');
		$btn->setUrl($this->ctrl->getLinkTarget(
			$this, 'showTestResults'
		));
		$btn->setCaption('Test Results', false);
		$toolbarGUI->addButtonInstance($btn);
		
		if( $this->object->getShowCancel() )
		{
			$button = ilLinkButton::getInstance();
			$button->setUrl($this->ctrl->getLinkTarget(
				$this, ilTestPlayerCommands::SUSPEND_TEST
			));
			$button->setCaption('cancel_test');
			$toolbarGUI->addButtonInstance($button);
		}
		
		if( $this->object->isPassDeletionAllowed() )
		{
			require_once 'Modules/Test/classes/confirmations/class.ilTestPassDeletionConfirmationGUI.php';
			
			$toolbarGUI->addButton(
				$this->lng->txt('tst_dyn_test_pass_deletion_button'),
				$this->getPassDeletionTarget(ilTestPassDeletionConfirmationGUI::CONTEXT_DYN_TEST_PLAYER)
			);
		}
		
		$content = $DIC->ui()->renderer()->render($modal).$toolbarGUI->getHTML();
		
		if( true ) // third variant for reporting panel attempt using two main panels
		{
			$content .= $this->renderQuestionSelectionStatisticsReport(
				$filteredData[0], $completeData[0], true
			);
		}
		elseif( true ) // second attempt using reporting panel with card
		{
			$content .= $this->renderQuestionSelectionStatisticsReport(
				$filteredData[0], $completeData[0], false
			);
		}
		elseif( true ) // first stats only draft using two labeled listing panel
		{
			$content .= $this->getSelectedQuestionsStatisticsHTML($filteredData[0], 'Questions Selected');
			$content .= $this->getCompleteQuestionPoolStatisticsHTML($completeData[0], 'All Questions in Pool');
		}
		else // former screen using tables for statistics and selection
		{
			$content .= $this->ctrl->getHTML($filteredTableGUI);
			$content .= $this->ctrl->getHTML($completeTableGUI);
		}
		
		$this->tpl->setContent($content);
	}
	
	protected function showQuestionSelectionCmd()
	{
		$this->prepareSummaryPage();
		
		require_once 'Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php';
		$toolbarGUI = new ilToolbarGUI();
		
		require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
		$button = ilLinkButton::getInstance();
		$button->setUrl($this->getStartTestFromQuestionSelectionLink());
		$button->setCaption($this->getEnterTestButtonLangVar());
		$button->setPrimary(true);
		$toolbarGUI->addButtonInstance($button);
		
			$button = ilLinkButton::getInstance();
			$button->setUrl($this->ctrl->getLinkTarget($this, 'showAnsweringStatistic'));
			$button->setCaption('Answering Statistic', false);
			$toolbarGUI->addButtonInstance($button);
		
		$this->ctrl->setParameter(
			$this, 'context', ilTestPassDeletionConfirmationGUI::CONTEXT_DYN_TEST_QUESTION_SELECTION
		);
		
		$btn = ilTestPlayerNavButton::getInstance();
		$btn->setNextCommand('showTestResults');
		$btn->setUrl($this->ctrl->getLinkTarget(
			$this, 'showTestResults'
		));
		$btn->setCaption('Test Results', false);
		$toolbarGUI->addButtonInstance($btn);
		
		if( false )
		{
			$button = ilLinkButton::getInstance();
			$button->setUrl($this->ctrl->getLinkTarget($this, 'changeQuestionSelection'));
			$button->setCaption('Change Question Selection', false);
			$toolbarGUI->addButtonInstance($button);

			$button = ilLinkButton::getInstance();
			$button->setUrl($this->ctrl->getLinkTarget($this, 'resetQuestionSelection'));
			$button->setCaption('Reset Question Selection', false);
			$toolbarGUI->addButtonInstance($button);
		}

		if( $this->object->getShowCancel() )
		{
			$button = ilLinkButton::getInstance();
			$button->setUrl($this->ctrl->getLinkTarget(
				$this, ilTestPlayerCommands::SUSPEND_TEST
			));
			$button->setCaption('cancel_test');
			$toolbarGUI->addButtonInstance($button);
		}
		
		if( $this->object->isPassDeletionAllowed() )
		{
			require_once 'Modules/Test/classes/confirmations/class.ilTestPassDeletionConfirmationGUI.php';
			
			$toolbarGUI->addButton(
				$this->lng->txt('tst_dyn_test_pass_deletion_button'),
				$this->getPassDeletionTarget(ilTestPassDeletionConfirmationGUI::CONTEXT_DYN_TEST_PLAYER)
			);
		}
		
		$content = $this->ctrl->getHTML($toolbarGUI);
		
		$form = $this->buildQuestionSelectionForm();
		$content .= $form->getHTML();
		
		$this->tpl->setVariable('TABLE_LIST_OF_QUESTIONS', $content);
		
		if( $this->object->getEnableProcessingTime() )
		{
			$this->outProcessingTime($this->testSession->getActiveId());
		}
	}
	
	protected function renderQuestionSelectionStatisticsReport($filteredData, $completeData, $alternativeVariant = false)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$f = $DIC->ui()->factory();
		
		/*$dropdown = $f->dropdown()->standard([
			$f->link()->standard('Change Question Selection',
				$this->ctrl->getLinkTarget($this, 'showQuestionSelection')
			),
			$f->link()->standard('Reset Question Selection',
				$this->ctrl->getLinkTarget($this, 'resetQuestionSelection')
			)
		]);*/

		$card = $f->card()->standard('Answering Statistic for Complete Question Pool')->withSections([
			$this->getAnsweringStatisticListing($completeData, 'All Questions in Pool', false)
		]);
		
		if($alternativeVariant)
		{
			$panel1 = $f->panel()->sub('', [
				$this->getQuestionSelectionDescription()
			])->withCard($card);//->withActions($dropdown);
			
			$panel2 = $f->panel()->sub('', [
				$this->getAnsweringStatisticListing($filteredData, 'Questions Selected', true)
			]);
			
			$reportPanel = [
				$f->panel()->report('Selection and Complete Pool', $panel1),
				$f->panel()->report('Answering Statistic for Selected Questions', $panel2),
			];
		}
		else
		{
			$panel1 = $f->panel()->sub('Selection and Complete Pool', [
				$this->getQuestionSelectionDescription()
			])->withCard($card);//->withActions($dropdown);
			
			$panel2 = $f->panel()->sub('Answering Statistic for Selected Questions', [
				$this->getAnsweringStatisticListing($filteredData, 'Questions Selected', true)
			]);
			
			$reportPanel = $f->panel()->report('Answering Statistic', [$panel1, $panel2]);
		}
		
		return $DIC->ui()->renderer()->render($reportPanel);
	}
	
	protected function getQuestionSelectionDescription()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$items = [];
		
		switch( $this->testSession->getQuestionSetFilterSelection()->getAnswerStatusSelection() )
		{
			case ilAssQuestionList::ANSWER_STATUS_FILTER_NON_ANSWERED_ONLY:
				$items[] = $this->lng->txt('tst_question_answer_status_non_answered');
				break;
			
			case ilAssQuestionList::ANSWER_STATUS_FILTER_WRONG_ANSWERED_ONLY:
				$items[] = $this->lng->txt('tst_question_answer_status_wrong_answered');
				break;
			
			case ilAssQuestionList::ANSWER_STATUS_FILTER_ALL_NON_CORRECT:
			default:
				$items[] = $this->lng->txt('tst_question_answer_status_all_non_correct');
		}
		
		foreach($this->dynamicQuestionSetConfig->getFilterTaxonomyIds() as $taxId)
		{
			if( $this->testSession->getQuestionSetFilterSelection()->hasSelectedTaxonomy($taxId) )
			{
				$selection = $this->testSession->getQuestionSetFilterSelection()->getSelectedTaxonomy($taxId);
				
				foreach($selection as $k => $v)
				{
					$selection[$k] = ilTaxonomyNode::_lookupTitle($v);
				}
				
				$items[] = $DIC->ui()->factory()->legacy(
					ilObject::_lookupTitle($taxId).': '.implode(', ', $selection)
				);
			}
		}
		
		return $DIC->ui()->factory()->listing()->unordered($items);
	}
	
	protected function getAnsweringStatisticListing($data, $totalQuestionsLabel, $dividerEnabled)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		
		$descriptive = $DIC->ui()->factory()->listing()->descriptive([
			$totalQuestionsLabel => (string)$data['total_all'],
			'' => (string)$data['total_all'],
		]);
		
		$panel = $DIC->ui()->factory()->panel()->data('')->withDividerEnabled($dividerEnabled);

		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy($totalQuestionsLabel),
			$DIC->ui()->factory()->legacy((string)$data['total_all'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions Answered Fully Correct'),
			$DIC->ui()->factory()->legacy((string)$data['correct_answered'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions Answered Wrongly'),
			$DIC->ui()->factory()->legacy((string)$data['wrong_answered'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions not Answered, yet'),
			$DIC->ui()->factory()->legacy((string)($data['non_answered_notseen'] + $data['non_answered_skipped']))
		);
		
		if( false )
		{
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Open Questions'),
				$DIC->ui()->factory()->legacy((string)$data['total_open'])
			);
			
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Never Seen Questions'),
				$DIC->ui()->factory()->legacy((string)$data['non_answered_notseen'])
			);
			
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Skipped Questions'),
				$DIC->ui()->factory()->legacy((string)$data['non_answered_skipped'])
			);
		}
		
		return $panel;
	}

	protected function getSelectedQuestionsStatisticsHTML($data, $allQuestionsLabel)
	{
		global $DIC;
		
		$panel = $DIC->ui()->factory()->panel()->data('')->withDividerEnabled(true);
		#var_dump($data); exit;
		
		foreach($this->dynamicQuestionSetConfig->getFilterTaxonomyIds() as $taxId)
		{
			if( $this->testSession->getQuestionSetFilterSelection()->hasSelectedTaxonomy($taxId) )
			{
				$selection = $this->testSession->getQuestionSetFilterSelection()->getSelectedTaxonomy($taxId);
				
				foreach($selection as $k => $v)
				{
					$selection[$k] = ilTaxonomyNode::_lookupTitle($v);
				}
				
				$selection = implode(', ', $selection);
				
				$panel->withAdditionalEntry(
					$DIC->ui()->factory()->legacy(ilObject::_lookupTitle($taxId)),
					$DIC->ui()->factory()->legacy($selection)
				);
			}
		}

		switch( $this->testSession->getQuestionSetFilterSelection()->getAnswerStatusSelection() )
		{
			case ilAssQuestionList::ANSWER_STATUS_FILTER_NON_ANSWERED_ONLY:
				$answerStatusFilterLabel = $this->lng->txt('tst_question_answer_status_non_answered');
				break;
				
			case ilAssQuestionList::ANSWER_STATUS_FILTER_WRONG_ANSWERED_ONLY:
				$answerStatusFilterLabel = $this->lng->txt('tst_question_answer_status_wrong_answered');
				break;
			
			case ilAssQuestionList::ANSWER_STATUS_FILTER_ALL_NON_CORRECT:
			default:
				$answerStatusFilterLabel = $this->lng->txt('tst_question_answer_status_all_non_correct');
		}

		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions to be Presented'),
			$DIC->ui()->factory()->legacy($answerStatusFilterLabel)
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy($allQuestionsLabel),
			$DIC->ui()->factory()->legacy((string)$data['total_all'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions Answered Fully Correct'),
			$DIC->ui()->factory()->legacy((string)$data['correct_answered'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions Answered Wrongly'),
			$DIC->ui()->factory()->legacy((string)$data['wrong_answered'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions not Answered, yet'),
			$DIC->ui()->factory()->legacy((string)($data['non_answered_notseen'] + $data['non_answered_skipped']))
		);
		
		if( false )
		{
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Open Questions'),
				$DIC->ui()->factory()->legacy((string)$data['total_open'])
			);
			
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Never Seen Questions'),
				$DIC->ui()->factory()->legacy((string)$data['non_answered_notseen'])
			);
			
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Skipped Questions'),
				$DIC->ui()->factory()->legacy((string)$data['non_answered_skipped'])
			);
		}
		
		$panelOuter = $DIC->ui()->factory()->panel()->standard('Currently Selected Questions', $panel);
		
		return $DIC->ui()->renderer()->render($panelOuter);
	}

	protected function getCompleteQuestionPoolStatisticsHTML($data, $allQuestionsLabel)
	{
		global $DIC;
		
		$panel = $DIC->ui()->factory()->panel()->data('')->withDividerEnabled(true);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy($allQuestionsLabel),
			$DIC->ui()->factory()->legacy((string)$data['total_all'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions Answered Fully Correct'),
			$DIC->ui()->factory()->legacy((string)$data['correct_answered'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions Answered Wrongly'),
			$DIC->ui()->factory()->legacy((string)$data['wrong_answered'])
		);
		
		$panel->withAdditionalEntry(
			$DIC->ui()->factory()->legacy('Questions not Answered, yet'),
			$DIC->ui()->factory()->legacy((string)($data['non_answered_notseen'] + $data['non_answered_skipped']))
		);
		
		if( false )
		{
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Open Questions'),
				$DIC->ui()->factory()->legacy((string)$data['total_open'])
			);
			
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Never Seen Questions'),
				$DIC->ui()->factory()->legacy((string)$data['non_answered_notseen'])
			);
			
			$panel->withAdditionalEntry(
				$DIC->ui()->factory()->legacy('Skipped Questions'),
				$DIC->ui()->factory()->legacy((string)$data['non_answered_skipped'])
			);
		}
		
		$panelOuter = $DIC->ui()->factory()->panel()->standard('Complete Question Pool', $panel);
		
		return $DIC->ui()->renderer()->render($panelOuter);
	}
	
	protected function filterQuestionSelectionCmd()
	{
		$tableGUI = $this->buildQuestionSetFilteredStatisticTableGUI();
		$tableGUI->writeFilterToSession();

		$taxFilterSelection = array();
		$answerStatusFilterSelection = ilAssQuestionList::ANSWER_STATUS_FILTER_ALL_NON_CORRECT;
		
		foreach( $tableGUI->getFilterItems() as $item )
		{
			if( strpos($item->getPostVar(), 'tax_') !== false )
			{
				$taxId = substr( $item->getPostVar(), strlen('tax_') );
				$taxFilterSelection[$taxId] = $item->getValue();
			}
			elseif( $item->getPostVar() == 'question_answer_status' )
			{
				$answerStatusFilterSelection = $item->getValue();
			}
		}
		
		$this->testSession->getQuestionSetFilterSelection()->setTaxonomySelection($taxFilterSelection);
		$this->testSession->getQuestionSetFilterSelection()->setAnswerStatusSelection($answerStatusFilterSelection);
		$this->testSession->saveToDb();
		
		$this->testSequence->resetTrackedQuestionList();
		$this->testSequence->saveToDb();

		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION_SELECTION);
	}
	
	protected function resetQuestionSelectionCmd()
	{
		$tableGUI = $this->buildQuestionSetFilteredStatisticTableGUI();
		$tableGUI->resetFilter();
		
		$this->testSession->getQuestionSetFilterSelection()->setTaxonomySelection( array() );
		$this->testSession->getQuestionSetFilterSelection()->setAnswerStatusSelection( null );
		$this->testSession->saveToDb();
		
		$this->testSequence->resetTrackedQuestionList();
		$this->testSequence->saveToDb();
		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION_SELECTION);
	}

	protected function previousQuestionCmd()
	{
		// nothing to do, won't be called
	}

	protected function fromPassDeletionCmd()
	{
		$this->resetCurrentQuestion();
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}
	
	protected function nextQuestionCmd()
	{
		$isWorkedThrough = assQuestion::_isWorkedThrough(
			$this->testSession->getActiveId(), $this->testSession->getCurrentQuestionId(), $this->testSession->getPass()
		);

		if( !$isWorkedThrough )
		{
			$this->testSequence->setQuestionPostponed($this->testSession->getCurrentQuestionId());
			$this->testSequence->saveToDb();
		}
		
		$this->resetCurrentQuestion();
		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}
	
	protected function markQuestionCmd()
	{
		global $DIC;
		$ilUser = $DIC['ilUser'];
		$this->object->setQuestionSetSolved(1, $this->testSession->getCurrentQuestionId(), $ilUser->getId());
		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}

	protected function unmarkQuestionCmd()
	{
		global $DIC;
		$ilUser = $DIC['ilUser'];
		$this->object->setQuestionSetSolved(0, $this->testSession->getCurrentQuestionId(), $ilUser->getId());
		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}

	protected function editSolutionCmd()
	{
		$this->ctrl->setParameter($this, 'pmode', ilTestPlayerAbstractGUI::PRESENTATION_MODE_EDIT);
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}

	protected function submitSolutionAndNextCmd()
	{
		if( $this->object->isForceInstantFeedbackEnabled() )
		{
			return $this->submitSolutionCmd();
		}

		if( $this->saveQuestionSolution(true, false) )
		{
			$questionId = $this->testSession->getCurrentQuestionId();

			$this->getQuestionInstance($questionId)->removeIntermediateSolution(
				$this->testSession->getActiveId(), $this->testSession->getPass()
			);

			$this->persistQuestionAnswerStatus();

			$this->ctrl->setParameter($this, 'pmode', '');

			$this->resetCurrentQuestion();
		}

		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}
	
	protected function submitSolutionCmd()
	{
		if( $this->saveQuestionSolution(true, false) )
		{
			$questionId = $this->testSession->getCurrentQuestionId();

			$this->getQuestionInstance($questionId)->removeIntermediateSolution(
				$this->testSession->getActiveId(), $this->testSession->getPass()
			);
			
			$this->persistQuestionAnswerStatus();

			if( $this->object->isForceInstantFeedbackEnabled() )
			{
				$this->ctrl->setParameter($this, 'instresp', 1);

				$this->testSequence->unsetQuestionPostponed($questionId);
				$this->testSequence->setQuestionChecked($questionId);
				$this->testSequence->saveToDb();
			}

			if( $this->getNextCommandParameter() )
			{
				if( $this->getNextSequenceParameter() )
				{
					$this->ctrl->setParameter($this, 'sequence', $this->getNextSequenceParameter());
					$this->ctrl->setParameter($this, 'pmode', '');
				}

				$this->ctrl->redirect($this, $this->getNextCommandParameter());
			}

			$this->ctrl->setParameter($this, 'pmode', ilTestPlayerAbstractGUI::PRESENTATION_MODE_VIEW);
		}
		else
		{
			$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
		}

// fau: testNav - remember to prevent the navigation confirmation
		$this->saveNavigationPreventConfirmation();
// fau.

// fau: testNav - handle navigation after saving
		if ($this->getNavigationUrlParameter())
		{
			ilUtil::redirect($this->getNavigationUrlParameter());
		}
		else
		{
			$this->ctrl->saveParameter($this, 'sequence');
		}
// fau.
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}

	protected function discardSolutionCmd()
	{
		$questionId = $this->testSession->getCurrentQuestionId();

		$currentQuestionOBJ = $this->getQuestionInstance($questionId);

		$currentQuestionOBJ->resetUsersAnswer(
			$this->testSession->getActiveId(), $this->testSession->getPass()
		);
		
		$this->ctrl->setParameter($this, 'pmode', ilTestPlayerAbstractGUI::PRESENTATION_MODE_VIEW);

		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}

	protected function skipQuestionCmd()
	{
		$this->nextQuestionCmd();
	}

	protected function isCheckedQuestionResettingConfirmationRequired()
	{
		if( !$this->getResetCheckedParameter() )
		{
			return false;
		}
		
		if( $this->testSession->getQuestionSetFilterSelection()->isAnswerStatusSelectionWrongAnswered() )
		{
			$this->testSequence->loadQuestions(
				$this->dynamicQuestionSetConfig, $this->testSession->getQuestionSetFilterSelection()
			);

			if( $this->testSequence->hasFilteredQuestionListCheckedQuestions() )
			{
				return true;
			}
		}

		return false;
	}
	
	protected function showQuestionCmd()
	{
		$this->updateWorkingTime();
		
		$this->testSequence->loadQuestions(
				$this->dynamicQuestionSetConfig, $this->testSession->getQuestionSetFilterSelection()
		);
		
		$this->testSequence->cleanupQuestions($this->testSession);
		
		if( $this->isCheckedQuestionResettingConfirmationRequired() )
		{
			$this->showCheckedQuestionResettingConfirmation();
			return;
		}
		
		if( $this->testSequence->getQuestionSet()->getSelectionQuestionList()->isInList($this->getQuestionIdParameter()) )
		{
			$this->testSession->setCurrentQuestionId($this->getQuestionIdParameter());
		}
		else
		{
			$this->resetQuestionIdParameter();
		}
		
		if( !$this->testSession->getCurrentQuestionId() )
		{
			$upComingQuestionId = $this->testSequence->getUpcomingQuestionId();
			
			$this->testSession->setCurrentQuestionId($upComingQuestionId);
			
			// seems to be a first try of freezing answers not too hard
			/*if( $this->testSequence->isQuestionChecked($upComingQuestionId) )
			{
				$this->testSequence->setQuestionUnchecked($upComingQuestionId);
			}*/
		}

		$navigationToolbarGUI = $this->getTestNavigationToolbarGUI();
		$navigationToolbarGUI->setQuestionSelectionButtonEnabled(true);

		if( $this->testSession->getCurrentQuestionId() )
		{
			$questionGui = $this->getQuestionGuiInstance($this->testSession->getCurrentQuestionId());
			$this->testSequence->setCurrentQuestionId($this->testSession->getCurrentQuestionId());

			$questionGui->setQuestionCount(
				$this->testSequence->getLastPositionIndex()
			);
			$questionGui->setSequenceNumber(
				$this->testSequence->getCurrentPositionIndex($this->testSession->getCurrentQuestionId())
			);

			if( !($questionGui instanceof assQuestionGUI) )
			{
				$this->handleTearsAndAngerQuestionIsNull(
					$this->testSession->getCurrentQuestionId(), $this->testSession->getCurrentQuestionId()
				);
			}

			$isQuestionWorkedThrough = assQuestion::_isWorkedThrough(
				$this->testSession->getActiveId(), $this->testSession->getCurrentQuestionId(), $this->testSession->getPass()
			);

			require_once 'Modules/Test/classes/class.ilTestQuestionHeaderBlockBuilder.php';
			$headerBlockBuilder = new ilTestQuestionHeaderBlockBuilder($this->lng);
			$headerBlockBuilder->setHeaderMode($this->object->getTitleOutput());
			$headerBlockBuilder->setQuestionTitle($questionGui->object->getTitle());
			$headerBlockBuilder->setQuestionPoints($questionGui->object->getPoints());
			$headerBlockBuilder->setQuestionPosition(
				$this->testSequence->getCurrentPositionIndex($this->testSession->getCurrentQuestionId())
			);
			$headerBlockBuilder->setQuestionCount($this->testSequence->getLastPositionIndex());
			$headerBlockBuilder->setQuestionPostponed($this->testSequence->isPostponedQuestion(
				$this->testSession->getCurrentQuestionId())
			);
			$headerBlockBuilder->setQuestionObligatory(
				$this->object->areObligationsEnabled() && ilObjTest::isQuestionObligatory($this->object->getId())
			);
			$questionGui->setQuestionHeaderBlockBuilder($headerBlockBuilder);

// fau: testNav - always use edit mode, except for fixed answer
			if( $this->isParticipantsAnswerFixed($this->testSession->getCurrentQuestionId()) )
			{
				$instantResponse = true;
				$presentationMode = ilTestPlayerAbstractGUI::PRESENTATION_MODE_VIEW;
			}
			else
			{
				$instantResponse = $this->getInstantResponseParameter();
				$presentationMode = ilTestPlayerAbstractGUI::PRESENTATION_MODE_EDIT;
			}
// fau.

			$this->prepareTestPage($presentationMode,
				$this->testSession->getCurrentQuestionId(), $this->testSession->getCurrentQuestionId()
			);
			
			$this->ctrl->setParameter($this, 'sequence', $this->testSession->getCurrentQuestionId());
			$this->ctrl->setParameter($this, 'pmode', $presentationMode);
			$formAction = $this->ctrl->getFormAction($this, ilTestPlayerCommands::SUBMIT_INTERMEDIATE_SOLUTION);
			
			switch($presentationMode)
			{
				case ilTestPlayerAbstractGUI::PRESENTATION_MODE_EDIT:

// fau: testNav - enable navigation toolbar in edit mode
					$navigationToolbarGUI->setDisabledStateEnabled(false);
// fau.
					$this->showQuestionEditable($questionGui, $formAction, $isQuestionWorkedThrough, $instantResponse);
					
					break;

				case ilTestPlayerAbstractGUI::PRESENTATION_MODE_VIEW:

					$this->showQuestionViewable($questionGui, $formAction, $isQuestionWorkedThrough, $instantResponse);
					
					break;

				default:

					require_once 'Modules/Test/exceptions/class.ilTestException.php';
					throw new ilTestException('no presentation mode given');
			}
			
			$navigationToolbarGUI->build(true);
			$this->populateTestNavigationToolbar($navigationToolbarGUI);

// fau: testNav - enable the question navigation in edit mode
			$this->populateQuestionNavigation(
				$this->testSession->getCurrentQuestionId(), false, $this->object->isForceInstantFeedbackEnabled()
			);
// fau.

			if ($instantResponse)
			{
// fau: testNav - always use authorized solution for instant feedback
				$this->populateInstantResponseBlocks(
					$questionGui, true
				);
// fau.
			}

// fau: testNav - add feedback modal
			if ($this->isForcedFeedbackNavUrlRegistered())
			{
				$this->populateInstantResponseModal($questionGui, $this->getRegisteredForcedFeedbackNavUrl());
				$this->unregisterForcedFeedbackNavUrl();
			}
// fau.
		}
		else
		{
			$this->prepareTestPage(ilTestPlayerAbstractGUI::PRESENTATION_MODE_VIEW, null, null);

			$navigationToolbarGUI->build(true);
			$this->populateTestNavigationToolbar($navigationToolbarGUI);
			
			$this->outCurrentlyFinishedPage();
		}
		
		$this->testSequence->saveToDb();
		$this->testSession->saveToDb();
	}
	
	protected function showInstantResponseCmd()
	{
		$questionId = $this->testSession->getCurrentQuestionId();
		
		$filterSelection = $this->testSession->getQuestionSetFilterSelection();

		$filterSelection->setForcedQuestionIds(array($this->testSession->getCurrentQuestionId()));

		$this->testSequence->loadQuestions($this->dynamicQuestionSetConfig, $filterSelection);
		$this->testSequence->cleanupQuestions($this->testSession);
		$this->testSequence->saveToDb();

		if( !$this->isParticipantsAnswerFixed($questionId) )
		{
			if( $this->saveQuestionSolution(true) )
			{
				$this->removeIntermediateSolution();
				$this->setAnswerChangedParameter(false);
			}
			else
			{
				$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
			}
			$this->testSequence->unsetQuestionPostponed($questionId);
			$this->testSequence->setQuestionChecked($questionId);
			$this->testSequence->saveToDb();
		}

		$this->ctrl->setParameter(
			$this, 'sequence', $this->testSession->getCurrentQuestionId()
		);

		$this->ctrl->setParameter($this, 'instresp', 1);
		
// fau: testNav - handle navigation after feedback
		if ($this->getNavigationUrlParameter())
		{
			$this->saveNavigationPreventConfirmation();
			$this->registerForcedFeedbackNavUrl($this->getNavigationUrlParameter());
		}
// fau.
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}
	
	protected function handleQuestionActionCmd()
	{
		$questionId = $this->testSession->getCurrentQuestionId();

		if( $questionId && !$this->isParticipantsAnswerFixed($questionId) )
		{
			$this->saveQuestionSolution(false);
// fau: testNav - add changed status of the question
			$this->setAnswerChangedParameter(true);
// fau.
		}

		$this->ctrl->setParameter(
				$this, 'sequence', $this->testSession->getCurrentQuestionId()
		);

		$this->ctrl->saveParameter($this, 'pmode');
		
		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}

	private function outCurrentlyFinishedPage()
	{
		if( $this->testSequence->openQuestionExists() )
		{
			$message = $this->lng->txt('tst_dyn_test_msg_currently_finished_selection');
		}
		else
		{
			$message = $this->lng->txt('tst_dyn_test_msg_currently_finished_completely');
			$message .= "<br /><br />{$this->buildFinishPagePassDeletionLink()}";
		}
		
		$msgHtml = $this->tpl->getMessageHTML($message);
		
		$tpl = new ilTemplate('tpl.test_currently_finished_msg.html', true, true, 'Modules/Test');
		$tpl->setVariable('TEST_CURRENTLY_FINISHED_MSG', $msgHtml);
		
		$this->tpl->setVariable('QUESTION_OUTPUT', $tpl->get());
	}
	
	protected function isFirstQuestionInSequence($sequenceElement)
	{
		return !$this->testSequence->trackedQuestionExists();
	}

	protected function isLastQuestionInSequence($sequenceElement)
	{
		return false; // always
	}
	
	/**
	 * Returns TRUE if the answers of the current user could be saved
	 *
	 * @return boolean TRUE if the answers could be saved, FALSE otherwise
	 */
	 protected function canSaveResult() 
	 {
		 return !$this->object->endingTimeReached();
	 }
	 
	/**
	 * saves the user input of a question
	 */
	public function saveQuestionSolution($authorized = true, $force = false)
	{
		// what is this formtimestamp ??
		if (!$force)
		{
			$formtimestamp = $_POST["formtimestamp"];
			if (strlen($formtimestamp) == 0) $formtimestamp = $_GET["formtimestamp"];
			if ($formtimestamp != $_SESSION["formtimestamp"])
			{
				$_SESSION["formtimestamp"] = $formtimestamp;
			}
			else
			{
				return FALSE;
			}
		}
		
		// determine current question
		
		$qId = $this->testSession->getCurrentQuestionId();
		
		if( !$qId || $qId != $_GET["sequence"])
		{
			return false;
		}
		
		// save question solution
		
		$this->saveResult = FALSE;

		if ($this->canSaveResult($qId) || $force)
		{
				$questionGUI = $this->object->createQuestionGUI("", $qId);
				
				if( $this->object->getJavaScriptOutput() )
				{
					$questionGUI->object->setOutputType(OUTPUT_JAVASCRIPT);
				}
				
				$activeId = $this->testSession->getActiveId();
				
				$this->saveResult = $questionGUI->object->persistWorkingState(
						$activeId, $pass = null, $this->object->areObligationsEnabled(), $authorized
				);
			
				if( $authorized && $this->object->isSkillServiceToBeConsidered() )
				{
					$this->handleSkillTriggering($this->testSession);
				}
		}
		
		if ($this->saveResult == FALSE)
		{
			$this->ctrl->setParameter($this, "save_error", "1");
			$_SESSION["previouspost"] = $_POST;
		}
		
		return $this->saveResult;
	}
	
	private function isQuestionAnsweredCorrect($questionId, $activeId, $pass)
	{
		$questionGUI = $this->object->createQuestionGUI("", $questionId);

		$reachedPoints = assQuestion::_getReachedPoints($activeId, $questionId, $pass);
		$maxPoints = $questionGUI->object->getMaximumPoints();
		
		if($reachedPoints < $maxPoints)
		{
			return false;
		}
		
		return true;
	}
	
	protected function buildQuestionsTableDataArray($questions, $marked_questions)
	{
		$data = array();
		
		foreach($questions as $key => $value )
		{
			$this->ctrl->setParameter($this, 'sequence', $value['question_id']);
			$href = $this->ctrl->getLinkTarget($this, 'gotoQuestion');
			$this->ctrl->setParameter($this, 'sequence', '');
			
			$description = "";
			if( $this->object->getListOfQuestionsDescription() )
			{
				$description = $value["description"];
			}
			
			$marked = false;
			if( count($marked_questions) )
			{
				if( isset($marked_questions[$value["question_id"]]) )
				{
					if( $marked_questions[$value["question_id"]]["solved"] == 1 )
					{
						$marked = true;
					}
				} 
			}
			
			array_push($data, array(
				'href' => $href,
				'title' => $this->object->getQuestionTitle($value["title"]),
				'description' => $description,
				'worked_through' => $this->testSequence->isAnsweredQuestion($value["question_id"]),
				'postponed' => $this->testSequence->isPostponedQuestion($value["question_id"]),
				'marked' => $marked
			));
		}
		
		return $data;
	}

	protected function buildQuestionSetAnswerStatisticRowArray($questions, $trackedQuestions)
	{
		$questionAnswerStats = array(
			'total_all' => count($questions),
			'total_open' => 0,
			'non_answered_notseen' => 0,
			'non_answered_skipped' => 0,
			'wrong_answered' => 0,
			'correct_answered' => 0
		);

		foreach($questions as $key => $value )
		{
			switch( $value['question_answer_status'] )
			{
				case ilAssQuestionList::QUESTION_ANSWER_STATUS_NON_ANSWERED:
					if( isset($trackedQuestions[$key]) )
					{
						$questionAnswerStats['non_answered_skipped']++;
					}
					else
					{
						$questionAnswerStats['non_answered_notseen']++;
					}
					$questionAnswerStats['total_open']++;
					break;
				case ilAssQuestionList::QUESTION_ANSWER_STATUS_WRONG_ANSWERED:
					$questionAnswerStats['wrong_answered']++;
					$questionAnswerStats['total_open']++;
					break;
				case ilAssQuestionList::QUESTION_ANSWER_STATUS_CORRECT_ANSWERED:
					$questionAnswerStats['correct_answered']++;
					break;
			}
		}

		return $questionAnswerStats;
	}

	private function buildQuestionSetCompleteStatisticTableGUI()
	{
		require_once 'Modules/Test/classes/tables/class.ilTestDynamicQuestionSetStatisticTableGUI.php';
		$gui = $this->buildQuestionSetStatisticTableGUI(
			ilTestDynamicQuestionSetStatisticTableGUI::COMPLETE_TABLE_ID
		);

		$gui->initTitle('tst_dynamic_question_set_complete');
		$gui->initColumns('tst_num_all_questions');

		return $gui;
	}
	
	private function buildQuestionSetFilteredStatisticTableGUI()
	{
		require_once 'Modules/Test/classes/tables/class.ilTestDynamicQuestionSetStatisticTableGUI.php';
		$gui = $this->buildQuestionSetStatisticTableGUI(
			ilTestDynamicQuestionSetStatisticTableGUI::FILTERED_TABLE_ID
		);

		$gui->initTitle('tst_dynamic_question_set_selection');
		$gui->initColumns('tst_num_selected_questions');

		require_once 'Services/Taxonomy/classes/class.ilObjTaxonomy.php';
		$gui->setTaxIds(ilObjTaxonomy::getUsageOfObject(
			$this->dynamicQuestionSetConfig->getSourceQuestionPoolId()
		));

		$gui->setTaxonomyFilterEnabled($this->dynamicQuestionSetConfig->isTaxonomyFilterEnabled());
		$gui->setAnswerStatusFilterEnabled($this->dynamicQuestionSetConfig->isAnswerStatusFilterEnabled());

		$gui->setFilterSelection($this->testSession->getQuestionSetFilterSelection());
		$gui->initFilter();
		$gui->setFilterCommand('filterQuestionSelection');
		$gui->setResetCommand('resetQuestionSelection');
		
		return $gui;
	}
		
	private function buildQuestionSetStatisticTableGUI($tableId)
	{
		require_once 'Modules/Test/classes/tables/class.ilTestDynamicQuestionSetStatisticTableGUI.php';
		$gui = new ilTestDynamicQuestionSetStatisticTableGUI(
				$this->ctrl, $this->lng, $this, ilTestPlayerCommands::SHOW_QUESTION_SELECTION, $tableId
		);

		return $gui;
	}
	
	private function getEnterTestButtonLangVar()
	{
		if( $this->testSequence->trackedQuestionExists() )
		{
			return 'tst_resume_dyn_test_with_cur_quest_sel';
		}
		
		return 'tst_start_dyn_test_with_cur_quest_sel';
	}

	protected function persistQuestionAnswerStatus()
	{
		$questionId = $this->testSession->getCurrentQuestionId();
		$activeId = $this->testSession->getActiveId();
		$pass = $this->testSession->getPass();

		if($this->isQuestionAnsweredCorrect($questionId, $activeId, $pass))
		{
			$this->testSequence->setQuestionAnsweredCorrect($questionId);
		}
		else
		{
			$this->testSequence->setQuestionAnsweredWrong($questionId);
		}

		$this->testSequence->saveToDb();
	}

	private function resetCurrentQuestion()
	{
		$this->testSession->setCurrentQuestionId(null);
		$this->testSession->saveToDb();

		$this->ctrl->setParameter($this, 'sequence', $this->testSession->getCurrentQuestionId());
		$this->ctrl->setParameter($this, 'pmode', '');
	}

	/**
	 * @return string
	 */
	private function buildFinishPagePassDeletionLink()
	{
		$href = $this->getPassDeletionTarget();

		$label = $this->lng->txt('tst_dyn_test_msg_pass_deletion_link');

		return "<a href=\"{$href}\">{$label}</a>";
	}

	/**
	 * @return string
	 */
	private function getPassDeletionTarget()
	{
		require_once 'Modules/Test/classes/confirmations/class.ilTestPassDeletionConfirmationGUI.php';
		
		$this->ctrl->setParameterByClass('ilTestEvaluationGUI', 'context', ilTestPassDeletionConfirmationGUI::CONTEXT_DYN_TEST_PLAYER);
		$this->ctrl->setParameterByClass('ilTestEvaluationGUI', 'active_id', $this->testSession->getActiveId());
		$this->ctrl->setParameterByClass('ilTestEvaluationGUI', 'pass', $this->testSession->getPass());

		return $this->ctrl->getLinkTargetByClass('ilTestEvaluationGUI', 'confirmDeletePass');
	}
	
	protected function resetQuestionIdParameter()
	{
		$this->resetSequenceElementParameter();
	}
	
	protected function getQuestionIdParameter()
	{
		return $this->getSequenceElementParameter();
	}
	
	protected function getResetCheckedParameter()
	{
		if( isset($_GET['reset_checked']) )
		{
			return $_GET['reset_checked'];
		}

		return null;

	}

	public function outQuestionSummaryCmd($fullpage = true, $contextFinishTest = false, $obligationsNotAnswered = false, $obligationsFilter = false)
	{
		$this->testSequence->loadQuestions(
			$this->dynamicQuestionSetConfig, $this->testSession->getQuestionSetFilterSelection()
		);

		$this->testSequence->setCurrentQuestionId($this->testSession->getCurrentQuestionId());
		
		parent::outQuestionSummaryCmd($fullpage, $contextFinishTest, $obligationsNotAnswered, $obligationsFilter);
	}
	
	protected function showCheckedQuestionResettingConfirmation()
	{
		require_once 'Services/Utilities/classes/class.ilConfirmationGUI.php';
		$confirmation = new ilConfirmationGUI();
		$confirmation->setFormAction($this->ctrl->getFormAction($this));
		$confirmation->setHeaderText($this->lng->txt('tst_dyn_unfreeze_answers_confirmation'));
		$confirmation->setConfirm($this->lng->txt('tst_dyn_unfreeze_answers'), ilTestPlayerCommands::UNFREEZE_ANSWERS);
		$confirmation->setCancel($this->lng->txt('tst_dyn_keep_answ_freeze'), ilTestPlayerCommands::SHOW_QUESTION);

		$this->populateMessageContent($confirmation->getHtml());
	}
	
	protected function unfreezeCheckedQuestionsAnswersCmd()
	{
		$this->testSequence->loadQuestions(
			$this->dynamicQuestionSetConfig, $this->testSession->getQuestionSetFilterSelection()
		);

		$this->testSequence->resetFilteredQuestionListsCheckedStatus();
		$this->testSequence->saveToDb();

		$this->ctrl->redirect($this, ilTestPlayerCommands::SHOW_QUESTION);
	}

	protected function populateQuestionNavigation($sequenceElement, $disabled, $primaryNext)
	{
		if( !$this->isLastQuestionInSequence($sequenceElement) )
		{
			$this->populateNextButtons($disabled, $primaryNext);
		}
	}
	
	protected function getStartTestFromQuestionSelectionLink()
	{
		$this->ctrl->setParameter($this, 'reset_checked', 1);		
		$link = $this->ctrl->getLinkTarget($this, ilTestPlayerCommands::SHOW_QUESTION);
		$this->ctrl->setParameter($this, 'reset_checked', '');

		return $link;
	}

	protected function isShowingPostponeStatusReguired($questionId)
	{
		return false;
	}

	protected function buildTestPassQuestionList()
	{
		global $DIC;
		$ilPluginAdmin = $DIC['ilPluginAdmin'];

		require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionList.php';
		$questionList = new ilAssQuestionList($this->db, $this->lng, $ilPluginAdmin);
		$questionList->setParentObjId($this->dynamicQuestionSetConfig->getSourceQuestionPoolId());
		$questionList->setQuestionInstanceTypeFilter(ilAssQuestionList::QUESTION_INSTANCE_TYPE_ORIGINALS);

		return $questionList;
	}

	protected function isQuestionSummaryFinishTestButtonRequired()
	{
		return false;
	}
	
	protected function isOptionalQuestionAnsweringConfirmationRequired($sequenceKey)
	{
		return false;
	}
}
