<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqSingleChoiceInstance
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
class ilAsqSingleChoiceQuestion extends ilAsqQuestionAbstract
{
	/**
	 * @var bool
	 */
	protected $shuffleEnabled;
	
	/**
	 * @var bool
	 */
	protected $singleLineAnswers;
	
	/**
	 * @var int
	 */
	protected $thumbnailSize;
	
	/**
	 * ilAsqSingleChoiceQuestion constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->setShuffleEnabled(false);
		$this->setSingleLineAnswers(true);
		$this->setThumbnailSize(0);
	}
	
	/**
	 * @return bool
	 */
	public function isShuffleEnabled(): bool
	{
		return $this->shuffleEnabled;
	}
	
	/**
	 * @param bool $shuffleEnabled
	 */
	public function setShuffleEnabled(bool $shuffleEnabled)
	{
		$this->shuffleEnabled = $shuffleEnabled;
	}
	
	/**
	 * @return bool
	 */
	public function isSingleLineAnswers(): bool
	{
		return $this->singleLineAnswers;
	}
	
	/**
	 * @param bool $singleLineAnswers
	 */
	public function setSingleLineAnswers(bool $singleLineAnswers)
	{
		$this->singleLineAnswers = $singleLineAnswers;
	}
	
	/**
	 * @return int
	 */
	public function getThumbnailSize(): int
	{
		return $this->thumbnailSize;
	}
	
	/**
	 * @param int $thumbnailSize
	 */
	public function setThumbnailSize(int $thumbnailSize)
	{
		$this->thumbnailSize = $thumbnailSize;
	}
	
	public function setPoints(float $points)
	{
		// TODO: Implement setPoints() method.
	}
	
	public function getPoints(): float
	{
		// TODO: Implement getPoints() method.
	}
	
	public function getAdditionalTableName()
	{
		return 'qpl_qst_sc';
	}
	
	public function load()
	{
		global $DIC;
		$ilDB = $DIC['ilDB'];
		
		$hasimages = 0;
		
		$result = $ilDB->queryF("SELECT qpl_questions.*, " . $this->getAdditionalTableName() . ".* FROM qpl_questions LEFT JOIN " . $this->getAdditionalTableName() . " ON " . $this->getAdditionalTableName() . ".question_fi = qpl_questions.question_id WHERE qpl_questions.question_id = %s",
			array("integer"),
			array($this->getId())
		);
		if ($result->numRows() == 1)
		{
			$data = $ilDB->fetchAssoc($result);
			$this->setParentId($data["obj_fi"]);
			$this->setTitle($data["title"]);
			#$this->setNrOfTries($data['nr_of_tries']);
			$this->setComment($data["description"]);
			#$this->setOriginalId($data["original_id"]);
			$this->setAuthor($data["author"]);
			#$this->setPoints($data["points"]);
			$this->setOwner($data["owner"]);
			include_once("./Services/RTE/classes/class.ilRTE.php");
			$this->setQuestionText(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
			$shuffle = (is_null($data['shuffle'])) ? true : $data['shuffle'];
			#$this->setShuffleEnabled($shuffle);
			
			$workingTime = new DateInterval('PT'
				.substr($data['working_time'], 0, 2).'H'
				.substr($data['working_time'], 3, 2).'M'
				.substr($data['working_time'], 6, 2).'S'
			);
			$this->setEstimatedWorkingTime($workingTime);
			
			#$this->setThumbSize($data['thumb_size']);
			$this->isSingleline = ($data['allow_images']) ? false : true;
			$this->lastChange = $data['tstamp'];
			$this->feedback_setting = $data['feedback_setting'];
			
			try {
				$this->setLifecycle(ilAsqQuestionLifecycle::getInstance($data['lifecycle']));
			} catch(ilAsqInvalidArgumentException $e) {
				$this->setLifecycle(ilAsqQuestionLifecycle::getDraftInstance());
			}
			
			try
			{
				#$this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
			}
			catch(ilTestQuestionPoolException $e)
			{
			}
		}
		
		$result = $ilDB->queryF("SELECT * FROM qpl_a_sc WHERE question_fi = %s ORDER BY aorder ASC",
			array('integer'),
			array($this->getId())
		);
		
		if( FALSE )
		if ($result->numRows() > 0)
		{
			while ($data = $ilDB->fetchAssoc($result))
			{
				$imagefilename = $this->getImagePath() . $data["imagefile"];
				if (!@file_exists($imagefilename))
				{
					$data["imagefile"] = "";
				}
				include_once("./Services/RTE/classes/class.ilRTE.php");
				$data["answertext"] = ilRTE::_replaceMediaObjectImageSrc($data["answertext"], 1);
				array_push($this->answers, new ASS_AnswerBinaryStateImage($data["answertext"], $data["points"], $data["aorder"], 1, $data["imagefile"]));
			}
		}
	}
	
	public function save()
	{
		// TODO: Implement save() method.
	}
	
	public function delete()
	{
		// TODO: Implement delete() method.
	}
	
	public function fromQtiItem(ilQTIItem $qtiItem)
	{
		// TODO: Implement fromQtiItem() method.
	}
	
	public function toQtiXML(): string
	{
		// TODO: Implement toQtiXML() method.
	}
	
	public function isComplete(): bool
	{
		// TODO: Implement isComplete() method.
	}
	
	public function getBestSolution(): ilAsqQuestionSolution
	{
		// TODO: Implement getBestSolution() method.
	}
	
	public function getSuggestedSolutionOutput(): \ILIAS\UI\Component\Component
	{
		// TODO: Implement getSuggestedSolutionOutput() method.
	}
	
	public function toJSON(): string
	{
		// TODO: Implement toJSON() method.
	}
	
	public function setOfflineExportImagePath($offlineExportImagePath = null)
	{
		// TODO: Implement setOfflineExportImagePath() method.
	}
	
	public function setOfflineExportPagePresentationMode($offlineExportPagePresentationMode = 'presentation')
	{
		// TODO: Implement setOfflineExportPagePresentationMode() method.
	}
}
