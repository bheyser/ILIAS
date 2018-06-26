<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";
require_once dirname(__FILE__).'/class.ilAssSourceCodePlugin.php';

/**
 * Infotext class for question type plugins
 *
 * @author	Björn Heyser <bheyser@databay.de>
 * @version	$Id$
 * @package Modules/TestQuestionPool
 */
class assSourceCode extends assQuestion
{
	const SOLUTION_POSTVAR = 'solution';
	
	/**
	 * @var ilDB
	 */
	protected $db = null;
	
	/**
	 * @var ilAssSourceCodeLanguageFactory
	 */
	public $sourceCodeLanguageFactory = null;
	
	/**
	 * @var ilAssSourceCodeLanguage
	 */
	public $sourceCodeLanguage = null;
	
	/**
	 * assSourceCode constructor.
	 *
	 * @param string $title
	 * @param string $comment
	 * @param string $author
	 * @param int $owner
	 * @param string $question
	 */
	public function __construct($title = "", $comment = "", $author = "", $owner = -1, $question = "")
	{
		$this->db = $GLOBALS['DIC'] ? $GLOBALS['DIC']['ilDB'] : $GLOBALS['ilDB'];
		
		$this->sourceCodeLanguageFactory = new ilAssSourceCodeLanguageFactory();
		
		// needed for excel export
		$this->getPlugin()->loadLanguageModule();

		parent::__construct($title, $comment, $author, $owner, $question);
	}
	
	public function getAvailableSourceCodeLanguages()
	{
		return $this->sourceCodeLanguageFactory->getAvailableSourceCodeLanguages();
	}
	
	/**
	 * @return ilAssSourceCodeLanguage
	 */
	public function getSourceCodeLanguage()
	{
		return $this->sourceCodeLanguage;
	}
	
	/**
	 * @param ilAssSourceCodeLanguage $sourceCodeLanguage
	 */
	public function setSourceCodeLanguage($sourceCodeLanguage)
	{
		$this->sourceCodeLanguage = $sourceCodeLanguage;
	}

	/**
	 * Get the plugin object
	 *
	 * @return ilAssSourceCodePlugin The plugin object
	 */
	public function getPlugin()
	{
		return ilAssSourceCodePlugin::getInstance();
	}

	/**
	 * Returns true, if the question is complete
	 *
	 * @return boolean True, if the question is complete for use, otherwise false
	 */
	public function isComplete()
	{
		if( !strlen($this->getTitle()) )
		{
			return false;
		}
		
		if( !strlen($this->getQuestion()) )
		{
			return false;
		}
		
		if( !(int)$this->getPoints() )
		{
			return false;
		}
		
		return $this->sourceCodeLanguageFactory->isValidSourceCodeLanguage(
			$this->getSourceCodeLanguage()
		);
	}

	/**
	 * Saves a question object to a database
	 * 
	 * @param	string		original id
	 * @access 	public
	 * @see assQuestion::saveToDb()
	 */
	public function saveToDb($original_id = "")
	{

		// save the basic data (implemented in parent)
		// a new question is created if the id is -1
		// afterwards the new id is set
		$this->saveQuestionDataToDb($original_id);

		// Now you can save additional data
		$this->saveQuestionSpecificDataToDb();

		// save stuff like suggested solutions
		// update the question time stamp and completion status
		parent::saveToDb();
	}

	/**
	 * Loads a question object from a database
	 * This has to be done here (assQuestion does not load the basic data)!
	 *
	 * @param integer $question_id A unique key which defines the question in the database
	 * @see assQuestion::loadFromDb()
	 */
	public function loadFromDb($question_id)
	{
		// load the basic question data
		$result = $this->db->query("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = "
				. $this->db->quote($question_id, 'integer'));

		$data = $this->db->fetchAssoc($result);
		$this->setId($question_id);
		$this->setTitle($data["title"]);
		$this->setComment($data["description"]);
		$this->setSuggestedSolution($data["solution_hint"]);
		$this->setOriginalId($data["original_id"]);
		$this->setObjId($data["obj_fi"]);
		$this->setAuthor($data["author"]);
		$this->setOwner($data["owner"]);
		$this->setPoints($data["points"]);

		include_once("./Services/RTE/classes/class.ilRTE.php");
		$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
		$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));

		// now you can load additional data
		
		$this->loadQuestionSpecificDataFromDb();
		
		try
		{
			$this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
		}
		catch(ilTestQuestionPoolException $e)
		{
		}

		// loads additional stuff like suggested solutions
		parent::loadFromDb($question_id);
	}
	
	protected function loadQuestionSpecificDataFromDb()
	{
		$res = $this->db->queryF(
			"SELECT code_lang FROM {$this->getAdditionalTableName()} WHERE question_fi = %s",
			array('integer'), array($this->getId())
		);
		
		while($row = $this->db->fetchAssoc($res))
		{
			if( strlen($row['code_lang']) )
			{
				$this->setSourceCodeLanguage($this->sourceCodeLanguageFactory->getSourceCodeLanguageByIdentifier(
					$row['code_lang']
				));
			}
		}
	}
	
	protected function saveQuestionSpecificDataToDb()
	{
		if( $this->getSourceCodeLanguage() )
		{
			$sourceCodeLangId = $this->getSourceCodeLanguage()->getIdentifier();
		}
		else
		{
			$sourceCodeLangId = null;
		}
			
		$this->db->replace(
			$this->getAdditionalTableName(),
			array('question_fi' => array('integer', $this->getId())),
			array('code_lang' => array('text', $sourceCodeLangId))
		);
	}
	
	/**
	 * Duplicates a question
	 * This is used for copying a question to a test
	 *
	 * @access public
	 */
	function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
	{
		if ($this->getId() <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;
							
		$original_id = assQuestion::_getOriginalId($this->getId());
		$clone->setId(-1);

		if( (int) $testObjId > 0 )
		{
			$clone->setObjId($testObjId);
		}

		if ($title)
		{
			$clone->setTitle($title);
		}
		if ($author)
		{
			$clone->setAuthor($author);
		}
		if ($owner)
		{
			$clone->setOwner($owner);
		}		
		
		if ($for_test)
		{
			$clone->saveToDb($original_id, false);
		}
		else
		{
			$clone->saveToDb('', false);
		}		

		// copy question page content
		$clone->copyPageOfQuestion($this->getId());
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this->getId());

		// call the event handler for duplication
		$clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Copies a question
	 * This is used when a question is copied on a question pool
	 *
	 * @access public
	 */
	function copyObject($target_questionpool_id, $title = "")
	{
		if ($this->getId() <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;
				
		$original_id = assQuestion::_getOriginalId($this->getId());
		$source_questionpool_id = $this->getObjId();
		$clone->setId(-1);
		$clone->setObjId($target_questionpool_id);
		if ($title)
		{
			$clone->setTitle($title);
		}
				
		// save the clone data
		$clone->saveToDb('', false);

		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);

		// call the event handler for copy
		$clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Synchronize a question with its original
	 * You need to extend this function if a question has additional data that needs to be synchronized
	 * 
	 * @access public
	 */
	function syncWithOriginal()
	{
		parent::syncWithOriginal();
	}
	

	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers.
	 *
	 * @param integer $active 	The Id of the active learner
	 * @param integer $pass 	The Id of the test pass
	 * @param boolean $returndetails (deprecated !!)
	 * @return integer/array $points/$details (array $details is deprecated !!)
	 * @access public
	 * @see  assQuestion::calculateReachedPoints()
	 */
	function calculateReachedPoints($active_id, $pass = NULL, $authorizedSolution = true, $returndetails = false)
	{
		return 0;
	} 


	/**
	 * Saves the learners input of the question to the database
	 *
	 * @param 	integer $test_id The database id of the test containing this question
	 * @return 	boolean Indicates the save status (true if saved successful, false otherwise)
	 * @access 	public
	 * @see 	assQuestion::saveWorkingData()
	 */
	function saveWorkingData($active_id, $pass = NULL, $authorized = true)
	{
		if (is_null($pass))
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}

		try
		{
			$solution = $this->getSolutionSubmit();
			
			$this->removeCurrentSolution($active_id, $pass, $authorized);
			$this->saveCurrentSolution($active_id, $pass, $solution->getEncryptedContent(), null, $authorized);
			
			include_once("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				if( strlen($solution->getPlainContent()) )
				{
					$this->logAction($this->lng->txtlng("assessment", "log_user_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
				}
				else
				{
					$this->logAction($this->lng->txtlng("assessment", "log_user_not_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
				}
			}
			
			return true;
		}
		catch(ilAssSourceCodeInvalidSubmitException $e)
		{
			ilUtil::sendFailure($e->getMessage(), true);
			return false;
		}
		
	}
	
	protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
	{
		// normally nothing needs to be reworked
	}
	
	public function getAjaxSolutionSubmitSuccessResponse($active_id, $pass)
	{
		return json_encode(array(
			'solution' => $this->getStoredSolution($active_id, $pass)->getEncodedContent(),
			'responseText' => $this->lng->txt("autosave_success")
		));
	}

	public function getQuestionType()
	{
		return ilAssSourceCodePlugin::QUESTION_TYPE_TAG;
	}

	public function getAdditionalTableName()
	{
		return "qpl_qst_sourcecode";
	}
	
	public function getRTETextWithMediaObjects()
	{
		return parent::getRTETextWithMediaObjects();
	}

	public function setExportDetailsXLS(&$worksheet, $startrow, $active_id, $pass, &$format_title, &$format_bold)
	{
		include_once ("./Services/Excel/classes/class.ilExcelUtils.php");
		$storedSolution = $this->getStoredSolution($active_id, $pass);

		$worksheet->writeString($startrow, 0, ilExcelUtils::_convert_text($this->getPlugin()->txt($this->getQuestionType())), $format_title);
		$worksheet->writeString($startrow, 1, ilExcelUtils::_convert_text($this->getTitle()), $format_title);
		$startrow++;
		
		if( $this->isEncodedExcelExportEnabled() )
		{
			$solution = $storedSolution->getEncodedContent();
		}
		else
		{
			$solution = $storedSolution->getPlainContent();
		}
		
		$worksheet->setRow($startrow, $storedSolution->getRowsAmount() * 10);
		$worksheet->writeString($startrow, 0, ilExcelUtils::_convert_text($solution));
		$startrow++;
		
		return $startrow++;
	}
	
	public function isEncodedExcelExportEnabled()
	{
		return (bool)$GLOBALS[$this->getPlugin()->getId()]['urlEncodedExcelExport'];
	}

	/**
	 * Creates a question from a QTI file
	 * Receives parameters from a QTI parser and creates a valid ILIAS question object
	 * Extension needed to get the plugin path for the import class
	 *
	 * @access public
	 * @see assQuestion::fromXML()
	 */
	function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		$this->getPlugin()->includeClass("import/qti12/class.assSourceCodeImport.php");
		$import = new assSourceCodeImport($this);
		$import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}

	/**
	 * Returns a QTI xml representation of the question and sets the internal
	 * domxml variable with the DOM XML representation of the QTI xml representation
	 * Extension needed to get the plugin path for the import class
	 *
	 * @return string The QTI xml representation of the question
	 * @access public
	 * @see assQuestion::toXML()
	 */
	function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		$this->getPlugin()->includeClass("export/qti12/class.assSourceCodeExport.php");
		$export = new assSourceCodeExport($this);
		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}
	
	public function getSolutionSubmit()
	{
		$webIde = $this->buildWebIdeGUI();
		$webIde->setInputValue(new ilAssSourceCodeSolution());
		
		if( !$webIde->checkInput() )
		{
			throw new ilAssSourceCodeInvalidSubmitException($webIde->getAlert());
		}
		
		$webIde->setValueByArray($_POST);
		
		return $webIde->getInputValue();
	}
	
	protected function savePreviewData(ilAssQuestionPreviewSession $previewSession)
	{
		try
		{
			$previewSession->setParticipantsSolution(serialize($this->getSolutionSubmit()));
		}
		catch(ilAssSourceCodeInvalidSubmitException $e)
		{
			ilUtil::sendFailure($e->getMessage(), true);
		}
	}
	
	public function getStoredSolution($active_id, $pass)
	{
		$solution = new ilAssSourceCodeSolution();
		
		foreach($this->getUserSolutionPreferingIntermediate($active_id, $pass) as $row)
		{
			$solution->setEncryptedContent($row['value1']);
		}
		
		return $solution;
	}
	
	/**
	 * @param anything $solution
	 * @return int $points = 0
	 */
	public function calculateReachedPointsforSolution($solution)
	{
		return 0;
	}
	
	/**
	 * @return ilWebIdeGUI
	 */
	public function buildWebIdeGUI($instanceSuffix = '')
	{
		$webIde = new ilWebIdeGUI();
		
		$webIde->setPostVar(assSourceCode::SOLUTION_POSTVAR);
		$webIde->setInstanceId(__CLASS__.$this->getId().$instanceSuffix);
		$webIde->setSourceCodeLanguage($this->getSourceCodeLanguage());
		$webIde->setLibraryPath($this->getPlugin()->getLibraryPath());
		$webIde->setStylesheetPath($this->getPlugin()->getStylesheetPath());
		$webIde->setTemplate($this->getPlugin()->getTemplate(ilWebIdeGUI::TEMPLATE_FILENAME));
		
		$webIde->setUploadHeaderLabel($this->getPlugin()->txt('file_upload_modal'));
		$webIde->setUploadWarning($this->getPlugin()->txt('file_upload_modal_warning'));
		
		$webIde->setUploadModalTpl(
			$this->getPlugin()->getTemplate(ilWebIdeGUI::MODAL_TEMPLATE_FILENAME)
		);
		
		return $webIde;
	}
}
