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
	public function getPoints(): float
	{
		// TODO: Implement getPoints() method.
	}
	
	public function getEstimatedWorkingTime(): string
	{
		// TODO: Implement getEstimatedWorkingTime() method.
	}
	
	public function load()
	{
		// TODO: Implement load() method.
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
