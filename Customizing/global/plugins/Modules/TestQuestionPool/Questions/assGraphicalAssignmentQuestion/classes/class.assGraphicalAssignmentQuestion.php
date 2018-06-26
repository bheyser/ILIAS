<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
require_once 'Modules/TestQuestionPool/interfaces/interface.ilObjQuestionScoringAdjustable.php';
require_once 'Modules/TestQuestionPool/interfaces/interface.ilObjAnswerScoringAdjustable.php';

/**
 * Objectmodel for the assGraphicalAssignmentQuestion
 *
 * Date: 11.01.13
 * Time: 09:30
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 */
class assGraphicalAssignmentQuestion extends assQuestion implements ilObjQuestionScoringAdjustable, ilObjAnswerScoringAdjustable
{

	/**
	 * Reference of the plugin object
	 * @var ilPlugin
	 */
	private $plugin;

	/**
	 * The filename for the graphical image
	 * @var string
	 */
	private $image;

	/**
	 * The size string for the Canvas container WIDTHxHEIGHT
	 * @var string
	 */
	private $canvas_size;

	/**
	 * The answers for the assGraphicalAssignmentQuestion
	 *
	 * @var assAnswerGraphicalAssignment[]
	 */
	private $answers;

	/**
	 * The color which is rendered in the canvas container
	 *
	 * @var string
	 */
	private $color;

	/**
	 * The constructor takes possible arguments and creates an instance of the assGraphicalAssignmentQuestion object
	 *
	 * @param string $title A title string to name the question
	 * @param string $comment A comment string to describe the question
	 * @param string $author A string containing the name of the author
	 * @param int $owner A numerical ID to identify the owner/creator
	 * @param string $question The question string of the assGraphicalAssignmentQuestion question
	 * @param string $image A string containing the name(path) of the image
	 * @access public
	 * @see assQuestion::__construct()
	 */
	function __construct(
		$title = "",
		$comment = "",
		$author = "",
		$owner = -1,
		$question = "",
		$image = ""
	) {
		parent::__construct($title, $comment, $author, $owner, $question);
		$this->image = $image;
		$this->answers = array();

		//init the plugin object
		$this->getPlugin();
	}

	/**
	 * Get the name of the Image
	 *
	 * @return string
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * Sets the image file name
	 *
	 * @param string $image
	 * @param string $tmp_image
	 */
	public function setImage($image, $tmp_image = "")
	{
		if (!empty($image))
		{
			$image = str_replace(" ", "_", $image);
			$this->image = $image;
		}
		if (!empty($tmp_image))
		{
			$imagepath = $this->getImagePath();
			if (!file_exists($imagepath))
			{
				ilUtil::makeDirParents($imagepath);
			}
			if (!ilUtil::moveUploadedFile($tmp_image, $image, $imagepath.$image))
			{
				$this->ilias->raiseError("The image could not be uploaded!", $this->ilias->error_obj->MESSAGE);
			}
			global $ilLog; $ilLog->write("gespeichert: " . $imagepath.$image);
		}
	}

	/**
	 * Set the string representing the canvas container size
	 *
	 * @param string $canvas_size
	 */
	public function setCanvasSize($canvas_size)
	{
		$this->canvas_size = $canvas_size;
	}

	/**
	 * Get the string representing the canvas container size
	 *
	 * @return string
	 */
	public function getCanvasSize()
	{
		return $this->canvas_size;
	}

	/**
	 * Get the canvas container size in an array with width and size per field
	 *
	 * @return array 0: width, 1: height
	 */
	public function getCanvasSizeArray()
	{
		return \explode('x', $this->canvas_size);
	}

	/**
	 * Sets an array with assAnswerGraphicalAssignment
	 *
	 * @param assAnswerGraphicalAssignment[] $answers
	 */
	public function setAnswers($answers)
	{
		$this->answers = $answers;
	}

	/**
	 * Get an array with assAnswerGraphicalAssignment
	 *
	 * @return assAnswerGraphicalAssignment[]
	 */
	public function getAnswers()
	{
		return $this->answers;
	}

	/**
	 * Adds an assAnswerGraphicalAssignment to assGraphicalAssignmentQuestion
	 *
	 * @param assAnswerGraphicalAssignment $answer
	 */
	public function addAnswer($answer)
	{
		$this->answers[] = $answer;
	}

	/**
	 * Removes an Answer with the delivered index form the Answer-Array
	 *
	 * @param int $idx
	 */
	public function removeAnswer($idx)
	{
		unset($this->answers[$idx]);
	}

	/**
	 * Clear the Answerslist of the assGraphicalAssignmentQuestion
	 */
	public function clearAnswers()
	{
		$this->answers = array();
	}

	/**
	 * @param string $color
	 */
	public function setColor($color)
	{
		$this->color = $color;
	}

	/**
	 * @return string
	 */
	public function getColor()
	{
		return $this->color;
	}

	/**
	 * Singleton for initializing the Plugin
	 *
	 * @return ilPlugin The plugin object
	 * @access public
	 */
	public function getPlugin()
	{
		if ($this->plugin == null)
		{
			include_once "./Services/Component/classes/class.ilPlugin.php";
			$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", ilassGraphicalAssignmentQuestionPlugin::getName());
		}
		return $this->plugin;
	}

	/**
	 * Returns true, if the question is complete for use
	 *
	 * @return bool True if the required fields are completed
	 * @access public
	 */
	public function isComplete()
	{
		return (strlen($this->title) && strlen($this->author) && strlen($this->question));
	}

	/**
	 * Saves a assGraphicalAssignmentQuestion object to database
	 *
	 * @param string $original_id
	 * @global ilDB $ilDB
	 */
	public function saveToDb($original_id = "")
	{
		global $ilDB;
		// save the basic data (implemented in parent)
		// a new question is created if the id is -1
		// afterward the new id is set
		$this->saveQuestionDataToDb($original_id);

		// Saves data to question related databasetable
		$ilDB->replace('qpl_qst_grasqst_data',
			array(
				'question_fi' => array('integer', $ilDB->quote($this->getId(), 'integer')),
			),
			array(
				'question_fi' => array('integer', $ilDB->quote($this->getId(), 'integer')),
				'image' => array('text', $this->getImage()),
				'canvas_size' => array('text', $this->getCanvasSize()),
				'color' => array('text', $this->getColor())
			)
		);
		// Updates the related answers of this question
		$this->updateAnswers();

		parent::saveToDb();
	}

	/**
	 * Loads an assGraphicalAssignmentQuestion object form a database and binds the data to the object
	 *
	 * @param int $question_id
	 * @global ilDB $ilDB
	 */
	public function loadFromDb($question_id)
	{
		global $ilDB;

		$result = $ilDB->queryF(
			"SELECT qpl_questions.*, {$this->getAdditionalTableName()}.* FROM qpl_questions LEFT JOIN {$this->getAdditionalTableName()} ON {$this->getAdditionalTableName()}.question_fi = qpl_questions.question_id WHERE question_id = %s",
			array('integer'),
			array($question_id)
		);
		if($result->numRows() == 1)
		{
			$data = $ilDB->fetchAssoc($result);
			$data = $this->loadAnswersFromDb($question_id, $data);

			$this->bindData($data);
		}

		parent::loadFromDb($question_id);
	}

	/**
	 * Duplicates an assGraphicalAssignmentQuestion
	 *
	 * @param bool $for_test
	 * @param string $title
	 * @param string $author
	 * @param string $owner
	 *
	 * @return int
	 */
	public function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
	{
		if(!$this->isQuestionSaved()) return false;

		$this_id = $this->getId();
		$thisObjId = $this->getObjId();
		$clone = $this->cloneQuestion($title, $author, $owner, $testObjId);
		$original_id = assQuestion::_getOriginalId($this_id);

		if($for_test)
		{
			$clone->saveToDb($original_id);
		}
		else
		{
			$clone->saveToDb();
		}

		$this->copyAssQuestionData($clone, $this_id);
		$clone->duplicateImage($thisObjId, $this_id);
		//auding-patch: start
		$clone->duplicateAudingFile($original_id);
		//auding-patch: end
		$clone->onDuplicate($thisObjId, $this_id, $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Copies an assGraphicalAssignmentQuestion object.
	 * It is possible to copy into another target questionpool
	 *
	 * @param int $target_questionpool
	 * @param string $title
	 *
	 * @return int
	 */
	public function copyObject($target_questionpool, $title = "")
	{
		if(!$this->isQuestionSaved()) return false;

		$clone = $this->cloneQuestion($title);
		$original_id = assQuestion::_getOriginalId($this->getId());
		$clone->setObjId($target_questionpool);

		$clone->saveToDb();

		$this->copyAssQuestionData($clone, $original_id);
		$clone->duplicateImage($this->obj_id, $original_id);


		//auding-patch: start
		$clone->duplicateAudingFile($original_id);
		//auding-patch: end

		return $clone->getId();
	}

	/**
	 * Synchronize a question with its original
	 */
	public function syncWithOriginal()
	{
		if($this->getOriginalId())
		{
			// get the original question as clone of the current
			// this keeps all current properties
			/*$orig = clone $this;
			$orig->setId($this->getOriginalId());
			$orig->setOriginalId(null);
			$orig->saveToDb("");

			$orig->deletePageOfQuestion($orig->getId());
			$orig->createPageObject();
			$orig->copyPageOfQuestion($this->getId());

			// back to the current question
			$this->updateSuggestedSolutions($orig->getId());
			#$this->syncFeedbackGeneric();*/
			#$this->syncXHTMLMediaObjectsOfQuestion();
			#$this->syncImages();
			parent::syncWithOriginal();
		}
	}

	public function getMaximumPoints()
	{
	 	$points = 0;
		foreach($this->answers as $answer)
		{
			$points += $answer->getMaximumPoints();
		}
		return $points;
	}
	public function reworkWorkingData($active_id, $pass, $obligationsAnswered)
		{
			//gvollbach added emtpy
		}


	public function calculateReachedPoints($active_id, $pass = null, $authorizedSolution = true, $returndetails = FALSE){
		global $ilDB;

		if($pass === null)
		{
			$pass = $this->getSolutionMaxPass($active_id);
		}

		$result = $this->getCurrentSolutionResultSet($active_id, $pass, $authorizedSolution);

		$user_results = array();
		while ($data = $ilDB->fetchAssoc($result))
		{
			if (strcmp($data["value2"], "") != 0)
			{
				$user_results[$data["value1"]] = array(
					"answer_id" => $data["value1"],
					"value" => $data["value2"]
				);
			}
		}

		$points = 0;
		foreach($this->answers as $answer_key => $answer)
		{
			foreach($answer->getItems() as $key => $item)
			{
				if($answer->getType() == assAnswerGraphicalAssignment::$ANSWER_TYPE_SELECTION)
				{
					if(array_key_exists($answer_key, $user_results) && $key == $user_results[$answer_key]["value"])
					{
						$points += $item->getPoints();
					}
				}
				else
				{
					if(array_key_exists($answer_key, $user_results) && $item->getAnswerText() == $user_results[$answer_key]["value"])
					{
						$points += $item->getPoints();
					}
				}
			}
		}

		return $points;
	}


	public function saveWorkingData($active_id, $pass = null, $authorized = true)
	{
		global $ilDB;

		if($pass === null)
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}

		$this->getProcessLocker()->requestUserSolutionUpdateLock();

		$this->removeCurrentSolution($active_id, $pass, $authorized);

		$entered_values = 0;
		foreach($_POST['answer'] as $answer_id => $answer)
		{
			if($answer['input'] != '' || (int)$answer['input'] >= 0)
			{
				$this->saveCurrentSolution($active_id, $pass, $answer_id, trim($answer['input']), $authorized);
				$entered_values++;
			}
		}
		$this->getProcessLocker()->releaseUserSolutionUpdateLock();

		include_once ("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
		if (ilObjAssessmentFolder::_enabledAssessmentLogging())
		{
			if ($entered_values)
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

	/**
	 * Save feedback for a single selected answer to the database
	 *
	 * @param integer $answer_index
	 * @param string $feedback
	 *
	 * @global ilDB $ilDB
	 */
	public function saveFeedbackSingleAnswer($answer_index, $feedback)
	{
		global $ilDB;

		$ilDB->manipulateF("DELETE FROM {$this->getAdditionalFeedbackTableName()} WHERE question_fi = %s AND answer = %s",
			array('integer', 'integer'),
			array($this->getId(), $answer_index)
		);

		if(strlen($feedback))
		{
			include_once "./Services/RTE/classes/class.ilRTE.php";
			$next_id = $ilDB->nextId($this->getAdditionalFeedbackTableName());
			$ilDB->manipulateF("INSERT INTO {$this->getAdditionalFeedbackTableName()} (feedback_id, question_fi, answer, feedback, tstamp) VALUES (%s, %s, %s, %s, %s)",
				array('integer', 'integer', 'integer','text', 'integer'),
				array($next_id, $this->getId(), $answer_index, ilRTE::_replaceMediaObjectImageSrc($feedback, 0), time())
			);
		}
	}

	public function getFeedbackGeneric($anser_index)
	{
	//gvollbach empty
	}
	/**
	 * Returns the feedback for a single selected answer
	 *
	 * @param $anser_index The index of the answer
	 * @global ilDB $ilDB The database
	 *
	 * @return string
	 */
	public function getFeedbackSingleAnswer($anser_index)
	{
		global $ilDB;

		$result = $ilDB->queryF("SELECT * FROM {$this->getAdditionalFeedbackTableName()} WHERE question_fi = %s and answer = %s",
			array('integer', 'integer'),
			array($this->getId(), $anser_index)
		);

		$feedback = "";
		if($result->numRows())
		{
			$row = $ilDB->fetchAssoc($result);
			include_once "./Services/RTE/classes/class.ilRTE.php";
			$feedback = ilRTE::_replaceMediaObjectImageSrc($row["feedback"], 1);
		}
		return $feedback;
	}

	/**
	 * Deletes a question and all materials from the database
	 *
	 * @param integer $question_id The database id of the question
	 *
	 * @return bool
	 */
	public function delete($question_id)
	{
		$this->deleteFeedbackSingleAnswers($question_id);

		return parent::delete($question_id);
	}

	/**
	 * Create a new Answer for assGraphicalAssignmentQuestion
	 *
	 * @param string $answer_type The answer_type
	 *
	 * @return assAnswerGraphicalAssignment
	 */
	public function createAnswer($answer_type)
	{
		include_once 'class.assAnswerGraphicalAssignment.php';
		return new assAnswerGraphicalAssignment($answer_type);;
	}

	/**
	 * Creates a question from a QTI file
	 *
	 * Receives parameters from a QTI parser and creates a valid ILIAS question object
	 *
	 * @param object $item The QTI item object
	 * @param integer $questionpool_id The id of the parent questionpool
	 * @param integer $tst_id The id of the parent test if the question is part of a test
	 * @param object $tst_object A reference to the parent test object
	 * @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	 * @param array $import_mapping An array containing references to included ILIAS objects
	 */
	public function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		$this->getPlugin()->includeClass("import/qti12/class.assGraphicalAssignmentQuestionImport.php");
		$import = new assGraphicalAssignmentQuestionImport($this);
		return $import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}

	/**
	 * Returns a QTI xml representation of the question and sets the internal
	 * domxml variable with the DOM XML representation of the QTI xml representation
	 *
	 * @return string The QTI xml representation of the question
	 */
	public function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		$this->getPlugin()->includeClass("export/qti12/class.assGraphicalAssignmentQuestionExport.php");
		$export = new assGraphicalAssignmentQuestionExport($this);
		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}

	/**
	 * Returns the name of the additional question data table
	 *
	 * all tables must have a 'question_fi' column
	 * data from these tables will be deleted if a question is deleted
	 *
	 * @return string the name of the additional tables
	 */
	public function getAdditionalTableName()
	{
		return 'qpl_qst_grasqst_data';
	}

	/**
	 * Returns the name of the additional answer data table
	 *
	 * all tables must have a 'question_fi' column
	 * data from these tables will be deleted if a question is deleted
	 *
	 * @return string the name of the additional tables
	 */
	public function getAnswerTableName()
	{
		return 'qpl_qst_grasqst_answer';
	}

	/**
	 * Returns the name of the additional feedback data table
	 *
	 * all tables must have a 'question_fi' column
	 * data from these tables will be deleted if a question is deleted
	 *
	 * @return string The name of the additional table
	 */
	public function getAdditionalFeedbackTableName()
	{
		return "qpl_qst_grasqst_fb";
	}

	/**
	 * Returns the question type of the question
	 *
	 * @return string The question type of the question
	 */
	public function getQuestionType()
	{
		return ilassGraphicalAssignmentQuestionPlugin::getName();
	}

	/**
	 * Binds data form array to assGraphicalAssignmentQuestion
	 *
	 * @param array $data
	 */
	private function bindData($data)
	{
		include_once './Modules/TestQuestionPool/classes/class.assAnswerSimple.php';

		$this->setId($data['question_id']);
		$this->setTitle($data['title']);
		$this->setComment($data['description']);
		$this->setOriginalId($data['original_id']);
		$this->setObjId($data['obj_fi']);
		$this->setAuthor($data['author']);
		$this->setOwner($data['owner']);
		$this->setPoints($data['points']);
		$this->setCanvasSize($data['canvas_size']);
		$this->setImage($data['image']);
		$this->setColor($data['color']);

		//auding-patch: start
		$this->setAudingFile($data['auding_file']);
		$this->setAudingNrOfSends($data['auding_nr_of_sends']);
		$this->setAudingActivate($data['auding_activate']);
		$this->setAudingMode($data['auding_mode']);
		//auding-patch: end

		$this->clearAnswers();
		foreach($data['elements'] as $element)
		{
			$answer = $this->findAnswerById($element['answer_id']);
			if($answer === null)
			{
				require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assGraphicalAssignmentQuestion/classes/class.assAnswerGraphicalAssignment.php';
				$answer = new assAnswerGraphicalAssignment($element['answer_type']);
				$answer->setId($element['answer_id']);
				$answer->setShuffle($element['shuffle']);
				$answer->setDestinationX($element['destination_x']);
				$answer->setDestinationY($element['destination_y']);
				$answer->setTargetX($element['target_x']);
				$answer->setTargetY($element['target_y']);
				$this->answers[] = $answer;
			}

			$item = new ASS_AnswerSimple(
				$element['answertext'],
				$element['points'],
				$element['order'],
				$element['item_id']
			);
			$answer->addItem($item);
		 }

		include_once("./Services/RTE/classes/class.ilRTE.php");
		$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
		$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));

	}

	/**
	 * Return true, if the question is already saved and the id is > 0.
	 *
	 * @return bool
	 */
	private function isQuestionSaved()
	{
		return $this->getId() > 0;
	}

	/**
	 * Generates a clone of the assGraphicalAssignmentQuestion and replaces possible fields
	 *
	 * @param string $title
	 * @param string $author
	 * @param string $owner
	 *
	 * @return assGraphicalAssignmentQuestion
	 */
	private function cloneQuestion($title = "", $author = "", $owner = "", $textObjId = null)
	{
		$clone = clone $this;
		$clone->setId(-1);

		if($textObjId) $clone->setObjId($textObjId);
		if($title) $clone->setTitle($title);
		if($author)	$clone->setAuthor($author);
		if($owner) $clone->setOwner($owner);

		return $clone;
	}

	/**
	 * Duplicates an image from the original_pool_id with the original_question_id
	 * into the clone pool and question_id location
	 *
	 * @param int $original_pool_id The
	 * @param int $original_question_id
	 */
	private function duplicateImage($original_pool_id, $original_question_id)
	{
		$imagepath          = $this->getImagePath();
		$imagepath_original = str_replace("/$this->obj_id/$this->id/images", "/$original_pool_id/$original_question_id/images", $imagepath);

		if (!file_exists($imagepath))
		{
			ilUtil::makeDirParents($imagepath);
		}
		$filename = $this->getImage();
		if (!copy($imagepath_original . $filename, $imagepath . $filename))
		{
			print "image could not be duplicated!!!! ";
		}
	}

	/**
	 * Copy all related elements for a question which are implemented in the assQuestion
	 *
	 * @param assGraphicalAssignmentQuestion $clone
	 * @param int $question_id
	 */
	private function copyAssQuestionData($clone, $question_id)
	{
		// copy question page content
		$clone->copyPageOfQuestion($question_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($question_id);
	}

	/**
	 * Updates all Answers and their items for the assGraphicalAssignmentQuestion
	 * In the first step all current answers are deleted and then reinserted every existing answer as a new answer
	 *
	 * @global ilDB $ilDB
	 */
	private function updateAnswers()
	{
		global $ilDB;

		$ilDB->manipulateF("DELETE FROM {$this->getAnswerTableName()} WHERE question_fi = %s",
			array("integer"),
			array($this->getId())
		);

		foreach($this->answers as $answer)
		{
			$next_id = $ilDB->nextId($this->getAnswerTableName());
			$answer->setId($next_id);

			foreach($answer->getItems() as $key => $item)
			{
				$ilDB->manipulateF(
					"INSERT INTO {$this->getAnswerTableName()} (answer_id, question_fi, item_id, answertext, points, aorder, answer_type, shuffle, destination_x, destination_y, target_x, target_y) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					array(
						'integer',
						'integer',
						'integer',
						'text',
						'float',
						'integer',
						'text',
						'boolean',
						'integer',
						'integer',
						'integer',
						'integer'
					),
					array(
						$answer->getId(),
						$this->getId(),
						$key,
						strlen($item->getAnswertext()) ? $item->getAnswertext() : "",
						$item->getPoints(),
						$item->getOrder(),
						$answer->getType(),
						$answer->getShuffle(),
						$answer->getDestinationX(),
						$answer->getDestinationY(),
						$answer->getTargetX(),
						$answer->getTargetY()
					)
				);
			}
		}
	}

	/**
	 * Loads all related answers of an assGraphicalAssignmentQuestion from the database and bind them to the
	 * assGraphicalAssignmentQuestion
	 *
	 * @param int $question_id
	 * @param array $data
	 * @global ilDB $ilDB
	 */
	private function loadAnswersFromDb($question_id, $data)
	{
		global $ilDB;

		$result = $ilDB->queryF(
			"SELECT * FROM {$this->getAnswerTableName()} WHERE question_fi = %s ORDER BY answer_id, aorder ASC",
			array('integer'),
			array($question_id)
		);

		$data['elements'] = array();

		if($result->numRows() > 0)
		{
			while($tmp = $ilDB->fetchAssoc($result))
			{
				$data['elements'][] = $tmp;
			}
		}

		return $data;
	}

	/**
	 * Find an assAnswerGraphicalAssignment by an delivered answer_id
	 *
	 * @param int $answer_id
	 *
	 * @return assAnswerGraphicalAssignment|null
	 */
	private function findAnswerById($answer_id)
	{
		foreach($this->answers as $answer)
		{
			if($answer->getId() == $answer_id)
			{
				return $answer;
			}
		}
		return null;
	}

	/**
	 * Deletes all Single-Answer-Feedback from the additional feedback table for a question_id
	 *
	 * @see assGraphicalAssignmentQuestion::getAdditionalFeedbackTableName()
	 * @param int $question_id The question id
	 *
	 * @global ilDB $ilDB
	 */
	private function deleteFeedbackSingleAnswers($question_id)
	{
	   global $ilDB;

		if($question_id < 1) return true;

		$ilDB->manipulateF("DELETE FROM {$this->getAdditionalFeedbackTableName()} WHERE question_fi = %s",
			array('integer'),
			array($question_id)
		);
	}

	/**
	 * 
	 */
	public function getSolutionSubmit()
	{
		$solutionSubmit = array();

		foreach($_POST['answer'] as $answer_id => $answer)
		{
			$solutionSubmit[(int)$answer_id] = trim($answer['input']);
		}

		return $solutionSubmit;
	}

	/**
	 * @param array $a_solution
	 * @return float
	 */
	protected function calculateReachedPointsForSolution($a_solution)
	{
		$points = 0;

		foreach($this->answers as $answer_key => $answer)
		{
			foreach($answer->getItems() as $key => $item)
			{
				if($answer->getType() == assAnswerGraphicalAssignment::$ANSWER_TYPE_SELECTION)
				{
					if(array_key_exists($answer_key, $a_solution) && $key == $a_solution[$answer_key])
					{
						$points += $item->getPoints();
					}
				}
				else
				{
					if(array_key_exists($answer_key, $a_solution) && $item->getAnswerText() == $a_solution[$answer_key])
					{
						$points += $item->getPoints();
					}
				}
			}
		}

		return $points;
	}

	public function saveAnswerSpecificDataToDb()
	{
		$this->updateAnswers();
	}

	public function saveAdditionalQuestionDataToDb()
	{

	}
}
