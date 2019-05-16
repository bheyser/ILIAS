<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilAsqQuestionPreviewToolbarGUI
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
class ilAsqQuestionPreviewToolbarGUI extends ilToolbarGUI
{
	/**
	 * @var ilLanguage
	 */
	public $lng = null;
	
	private $resetPreviewCmd;
	
	public function __construct(ilLanguage $lng)
	{
		$this->lng = $lng;
		
		parent::__construct();
	}
	
	public function build()
	{
		$this->addFormButton($this->lng->txt('qpl_reset_preview'), $this->getResetPreviewCmd(), '', true);
	}
	
	public function setResetPreviewCmd($resetPreviewCmd)
	{
		$this->resetPreviewCmd = $resetPreviewCmd;
	}
	
	public function getResetPreviewCmd()
	{
		return $this->resetPreviewCmd;
	}
}