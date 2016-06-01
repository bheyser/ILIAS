<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Modules/Test/classes/class.ilTestServiceGUI.php';
require_once './Modules/Test/classes/class.ilTestPDFGenerator.php';
require_once './Modules/Test/classes/class.ilTestArchiver.php';

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/Test
 */
class ilTestArchiveService
{
	/**
	 * @var ilObjTest
	 */
	protected $testOBJ;

	/**
	 * @var ilTestParticipantData
	 */
	protected $participantData;

	public function __construct(ilObjTest $testOBJ)
	{
		$this->testOBJ = $testOBJ;
		$this->participantData = null;
	}

	/**
	 * @return ilTestParticipantData
	 */
	public function getParticipantData()
	{
		return $this->participantData;
	}

	/**
	 * @param ilTestParticipantData $participantData
	 */
	public function setParticipantData(ilTestParticipantData $participantData)
	{
		$this->participantData = $participantData;
	}

	public function archivePassesByActives($passesByActives)
	{
		foreach($passesByActives as $activeId => $passes)
		{
			foreach($passes as $pass)
			{
				$this->archiveActivesPass($activeId, $pass);
			}
		}
	}

	public function archiveActivesPass($activeId, $pass)
	{
		$content = $this->renderOverviewContent($activeId, $pass);
		$filename = $this->buildOverviewFilename($activeId, $pass);

		ilTestPDFGenerator::generatePDF($content, ilTestPDFGenerator::PDF_OUTPUT_FILE, $filename);

		$archiver = new ilTestArchiver($this->testOBJ->getId());
		$archiver->setParticipantData($this->getParticipantData());
		$archiver->handInTestResult($activeId, $pass, $filename);

		unlink($filename);
	}

	/**
	 * @param $activeId
	 * @param $pass
	 * @return string
	 */
	private function renderOverviewContent($activeId, $pass)
	{
		$results = $this->testOBJ->getTestResult(
			$activeId, $pass, false
		);

		$gui = new ilTestServiceGUI($this->testOBJ);

		//uzk-patch: begin
		return $this->buildUZKHeader($gui, $activeId, $pass) . $gui->getPassListOfAnswers(
			$results, $activeId, $pass, true, false, false, true, false
		);
	}

	/**
	 * @param $gui ilTestServiceGUI
	 * @param $activeId
	 * @param $pass
	 * @return string
	 */
	private function buildUZKHeader($gui, $activeId, $pass)
	{
		return $this->testOBJ->lookupExamId($activeId, $pass) . $gui->getResultsHeadUserAndPass($activeId, $pass + 1);
	}
	//uzk-patch: end

	/**
	 * @param $activeId
	 * @param $pass
	 * @return string
	 */
	private function buildOverviewFilename($activeId, $pass)
	{
		$tmpFileName = ilUtil::ilTempnam();
		return dirname($tmpFileName).'/scores-'.$this->testOBJ->getId().'-'.$activeId.'-'.$pass.'.pdf';
	}
}