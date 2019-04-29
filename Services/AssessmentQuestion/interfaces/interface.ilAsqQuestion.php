<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface ilAsqQuestion
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package    Services/AssessmentQuestion
 */
interface ilAsqQuestion
{
	/**
	 * @param $parentId
	 */
	public function setParentId(int $parentId);
	
	/**
	 * @return int
	 */
	public function getParentId() : int;
	
	/**
	 * @param int $questionId
	 */
	public function setId(int $questionId);
	
	/**
	 * @return int
	 */
	public function getId() : int;
	
	/**
	 * @param ilAsqQuestionType
	 */
	public function setQuestionType(ilAsqQuestionType $questionType);
	
	/**
	 * @return ilAsqQuestionType
	 */
	public function getQuestionType() : ilAsqQuestionType;
	
	/**
	 * @param string $title
	 */
	public function setTitle(string $title);
	
	/**
	 * @return string
	 */
	public function getTitle() : string;
	
	/**
	 * @param string $comment
	 */
	public function setComment(string $comment);
	
	/**
	 * @return string
	 */
	public function getComment() : string;
	
	/**
	 * @param int $owner
	 */
	public function setOwner(int $owner);
	
	/**
	 * @return int
	 */
	public function getOwner() : int;
	
	/**
	 * @param string $questionText
	 */
	public function setQuestionText(string $questionText);
	
	/**
	 * @return string
	 */
	public function getQuestionText() : string;
	
	/**
	 * @return float
	 */
	public function getPoints() : float;
	
	/**
	 * @return string
	 */
	public function getEstimatedWorkingTime() : string;
	
	/**
	 * Loads question data
	 */
	public function load();
	
	/**
	 * Save question data
	 */
	public function save();
	
	/**
	 * Delete question
	 */
	public function delete();
	
	/**
	 * @param ilQTIItem $qtiItem
	 */
	public function fromQtiItem(ilQTIItem $qtiItem);
	
	/**
	 * @return string
	 */
	public function toQtiXML() : string;
	
	/**
	 * @return bool
	 */
	public function isComplete() : bool;
	
	/**
	 * @return ilAsqQuestionSolution
	 */
	public function getBestSolution() : ilAsqQuestionSolution;
	
	/**
	 * @return \ILIAS\UI\Component\Component
	 */
	public function getSuggestedSolutionOutput() : \ILIAS\UI\Component\Component;
	
	/**
	 * @return string
	 */
	public function toJSON() : string;
	
	/**
	 * @param string $offlineExportImagePath
	 */
	public function setOfflineExportImagePath($offlineExportImagePath = null);
	
	/**
	 * @param string $offlineExportPagePresentationMode
	 */
	public function setOfflineExportPagePresentationMode($offlineExportPagePresentationMode = 'presentation');
}