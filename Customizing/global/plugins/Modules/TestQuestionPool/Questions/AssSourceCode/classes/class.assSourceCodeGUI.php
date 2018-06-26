<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";
require_once dirname(__FILE__).'/class.ilAssSourceCodePlugin.php';
// uni-goettingen-patch: begin
require_once './Modules/Test/classes/class.ilTestPlayerCommands.php';
// uni-goettingen-patch: end

/**
 * Infotext GUI class for question type plugins
 *
 * @author	Björn Heyser <bheyser@databay.de>
 * @version	$Id$
 * @package Modules/TestQuestionPool
 *
 * @ilCtrl_isCalledBy assSourceCodeGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 */
class assSourceCodeGUI extends assQuestionGUI
{
	/**
	 * @var ilAssSourceCodePlugin	The plugin object
	 */
	var $plugin = null;


	/**
	 * @var assSourceCode	The question object
	 */
	var $object = null;

	/**
	* Constructor
	*
	* @param integer $id The database id of a question object
	* @access public
	*/
	public function __construct($id = -1)
	{
		$this->plugin = ilAssSourceCodePlugin::getInstance();

		parent::__construct();

		$this->plugin->includeClass("class.assSourceCode.php");
		$this->object = new assSourceCode();

		if ($id > 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	public function getFormEncodingType()
	{
		return self::FORM_ENCODING_MULTIPART;
	}

	protected function buildEditForm()
	{
		$form = $this->buildBasicEditFormObject();
		$this->addBasicQuestionFormProperties($form);

		$this->addQuestionSpecificFormProperties($form);
		$this->addAnswerSpecificFormProperties($form);

		$this->populateTaxonomyFormSection($form);
		$this->addQuestionFormCommandButtons($form);

		return $form;
	}

	protected function addQuestionSpecificFormProperties(ilPropertyFormGUI $form)
	{
		$scoringPoints = new ilNumberInputGUI($this->lng->txt('points'), 'points');
		$scoringPoints->setSize(3);
		$scoringPoints->setMinValue(0);
		$scoringPoints->allowDecimals(1);
		$scoringPoints->setRequired(true);
		$scoringPoints->setValue($this->object->getPoints());
		$form->addItem($scoringPoints);

		$srcCodeLanguage = $this->buildCodeLanguageSelectInputGUI();
		if($this->object->getSourceCodeLanguage())
		{
			$srcCodeLanguage->setValue($this->object->getSourceCodeLanguage()->getIdentifier());
		}
		$form->addItem($srcCodeLanguage);
	}

	protected function addAnswerSpecificFormProperties(ilPropertyFormGUI $form)
	{
		// currently no fields
	}

	protected function buildCodeLanguageSelectInputGUI()
	{
		$selectInput = new ilSelectInputGUI($this->plugin->txt('form_prop_code_lang'), 'code_lang');
		$selectInput->setInfo($this->plugin->txt('form_prop_code_lang_info'));

		$selectInput->setRequired(true);

		$options = $this->object->sourceCodeLanguageFactory->getAvailableSourceCodeLanguagePresentationLabelsByIdentifiers(
			ilAssSourceCodePlugin::getInstance()
		);

		$selectInput->setOptions(array_merge(
			array('0' => $this->lng->txt('please_select')), $options
		));

		return $selectInput;
	}

	public function editQuestion(ilPropertyFormGUI $form = null)
	{
		if( $form === null )
		{
			$form = $this->buildEditForm();
		}

		$this->getQuestionTemplate();

		$this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		//$this->tpl->addCss('Modules/Test/templates/default/ta.css');
	}

	protected function writePostData($forceSaving = false)
	{
		$form = $this->buildEditForm();
		$form->setValuesByPost();

		if( !$form->checkInput() && !$forceSaving )
		{
			$this->editQuestion($form);
			return 1;
		}

		$this->writeQuestionGenericPostData();

		$this->writeQuestionSpecificPostData($form);
		$this->writeAnswerSpecificPostData($form);

		$this->saveTaxonomyAssignments();

		return 0;
	}

	protected function writeQuestionSpecificPostData(ilPropertyFormGUI $form)
	{
		$this->object->setPoints($form->getInput('points'));

		$sourceCodeLanguage = $this->object->sourceCodeLanguageFactory->getSourceCodeLanguageByIdentifier(
			$form->getInput('code_lang')
		);

		$this->object->setSourceCodeLanguage($sourceCodeLanguage);
	}

	protected function writeAnswerSpecificPostData(ilPropertyFormGUI $form)
	{
		// currently no fields
	}

	/**
	 * Get the HTML output of the question for a test
	 * (this function could be private)
	 *
	 * @param integer $active_id			The active user id
	 * @param integer $pass					The test pass
	 * @param boolean $is_postponed			Question is postponed
	 * @param boolean $use_post_solutions	Use post solutions
	 * @param boolean $show_feedback		Show a feedback
	 * @return string
	 */
	public function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
		$template = ilAssSourceCodePlugin::getInstance()->getTemplate("tpl.il_ass_sourcecode.html");
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput( $this->object->getQuestion(), TRUE));

		$webIde = $this->object->buildWebIdeGUI();
		$webIde->setAsyncSaveTarget($this->getTargetGuiClass());
		$webIde->setAsyncSaveCommand(ilTestPlayerCommands::AUTO_SAVE);

		if ($active_id)
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (is_null($pass))
			{
				$pass = ilObjTest::_getPass($active_id);
			}

			$solution = $this->object->getStoredSolution($active_id, $pass);
			$webIde->setInputValue($solution);
		}

		$webIde->setUploadCommand($this->getQuestionActionCmd());

		$template->setVariable('WEBIDE_HTML', $webIde->getHTML());

		return $this->outQuestionPage("", $is_postponed, $active_id, $template->get());
	}

	/**
	 * @param boolean	show only the question instead of embedding page (true/false)
	 * @param boolean	make the question render answer option feedback inline (or not)
	 */
	public function getPreview($show_question_only = false, $showInlineFeedback = false)
	{
		$template = ilAssSourceCodePlugin::getInstance()->getTemplate('tpl.il_ass_sourcecode.html');

		$questionText = $this->object->prepareTextareaOutput($this->object->getQuestion(), true);
		$template->setVariable('QUESTIONTEXT', $questionText);

		$webIde = $this->object->buildWebIdeGUI();
		$webIde->setAsyncSaveTarget($this->getTargetGuiClass());
		$webIde->setAsyncSaveCommand(ilTestPlayerCommands::AUTO_SAVE);

		// uni-goettingen-patch: begin
    if( !$this->isContentEditingOutputMode() )
    //if( !$this->isUserInputOutputMode() )
    // uni-goettingen-patch: end
		{
			$webIde->setReadOnlyEnabled(true);
		}

		if( $this->getPreviewSession() && $this->previewParticipantSolutionExist($this->getPreviewSession()) )
		{
			$solution = unserialize($this->getPreviewSession()->getParticipantsSolution());
			$webIde->setInputValue($solution);
		}
		else
		{
			$webIde->setInputValue(new ilAssSourceCodeSolution());
		}

		$template->setVariable('WEBIDE_HTML', $webIde->getHTML());

		if($show_question_only)
		{
			return $template->get();
		}

		return $this->getILIASPage($template->get());
	}

	private function previewParticipantSolutionExist(ilAssQuestionPreviewSession $previewSession)
	{
		if( method_exists($previewSession, 'hasParticipantsSolution') )
		{
			return $previewSession->hasParticipantsSolution();
		}

		return $previewSession->getParticipantsSolution() !== null;
	}

	/**
	 * Get the question solution output
	 * @param integer $active_id             The active user id
	 * @param integer $pass                  The test pass
	 * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
	 * @param boolean $result_output         Show the reached points for parts of the question
	 * @param boolean $show_question_only    Show the question without the ILIAS content around
	 * @param boolean $show_feedback         Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
	 * @return The solution output of the question as HTML code
	 */
	function getSolutionOutput(
		$active_id,
		$pass = NULL,
		$graphicalOutput = FALSE,
		$result_output = FALSE,
		$show_question_only = TRUE,
		$show_feedback = FALSE,
		$show_correct_solution = FALSE,
		$show_manual_scoring = FALSE,
		$show_question_text = TRUE
	)
	{
		if ($show_correct_solution)
		{
			return '';
		}

		$template = ilAssSourceCodePlugin::getInstance()->getTemplate('tpl.il_ass_sourcecode.html');

		$solution = $this->object->getStoredSolution($active_id, $pass);

		$webIde = $this->object->buildWebIdeGUI($show_correct_solution ? '_ref' : '_usr');
		$webIde->setPrintModeEnabled($this->isPdfOutputMode());
		$webIde->setReadOnlyEnabled(true);

		$webIde->setInputValue($solution);

		$template->setVariable('WEBIDE_HTML', $webIde->getHTML());

		if( $show_question_text )
		{
			$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput(
				$this->object->getQuestion(), true
			));
		}

		$solutiontemplate = $this->getSolutionOutputContainerTemplate();
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $template->get());

		$feedback = ($show_feedback) ? $this->getGenericFeedbackOutput($active_id, $pass) : "";
		if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $feedback, true ));

		if( $show_question_only )
		{
			return $solutiontemplate->get();
		}

		return $this->getILIASPage( $solutiontemplate->get() );
	}

	/**
	 * Returns the answer specific feedback for the question
	 *
	 * @param integer $active_id Active ID of the user
	 * @param integer $pass Active pass
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	function getSpecificFeedbackOutput($active_id, $pass)
	{
		// By default no answer specific feedback is defined
		$output = "";
		return $this->object->prepareTextareaOutput($output, TRUE);
	}


	/**
	* Sets the ILIAS tabs for this question type
	* called from ilObjTestGUI and ilObjQuestionPoolGUI
	*/
	public function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;

		$this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"])
		{
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
			{
				// edit page
				$ilTabs->addTarget("edit_page",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "", $force_active);
			}

			// edit page
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "preview"),
				array("preview"),
				"ilAssQuestionPageGUI", "", $force_active);
		}

		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";

			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			$commands = $_POST["cmd"];

			// edit question properties
			$ilTabs->addTarget("edit_properties",
				$url,
				array("editQuestion", "save", "cancel", "cancelExplorer", "linkChilds",
				"parseQuestion", "saveEdit"),
				$classname, "", $force_active);
		}

		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);

		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);

		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("solution_hint",
				$this->ctrl->getLinkTargetByClass($classname, "suggestedsolution"),
				array("suggestedsolution", "saveSuggestedSolution", "outSolutionExplorer", "cancel",
					"addSuggestedSolution","cancelExplorer", "linkChilds", "removeSuggestedSolution"
				),
				$classname,
				""
			);
		}

		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}

		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];
			$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}

	protected function getSolutionOutputContainerTemplate()
	{
		foreach(class_parents($this) as $parent)
		{
			if( method_exists($parent, 'getSolutionOutputContainerTemplate') )
			{
				return parent::getSolutionOutputContainerTemplate();
			}
		}

		return new ilTemplate(
			'tpl.il_as_tst_solution_output.html', true, true, 'Modules/TestQuestionPool'
		);
	}
}
