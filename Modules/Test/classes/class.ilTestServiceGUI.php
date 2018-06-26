<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Modules/Test/classes/inc.AssessmentConstants.php";
include_once 'Modules/Test/classes/class.ilTestService.php';

/**
* Service GUI class for tests. This class is the parent class for all
* service classes which are called from ilObjTestGUI. This is mainly
* done to reduce the size of ilObjTestGUI to put command service functions
* into classes that could be called by ilCtrl.
*
* @ilCtrl_IsCalledBy ilTestServiceGUI: ilObjTestGUI
*
* @author	Helmut Schottmüller <helmut.schottmueller@mac.com>
* @author	Björn Heyser <bheyser@databay.de>
* @version	$Id$
*
* @ingroup ModulesTest
*/
class ilTestServiceGUI
{
	/**
	 * @var ilObjTest
	 */
	public $object = null;

	/**
	 * @var ilTestService
	 */
	public $service = null;

	/**
	 * @var ilDBInterface
	 */
	protected $db;

	var $lng;
	var $tpl;

	/**
	 * @var ilCtrl
	 */
	var $ctrl;

	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;

	/**
	 * @var ilObjectDataCache
	 */
	protected $objCache;

	var $ilias;
	var $tree;
	var $ref_id;

	/**
	 * factory for test session
	 *
	 * @var ilTestSessionFactory
	 */
	protected $testSessionFactory = null;

	/**
	 * factory for test sequence
	 *
	 * @var ilTestSequenceFactory
	 */
	protected $testSequenceFactory = null;

	/**
	 * @var ilTestParticipantData
	 */
	protected $participantData;

	/**
	 * @var ilTestObjectiveOrientedContainer
	 */
	private $objectiveOrientedContainer;

	private $contextResultPresentation = true;

	/**
	 * @return boolean
	 */
	public function isContextResultPresentation()
	{
		return $this->contextResultPresentation;
	}

	/**
	 * @param boolean $contextResultPresentation
	 */
	public function setContextResultPresentation($contextResultPresentation)
	{
		$this->contextResultPresentation = $contextResultPresentation;
	}

	/**
	 * The constructor takes the test object reference as parameter
	 *
	 * @param object $a_object Associated ilObjTest class
	 * @access public
	 */
	public function __construct(ilObjTest $a_object)
	{
		global $lng, $tpl, $ilCtrl, $ilias, $tree, $ilDB, $ilPluginAdmin, $ilTabs, $ilObjDataCache;

		$this->db = $ilDB;
		$this->lng =& $lng;
		$this->tpl =& $tpl;
		$this->ctrl =& $ilCtrl;
		$this->tabs = $ilTabs;
		$this->objCache = $ilObjDataCache;
		$this->ilias =& $ilias;
		$this->object =& $a_object;
		$this->tree =& $tree;
		$this->ref_id = $a_object->ref_id;

		$this->service = new ilTestService($a_object);

		require_once 'Modules/Test/classes/class.ilTestSessionFactory.php';
		$this->testSessionFactory = new ilTestSessionFactory($this->object);

		require_once 'Modules/Test/classes/class.ilTestSequenceFactory.php';
		$this->testSequenceFactory = new ilTestSequenceFactory($ilDB, $lng, $ilPluginAdmin, $this->object);

		$this->objectiveOrientedContainer = null;
	}

	/**
	 * @param \ilTestParticipantData $participantData
	 */
	public function setParticipantData($participantData)
	{
		$this->participantData = $participantData;
	}

	/**
	 * @return \ilTestParticipantData
	 */
	public function getParticipantData()
	{
		return $this->participantData;
	}

	/**
	 * @param ilTestSession $testSession
	 * @param $short
	 * @return array
	 */
	public function getPassOverviewTableData(ilTestSession $testSession, $passes, $withResults)
	{
		$data = array();

		if($this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired())
		{
			$considerHiddenQuestions = false;

			require_once 'Modules/Course/classes/Objectives/class.ilLOTestQuestionAdapter.php';
			$objectivesAdapter = ilLOTestQuestionAdapter::getInstance($testSession);
		}
		else
		{
			$considerHiddenQuestions = true;
		}

		$scoredPass = $this->object->_getResultPass($testSession->getActiveId());

		require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionHintTracking.php';
		$questionHintRequestRegister = ilAssQuestionHintTracking::getRequestRequestStatisticDataRegisterByActiveId(
			$testSession->getActiveId()
		);

		foreach($passes as $pass)
		{
			$row = array();

			$considerOptionalQuestions = true;

			if($this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired())
			{
				$testSequence = $this->testSequenceFactory->getSequenceByActiveIdAndPass($testSession->getActiveId(), $pass);

				$testSequence->loadFromDb();
				$testSequence->loadQuestions();

				if($this->object->isRandomTest() && !$testSequence->isAnsweringOptionalQuestionsConfirmed())
				{
					$considerOptionalQuestions = false;
				}

				$testSequence->setConsiderHiddenQuestionsEnabled($considerHiddenQuestions);
				$testSequence->setConsiderOptionalQuestionsEnabled($considerOptionalQuestions);

				$objectivesList = $this->buildQuestionRelatedObjectivesList($objectivesAdapter, $testSequence);
				$objectivesList->loadObjectivesTitles();

				$row['objectives'] = $objectivesList->getUniqueObjectivesStringForQuestions($testSequence->getUserSequenceQuestions());
			}

			if($withResults)
			{
				$result_array = $this->object->getTestResult($testSession->getActiveId(), $pass, false, $considerHiddenQuestions, $considerOptionalQuestions);

				foreach($result_array as $resultStructKEY => $question)
				{
					if( $resultStructKEY === 'test' || $resultStructKEY === 'pass' )
					{
						continue;
					}

					$requestData = $questionHintRequestRegister->getRequestByTestPassIndexAndQuestionId($pass, $question['qid']);

					if( $requestData instanceof ilAssQuestionHintRequestStatisticData && $result_array[$resultStructKEY]['requested_hints'] === null )
					{
						$result_array['pass']['total_requested_hints'] += $requestData->getRequestsCount();

						$result_array[$resultStructKEY]['requested_hints'] = $requestData->getRequestsCount();
						$result_array[$resultStructKEY]['hint_points'] = $requestData->getRequestsPoints();
					}
				}

				if(!$result_array['pass']['total_max_points'])
				{
					$percentage = 0;
				} else
				{
					$percentage = ($result_array['pass']['total_reached_points'] / $result_array['pass']['total_max_points']) * 100;
				}
				$total_max = $result_array['pass']['total_max_points'];
				$total_reached = $result_array['pass']['total_reached_points'];
				$total_requested_hints = $result_array['pass']['total_requested_hints'];
			}

			if($withResults)
			{
				$row['scored'] = ($pass == $scoredPass);
			}

			$row['pass'] = $pass;
			$row['date'] = $this->object->getPassFinishDate($testSession->getActiveId(), $pass);
			if($withResults)
			{
				$row['num_workedthrough_questions'] = $result_array['pass']['num_workedthrough'];
				$row['num_questions_total'] = $result_array['pass']['num_questions_total'];

				if($this->object->isOfferingQuestionHintsEnabled())
				{
					$row['hints'] = $total_requested_hints;
				}

				$row['reached_points'] = $total_reached;
				$row['max_points'] = $total_max;
				$row['percentage'] = $percentage;
			}

			$data[] = $row;
		}

		return $data;
	}

	/**
	 * @param ilTestObjectiveOrientedContainer $objectiveOrientedContainer
	 */
	public function setObjectiveOrientedContainer(ilTestObjectiveOrientedContainer $objectiveOrientedContainer)
	{
		$this->objectiveOrientedContainer = $objectiveOrientedContainer;
	}

	/**
	 * @return ilTestObjectiveOrientedContainer
	 */
	public function getObjectiveOrientedContainer()
	{
		return $this->objectiveOrientedContainer;
	}

	/**
	 * execute command
	 */
	function executeCommand()
	{
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);

		$cmd = $this->getCommand($cmd);
		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}
		return $ret;
	}

	/**
	 * Retrieves the ilCtrl command
	 *
	 * @access public
	 */
	function getCommand($cmd)
	{
		return $cmd;
	}

	protected function handleTabs($activeTabId)
	{
		if( $this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired() )
		{
			require_once 'Services/Link/classes/class.ilLink.php';
			$courseLink = ilLink::_getLink($this->getObjectiveOrientedContainer()->getRefId());
			$this->tabs->setBack2Target($this->lng->txt('back_to_objective_container'), $courseLink);

			$this->tabs->addTab(
				'results_pass_oriented', $this->lng->txt('tst_tab_results_pass_oriented'),
				$this->ctrl->getLinkTargetByClass('ilTestEvaluationGUI', 'outUserResultsOverview')
			);

			$this->tabs->addTab(
				'results_objective_oriented', $this->lng->txt('tst_tab_results_objective_oriented'),
				$this->ctrl->getLinkTargetByClass('ilTestEvalObjectiveOrientedGUI', 'showVirtualPass')
			);

			$this->tabs->setTabActive($activeTabId);
		}
	}

	/**
	 * @return bool
	 */
	protected function isPdfDeliveryRequest()
	{
		if( !isset($_GET['pdf']) )
		{
			return false;
		}

		if( !(bool)$_GET['pdf'] )
		{
			return false;
		}

		return true;
	}

	/**
	 * @return ilTestPassOverviewTableGUI $tableGUI
	 */
	public function buildPassOverviewTableGUI($targetGUI)
	{
		require_once 'Modules/Test/classes/tables/class.ilTestPassOverviewTableGUI.php';

		$table = new ilTestPassOverviewTableGUI($targetGUI, '');

		$table->setPdfPresentationEnabled(
			isset($_GET['pdf']) && $_GET['pdf'] == 1
		);

		$table->setObjectiveOrientedPresentationEnabled(
			$this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired()
		);

		return $table;
	}

	/**
	 * Returns the list of answers of a users test pass
	 *
	 * @param array $result_array An array containing the results of the users test pass (generated by ilObjTest::getTestResult)
	 * @param integer $active_id Active ID of the active user
	 * @param integer $pass Test pass
	 * @param boolean $show_solutions TRUE, if the solution output should be shown in the answers, FALSE otherwise
	 * @return string HTML code of the list of answers
	 * @access public
	 */
	// uni-goettingen-patch: begin
	function getPassListOfAnswers(&$result_array, $active_id, $pass, $show_solutions = FALSE, $only_answered_questions = FALSE, $show_question_only = FALSE, $show_reached_points = FALSE, $anchorNav = false, ilTestQuestionRelatedObjectivesList $objectivesList = null, ilTestResultHeaderLabelBuilder $testResultHeaderLabelBuilder = null, $show_print_watermark = FALSE)
	// uni-goettingen-patch: end
	{
		// uni-goettingen-patch: begin
		global $ilUser;
		// uni-goettingen-patch: end
		$maintemplate = new ilTemplate("tpl.il_as_tst_list_of_answers.html", TRUE, TRUE, "Modules/Test");

		$counter = 1;
		// output of questions with solutions
		foreach ($result_array as $question_data)
		{
			if (($question_data["workedthrough"] == 1) || ($only_answered_questions == FALSE))
			{
				$template = new ilTemplate("tpl.il_as_qpl_question_printview.html", TRUE, TRUE, "Modules/TestQuestionPool");
				$question = $question_data["qid"];
				if (is_numeric($question))
				{
					$maintemplate->setCurrentBlock("printview_question");
					$question_gui = $this->object->createQuestionGUI("", $question);
					if (is_object($question_gui))
					{
						if( $this->isPdfDeliveryRequest() )
						{
							$question_gui->setRenderPurpose(assQuestionGUI::RENDER_PURPOSE_PRINT_PDF);
						}

						if($anchorNav)
						{
							$template->setCurrentBlock('block_id');
							$template->setVariable('BLOCK_ID', "detailed_answer_block_act_{$active_id}_qst_{$question}");
							$template->parseCurrentBlock();

							$template->setCurrentBlock('back_anchor');
							$template->setVariable('HREF_BACK_ANCHOR', "#pass_details_tbl_row_act_{$active_id}_qst_{$question}");
							$template->setVariable('TXT_BACK_ANCHOR', $this->lng->txt('tst_back_to_question_list'));
							$template->parseCurrentBlock();
						}

						if ($show_reached_points)
						{
							$template->setCurrentBlock("result_points");
							$template->setVariable("RESULT_POINTS", $this->lng->txt("tst_reached_points") . ": " . $question_gui->object->getReachedPoints($active_id, $pass) . " " . $this->lng->txt("of") . " " . $question_gui->object->getMaximumPoints());
							$template->parseCurrentBlock();
						}
						$template->setVariable("COUNTER_QUESTION", $counter.". ");
						$template->setVariable("TXT_QUESTION_ID", $this->lng->txt('question_id_short'));
						$template->setVariable("QUESTION_ID", $question_gui->object->getId());
						$template->setVariable("QUESTION_TITLE", $this->object->getQuestionTitle($question_gui->object->getTitle()));

						if( $objectivesList !== null )
						{
							$objectives = $this->lng->txt('tst_res_lo_objectives_header').': ';
							$objectives .= $objectivesList->getQuestionRelatedObjectiveTitles($question_gui->object->getId());
							$template->setVariable("OBJECTIVES", $objectives);
						}

						$show_question_only = ($this->object->getShowSolutionAnswersOnly()) ? TRUE : FALSE;

						$showFeedback = $this->isContextResultPresentation() && $this->object->getShowSolutionFeedback();
						$show_solutions = $this->isContextResultPresentation() && $show_solutions;

						if($show_solutions)
						{
							$compare_template = new ilTemplate('tpl.il_as_tst_answers_compare.html', TRUE, TRUE, 'Modules/Test');
							$compare_template->setVariable("HEADER_PARTICIPANT", $this->lng->txt('tst_header_participant'));
							$compare_template->setVariable("HEADER_SOLUTION", $this->lng->txt('tst_header_solution'));
							$result_output = $question_gui->getSolutionOutput($active_id, $pass, $show_solutions, FALSE, $show_question_only, $showFeedback);
							$best_output   = $question_gui->getSolutionOutput($active_id, $pass, FALSE, FALSE, $show_question_only, FALSE, TRUE);

							$compare_template->setVariable('PARTICIPANT', $result_output);
							$compare_template->setVariable('SOLUTION', $best_output);
							$template->setVariable('SOLUTION_OUTPUT', $compare_template->get());
						}
						else
						{
							$result_output = $question_gui->getSolutionOutput($active_id, $pass, $show_solutions, FALSE, $show_question_only, $showFeedback);
							$template->setVariable('SOLUTION_OUTPUT', $result_output);
						}
						// uni-goettingen-patch: begin
						if($show_print_watermark)
						{
							$aid = $this->object->getActiveIdOfUser($ilUser->getId());
							$watermark = "<p style=\"font-size: 0.2cm;\" class=\"text-center\">";

							$user_id = $this->object->_getUserIdFromActiveId($aid);
							$uname = $this->object->userLookupRealFullName($user_id, TRUE);
							$matNo = $this->object->userLookupMatriculation($user_id);
							$title = $this->object->getTitle();

							$watermark .= "<b>Name:</b> ";
							$watermark .= $uname;
							$watermark .= "&nbsp;&nbsp;&nbsp;&nbsp;&middot;&nbsp;&nbsp;&nbsp;&nbsp;<b>Mat#:</b>";
							$watermark .= $matNo;
							$watermark .= "&nbsp;&nbsp;&nbsp;&nbsp;&middot;&nbsp;&nbsp;&nbsp;&nbsp;<b>Klausur:</b>";
							$watermark .= $title;
							$watermark .= "</p>";

							$template->setVariable('WATERMARK', $watermark);
						}
						else
						{
							$template->setVariable('WATERMARK', "");
						}
						// uni-goettingen-patch: end

						$maintemplate->setCurrentBlock("printview_question");
						$maintemplate->setVariable("QUESTION_PRINTVIEW", $template->get());
						$maintemplate->parseCurrentBlock();
						$counter ++;
					}
				}
			}
		}

		// uni-goettingen-patch: begin
		if($testResultHeaderLabelBuilder != null)
		{
		// uni-goettingen-patch: end
			if($pass !== null)
			{
				$headerText = $testResultHeaderLabelBuilder->getListOfAnswersHeaderLabel($pass + 1);
			}
			else
			{
				$headerText = $testResultHeaderLabelBuilder->getVirtualListOfAnswersHeaderLabel();
			}
		}
		else
		{
			$headerText = '';
		}

		$maintemplate->setVariable("RESULTS_OVERVIEW", $headerText);
		return $maintemplate->get();
	}

	/**
	 * Returns the list of answers of a users test pass and offers a scoring option
	 *
	 * @param array $result_array An array containing the results of the users test pass (generated by ilObjTest::getTestResult)
	 * @param integer $active_id Active ID of the active user
	 * @param integer $pass Test pass
	 * @param boolean $show_solutions TRUE, if the solution output should be shown in the answers, FALSE otherwise
	 * @return string HTML code of the list of answers
	 * @access public
	 *
	 * @deprecated
	 */
	function getPassListOfAnswersWithScoring(&$result_array, $active_id, $pass, $show_solutions = FALSE)
	{
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";

		$maintemplate = new ilTemplate("tpl.il_as_tst_list_of_answers.html", TRUE, TRUE, "Modules/Test");

		include_once "./Modules/Test/classes/class.ilObjAssessmentFolder.php";
		$scoring = ilObjAssessmentFolder::_getManualScoring();

		$counter = 1;
		// output of questions with solutions
		foreach ($result_array as $question_data)
		{
			$question = $question_data["qid"];
			if (is_numeric($question))
			{
				$question_gui = $this->object->createQuestionGUI("", $question);
				if (in_array($question_gui->object->getQuestionTypeID(), $scoring))
				{
					$template = new ilTemplate("tpl.il_as_qpl_question_printview.html", TRUE, TRUE, "Modules/TestQuestionPool");
					$scoretemplate = new ilTemplate("tpl.il_as_tst_manual_scoring_points.html", TRUE, TRUE, "Modules/Test");
					#mbecker: No such block. $this->tpl->setCurrentBlock("printview_question");
					$template->setVariable("COUNTER_QUESTION", $counter.". ");
					$template->setVariable("QUESTION_TITLE", $this->object->getQuestionTitle($question_gui->object->getTitle()));
					$points = $question_gui->object->getMaximumPoints();
					if ($points == 1)
					{
						$template->setVariable("QUESTION_POINTS", $points . " " . $this->lng->txt("point"));
					}
					else
					{
						$template->setVariable("QUESTION_POINTS", $points . " " . $this->lng->txt("points"));
					}

					$show_question_only = ($this->object->getShowSolutionAnswersOnly()) ? TRUE : FALSE;
					$result_output = $question_gui->getSolutionOutput($active_id, $pass, $show_solutions, FALSE, $show_question_only, $this->object->getShowSolutionFeedback(), FALSE, TRUE);

					$solout = $question_gui->object->getSuggestedSolutionOutput();
					if (strlen($solout))
					{
						$scoretemplate->setCurrentBlock("suggested_solution");
						$scoretemplate->setVariable("TEXT_SUGGESTED_SOLUTION", $this->lng->txt("solution_hint"));
						$scoretemplate->setVariable("VALUE_SUGGESTED_SOLUTION", $solout);
						$scoretemplate->parseCurrentBlock();
					}

					$scoretemplate->setCurrentBlock("feedback");
					$scoretemplate->setVariable("FEEDBACK_NAME_INPUT", $question);
					$feedback = $this->object->getManualFeedback($active_id, $question, $pass);
					$scoretemplate->setVariable("VALUE_FEEDBACK", ilUtil::prepareFormOutput($this->object->prepareTextareaOutput($feedback, TRUE)));
					$scoretemplate->setVariable("TEXT_MANUAL_FEEDBACK", $this->lng->txt("set_manual_feedback"));
					$scoretemplate->parseCurrentBlock();

					$scoretemplate->setVariable("NAME_INPUT", $question);
					$this->ctrl->setParameter($this, "active_id", $active_id);
					$this->ctrl->setParameter($this, "pass", $pass);
					$scoretemplate->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "manscoring"));
					$scoretemplate->setVariable("LABEL_INPUT", $this->lng->txt("tst_change_points_for_question"));
					$scoretemplate->setVariable("VALUE_INPUT", " value=\"" . assQuestion::_getReachedPoints($active_id, $question_data["qid"], $pass) . "\"");
					$scoretemplate->setVariable("VALUE_SAVE", $this->lng->txt("save"));

					$template->setVariable("SOLUTION_OUTPUT", $result_output);
					$maintemplate->setCurrentBlock("printview_question");
					$maintemplate->setVariable("QUESTION_PRINTVIEW", $template->get());
					$maintemplate->setVariable("QUESTION_SCORING", $scoretemplate->get());
					$maintemplate->parseCurrentBlock();
				}
				$counter ++;
			}
		}
		if ($counter == 1)
		{
			// no scorable questions found
			$maintemplate->setVariable("NO_QUESTIONS_FOUND", $this->lng->txt("manscoring_questions_not_found"));
		}
		$maintemplate->setVariable("RESULTS_OVERVIEW", sprintf($this->lng->txt("manscoring_results_pass"), $pass+1));

		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDomEvent();

		return $maintemplate->get();
	}

	protected function getPassDetailsOverviewTableGUI($result_array, $active_id, $pass, $targetGUI, $targetCMD, $questionDetailsCMD, $questionAnchorNav, ilTestQuestionRelatedObjectivesList $objectivesList = null)
	{
		$this->ctrl->setParameter($targetGUI, 'active_id', $active_id);
		$this->ctrl->setParameter($targetGUI, 'pass', $pass);

		$tableGUI = $this->buildPassDetailsOverviewTableGUI($targetGUI, $targetCMD);

		if(!$this->isPdfDeliveryRequest())
		{
			$tableGUI->setAnswerListAnchorEnabled($questionAnchorNav);
		}

		$tableGUI->setSingleAnswerScreenCmd($questionDetailsCMD);
		$tableGUI->setShowHintCount($this->object->isOfferingQuestionHintsEnabled());

		if( $objectivesList !== null )
		{
			$tableGUI->setQuestionRelatedObjectivesList($objectivesList);
			$tableGUI->setObjectiveOrientedPresentationEnabled(true);
		}

		$tableGUI->setActiveId($active_id);
		$tableGUI->setShowSuggestedSolution(false);

		$usersQuestionSolutions = array();

		foreach($result_array as $key => $val)
		{
			if($key === 'test' || $key === 'pass')
			{
				continue;
			}

			if( $this->object->getShowSolutionSuggested() && strlen($val['solution']) )
			{
				$tableGUI->setShowSuggestedSolution(true);
			}

			if( isset($val['pass']) )
			{
				$tableGUI->setPassColumnEnabled(true);
			}

			$usersQuestionSolutions[$key] = $val;
		}

		$tableGUI->initColumns()->initFilter();

		$tableGUI->setFilterCommand($targetCMD.'SetTableFilter');
		$tableGUI->setResetCommand($targetCMD.'ResetTableFilter');

		$tableGUI->setData($usersQuestionSolutions);

		return $tableGUI;
	}

	/**
	 * Returns HTML code for a signature field
	 *
 	 * @return string HTML code of the date and signature field for the test results
	 * @access public
	 */
	function getResultsSignature()
	{
		// uni-goettingen-patch: begin
		if ($this->object->getShowSolutionSignature() && !$this->object->isFullyAnonymized())
		// uni-goettingen-patch: end
		{
			$template = new ilTemplate("tpl.il_as_tst_results_userdata_signature.html", TRUE, TRUE, "Modules/Test");
			$template->setVariable("TXT_DATE", $this->lng->txt("date"));
			$old_value = ilDatePresentation::useRelativeDates();
			ilDatePresentation::setUseRelativeDates(false);
			$template->setVariable("VALUE_DATE", ilDatePresentation::formatDate(new ilDate(time(), IL_CAL_UNIX)));
			ilDatePresentation::setUseRelativeDates($old_value);
			$template->setVariable("TXT_SIGNATURE", $this->lng->txt("tst_signature"));
			$template->setVariable("IMG_SPACER", ilUtil::getImagePath("spacer.png"));
			return $template->get();
		}
		else
		{
			return "";
		}
	}

	/**
	 * Returns the user data for a test results output
	 *
	 * @param ilTestSession|ilTestSessionDynamicQuestionSet
	 * @param integer $user_id The user ID of the user
	 * @param boolean $overwrite_anonymity TRUE if the anonymity status should be overwritten, FALSE otherwise
	 * @return string HTML code of the user data for the test results
	 * @access public
	 */
	function getAdditionalUsrDataHtmlAndPopulateWindowTitle($testSession, $active_id, $overwrite_anonymity = FALSE)
	{
		if(!is_object($testSession)) throw new TestException();
		$template = new ilTemplate("tpl.il_as_tst_results_userdata.html", TRUE, TRUE, "Modules/Test");
		include_once './Services/User/classes/class.ilObjUser.php';
		$user_id = $this->object->_getUserIdFromActiveId($active_id);
		if (strlen(ilObjUser::_lookupLogin($user_id)) > 0)
		{
			$user = new ilObjUser($user_id);
		}
		else
		{
			$user = new ilObjUser();
			$user->setLastname($this->lng->txt("deleted_user"));
		}
		$t = $testSession->getSubmittedTimestamp();
		if (!$t)
		{
			$t = $this->object->_getLastAccess($testSession->getActiveId());
		}

		if( $this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired() )
		{
			$uname = $this->object->userLookupFullName($user_id, $overwrite_anonymity);
			$template->setCurrentBlock("name");
			$template->setVariable('TXT_USR_NAME', $this->lng->txt("name"));
			$template->setVariable('VALUE_USR_NAME', $uname);
			$template->parseCurrentBlock();
		}

		$title_matric = "";
		// uni-goettingen-patch: begin
		if (strlen($user->getMatriculation()) && (($this->object->isFullyAnonymized()) || ($overwrite_anonymity)))
		// uni-goettingen-patch: end
		{
			$template->setCurrentBlock("matriculation");
			$template->setVariable("TXT_USR_MATRIC", $this->lng->txt("matriculation"));
			$template->setVariable("VALUE_USR_MATRIC", $user->getMatriculation());
			$template->parseCurrentBlock();
			$title_matric = " - " . $this->lng->txt("matriculation") . ": " . $user->getMatriculation();
		}

		$invited_user = array_pop($this->object->getInvitedUsers($user_id));
		if (strlen($invited_user["clientip"]))
		{
			$template->setCurrentBlock("client_ip");
			$template->setVariable("TXT_CLIENT_IP", $this->lng->txt("client_ip"));
			$template->setVariable("VALUE_CLIENT_IP", $invited_user["clientip"]);
			$template->parseCurrentBlock();
			$title_client = " - " . $this->lng->txt("clientip") . ": " . $invited_user["clientip"];
		}

		$template->setVariable("TXT_TEST_TITLE", $this->lng->txt("title"));
		$template->setVariable("VALUE_TEST_TITLE", $this->object->getTitle());

		// change the pagetitle (tab title or title in title bar of window)
		$pagetitle = $this->object->getTitle() . $title_matric . $title_client;
		$this->tpl->setHeaderPageTitle($pagetitle);

		return $template->get();
	}

	/**
	 * Returns an output of the solution to an answer compared to the correct solution
	 *
	 * @param integer $question_id Database ID of the question
	 * @param integer $active_id Active ID of the active user
	 * @param integer $pass Test pass
	 * @return string HTML code of the correct solution comparison
	 * @access public
	 */
	function getCorrectSolutionOutput($question_id, $active_id, $pass, ilTestQuestionRelatedObjectivesList $objectivesList = null)
	{
		global $ilUser;

		$test_id = $this->object->getTestId();
		$question_gui = $this->object->createQuestionGUI("", $question_id);

		if( $this->isPdfDeliveryRequest() )
		{
			$question_gui->setRenderPurpose(assQuestionGUI::RENDER_PURPOSE_PRINT_PDF);
		}

		$template = new ilTemplate("tpl.il_as_tst_correct_solution_output.html", TRUE, TRUE, "Modules/Test");
		$show_question_only = ($this->object->getShowSolutionAnswersOnly()) ? TRUE : FALSE;
		$result_output = $question_gui->getSolutionOutput($active_id, $pass, TRUE, FALSE, $show_question_only, $this->object->getShowSolutionFeedback(), FALSE, FALSE, TRUE);
		$best_output = $question_gui->getSolutionOutput($active_id, $pass, FALSE, FALSE, $show_question_only, FALSE, TRUE, FALSE, FALSE);
		if( $this->object->getShowSolutionFeedback() && $_GET['cmd'] != 'outCorrectSolution' )
		{
			$specificAnswerFeedback = $question_gui->getSpecificFeedbackOutput($active_id, $pass);
			if( strlen($specificAnswerFeedback) )
			{
				$template->setCurrentBlock("outline_specific_feedback");
				$template->setVariable("OUTLINE_SPECIFIC_FEEDBACK", $specificAnswerFeedback);
				$template->parseCurrentBlock();
			}
		}
		if ($this->object->isBestSolutionPrintedWithResult() && strlen($best_output))
		{
			$template->setCurrentBlock("best_solution");
			$template->setVariable("TEXT_BEST_SOLUTION", $this->lng->txt("tst_best_solution_is"));
			$template->setVariable("BEST_OUTPUT", $best_output);
			$template->parseCurrentBlock();
		}
		$template->setVariable("TEXT_YOUR_SOLUTION", $this->lng->txt("tst_your_answer_was"));
		$maxpoints = $question_gui->object->getMaximumPoints();
		if ($maxpoints == 1)
		{
			$template->setVariable("QUESTION_TITLE", $this->object->getQuestionTitle($question_gui->object->getTitle()) . " (" . $maxpoints . " " . $this->lng->txt("point") . ")");
		}
		else
		{
			$template->setVariable("QUESTION_TITLE", $this->object->getQuestionTitle($question_gui->object->getTitle()) . " (" . $maxpoints . " " . $this->lng->txt("points") . ")");
		}
		if( $objectivesList !== null )
		{
			$objectives = $this->lng->txt('tst_res_lo_objectives_header').': ';
			$objectives .= $objectivesList->getQuestionRelatedObjectiveTitles($question_gui->object->getId());
			$template->setVariable('OBJECTIVES', $objectives);
		}
		$template->setVariable("SOLUTION_OUTPUT", $result_output);
		$template->setVariable("RECEIVED_POINTS", sprintf($this->lng->txt("you_received_a_of_b_points"), $question_gui->object->getReachedPoints($active_id, $pass), $maxpoints));
		$template->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$template->setVariable("BACKLINK_TEXT", "&lt;&lt; " . $this->lng->txt("back"));
		return $template->get();
	}

	/**
	 * Output of the pass overview for a test called by a test participant
	 *
	 * @param ilTestSession|ilTestSessionDynamicQuestionSet $testSession
	 * @param integer $active_id
	 * @param integer $pass
	 * @param boolean $show_pass_details
	 * @param boolean $show_answers
	 * @param boolean $show_question_only
	 * @param boolean $show_reached_points
	 * @access public
	 */
	function getResultsOfUserOutput($testSession, $active_id, $pass, $targetGUI, $show_pass_details = TRUE, $show_answers = TRUE, $show_question_only = FALSE, $show_reached_points = FALSE)
	{
		global $ilObjDataCache;

		include_once("./Services/UICore/classes/class.ilTemplate.php");
		$template = new ilTemplate("tpl.il_as_tst_results_participant.html", TRUE, TRUE, "Modules/Test");

		if( $this->participantData instanceof ilTestParticipantData )
		{
			$user_id = $this->participantData->getUserIdByActiveId($active_id);
			$uname = $this->participantData->getConcatedFullnameByActiveId($active_id, false);
		}
		else
		{
			$user_id = $this->object->_getUserIdFromActiveId($active_id);
			// uni-goettingen-patch: begin
			$uname = $this->object->userLookupRealFullName($user_id, TRUE);
			// uni-goettingen-patch: end
		}

		if (((array_key_exists("pass", $_GET)) && (strlen($_GET["pass"]) > 0)) || (!is_null($pass)))
		{
			if (is_null($pass))	$pass = $_GET["pass"];
		}

		if (!is_null($pass))
		{
			require_once 'Modules/Test/classes/class.ilTestResultHeaderLabelBuilder.php';
			$testResultHeaderLabelBuilder = new ilTestResultHeaderLabelBuilder($this->lng, $ilObjDataCache);

			$objectivesList = null;

			if( $this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired() )
			{
				$testSequence = $this->testSequenceFactory->getSequenceByActiveIdAndPass($active_id, $pass);
				$testSequence->loadFromDb();
				$testSequence->loadQuestions();

				require_once 'Modules/Course/classes/Objectives/class.ilLOTestQuestionAdapter.php';
				$objectivesAdapter = ilLOTestQuestionAdapter::getInstance($testSession);

				$objectivesList = $this->buildQuestionRelatedObjectivesList($objectivesAdapter, $testSequence);
				$objectivesList->loadObjectivesTitles();

				$testResultHeaderLabelBuilder->setObjectiveOrientedContainerId($testSession->getObjectiveOrientedContainerId());
				$testResultHeaderLabelBuilder->setUserId($testSession->getUserId());
				$testResultHeaderLabelBuilder->setTestObjId($this->object->getId());
				$testResultHeaderLabelBuilder->setTestRefId($this->object->getRefId());
				$testResultHeaderLabelBuilder->initObjectiveOrientedMode();
			}

			$result_array = $this->object->getTestResult(
				$active_id, $pass, false, !$this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired()
			);

			$user_id = $this->object->_getUserIdFromActiveId($active_id);
			$showAllAnswers = TRUE;
			if ($this->object->isExecutable($testSession, $user_id))
			{
				$showAllAnswers = FALSE;
			}
			if ($show_answers)
			{
				$list_of_answers = $this->getPassListOfAnswers(
					$result_array, $active_id, $pass, $_SESSION['tst_results_show_best_solutions'],
					$showAllAnswers, $show_question_only, $show_reached_points, $show_pass_details,
					$objectivesList, $testResultHeaderLabelBuilder
				);
				$template->setVariable("LIST_OF_ANSWERS", $list_of_answers);
			}

			if ($show_pass_details)
			{
				$overviewTableGUI = $this->getPassDetailsOverviewTableGUI($result_array, $active_id, $pass, $targetGUI, "getResultsOfUserOutput", '', $show_answers, $objectivesList);
				$overviewTableGUI->setTitle($testResultHeaderLabelBuilder->getPassDetailsHeaderLabel($pass + 1));
				$template->setVariable("PASS_DETAILS", $overviewTableGUI->getHTML());
			}

			$signature = $this->getResultsSignature();
			$template->setVariable("SIGNATURE", $signature);

			if ($this->object->isShowExamIdInTestResultsEnabled())
			{
				$template->setCurrentBlock('exam_id_footer');
				$template->setVariable('EXAM_ID_VAL', ilObjTest::lookupExamId(
					$testSession->getActiveId(), $pass
				));
				$template->setVariable('EXAM_ID_TXT', $this->lng->txt('exam_id'));
				$template->parseCurrentBlock();
			}
		}

		$template->setCurrentBlock('participant_back_anchor');
		$template->setVariable("HREF_PARTICIPANT_BACK_ANCHOR", "#tst_results_toolbar");
		$template->setVariable("TXT_PARTICIPANT_BACK_ANCHOR", $this->lng->txt('tst_back_to_top'));
		$template->parseCurrentBlock();

		$template->setCurrentBlock('participant_block_id');
		$template->setVariable("PARTICIPANT_BLOCK_ID", "participant_active_{$active_id}");
		$template->parseCurrentBlock();

		if( $this->isGradingMessageRequired() )
		{
			$gradingMessageBuilder = $this->getGradingMessageBuilder($active_id);
			$gradingMessageBuilder->buildList();

			$template->setCurrentBlock('grading_message');
			$template->setVariable('GRADING_MESSAGE', $gradingMessageBuilder->getList());
			$template->parseCurrentBlock();
		}


		$user_data = $this->getAdditionalUsrDataHtmlAndPopulateWindowTitle($testSession, $active_id, TRUE);
		$template->setVariable("TEXT_HEADING", sprintf($this->lng->txt("tst_result_user_name"), $uname));
		$template->setVariable("USER_DATA", $user_data);

		$this->populatePassFinishDate($template, $this->object->getPassFinishDate($active_id, $pass));

		return $template->get();
	}

	/**
	 * Returns the user and pass data for a test results output
	 *
	 * @param integer $active_id The active ID of the user
	 * @return string HTML code of the user data for the test results
	 * @access public
	 */
	function getResultsHeadUserAndPass($active_id, $pass)
	{
		$template = new ilTemplate("tpl.il_as_tst_results_head_user_pass.html", TRUE, TRUE, "Modules/Test");
		include_once './Services/User/classes/class.ilObjUser.php';
		$user_id = $this->object->_getUserIdFromActiveId($active_id);
		if (strlen(ilObjUser::_lookupLogin($user_id)) > 0)
		{
			$user = new ilObjUser($user_id);
		}
		else
		{
			$user = new ilObjUser();
			$user->setLastname($this->lng->txt("deleted_user"));
		}
		$title_matric = "";
		// uni-goettingen-patch: begin
		if (strlen($user->getMatriculation()) && (($this->object->isFullyAnonymized())))
		// uni-goettingen-patch: end
		{
			$template->setCurrentBlock("user_matric");
			$template->setVariable("TXT_USR_MATRIC", $this->lng->txt("matriculation"));
			$template->parseCurrentBlock();
			$template->setCurrentBlock("user_matric_value");
			$template->setVariable("VALUE_USR_MATRIC", $user->getMatriculation());
			$template->parseCurrentBlock();
			$template->touchBlock("user_matric_separator");
			$title_matric = " - " . $this->lng->txt("matriculation") . ": " . $user->getMatriculation();
		}

		$invited_user = array_pop($this->object->getInvitedUsers($user_id));
		if (strlen($invited_user["clientip"]))
		{
			$template->setCurrentBlock("user_clientip");
			$template->setVariable("TXT_CLIENT_IP", $this->lng->txt("client_ip"));
			$template->parseCurrentBlock();
			$template->setCurrentBlock("user_clientip_value");
			$template->setVariable("VALUE_CLIENT_IP", $invited_user["clientip"]);
			$template->parseCurrentBlock();
			$template->touchBlock("user_clientip_separator");
			$title_client = " - " . $this->lng->txt("clientip") . ": " . $invited_user["clientip"];
		}

		$template->setVariable("TXT_USR_NAME", $this->lng->txt("name"));
		$uname = $this->object->userLookupFullName($user_id, FALSE);
		$template->setVariable("VALUE_USR_NAME", $uname);
		$template->setVariable("TXT_PASS", $this->lng->txt("scored_pass"));
		$template->setVariable("VALUE_PASS", $pass);
		return $template->get();
	}

	/**
	 * Creates a HTML representation for the results of a given question in a test
	 *
	 * @param integer $question_id The original id of the question
	 * @param integer $test_id The test id
	 * @return string HTML code of the question results
	 */
	public function getQuestionResultForTestUsers($question_id, $test_id)
	{
        // prepare generation before contents are processed (for mathjax)
		require_once 'Services/PDFGeneration/classes/class.ilPDFGeneration.php';
		ilPDFGeneration::prepareGeneration();

		// REQUIRED, since we call this object regardless of the loop
		$question_gui = $this->object->createQuestionGUI("", $question_id);

		$foundusers = $this->object->getParticipantsForTestAndQuestion($test_id, $question_id);
		$output     = '';
		foreach($foundusers as $active_id => $passes)
		{
			$resultpass = $this->object->_getResultPass($active_id);
			for($i = 0; $i < count($passes); $i++)
			{
				if(($resultpass !== null) && ($resultpass == $passes[$i]["pass"]))
				{
					if($output)
					{
						$output .= "<br /><br /><br />";
					}

					// check if re-instantiation is really neccessary
					$question_gui = $this->object->createQuestionGUI("", $passes[$i]["qid"]);
					$output .= $this->getResultsHeadUserAndPass($active_id, $resultpass + 1);
					$question_gui->setRenderPurpose(assQuestionGUI::RENDER_PURPOSE_PRINT_PDF);
					$output .= $question_gui->getSolutionOutput(
						$active_id,
						$resultpass,
						$graphicalOutput = FALSE,
						$result_output = FALSE,
						$show_question_only = FALSE,
						$show_feedback = FALSE
					);
				}
			}
		}

		require_once './Modules/Test/classes/class.ilTestPDFGenerator.php';
		ilTestPDFGenerator::generatePDF($output, ilTestPDFGenerator::PDF_OUTPUT_DOWNLOAD, $question_gui->object->getTitle());
	}

	/**
	 * @return ilTestPassDetailsOverviewTableGUI
	 */
	protected function buildPassDetailsOverviewTableGUI($targetGUI, $targetCMD)
	{
		require_once 'Modules/Test/classes/tables/class.ilTestPassDetailsOverviewTableGUI.php';
		$tableGUI = new ilTestPassDetailsOverviewTableGUI($this->ctrl, $targetGUI, $targetCMD);
		$tableGUI->setIsPdfGenerationRequest($this->isPdfDeliveryRequest());
		return $tableGUI;
	}

	protected function isGradingMessageRequired()
	{
		if( $this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired() )
		{
			return false;
		}

		if( $this->object->isShowGradingStatusEnabled() )
		{
			return true;
		}

		if( $this->object->isShowGradingMarkEnabled() )
		{
			return true;
		}

		if( $this->object->areObligationsEnabled() )
		{
			return true;
		}

		return false;
	}

	/**
	 * @param integer $activeId
	 * @return ilTestGradingMessageBuilder
	 */
	protected function getGradingMessageBuilder($activeId)
	{
		require_once 'Modules/Test/classes/class.ilTestGradingMessageBuilder.php';
		$gradingMessageBuilder = new ilTestGradingMessageBuilder($this->lng, $this->object);

		$gradingMessageBuilder->setActiveId($activeId);

		return $gradingMessageBuilder;
	}

	protected function buildQuestionRelatedObjectivesList(ilLOTestQuestionAdapter $objectivesAdapter, ilTestQuestionSequence $testSequence)
	{
		require_once 'Modules/Test/classes/class.ilTestQuestionRelatedObjectivesList.php';
		$questionRelatedObjectivesList = new ilTestQuestionRelatedObjectivesList();

		$objectivesAdapter->buildQuestionRelatedObjectiveList($testSequence, $questionRelatedObjectivesList);

		return $questionRelatedObjectivesList;
	}

	protected function getFilteredTestResult($active_id, $pass, $considerHiddenQuestions, $considerOptionalQuestions)
	{
		global $ilDB, $ilPluginAdmin;

		$table_gui = $this->buildPassDetailsOverviewTableGUI($this, 'outUserPassDetails');
		$table_gui->initFilter();

		require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionList.php';
		$questionList = new ilAssQuestionList($ilDB, $this->lng, $ilPluginAdmin);

		$questionList->setParentObjIdsFilter(array($this->object->getId()));
		$questionList->setQuestionInstanceTypeFilter(ilAssQuestionList::QUESTION_INSTANCE_TYPE_DUPLICATES);

		foreach ($table_gui->getFilterItems() as $item)
		{
			if( substr($item->getPostVar(), 0, strlen('tax_')) == 'tax_' )
			{
				$v = $item->getValue();

				if( is_array($v) && count($v) && !(int)$v[0] )
				{
					continue;
				}

				$taxId = substr($item->getPostVar(), strlen('tax_'));
				$questionList->addTaxonomyFilter($taxId, $item->getValue(), $this->object->getId(), 'tst');
			}
			elseif( $item->getValue() !== false )
			{
				$questionList->addFieldFilter($item->getPostVar(), $item->getValue());
			}
		}

		$questionList->load();

		$filteredTestResult = array();

		$resultData = $this->object->getTestResult($active_id, $pass, false, $considerHiddenQuestions, $considerOptionalQuestions);

		foreach($resultData as $resultItemKey => $resultItemValue)
		{
			if($resultItemKey === 'test' || $resultItemKey === 'pass')
			{
				continue;
			}

			if( !$questionList->isInList($resultItemValue['qid']) )
			{
				continue;
			}

			$filteredTestResult[] = $resultItemValue;
		}

		return $filteredTestResult;
	}

	/**
	 * @param string $content
	 */
	protected function populateContent($content)
	{
		if($this->isPdfDeliveryRequest())
		{
			require_once 'class.ilTestPDFGenerator.php';

			ilTestPDFGenerator::generatePDF(
				$content, ilTestPDFGenerator::PDF_OUTPUT_DOWNLOAD, $this->object->getTitle()
			);
		}
		else
		{
			$this->tpl->setContent($content);
		}
	}

	/**
	 * @return ilTestResultsToolbarGUI
	 */
	protected function buildUserTestResultsToolbarGUI()
	{
		require_once 'Modules/Test/classes/toolbars/class.ilTestResultsToolbarGUI.php';
		$toolbar = new ilTestResultsToolbarGUI($this->ctrl, $this->tpl, $this->lng);

		$toolbar->setSkillResultButtonEnabled($this->object->isSkillServiceToBeConsidered());

		return $toolbar;
	}

	protected function outCorrectSolutionCmd()
	{
		$this->outCorrectSolution(); // cannot be named xxxCmd, because it's also called from context without Cmd in names
	}

	/**
	 * Creates an output of the solution of an answer compared to the correct solution
	 *
	 * @access public
	 */
	protected function outCorrectSolution()
	{
		if( !$this->object->getShowSolutionDetails() )
		{
			ilUtil::sendInfo($this->lng->txt("no_permission"), true);
			$this->ctrl->redirectByClass("ilobjtestgui", "infoScreen");
		}

		$testSession = $this->testSessionFactory->getSession();
		$activeId = $testSession->getActiveId();

		if( !($activeId > 0) )
		{
			$this->ctrl->redirectByClass("ilobjtestgui", "infoScreen");
		}

		$this->ctrl->saveParameter($this, "pass");
		$pass = (int)$_GET['pass'];

		$questionId = (int)$_GET['evaluation'];

		if( $this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired() )
		{
			$testSequence = $this->testSequenceFactory->getSequenceByActiveIdAndPass($activeId, $pass);
			$testSequence->loadFromDb();
			$testSequence->loadQuestions();

			require_once 'Modules/Course/classes/Objectives/class.ilLOTestQuestionAdapter.php';
			$objectivesAdapter = ilLOTestQuestionAdapter::getInstance($testSession);
			$objectivesList = $this->buildQuestionRelatedObjectivesList($objectivesAdapter, $testSequence);
			$objectivesList->loadObjectivesTitles();
		}
		else
		{
			$objectivesList = null;
		}

		global $ilTabs;

		if($this instanceof ilTestEvalObjectiveOrientedGUI)
		{
			$ilTabs->setBackTarget(
				$this->lng->txt("tst_back_to_virtual_pass"), $this->ctrl->getLinkTarget($this, 'showVirtualPass')
			);
		}
		else
		{
			$ilTabs->setBackTarget(
				$this->lng->txt("tst_back_to_pass_details"), $this->ctrl->getLinkTarget($this, 'outUserPassDetails')
			);
		}

		include_once("./Services/Style/Content/classes/class.ilObjStyleSheet.php");
		$this->tpl->setCurrentBlock("ContentStyle");
		$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", ilObjStyleSheet::getContentStylePath(0));
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("SyntaxStyle");
		$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET", ilObjStyleSheet::getSyntaxStylePath());
		$this->tpl->parseCurrentBlock();

		$this->tpl->addCss(ilUtil::getStyleSheetLocation("output", "test_print.css", "Modules/Test"), "print");
		if ($this->object->getShowSolutionAnswersOnly())
		{
			$this->tpl->addCss(ilUtil::getStyleSheetLocation("output", "test_print_hide_content.css", "Modules/Test"), "print");
		}

		$solution = $this->getCorrectSolutionOutput($questionId, $activeId, $pass, $objectivesList);

		$this->tpl->setContent($solution);
	}

	/**
	 * @param ilTemplate $tpl
	 * @param int $passFinishDate
	 */
	public function populatePassFinishDate($tpl, $passFinishDate)
	{
		$oldValue = ilDatePresentation::useRelativeDates();
		ilDatePresentation::setUseRelativeDates(false);
		$passFinishDate = ilDatePresentation::formatDate(new ilDateTime($passFinishDate, IL_CAL_UNIX));
		ilDatePresentation::setUseRelativeDates($oldValue);
		$tpl->setVariable("PASS_FINISH_DATE_LABEL", $this->lng->txt('tst_pass_finished_on'));
		$tpl->setVariable("PASS_FINISH_DATE_VALUE", $passFinishDate);
	}
	// uni-goettingen-patch: begin
	public function setHashedOutputFile($active_id, $file_path, $file_hash)
	{
		$session = $this->testSessionFactory->getSession($active_id);
		$session->setSubmittedFile($file_path);
		$session->setSubmittedFileHash($file_hash);
		$session->saveToDb();
}
	public function processHtmlResultsForArchiving($in_html, $path)
	{
		$out_html = "";
		$img_path = $path . "/img";
		if(!file_exists($img_path))
		 	mkdir($img_path, 0775);
		$css_path = $path . "/css";
		if(!file_exists($css_path))
			mkdir($css_path, 0775);
		$js_path = $path . "/js";
		if(!file_exists($js_path))
			mkdir($js_path, 0775);

		$matching_il_util_onload = false;
		$jsme_functions = array();

		foreach(explode(PHP_EOL, $in_html) as $line)
		{
			$out_line = "";
			$match = array();
			if(preg_match('/^(.*)(<[iI][mM][gG].*?>)(.*)$/', $line, $match))
			{
				$pre_img_tag  = $match[1];
				$post_img_tag = $match[3];
				$img_match = array();
				preg_match('/^(<img.*src=")(.*)"\sw.*(".*>)$/', $match[2], $img_match);
				$open_tag     = $img_match[1];
				$img_file     = $img_match[2];
				$close_tag    = $img_match[3];

				$match_url = array();
				// $out_line .= "\n<!-- Found img tag: ".$match[2]." -->\n";
				// $out_line .= "\n<!-- img_file = ".$img_file." -->\n";

				if(preg_match('/^https?:\/\/.*?(\/.*)\/([^\?\/]*)\??.*$/', $img_file, $match_url))
				//if(preg_match('/^https?:\/\/.*?\/.*?([^\/]*)$/', $img_file, $match_url))
				{
					// $match_url[1] nicht nötig
					$raw_img_path = ILIAS_ABSOLUTE_PATH . $match_url[1] ."/". $match_url[2];
					$dest         = $img_path."/".$match_url[2];
					try{
						copy($raw_img_path, $dest);
					}
					catch (Exception $e)
					{
						ilUtil::sendInfo("Error: ".$e, TRUE);
					}
					$out_line .= $pre_img_tag.$open_tag."img/".$match_url[2].$close_tag.$post_img_tag;
					// $raw_img_path = ILIAS_ABSOLUTE_PATH ."/". $match_url[1];
					// $dest         = $img_path."/".$match_url[1];
					// copy($raw_img_path, $dest);
					// $out_line .= $pre_img_tag.$open_tag."img/".$match_url[1].$close_tag.$post_img_tag;
				}
				else
				{
					//$out_line .= $pre_img_tag.$open_tag.$img_file.$close_tag.$post_img_tag;
					$img_file_path = ILIAS_ABSOLUTE_PATH . substr($img_file, 1);
					$img_basename = basename($img_file);
					try
					{
						copy($img_file_path, $img_path."/".$img_basename);
					}
					catch (Exception $e)
					{
						ilUtil::sendInfo("Error: ".$e, TRUE);
					}
					$out_line    .= $pre_img_tag.$open_tag."img/".$img_basename.$close_tag;
				}
			}
			else
			{
				$out_line = $line;
			}

			$match = array();
			if(preg_match('/^\s*il.Util.addOnLoad\(\s*$/', $out_line))
			{
				$matching_il_util_onload = true;
				$out_line = "";
			}
			if($matching_il_util_onload)
			{
				if(preg_match('/^\s*function\(\){\s*$/', $out_line))
					$out_line = "";
				elseif(preg_match('/^(.*?JSME\(")(.*?)(".*)$/', $out_line, $match))
				{
					$out_line = "function activate_".$match[2]."(){\n".$match[0];
					$jsme_functions[] = "activate_".$match[2];
				}
				/*elseif(preg_match('/^(.*options\(")("\).*)$/', $out_line, $match))
				{
					$out_line = $match[1]."depict".$match[2];
				}*/
				elseif(preg_match('/^\s*\);\s*$/', $out_line))
				{
					$out_line = "";
					$matching_il_util_onload = false;
				}
			}
			if(strlen($out_line) > 0)
				$out_html .= $out_line."\n";
		}

		$out_html .= "<script>\n";
		$out_html .= "function jsmeOnLoad(){\n";
		foreach($jsme_functions as $func) {
			// $out_html .= "  ".$func['func']."Applet = new JSApplet.JSME(\"".$func['func']."\", \"800px\", \"700px\");\n";
			// $out_html .= "  ".$func['func']."Applet.readMolecule(\"".$func['mol']."\");\n";
			$out_html .= "  ".$func."();\n";
		}
		$out_html .= "}\n";
		// $out_html .= "window.onload = onLoadFunction();\n";
		$out_html .= "</script>\n";
		// $out_html .= "<!-- ILIAS_ABSOLUTE_PATH = ".ILIAS_ABSOLUTE_PATH."-->";

		$this->copy(ILIAS_ABSOLUTE_PATH."/Modules/Test/templates/default/ta.css", $css_path."/ta.css");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/MediaObjects/media_element_2_14_2/mediaelementplayer.min.css", $css_path."/mediaelementplayer.min.css");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/yui2/build/container/assets/skins/sam/container.css", $css_path."/container.css");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/Accordion/css/accordion.css", $css_path."/accordion.css");
		$this->copy(ILIAS_ABSOLUTE_PATH."/templates/default/delos.css", $css_path."/delos.css");
		$this->copy(ILIAS_ABSOLUTE_PATH."/templates/default/delos_cont.css", $css_path."/delos_cont.css");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/JavaScript/js/Basic.js", $js_path."/Basic.js");
//		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/jQuery/templates/default/jquery-ui.css", $css_path."/jquery-ui.css");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/jquery/dist/jquery.js", $js_path."/jquery-min.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/jquery-migrate/jquery-migrate.min.js", $js_path."/jquery-migrate-min.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/jquery-ui/jquery-ui.min.js", $js_path."/jquery-ui.min.js");
//		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/jQuery/js/ui_1_11_4/jquery-ui.slider.min.js", $js_path."/jquery-ui.slider.min.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/maphilight/jquery.maphilight.min.js", $js_path."/maphilight.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/bootstrap/dist/js/bootstrap.min.js", $js_path."/bootstrap.min.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/yui2/build/yahoo-dom-event/yahoo-dom-event.js", $js_path."/yahoo-dom-event.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/yui2/build/animation/animation-min.js", $js_path."/animation-min.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/yui2/build/connection/connection-min.js", $js_path."/connection-min.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/libs/bower/bower_components/yui2/build/container/container_core-min.js", $js_path."/container_core-min.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/COPage/js/ilCOPagePres.js", $js_path."/ilCOPagePres.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/COPage/js/ilCOPageQuestionHandler.js", $js_path."/ilCOPageQuestionHandler.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/MediaObjects/media_element_2_14_2/mediaelement-and-player.js", $js_path."/mediaelement-and-player.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/UIComponent/Overlay/js/ilOverlay.js", $js_path."/ilOverlay.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/UIComponent/AdvancedSelectionList/js/AdvancedSelectionList.js", $js_path."/AdvancedSelectionList.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/Accordion/js/accordion.js", $js_path."/accordion.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/Contact/BuddySystem/js/buddy_system.js", $js_path."/buddy_system.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/Awareness/js/Awareness.js", $js_path."/Awareness.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Services/Notifications/templates/default/notifications.js", $js_path."/notifications.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Modules/TestQuestionPool/js/md5sum.js", $js_path."/md5sum.js");
//		$this->copy(ILIAS_ABSOLUTE_PATH."", $js_path."/");

//		$this->copy(ILIAS_ABSOLUTE_PATH."/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assJSMEQuestion/templates/jsme/jsme.nocache.js", $js_path."/jsme.nocache.js");
		$this->copy(ILIAS_ABSOLUTE_PATH."/Customizing/global/plugins/Modules/TestQuestionPool/Questions/AssSourceCode/css/il_web_ide.css", $css_path."/il_web_ide.css");
		$this->rec_copy(ILIAS_ABSOLUTE_PATH."/Customizing/global/plugins/Modules/TestQuestionPool/Questions/AssSourceCode/lib/ace-builds-1.2.6/src-min-noconflict/", $js_path."/ace-builds-1.2.6/src-min-noconflict/");

		$source = ILIAS_ABSOLUTE_PATH."/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assJSMEQuestion/templates/jsme";
		$dest   = $path;
		$this->copyr($source, $dest);
		return $out_html;
	}

	private function copy($source, $dest)
	{
		// copy only if destination doesn't exist
		if(!file_exists($dest))
			copy($source, $dest);
	}

	private function rec_copy($source, $dest)
	{
		if (!file_exists($dest)) {
			mkdir($dest, 0755, true);
		}
		foreach (
		  $iterator = new RecursiveIteratorIterator(
		  new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
		  RecursiveIteratorIterator::SELF_FIRST) as $item) {
		  if ($item->isDir()) {
				if (!file_exists($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
		    	mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
				}
		  } else {
		    copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
		  }
		}
	}

	private function copyr($source, $dest)
	{
    // recursive function to copy
    // all subdirectories and contents, unless the destinatin exists:
    if(!file_exists($dest."/".basename($source))) {
	    if(is_dir($source)) {
	        $dir_handle=opendir($source);
	        $sourcefolder = basename($source);
	        mkdir($dest."/".$sourcefolder, 0775);
	        while($file=readdir($dir_handle)){
	            if($file!="." && $file!=".."){
	                if(is_dir($source."/".$file)){
	                    self::copyr($source."/".$file, $dest."/".$sourcefolder);
	                } else {
	                    copy($source."/".$file, $dest."/".$file);
	                }
	            }
	        }
	        closedir($dir_handle);
	    } else {
	        // can also handle simple copy commands
	        copy($source, $dest);
	    }
	  }
	}
}

// uni-goettingen-patch: end

// internal sort function to sort the result array
function sortResults($a, $b)
{
	$sort = ($_GET["sort"]) ? ($_GET["sort"]) : "nr";
	$sortorder = ($_GET["sortorder"]) ? ($_GET["sortorder"]) : "asc";
	if (strcmp($sortorder, "asc"))
	{
		$smaller = 1;
		$greater = -1;
	}
	else
	{
		$smaller = -1;
		$greater = 1;
	}
	if ($a[$sort] == $b[$sort]) return 0;
	return ($a[$sort] < $b[$sort]) ? $smaller : $greater;
}
