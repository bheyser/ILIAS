<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Modules/Test/classes/inc.AssessmentConstants.php';
include_once 'Modules/Test/classes/class.ilTestScoringGUI.php';

/**
 * ilTestScoringByQuestionsGUI
 * @author     Michael Jansen <mjansen@databay.de>
 * @author     Björn Heyser <bheyser@databay.de>
 * @version    $Id$
 * @ingroup    ModulesTest
 * @extends    ilTestServiceGUI
 */
class ilTestScoringByQuestionsGUI extends ilTestScoringGUI
{
	/**
	 * @param ilObjTest $a_object
	 */
	public function __construct(ilObjTest $a_object)
	{
		parent::__construct($a_object);
	}

	/**
	 * @return mixed
	 */
	public function executeCommand()
	{
		/**
		 * @var $ilAccessHandler
		 */
		global $ilAccess;

		// uni-goettingen-patch: begin
		if(
			!$ilAccess->checkAccess("write", "", $this->ref_id) &&
			!$ilAccess->checkAccess("man_scoring_access", "", $this->ref_id)
		)
		// uni-goettingen-patch: end
		{
			ilUtil::sendFailure($this->lng->txt('cannot_edit_test'), true);
			$this->ctrl->redirectByClass('ilobjtestgui', 'infoScreen');
		}

		require_once 'Modules/Test/classes/class.ilObjAssessmentFolder.php';
		if(!ilObjAssessmentFolder::_mananuallyScoreableQuestionTypesExists())
		{
			ilUtil::sendFailure($this->lng->txt('manscoring_not_allowed'), true);
			$this->ctrl->redirectByClass('ilobjtestgui', 'infoScreen');
		}

		$cmd        = $this->ctrl->getCmd('showManScoringByQuestionParticipantsTable');
		$next_class = $this->ctrl->getNextClass($this);

		$cmd = $this->getCommand($cmd);
		$this->buildSubTabs('man_scoring_by_qst');
		switch($next_class)
		{
			default:
				$ret = $this->$cmd();
				break;
		}

		return $ret;
	}

	/**
	 *
	 */
	private function showManScoringByQuestionParticipantsTable($manPointsPost = array())
	{
		/**
		 * @var $tpl      ilTemplate
		 * @var $ilAccess ilAccessHandler
		 */
		global $tpl, $ilAccess;

		// uni-goettingen-patch: begin
		if(
			!$ilAccess->checkAccess("write", "", $this->ref_id) &&
			!$ilAccess->checkAccess("man_scoring_access", "", $this->ref_id)
		)
		// uni-goettingen-patch: end
		{
			ilUtil::sendInfo($this->lng->txt('cannot_edit_test'), true);
			$this->ctrl->redirectByClass('ilobjtestgui', 'infoScreen');
		}

		include_once 'Services/jQuery/classes/class.iljQueryUtil.php';
		iljQueryUtil::initjQuery();

		include_once 'Services/YUI/classes/class.ilYuiUtil.php';
		ilYuiUtil::initPanel();
		ilYuiUtil::initOverlay();

		$mathJaxSetting = new ilSetting('MathJax');
		if($mathJaxSetting->get("enable"))
		{
			$tpl->addJavaScript($mathJaxSetting->get("path_to_mathjax"));
		}

		$tpl->addJavaScript("./Services/JavaScript/js/Basic.js");
		$tpl->addJavaScript("./Services/Form/js/Form.js");
		$tpl->addJavascript('./Services/UIComponent/Modal/js/Modal.js');
		$tpl->addCss($this->object->getTestStyleLocation("output"), "screen");

		$this->lng->toJSMap(array('answer' => $this->lng->txt('answer')));

		require_once 'Modules/Test/classes/tables/class.ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI.php';
		$table = new ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI($this);
		
		$table->setManualScoringPointsPostData($manPointsPost);

		$qst_id  = $table->getFilterItemByPostVar('question')->getValue();
		$pass_id = $table->getFilterItemByPostVar('pass')->getValue();

		$table_data = array();

		$selected_questionData = null;

		if(is_numeric($qst_id))
		{
			$scoring = ilObjAssessmentFolder::_getManualScoring();
			$info = assQuestion::_getQuestionInfo($qst_id);
			$selected_questionData = $info;
			$type = $info["question_type_fi"];
			if(in_array($type, $scoring))
			{
				$selected_questionData = $info;
			}
		}

		if($selected_questionData && is_numeric($pass_id))
		{
			$data = $this->object->getCompleteEvaluationData(FALSE);
			
			foreach($data->getParticipants() as $active_id => $participant)
			{
				$testResultData = $this->object->getTestResult($active_id, $pass_id - 1);
				foreach($testResultData as $questionData)
				{
					if( !isset($questionData['qid']) || $questionData['qid'] != $selected_questionData['question_id'] )
					{
						continue;
					}

					$table_data[] = array(
						'pass_id'        => $pass_id - 1,
						'active_id'      => $active_id,
						'qst_id'         => $questionData['qid'],
						'reached_points' => assQuestion::_getReachedPoints($active_id, $questionData['qid'], $pass_id - 1),
						'maximum_points' => assQuestion::_getMaximumPoints($questionData['qid']),
						'participant'    => $participant,
					);
				}
			}
		}
		else
		{
			$table->disable('header');
		}

		if($selected_questionData)
		{
			$maxpoints = assQuestion::_getMaximumPoints($selected_questionData['question_id']);
			$table->setCurQuestionMaxPoints($maxpoints);
			if($maxpoints == 1)
			{
				$maxpoints = ' (' . $maxpoints . ' ' . $this->lng->txt('point') . ')';
			}
			else
			{
				$maxpoints = ' (' . $maxpoints . ' ' . $this->lng->txt('points') . ')';
			}
			$table->setTitle($this->lng->txt('tst_man_scoring_by_qst') . ': ' . $selected_questionData['title'] . $maxpoints . ' ['. $this->lng->txt('question_id_short') . ': ' . $selected_questionData['question_id']  . ']');
		}
		else
		{
			$table->setTitle($this->lng->txt('tst_man_scoring_by_qst'));
		}

		$table->setData($table_data);
		$tpl->setContent($table->getHTML());
	}
	
	// uni-goettingen-patch: begin
	/**
	 * @param bool $ajax
	 */
	public function saveManScoringByQuestion($ajax = false)
	// uni-goettingen-patch: end
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		global  $ilAccess, $lng;

		// uni-goettingen-patch: begin
		if(
			!$ilAccess->checkAccess("write", "", $this->ref_id) &&
			!$ilAccess->checkAccess("man_scoring_access", "", $this->ref_id)
		)
		{
			if ($ajax) {
				echo $this->lng->txt('cannot_edit_test');
				exit();
			}
			// uni-goettingen-patch: end
			ilUtil::sendInfo($this->lng->txt('cannot_edit_test'), true);
			$this->ctrl->redirectByClass('ilobjtestgui', 'infoScreen');
		}
		
		if(!isset($_POST['scoring']) || !is_array($_POST['scoring']))
		{
			ilUtil::sendFailure($this->lng->txt('tst_save_manscoring_failed_unknown'));
			$this->showManScoringByQuestionParticipantsTable();
			return;
		}

		include_once 'Modules/TestQuestionPool/classes/class.assQuestion.php';
		include_once 'Modules/Test/classes/class.ilObjTestAccess.php';
		include_once 'Services/Tracking/classes/class.ilLPStatusWrapper.php';

		$oneExceededMaxPoints = false;
		$manPointsPost = array();
		$skipParticipant = array();
		$maxPointsByQuestionId = array();
		foreach($_POST['scoring'] as $pass => $active_ids)
		{
			foreach((array)$active_ids as $active_id => $questions)
			{
				// check for existing test result data
				if( !$this->object->getTestResult($active_id, $pass) )
				{
					if( !isset($skipParticipant[$pass]) )
					{
						$skipParticipant[$pass] = array();
					}
					
					$skipParticipant[$pass][$active_id] = true;
					
					continue;
				}
				
				foreach((array)$questions as $qst_id => $reached_points)
				{
					if( !isset($manPointsPost[$pass]) )
					{
						$manPointsPost[$pass] = array();
					}

					if( !isset($manPointsPost[$pass][$active_id]) )
					{
						$manPointsPost[$pass][$active_id] = array();
					}

					$maxPointsByQuestionId[$qst_id] = assQuestion::_getMaximumPoints($qst_id);
					
					if( $reached_points > $maxPointsByQuestionId[$qst_id] )
					{
						$oneExceededMaxPoints = true;
					}
						
					$manPointsPost[$pass][$active_id][$qst_id] = $reached_points;
				}
			}
		}
		
		if( $oneExceededMaxPoints )
		{
			ilUtil::sendFailure(sprintf($this->lng->txt('tst_save_manscoring_failed'), $pass + 1));
			$this->showManScoringByQuestionParticipantsTable($manPointsPost);
			return;
		}
		
		$changed_one = false;
		$lastAndHopefullyCurrentQuestionId = null;
		// uni-goettingen-patch: begin
		$active_ids = array();
		// uni-goettingen-patch: end
		foreach($_POST['scoring'] as $pass => $active_ids)
		{
			foreach((array)$active_ids as $active_id => $questions)
			{
				$update_participant = false;
				
				if($skipParticipant[$pass][$active_id])
				{
					continue;
				}

				foreach((array)$questions as $qst_id => $reached_points)
				{
					// uni-goettingen-patch: begin
					$this->saveFeedback($active_id, $qst_id, $pass);
					// uni-goettingen-patch: end
					$update_participant = assQuestion::_setReachedPoints(
						$active_id, $qst_id, $reached_points, $maxPointsByQuestionId[$qst_id], $pass, 1, $this->object->areObligationsEnabled()
					);
				}

				if($update_participant)
				{
					$changed_one = true;

					$lastAndHopefullyCurrentQuestionId = $qst_id;

					ilLPStatusWrapper::_updateStatus(
						$this->object->getId(), ilObjTestAccess::_getParticipantId($active_id)
					);
				}
			}
		}

		$correction_feedback = array();
		$correction_points = 0;
		// uni-goettingen-patch: end
		if($changed_one)
		{
			$qTitle = '';
			if($lastAndHopefullyCurrentQuestionId)
			{
				$question = assQuestion::_instantiateQuestion($lastAndHopefullyCurrentQuestionId);
				$qTitle = $question->getTitle();
			}
			$msg = sprintf(
				$this->lng->txt('tst_saved_manscoring_by_question_successfully'), $qTitle, $pass + 1
			);
			ilUtil::sendSuccess($msg, true);

			require_once './Modules/Test/classes/class.ilTestScoring.php';
			$scorer = new ilTestScoring($this->object);
			// uni-goettingen-patch: begin
			$scorer->setPreserveManualScores(true);
			if(is_array($active_ids) && count($active_ids) == 1)
			{
				reset($active_ids);
				$active_id = key($active_ids);
				$scorer->recalculateSolution($active_id, $pass);
				$correction_feedback = $this->object->getSingleManualFeedback($active_id, $qst_id, $pass);
				$correction_points = assQuestion::_getReachedPoints($active_id, $qst_id, $pass);
			}
			else
			{
			$scorer->recalculateSolutions();
		}
		}
		if($ajax && is_array($correction_feedback) && count($correction_feedback) > 0)
		{
			$correction_feedback['finalized_by'] = ilObjUser::_lookupFullname($correction_feedback['finalized_by_usr_id']);
			if(strlen($correction_feedback['finalized_tstamp']) > 0)
			{
				$time = new ilDateTime($correction_feedback['finalized_tstamp'], IL_CAL_UNIX);
				$correction_feedback['finalized_on_date'] = $time->get(IL_CAL_DATETIME);
			}
			else
			{
				$correction_feedback['finalized_on_date'] = '';
			}
		
			echo json_encode(array( 'feedback' => $correction_feedback, 'points' => $correction_points));
			exit();
		}
		else
		{
		$this->showManScoringByQuestionParticipantsTable();
	}
	}

	/**
	 * 
	 */
	private function applyManScoringByQuestionFilter()
	{
		require_once 'Modules/Test/classes/tables/class.ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI.php';
		$table = new ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI($this);
		$table->resetOffset();
		$table->writeFilterToSession();
		$this->showManScoringByQuestionParticipantsTable();
	}

	/**
	 * 
	 */
	private function resetManScoringByQuestionFilter()
	{
		require_once 'Modules/Test/classes/tables/class.ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI.php';
		$table = new ilTestManScoringParticipantsBySelectedQuestionAndPassTableGUI($this);
		$table->resetOffset();
		$table->resetFilter();
		$this->showManScoringByQuestionParticipantsTable();
	}

	private function getAnswerDetail()
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		global $ilAccess;
		
		if(!$ilAccess->checkAccess('write', '', $this->ref_id))
		{
			exit();
		}

		$active_id   = $_GET['active_id'];
		$pass        = $_GET['pass_id'];
		$question_id = $_GET['qst_id'];

		// uni-goettingen-patch: begin
		$data = $this->object->getCompleteEvaluationData(FALSE);
		$participant = $data->getParticipant($active_id);
		
		$question_gui = $this->object->createQuestionGUI('', $question_id);
		$result_output  = $question_gui->getSolutionOutput($active_id, $pass, FALSE, FALSE, FALSE, $this->object->getShowSolutionFeedback());
		$max_points     = $question_gui->object->getMaximumPoints();

		$tpl = new ilTemplate('tpl.il_as_tst_correct_solution_output.html', TRUE, TRUE, 'Modules/Test');
		$this->appendUserNameToModal($tpl, $participant);
		$this->appendQuestionTitleToModal($tpl, $question_id, $max_points, $question_gui->object->getTitle());
		$this->appendSolutionAndPointsToModal($tpl, $result_output, $question_gui->object->getReachedPoints($active_id, $pass), $max_points);
		$this->appendFormToModal($tpl, $pass, $active_id, $question_id, $max_points);
		// uni-goettingen-patch: end
		echo $tpl->get();
		exit();
	}

	// uni-goettingen-patch: begin
	public function checkConstraintsBeforeSaving()
	{
			$this->saveManScoringByQuestion(true);
	}
	

	private function enforceAccessConstraint()
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		global $ilAccess;
		if(
			!$ilAccess->checkAccess("write", "", $this->ref_id) &&
			!$ilAccess->checkAccess("man_scoring_access", "", $this->ref_id)
		)
		{
			exit();
		}
	}

	/**
	 * @param ilTemplate $tmp_tpl
	 * @param $participant
	 */
	private function appendUserNameToModal($tmp_tpl, $participant)
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		global $ilAccess;
		// uni-goettingen-patch: begin
		if ( $this->object->isFullyAnonymized()  ||
			( $this->object->getAnonymity() == 2 && !$ilAccess->checkAccess('write','',$this->object->getRefId())))
		{
			$tmp_tpl->setVariable('TEXT_YOUR_SOLUTION', $this->lng->txt('answers_of') .' '. $this->lng->txt('anonymous'));
		}
		else
		{
		$tmp_tpl->setVariable('TEXT_YOUR_SOLUTION', $this->lng->txt('answers_of') .' '. $participant->getName());
		}
		}
		// uni-goettingen-patch: end
	/**
	 * @param ilTemplate $tmp_tpl
	 * @param $question_id
	 * @param $max_points
	 * @param $title
	 */
	private function appendQuestionTitleToModal($tmp_tpl, $question_id, $max_points, $title)
	{
		$add_title = ' ['. $this->lng->txt('question_id_short') . ': ' . $question_id  . ']';
		
		if($maxpoints == 1)
		{
			$tmp_tpl->setVariable('QUESTION_TITLE', $this->object->getQuestionTitle($question_gui->object->getTitle()) . ' (' . $maxpoints . ' ' . $this->lng->txt('point') . ')' . $add_title);
		}
		else
		{
			$tmp_tpl->setVariable('QUESTION_TITLE', $this->object->getQuestionTitle($question_gui->object->getTitle()) . ' (' . $maxpoints . ' ' . $this->lng->txt('points') . ')' . $add_title);
		}
		$tmp_tpl->setVariable('SOLUTION_OUTPUT', $result_output);
		$tmp_tpl->setVariable('RECEIVED_POINTS', sprintf($this->lng->txt('part_received_a_of_b_points'), $question_gui->object->getReachedPoints($active_id, $pass), $maxpoints));

		echo $tmp_tpl->get();
		exit();
	}
}
