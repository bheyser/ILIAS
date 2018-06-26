<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
include_once 'class.assAnswerGraphicalAssignment.php';
require_once 'Modules/TestQuestionPool/interfaces/interface.ilGuiQuestionScoringAdjustable.php';
require_once 'Modules/TestQuestionPool/interfaces/interface.ilGuiAnswerScoringAdjustable.php';

/**
 * The assGraphicalAssignmentQuestionGui representing the Gui for assGraphicalAssignmentQuestion
 *
 * Date: 11.01.13
 * Time: 14:53
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 * @ilCtrl_isCalledBy assGraphicalAssignmentQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilTestOutputGUI
 */
class assGraphicalAssignmentQuestionGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable{

	public static $CANVAS_SIZE_SMALL = "800x600";
	public static $CANVAS_SIZE_MEDIUM = "1024x768";
	public static $CANVAS_SIZE_LARGE = "1280x1024";
	public static $CANVAS_SIZE_EXTRALARGE = "1440x900";

	private static $FABRIC_JS_LIBRARY_SOURCE = 'templates/js/fabric.js';
	private static $JS_DRAWER_UTILITY_SOURCE = 'templates/js/js.drawerUtility.js';
	private static $JS_AUTO_GROW_INPUT_SOURCE = 'templates/js/js.autoGrowInput.js';
	private static $DEFAULT_PREVIEW_TEMPLATE = "default/tpl.il_as_qpl_grasqst_output.html";
	private static $SOLUTION_TEMPLATE = "default/tpl.il_as_qpl_grasqst_output_solution.html";

	/**
	 * An ID for the Canvas-Container, which is increased for each rendered Canvas-Container
	 *
	 * @var int
	 */
	private $container_id_counter = 1;

	/**
	 * The ilPropertyFormGui representing the assGraphicalAssignmentQuestion
	 *
	 * @var ilPropertyFormGUI
	 */
	private $form;

    /**
	 * The ilPlugin object representing the assGraphicalAssignmentQuestion
	 *
	 * @var ilPlugin|null
	 */
	public $plugin = null;


	/**
	 * @param int $id
	 */
	public function __construct($id = -1)
	{
		parent::__construct();
		$this->object = new assGraphicalAssignmentQuestion();
		$this->plugin = $this->object->getPlugin();
		if($id > 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	/**
	 * Command: edit the question
	 */
	public function editQuestion()
	{
		$this->initQuestionForm();
		$this->initializeEditTemplate();
	}

	/**
	 * Command: save the question
	 */
	public function save()
	{
		parent::save();
		//question couldn't be saved
		$this->form->setValuesByPost();
		$this->initializeEditTemplate();
	}

	/**
	 * Command: save and show page editor
	 */
	public function saveEdit()
	{
		parent::saveEdit();
		//question couldn't be saved

		$this->form->setValuesByPost();
		$this->initializeEditTemplate();
	}

	/**
	 * Command Add a new assAnswerGraphicalAssignment to the assGraphicalAssignmentQuestion
	 */
	public function addElement()
	{
		$this->writePostData(true);
		$answer = $this->object->createAnswer(assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT);
		$answer->addItem($answer->createItem());

		$this->object->addAnswer($answer);
		$this->object->saveToDb();

		$this->ctrl->redirect($this, 'editQuestion');
	}
	/**
	 * Get prevent rte usage
	 *
	 * @return	boolean	prevent rte usage
	 */
	function getPreventRteUsage()
	{
		return $this->prevent_rte_usage;
	}

	/**
	 * Set Self-Assessment Editing Mode.
	 *
	 * @param	boolean	$a_selfassessmenteditingmode	Self-Assessment Editing Mode
	 */
	function setSelfAssessmentEditingMode($a_selfassessmenteditingmode)
	{
		$this->selfassessmenteditingmode = $a_selfassessmenteditingmode;
	}

	/**
	 * Get Self-Assessment Editing Mode.
	 *
	 * @return	boolean	Self-Assessment Editing Mode
	 */
	function getSelfAssessmentEditingMode()
	{
		return $this->selfassessmenteditingmode;
	}

	/**
	 * Validates the form input data and write the form data into the assGraphicalAssignmentQuestion object
	 * (called from generic commands in assQuestionGUI)
	 *
	 * @return int 0: question can be saved / 1: question cannot be saved, causing an error in the form
	 */
	public function writePostData($save_unchecked_answers = false)
	{
		$this->deleteAnswers();

		$this->initQuestionForm();

		if($this->form->checkInput())
		{
			$this->writeQuestionGenericPostData();
			$this->object->setCanvasSize($this->form->getInput('canvas_size'));
			$this->object->setColor($this->form->getInput('color'));
			$this->handleFileUpload($this->form->getInput('image'));
			$result = $this->handleAnswersInput();

			$this->saveTaxonomyAssignments();

			// indicator to save the question
			return $result;
		}

		// This is required to save the answer locations if you click to addElement if there are already moved
		// elements in the canvas container
		if($save_unchecked_answers)
		{
			$this->handleAnswersInput();
		}
		// indicator to show the edit form with errors
		return 1;
	}

	/**
	 * Get the HTML output for the assGraphicalAssignmentQuestion preview
	 *
	 * @param bool $show_question_only
	 *j
	 * @return string
	 */
	public function getPreview($show_question_only = false, $showInlineFeedback = false)
	{
		$tpl = $this->getOutputTemplate(self::$DEFAULT_PREVIEW_TEMPLATE);
		//auding-patch: start
		$this->outAudingPreview($tpl);
		//auding-patch: end
		$output = $tpl->get();

		if(!$show_question_only)
		{
			$output = $this->getILIASPage($output);
		}

		return $output;
	}

	public function getSpecificFeedbackOutput($active_id, $pass)
	{
		//gvollbach added emtpy
	}
	/**
	 * Get the question solution output
	 *
	 * @param integer $active_id The active user id
	 * @param integer $pass The test pass
	 * @param boolean $graphicalOutput Show visual feedback for right/wrong answers
	 * @param boolean $result_output Show the reached points for parts of the question
	 * @param boolean $show_question_only Show the question without the ILIAS content around
	 * @param boolean $show_feedback Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring Show specific information for the manual scoring output
	 * @return The solution output of the question as HTML code
	 */
	public function getSolutionOutput($active_id, $pass = null, $graphicalOutput = false, $result_output = false, $show_question_only = true, $show_feedback = false, $show_correct_solution = false, $show_manual_scoring = false, $show_question_text = true)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solutions = array();
		if (($active_id > 0) && (!$show_correct_solution))
		{
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				$user_solutions[] = $solution_value["value2"];
			}
		}
		else
		{
			$best_solutions = array();
			foreach($this->object->getAnswers() as $index => $answer)
			{
				$max_points = 0;
				foreach($answer->getItems() as $item_key => $item)
				{
					if($item->getPoints() > $max_points)
					{
						if($answer->getType() == assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT)
						{
							$best_solutions[$index] = $item->getAnswertext();
						}
						else
						{
							$best_solutions[$index] = $item_key;
						}

						$max_points = $item->getPoints();
					}
				}
			}
			$user_solutions = $best_solutions;
		}


		// generate the question output
		#include_once "./classes/class.ilTemplate.php";
		$solution_template = new ilTemplate("tpl.il_as_tst_solution_output.html",true, true, "Modules/TestQuestionPool");
		$template = $this->getOutputTemplate(self::$SOLUTION_TEMPLATE, $user_solutions);

		if (($active_id > 0) && (!$show_correct_solution))
		{
			if ($graphicalOutput)
			{
				// output of ok/not ok icons for user entered solutions
				$reached_points = $this->object->getReachedPoints($active_id, $pass);
				if ($reached_points == $this->object->getMaximumPoints())
				{
					$template->setCurrentBlock("icon_ok");
					$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.svg"));
					$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock("icon_ok");
					if ($reached_points > 0)
					{
						$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_mostly_ok.svg"));
						$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_not_correct_but_positive"));
					}
					else
					{
						$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.svg"));
						$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
					}
					$template->parseCurrentBlock();
				}
			}
		}
		$template->setVariable("QUESTIONTEXT", $this->object->getQuestion());
		$template->setVariable("ID_COUNTER", uniqid());

		$feedback = ($show_feedback) ? $this->getAnswerFeedbackOutput($active_id, $pass) : "";

		if (strlen($feedback)) $solution_template->setVariable("FEEDBACK", $feedback);

		$solution_template->setVariable("SOLUTION_OUTPUT", $template->get());

		$output = $solution_template->get();

		if (!$show_question_only)
		{
			// get page object output
			$output = $this->getILIASPage($output);
		}

		return $output;

	}

	/**
	 * @param bool $checkonly
	 *
	 * @return bool
	 */
	public function feedback($checkonly = false)
	{
		$save = (strcmp($this->ctrl->getCmd(), "saveFeedback") == 0) ? true : false;
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt('feedback_answers'));
		$form->setTableWidth("100%");
		$form->setId("feedback");

		$complete = new ilTextAreaInputGUI($this->lng->txt("feedback_complete_solution"), "feedback_complete");
		$complete->setValue($this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(1)));
		$complete->setRequired(false);
		$complete->setRows(10);
		$complete->setCols(80);
		if (!$this->getPreventRteUsage())
		{
			$complete->setUseRte(true);
		}
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$complete->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
		$complete->addPlugin("latex");
		$complete->addButton("latex");
		$complete->addButton("pastelatex");
		$complete->setRTESupport($this->object->getId(), "qpl", "assessment");
		$form->addItem($complete);

		$incomplete = new ilTextAreaInputGUI($this->lng->txt("feedback_incomplete_solution"), "feedback_incomplete");
		$incomplete->setValue($this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(0)));
		$incomplete->setRequired(false);
		$incomplete->setRows(10);
		$incomplete->setCols(80);
		if (!$this->getPreventRteUsage())
		{
			$incomplete->setUseRte(true);
		}
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$incomplete->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
		$incomplete->addPlugin("latex");
		$incomplete->addButton("latex");
		$incomplete->addButton("pastelatex");
		$incomplete->setRTESupport($this->object->getId(), "qpl", "assessment");
		$form->addItem($incomplete);

		if(!$this->getSelfAssessmentEditingMode())
		{
			foreach($this->object->getAnswers() as $index => $answer)
			{
				$text = $this->plugin->txt("element") . " " . ($index + 1);
				$answerObj = new ilTextAreaInputGUI($this->object->prepareTextareaOutput($text), "feedback_answer_$index");
				$answerObj->setValue($this->object->prepareTextareaOutput($this->object->getFeedbackSingleAnswer($index)));
				$answerObj->setRequired(false);
				$answerObj->setRows(10);
				$answerObj->setCols(80);
				$answerObj->setUseRte(true);
				include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
				$answerObj->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
				$answerObj->addPlugin("latex");
				$answerObj->addButton("latex");
				$answerObj->addButton("pastelatex");

				$answerObj->setRTESupport($this->object->getId(), "qpl", "assessment");
				$form->addItem($answerObj);
			}
		}

		global $ilAccess;
		if ($ilAccess->checkAccess("write", "", $_GET['ref_id']) || $this->getSelfAssessmentEditingMode())
		{
			$form->addCommandButton("saveFeedback", $this->lng->txt("save"));
		}

		if ($save)
		{
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
		}
		if (!$checkonly) $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
		return $errors;
	}


	/**
	 * Saves the feedback for an assGraphicalAssignmentQuestion
	 */
	public function saveFeedback()
	{
		$this->feedback(true);
		$this->object->saveFeedbackGeneric(0, $_POST['feedback_incomplete']);
		$this->object->saveFeedbackGeneric(1, $_POST['feedback_complete']);
		foreach($this->object->getAnswers() as $index => $answer)
		{
			$this->object->saveFeedbackSingleAnswer($index, $_POST["feedback_answer_$index"]);
		}

		$this->object->cleanupMediaObjectUsage();
		parent::saveFeedback();
	}

	/**
	 * Sets the ILIAS tabs for the assGraphicalAssignmentQuestionType called from ilObjTestGUI and ilObjQuestionPoolGUI
	 *
	 * @global ilRbacSystem $rbacsystem
	 * @global ilTabsGUI $ilTabs
	 */
	public function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;

		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET['q_id']);

		$classname = ilassGraphicalAssignmentQuestionPlugin::getName() . 'GUI';
		$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", ilassGraphicalAssignmentQuestionPlugin::getName());
		$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET['q_id']);

		if($_GET['q_id'])
		{
			if($rbacsystem->checkAccess("write", $_GET['ref_id']))
			{
				$ilTabs->addTarget("edit_page",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "", false);
			}

			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "preview"),
				array("preview"),
				"ilAssQuestionPageGUI", "", false);
		}

		if($rbacsystem->checkAccess("write", $_GET['ref_id']))
		{
			$url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			$ilTabs->addTarget("edit_question",
				$url,
				array("editQuestion", "save", "cancel", "cancelExplorer", "linkChicls", "parseQuestion", "saveEdit"),
				$classname, "", false
			);
		}

		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);

		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);

		if($_GET['q_id'])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, ""
			);
		}

		if($_GET['calling_test'] > 0 || $_GET["test_ref_id"] > 0)
		{
			$ref_id = $_GET['calling_test'];
			if(strlen($ref_id) == 0) $ref_id = $_GET['test_ref_id'];

			global $___test_express_mode;

			if (!$_GET['test_express_mode'] && !$___test_express_mode) {
				$ilTabs->setBackTarget(
					$this->lng->txt("backtocallingtest"),
					"ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id"
				);
			}
			else {
				$link = ilTestExpressPage::getReturnToPageLink();
				$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), $link);
			}
		}
		else
		{
			$ilTabs->setBackTarget(
				$this->lng->txt("qpl"),
				$this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions")
			);
		}
	}

	/**
	 * Initialize the form representing the configuration GUI for the
	 * assGraphicalAssignmentQuestion
	 */
	private function initQuestionForm()
	{
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->plugin->txt(ilassGraphicalAssignmentQuestionPlugin::getName()));
		$form->setMultipart(true);
		$form->setTableWidth("100%");
		$form->setId(ilassGraphicalAssignmentQuestionPlugin::getPluginId());

		$this->addBasicQuestionFormProperties($form);

		if($this->object->getId())
		{
			$hidden = new ilHiddenInputGUI("", "ID");
			$hidden->setValue($this->object->getId());
			$form->addItem($hidden);
		}

		//canvas size selector
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		$size_selector = new ilSelectInputGUI($this->plugin->txt("canvas_size"), "canvas_size");
		$size_selector->setOptions(array(
			self::$CANVAS_SIZE_SMALL => self::$CANVAS_SIZE_SMALL,
			self::$CANVAS_SIZE_MEDIUM => self::$CANVAS_SIZE_MEDIUM,
			self::$CANVAS_SIZE_LARGE => self::$CANVAS_SIZE_LARGE,
			self::$CANVAS_SIZE_EXTRALARGE => self::$CANVAS_SIZE_EXTRALARGE,
		));
		$size_selector->setValue($this->object->getCanvasSize());
		$form->addItem($size_selector);

		include_once("./Services/Form/classes/class.ilColorPickerInputGUI.php");
		$color = new ilColorPickerInputGUI($this->plugin->txt('color'), 'color');
		$color->setValue($this->object->getColor());
		$form->addItem($color);

		include_once("class.ilGraphicalAssignmentCanvasInputGUI.php");
		$canvas = new ilGraphicalAssignmentCanvasInputGUI($this->lng->txt('image'), 'image');
		$canvas->setSizeArray($this->object->getCanvasSizeArray());
		$canvas->setColor($this->object->getColor());
		$canvas->setRequired(true);
		$canvas->setImage($this->object->getImagePathWeb() . $this->object->getImage());
		$canvas->setValue($this->object->getImage());
		$form->addItem($canvas);

		$this->initAnswerFormElements($form);
		$this->populateTaxonomyFormSection($form);

		/*$form->addCommandButton('addElement', $this->plugin->txt('add_element'));*/

		$this->addQuestionFormCommandButtons($form);
		$this->form = $form;
	}

	/**
	 * Initialize all required form elements for displaying the answers input
	 * in assGraphicalAssignmentQuestion
	 * @param ilPropertyFormGUI $form
	 * @param bool $correction
	 */
	private function initAnswerFormElements($form, $correction = false)
	{
		require_once 'class.ilHiddenArrayInputGUI.php';

		foreach($this->object->getAnswers() as $index => $answer)
		{
			$header = new ilFormSectionHeaderGUI();
			$header->setTitle($this->plugin->txt("element") . " " . ($index +1));
			$form->addItem($header);

			$answer_counter = new ilHiddenArrayInputGUI("answers[$index]");
			$answer_counter->setPostVar("answers[$index]");
			$answer_counter->setValue($index);
			$form->addItem($answer_counter);

			$this->generateHiddenCoordElement($index, "destination_x", $answer->getDestinationX(), $form);
			$this->generateHiddenCoordElement($index, "destination_y", $answer->getDestinationY(), $form);
			$this->generateHiddenCoordElement($index, "target_x", $answer->getTargetX(), $form);
			$this->generateHiddenCoordElement($index, "target_y", $answer->getTargetY(), $form);

			include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
			$answer_input = new ilSelectInputGUI($this->plugin->txt('type'), "answer_type_$index");
			$answer_input->setOptions(array(
				assAnswerGraphicalAssignment::$ANSWER_TYPE_SELECTION => $this->plugin->txt(assAnswerGraphicalAssignment::$ANSWER_TYPE_SELECTION),
				assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT      => $this->plugin->txt(assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT),
			));
			
			$answer_input->setDisabled($correction);
			$answer_input->setValue($answer->getType());
			$form->addItem($answer_input);

			include_once("./Modules/TestQuestionPool/classes/class.ilAnswerWizardInputGUI.php");
			$items = new ilAnswerWizardInputGUI($this->lng->txt("values"), "items_$index");
			$items->setRequired(true);
			$items->setQuestionObject($this->object);
			$items->setSingleline(true);
			$items->setAllowMove(false);

			if(count($answer->getItems()) == 0)
			{
				include_once("./Modules/TestQuestionPool/classes/class.assAnswerSimple.php");
				$answer->addItem(new ASS_AnswerSimple("", 0, 0));
			}
			$items->setValues($answer->getItems());
			$form->addItem($items);

			if($answer->getType() == assAnswerGraphicalAssignment::$ANSWER_TYPE_SELECTION)
			{
				$shuffle = new ilCheckboxInputGUI($this->plugin->txt("shuffle_answer"), "shuffle_$index");
				$shuffle->setValue(1);
				$shuffle->setChecked($answer->getShuffle());
				$shuffle->setRequired(false);
				$form->addItem($shuffle);
			}

			if( ! $correction)
			{
				$delete = new ilCheckboxInputGUI($this->plugin->txt("remove_answer"), "remove_$index");
				$delete->setValue(1);
				$delete->setChecked(false);
				$delete->setRequired(false);
				$form->addItem($delete);
			}

		}
	}

	/**
	 * Generates a new ilHiddenInputGui which includes a position of the assAnswerGraphicalAssignment in the
	 * canvas container
	 *
	 * @param int $index
	 * @param string $position
	 * @param float $value
	 * @param ilPropertyFormGUI $form
	 */
	private function generateHiddenCoordElement($index, $position, $value, $form)
	{
		$coords = new ilHiddenArrayInputGUI("answer[$index][$position]");
		$coords->setValue($value);
		$form->addItem($coords);
	}

	/**
	 * Initialize the assGraphicalAssignmentQuestion template for the PropertyFormGUI
	 * and insert the form html into the template
	 */
	private function initializeEditTemplate()
	{
		$this->getQuestionTemplate();

		$this->tpl->addJavaScript(ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$FABRIC_JS_LIBRARY_SOURCE);
		$this->tpl->addJavaScript(ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$JS_DRAWER_UTILITY_SOURCE);
		$this->tpl->addJavaScript(ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$JS_AUTO_GROW_INPUT_SOURCE);
		$this->tpl->setVariable("CANVAS_COLOR", $this->object->getColor());
		$this->tpl->setVariable("QUESTION_DATA", $this->form->getHTML());

	}

	/**
	 * Get the html output of the assGraphicalAssignmentQuestion for test
	 *
	 * @param int $active_id
	 * @param null|int $pass
	 * @param bool $is_postponed
	 * @param bool $use_post_solutions
	 * @param bool $show_feedback
	 *
	 * @return mixed
	 */
	public function getTestOutput($active_id, $pass = null, $is_postponed = false, $use_post_solutions = false, $show_feedback = false)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if($active_id)
		{
			require_once './Modules/Test/classes/class.ilObjTest.php';
			if(!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}

			$solutions = $this->object->getUserSolutionPreferingIntermediate($active_id, $pass);
			if(is_array($solutions))
			{
				foreach($solutions as $solution)
				{
					$user_solution[$solution['value1']] = $solution['value2'];
				}
			}
		}

		$tpl = $this->getOutputTemplate(self::$DEFAULT_PREVIEW_TEMPLATE, $user_solution, true);
		//auding-patch: start
		$this->outAuding($tpl);
		//auding-patch: end
		$output = $tpl->get();

		return $this->outQuestionPage("", $is_postponed, $active_id, $output);
	}

	/**
	 * Get the output of the delivered template
	 *
	 * @param string $tpl_name
	 *
	 * @return string
	 */
	private function getOutputTemplate($tpl_name, $solutions = array(), $for_test = false)
	{
		$tpl = $this->plugin->getTemplate($tpl_name);

		$jsFiles = [
			ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$FABRIC_JS_LIBRARY_SOURCE,
			ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$JS_DRAWER_UTILITY_SOURCE,
			ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$JS_AUTO_GROW_INPUT_SOURCE
		];

		if($this->ctrl->isAsynch())
		{
			foreach($jsFiles as $jsFileSrc)
			{
				$tpl->setCurrentBlock('js_qst_files');
				$tpl->setVariable('SRC', $jsFileSrc);
				$tpl->parseCurrentBlock();
			}
		}
		else
		{
			foreach($jsFiles as $jsFileSrc)
			{
				$this->tpl->addJavaScript($jsFileSrc);
			}
		}

		$tpl->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput(
			$this->object->getQuestion(), true
		));

		$size = $this->object->getCanvasSizeArray();
		$tpl->setVariable("CANVAS_COLOR", $this->object->getColor());
		$tpl->setVariable("CANVAS_WIDTH", $size[0]);
		$tpl->setVariable("CANVAS_HEIGHT", $size[1]);
		$tpl->setVariable("SRC_IMAGE", $this->object->getImagePathWeb() . $this->object->getImage());

		$image_size = getimagesize($this->object->getImagePath() . $this->object->getImage());
		$image_position = array(
			'left' => 0,
			'top' => 0
		);

		if($image_size[0] < $size[0]){
			$image_position['left'] = ($size[0] - $image_size[0]) / 2;
			$image_position['top'] = ($size[1] - $image_size[1]) / 2;
		}

		$tpl->setVariable("IMAGE_POSITION_LEFT", $image_position['left'] );
		$tpl->setVariable("IMAGE_POSITION_TOP", $image_position['top'] );
		$tpl->setVariable("IMAGE_WIDTH", $image_size[0] );
		$tpl->setVariable("IMAGE_HEIGHT", $image_size[1] );

		foreach($this->object->getAnswers() as $idx => $answer)
		{
			$tpl->setCurrentBlock("input_coords");
			$tpl->setVariable("ANSWER_INDEX", $idx);
			$tpl->setVariable('TARGET_X', $answer->getTargetX());
			$tpl->setVariable('TARGET_Y', $answer->getTargetY());
			$tpl->setVariable('DESTINATION_X', $answer->getDestinationX());
			$tpl->setVariable('DESTINATION_Y', $answer->getDestinationY());
			$tpl->parseCurrentBlock();

			if($answer->getType() == assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT)
			{
				$tpl->setCurrentBlock("input_text");
				if(isset($solutions[$idx]))
				{
					$tpl->setVariable("VALUE", $solutions[$idx]);
				}

				$tpl->setVariable("ANSWER_INDEX", $idx);
				$tpl->parseCurrentBlock();

			} else
			{
				$items = $answer->getItems();
				if($for_test){
					$items = $answer->getItemsShuffled();
				}

				foreach($items as $item_idx => $item){
					$tpl->setCurrentBlock("input_option");
					if(isset($solutions[$idx]) && $item_idx == $solutions[$idx])
					{
						$tpl->setVariable("SELECTED", "selected=\"selected\"");
					}

					$tpl->setVariable('OPTION_TEXT', $item->getAnswertext());
					$tpl->setVariable('OPTION_VALUE', $item->getId());
					$tpl->parseCurrentBlock();
				}
				$tpl->setCurrentBlock("input_select");
				$tpl->setVariable("ANSWER_INDEX", $idx);
				$tpl->parseCurrentBlock();
			}
		}
		
		$tpl->setVariable("ID_COUNTER", uniqid());

		return $tpl;
	}

	/**
	 * Removes all Answers from the $_POST array if they are marked to delete
	 */
	private function deleteAnswers()
	{
		if(isset($_POST['answers']))
		{
			foreach($_POST['answers'] as $idx)
			{
				if(isset($_POST["remove_$idx"]))
				{
					unset($_POST["answer_type_$idx"]);
					unset($_POST["items_$idx"]);
					unset($_POST["answer_type_$idx"]);
					unset($_POST["answers"][$idx]);
					unset($_POST["answer"][$idx]);
					unset($_POST["remove_$idx"]);

					$this->object->removeAnswer($idx);
				}
			}
		}
	}

	/**
	 * Handles the answer input for a assGraphicalAssignmentQuestion. In the first step the function clears all current
	 * answers of the question and then put all still existing answers into the questions answer storage
	 */
	private function handleAnswersInput()
	{
		$this->object->clearAnswers();

		if(isset($_POST['answers']) && is_array($_POST['answers']))
		{
			$answer_input = $_POST["answer"];

			foreach($_POST['answers'] as $idx)
			{
				if(!isset($_POST["remove_$idx"]) || $_POST["remove_$idx"] == "")
				{
					$answer_type = $_POST["answer_type_$idx"];
					$answer = new assAnswerGraphicalAssignment($answer_type);

					$answer->setDestinationX($answer_input[$idx]['destination_x']);
					$answer->setDestinationY($answer_input[$idx]['destination_y']);
					$answer->setTargetX($answer_input[$idx]['target_x']);
					$answer->setTargetY($answer_input[$idx]['target_y']);

					$shuffle = $_POST["shuffle_$idx"];

					if($shuffle != null && $shuffle == '1')
					{
						$answer->setShuffle(true);
					}

					if(isset($_POST["items_$idx"]) && is_array($_POST["items_$idx"]))
					{
						$num_values = array_count_values($_POST["items_$idx"]['answer']);
						$inputCount = count($_POST["items_$idx"]['answer']);

						// There are one ore more duplicated answer possibilities in the $_POST array.
						if(count($num_values) != $inputCount){
							include_once("./Services/Utilities/classes/class.ilUtil.php");

							$item = $this->form->getItemByPostVar("items_$idx");
							$item->setAlert($this->plugin->txt("duplicated_answer_item"));

							ilUtil::sendFailure($this->plugin->txt("duplicated_answer_item"));
							return 1;
						}

						for($i = 0; $i < $inputCount ; $i++)
						{
							include_once("./Modules/TestQuestionPool/classes/class.assAnswerSimple.php");
							$item = new ASS_AnswerSimple($_POST["items_$idx"]['answer'][$i], $_POST["items_$idx"]['points'][$i], $i);
							$answer->addItem($item);
						}
					}

					$this->object->addAnswer($answer);
				}
			}
		}

		//return 0;
	}

	/**
	 * Handles the file input for assGraphicalAssignmentQuestion
	 *
	 * @param array $input
	 */
	private function handleFileUpload($input)
	{
		if(strlen($input['tmp_name'])) {
			$this->object->setImage($input['name'], $input['tmp_name']);
		} else {
			$this->object->setImage($input['name']);
		}
	}

	/**
	 * @param ilPropertyFormGUI $form
	 * @return ilPropertyFormGUI
	 */
	public function populateAnswerSpecificFormPart(ilPropertyFormGUI $form)
	{
		$this->initAnswerFormElements($form, true);
		return $form;
	}

	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function writeAnswerSpecificPostData(ilPropertyFormGUI $form)
	{
		$this->handleAnswersInput();
	}

	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function populateQuestionSpecificFormPart(ilPropertyFormGUI $form)
	{
		$question = new ilTextAreaInputGUI($this->lng->txt("question"), "question");
		$question->setValue($this->object->prepareTextareaOutput($this->object->getQuestion()));
		$question->setRequired(TRUE);
		$question->setRows(10);
		$question->setCols(80);

		if (!$this->object->getSelfAssessmentEditingMode())
		{
			if( $this->object->getAdditionalContentEditingMode() != assQuestion::ADDITIONAL_CONTENT_EDITING_MODE_PAGE_OBJECT )
			{
				$question->setUseRte(TRUE);
				include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
				$question->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
				$question->addPlugin("latex");
				$question->addButton("latex");
				$question->addButton("pastelatex");
				$question->setRTESupport($this->object->getId(), "qpl", "assessment");
			}
		}
		else
		{
			$question->setRteTags(self::getSelfAssessmentTags());
			$question->setUseTagsForRteOnly(false);
		}
		$form->addItem($question);

		include_once("class.ilGraphicalAssignmentCanvasInputGUI.php");
		$canvas = new ilGraphicalAssignmentCanvasInputGUI($this->lng->txt('image'), 'image');
		$canvas->setSizeArray($this->object->getCanvasSizeArray());
		$canvas->setColor($this->object->getColor());
		$canvas->setRequired(true);
		$canvas->setImage($this->object->getImagePathWeb() . $this->object->getImage());
		$canvas->setValue($this->object->getImage());
		$canvas->setDisabled(true);
		$form->addItem($canvas);

		global $tpl;

		$tpl->addJavaScript(ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$FABRIC_JS_LIBRARY_SOURCE);
		$tpl->addJavaScript(ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$JS_DRAWER_UTILITY_SOURCE);
		$tpl->addJavaScript(ilassGraphicalAssignmentQuestionPlugin::getLocation() . self::$JS_AUTO_GROW_INPUT_SOURCE);
		$tpl->setVariable("CANVAS_COLOR", $this->object->getColor());

	}

	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function writeQuestionSpecificPostData(ilPropertyFormGUI $form)
	{
		require_once 'Modules/TestQuestionPool/classes/class.ilAssHtmlQuestionContentPurifier.php';
		$purifier = new ilAssHtmlQuestionContentPurifier();
		$question_text = $purifier->purify($_POST['question']);
		$this->object->setQuestion($question_text);
	}

	/**
	 * @return array
	 */
	public function getAfterParticipationSuppressionQuestionPostVars()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function getAfterParticipationSuppressionAnswerPostVars()
	{
		return array();
	}

	/**
	 * @param array $relevant_answers
	 * @return string
	 */
	public function getAggregatedAnswersView($relevant_answers)
	{

		global $tpl;
		$tpl->addJavaScript('Customizing/global/plugins/Modules/TestQuestionPool/Questions/assGraphicalAssignmentQuestion/templates/js/assGraphicalAssessmentCorrectionHelper.js');
		$overview = array();
		$aggregation = array();
		foreach ($relevant_answers as $answer)
		{
			$overview[$answer['active_fi']][$answer['pass']][$answer['value1']] = $answer['value2'];
		}

		foreach($overview as $active)
		{
			foreach ($active as $answer)
			{
				foreach ($answer as $option => $value)
				{
					$aggregation[$option][$value] = $aggregation[$option][$value] + 1;
				}
			}
		}

		$html = '<div>';
		$i = 0;
		foreach ($this->object->getAnswers() as $gap)
		{
			if ($gap->getType() ==  assAnswerGraphicalAssignment::$ANSWER_TYPE_SELECTION)
			{
				$html .= '<p>'.$this->lng->txt('qpl_qst_grasqst_element') .' '. ($i+1) . ' - ' . $this->lng->txt('qpl_qst_grasqst_answer_type_selection').'</p>';
				$html .= '<ul>';
				$j = 0;
				foreach($gap->getItems() as $gap_item)
				{
					$aggregate = $aggregation[$i];
					$html .= '<li>' . $gap_item->getAnswerText() . ' - ' . ($aggregate[$j] ? $aggregate[$j] : 0) . '</li>';
					$j++;
				}
				$html .= '</ul>';
			}

			if($gap->getType() == assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT)
			{
				$present_elements = array();
				foreach($gap->getItems() as $item)
				{
					/** @var assAnswerCloze $item */
					$present_elements[] = $item->getAnswertext();
				}

				$html .= '<p>'.$this->lng->txt('qpl_qst_grasqst_element') .' '. ($i+1) . ' - ' . $this->lng->txt('qpl_qst_grasqst_answer_type_text').'</p>';
				$html .= '<ul>';
				$aggregate = (array)$aggregation[$i];
				foreach($aggregate as $answer => $count)
				{
					$show_mover = '';
					if(in_array($answer, $present_elements))
					{
						$show_mover = ' style="display: none;" ';
					}

					$html .= '<li>' . $answer . ' - ' . $count
						. '&nbsp;<button class="clone_fields_add btn btn-link" ' . $show_mover . ' data-answer="'.$answer.'" name="add_gap_'.$i.'_0">
						<span class="sr-only"></span><span class="glyphicon glyphicon-plus"></span></button>
						</li>';
				}
				$html .= '</ul>';
			}
			$i++;
			$html .= '<hr />';
		}

		$html .= '</div>';
		return $html;
	}
}
