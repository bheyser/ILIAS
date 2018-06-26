<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/Test/classes/class.ilTestServiceGUI.php';

/**
 * Class ilTestSubmissionReviewGUI
 *
 * @author Maximilian Becker <mbecker@databay.de>
 * @version $Id$
 *
 * @ctrl_calls 	  ilTestSubmissionReviewGUI: ilAssQuestionPageGUI
 */
class ilTestSubmissionReviewGUI extends ilTestServiceGUI
{
    /** @var ilTestOutputGUI */
    protected $testOutputGUI = null;

    /** @var \ilTestSession */
    protected $testSession;

    public function __construct(ilTestOutputGUI $testOutputGUI, ilObjTest $testOBJ, ilTestSession $testSession)
    {
        $this->testOutputGUI = $testOutputGUI;
        $this->testSession = $testSession;

        parent::__construct($testOBJ);
    }

    public function executeCommand()
    {
        if (!$this->object->getEnableExamview()) {
            return '';
        }

        switch ($this->ctrl->getNextClass($this)) {
            default:
                $this->dispatchCommand();
                break;
        }

        return '';
    }

    protected function dispatchCommand()
    {
        switch ($this->ctrl->getCmd()) {
            case 'pdfDownload':

                if ($this->object->getShowExamviewPdf()) {
                    $this->pdfDownload();
                }

                break;

            case 'show':
            default:

                $this->show();
        }
    }

    /**
     * Returns the name of the current content block (depends on the kiosk mode setting)
     *
     * @return string The name of the content block
     * @access public
     */
    private function getContentBlockName()
    {
        if ($this->object->getKioskMode()) {
            $this->tpl->setBodyClass("kiosk");
            $this->tpl->setAddFooter(false);
            return "CONTENT";
        } else {
            return "ADM_CONTENT";
        }
    }

    // uni-goettingen-patch: begin
    protected function isBackToTestPassButtonRequired()
    {
        // im test und in den prozessen danach ist dieses objekt eigentlich immer verfügbar
        $active_id = $this->testSession->getActiveId();
        // die active id selbst ist auch da, sonst wären wir vorher gar nicht im Test klar gekommen

        // hier kann nur dann false zurück kommen, wenn ich keine active Id übergebe
        $starting_time = $this->object->getStartingTimeOfUser($active_id);
        // was ja perse im test und in den finish prozessen nicht sein kann

        // das kann man sich also sparen, jeder Testbenutzer hatte einen Start Moment ;-)
        //if ($starting_time === FALSE)
        //{
        //		return true;
        //}
        // es hätte auch eigentlich sonst zum Gegenteil führen müssen, wenn ich keine Active ID habe
        // der button schafft es dann ja nicht mich zurück zu leiten ;-)

        if (!$this->object->isMaxProcessingTimeReached($starting_time, $active_id)) {
            return true;
        }

        return false;

        // nicht den mut verlieren, ilias ist nicht einfach :-)
    }
    // uni-goettingen-patch: end

    /**
     * @return ilToolbarGUI
     */
    protected function buildToolbar($toolbarId)
    {
      require_once 'Modules/Test/classes/class.ilTestPlayerCommands.php';
      require_once 'Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php';
      require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
      require_once 'Services/UIComponent/Button/classes/class.ilButton.php';

      $toolbar = new ilToolbarGUI();
      $toolbar->setId($toolbarId);

      $backUrl = $this->ctrl->getLinkTarget($this->testOutputGUI, $this->object->getListOfQuestionsEnd() ?
        ilTestPlayerCommands::QUESTION_SUMMARY : ilTestPlayerCommands::BACK_FROM_FINISHING
      );

      $button = ilLinkButton::getInstance();
      $button->setCaption('btn_previous');
      $button->setUrl($backUrl);
      $toolbar->addButtonInstance($button);

      if( $this->object->getShowExamviewPdf() )
      {
        $pdfUrl = $this->ctrl->getLinkTarget($this, 'pdfDownload');

        $button = ilLinkButton::getInstance();
        $button->setCaption('pdf_export');
        $button->setUrl($pdfUrl);
        $button->setTarget(ilButton::FORM_TARGET_BLANK);
        $toolbar->addButtonInstance($button);
      }

      $this->ctrl->setParameter($this->testOutputGUI, 'reviewed', 1);
      $nextUrl = $this->ctrl->getLinkTarget($this->testOutputGUI, ilTestPlayerCommands::FINISH_TEST);
      $this->ctrl->setParameter($this->testOutputGUI, 'reviewed', 0);

      $button = ilLinkButton::getInstance();
      $button->setPrimary(true);
      $button->setCaption('btn_next');
      $button->setUrl($nextUrl);
      $toolbar->addButtonInstance($button);

      return $toolbar;
    }

    protected function buildUserReviewOutput()
    {
      $ilObjDataCache = isset($GLOBALS['DIC']) ? $GLOBALS['DIC']['ilObjDataCache'] : $GLOBALS['ilObjDataCache'];

      require_once 'Modules/Test/classes/class.ilTestResultHeaderLabelBuilder.php';
      $testResultHeaderLabelBuilder = new ilTestResultHeaderLabelBuilder($this->lng, $ilObjDataCache);

      $objectivesList = null;

      if( $this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired() )
      {
        $testSequence = $this->testSequenceFactory->getSequenceByActiveIdAndPass($this->testSession->getActiveId(), $this->testSession->getPass());
        $testSequence->loadFromDb();
        $testSequence->loadQuestions();

        require_once 'Modules/Course/classes/Objectives/class.ilLOTestQuestionAdapter.php';
        $objectivesAdapter = ilLOTestQuestionAdapter::getInstance($this->testSession);

        $objectivesList = $this->buildQuestionRelatedObjectivesList($objectivesAdapter, $testSequence);
        $objectivesList->loadObjectivesTitles();

        $testResultHeaderLabelBuilder->setObjectiveOrientedContainerId($this->testSession->getObjectiveOrientedContainerId());
        $testResultHeaderLabelBuilder->setUserId($this->testSession->getUserId());
        $testResultHeaderLabelBuilder->setTestObjId($this->object->getId());
        $testResultHeaderLabelBuilder->setTestRefId($this->object->getRefId());
        $testResultHeaderLabelBuilder->initObjectiveOrientedMode();
      }

      $results = $this->object->getTestResult(
        $this->testSession->getActiveId(), $this->testSession->getPass(), false,
        !$this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired()
      );

      require_once 'class.ilTestEvaluationGUI.php';
      $testevaluationgui = new ilTestEvaluationGUI($this->object);
      $testevaluationgui->setContextResultPresentation(false);

      $results_output = $testevaluationgui->getPassListOfAnswers( $results,
        $this->testSession->getActiveId(), $this->testSession->getPass(),
        false, false, false, false,
        false, $objectivesList, $testResultHeaderLabelBuilder
      );

      return $results_output;
    }

    public function show()
    {
      $html = $this->buildToolbar('review_nav_top')->getHTML();
      $html .= $this->buildUserReviewOutput() . '<br />';
      $html .= $this->buildToolbar('review_nav_bottom')->getHTML();

      $this->tpl->setVariable($this->getContentBlockName(), $html);
    //     $pdfUrl = $this->ctrl->getLinkTarget($this, 'pdfDownload');
    //
    //     // uni-goettingen-patch: begin
    //     global $ilObjDataCache, $ilDB;
    //
    //     $show_back_button = $this->isBackToTestPassButtonRequired();
    //     // uni-goettingen-patch: end
    //
    //     $template = new ilTemplate("tpl.il_as_tst_submission_review.html", true, true, "Modules/Test");
    //
    //
    //     $this->ctrl->setParameter($this, "skipfinalstatement", 1);
    //     $template->setVariable("FORMACTION", $this->ctrl->getFormAction($this->testOutputGUI, 'redirectBack').'&reviewed=1');
    //
    //     $template->setVariable("BUTTON_CONTINUE", $this->lng->txt("btn_next"));
    //     $template->setVariable("BUTTON_BACK", $this->lng->txt("btn_previous"));
    //     // uni-goettingen-patch: begin
    //     if ($show_back_button) {
    //         $template->setVariable("BUTTON_BACK_STYLE", "");
    //     } else {
    //         $template->setVariable("BUTTON_BACK_STYLE", "style=\"display: none;\"");
    //     }
    //
    //     if ($this->object->getExamviewPrintview()) {
    //         $template->setVariable("BUTTON_FINISH_TEST", $this->lng->txt("finish_test_print"));
    //         $template->setVariable("PRINT_CMD", "window.print();");
    //     } else {
    //         $template->setVariable("BUTTON_FINISH_TEST", $this->lng->txt("finish_test3"));
    //         $template->setVariable("PRINT_CMD", "");
    //     }
    //     // uni-goettingen-patch: end
    //
    //     $this->ctrl->setParameter($this->testOutputGUI, 'reviewed', 1);
    //     $nextUrl = $this->ctrl->getLinkTarget($this->testOutputGUI, ilTestPlayerCommands::FINISH_TEST);
    //     $this->ctrl->setParameter($this->testOutputGUI, 'reviewed', 0);
    //
    //     $button = ilLinkButton::getInstance();
    //     $button->setPrimary(true);
    //     $button->setCaption('btn_next');
    //     $button->setUrl($nextUrl);
    //     $toolbar->addButtonInstance($button);
    //
    //     // uni-goettingen-patch: begin
    //     if ($this->participantData instanceof ilTestParticipantData) {
    //         $uname = $this->participantData->getConcatedFullnameByActiveId($this->testSession->getActiveId(), false);
    //         $matNo = $this->participantData->getMatriculationByActiveId($this->testSession->getActiveId());
    //     } else {
    //         $uname = $this->object->userLookupRealFullName($this->testSession->getUserId(), true);
    //         $matNo = $this->object->userLookupMatriculation($this->testSession->getUserId());
    //     }
    //
    //     $template->setVariable("USER_NAME", $uname);
    //     $template->setVariable("MAT_NR", $matNo);
    //     $template->setVariable("DATE", date("d.m.Y H:i"));
    //     $template->setVariable("DATE_SIG", date("d.m.Y H:i"));
    //     $template->setVariable("TITLE", $this->object->getTitle());
    //
    //     // uni-goettingen-patch: begin
    //     if ($this->object->getShowSolutionSignature()) {
    //         $template->setVariable("SIGNATURE", "
		// 	<p>
		// 		<b>Datum:</b> ".date("d.m.Y H:i")." &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		// 		<b>Unterschrift:</b> __________________________________________________
		// 	</p>
		// 	");
    //     }
    //     // uni-goettingen-patch: end
    //
    //     $ip_address = $_SERVER['REMOTE_ADDR'];
    //     $result = $ilDB->query("SELECT seatnr, sector FROM seat_number WHERE ip = ".$ilDB->quote($ip_address, "text"));
    //     $record = $ilDB->fetchAssoc($result);
    //
    //     $template->setVariable("SEAT_NR", $record["seatnr"]);
    //     // uni-goettingen-patch: end
    //
    //     require_once 'Modules/Test/classes/class.ilTestResultHeaderLabelBuilder.php';
    //     $testResultHeaderLabelBuilder = new ilTestResultHeaderLabelBuilder($this->lng, $ilObjDataCache);
    //
    //     $objectivesList = null;
    //
    //     if ($this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired()) {
    //         $testSequence = $this->testSequenceFactory->getSequenceByActiveIdAndPass($this->testSession->getActiveId(), $this->testSession->getPass());
    //         $testSequence->loadFromDb();
    //         $testSequence->loadQuestions();
    //
    //         require_once 'Modules/Course/classes/Objectives/class.ilLOTestQuestionAdapter.php';
    //         $objectivesAdapter = ilLOTestQuestionAdapter::getInstance($this->testSession);
    //
    //         $objectivesList = $this->buildQuestionRelatedObjectivesList($objectivesAdapter, $testSequence);
    //         $objectivesList->loadObjectivesTitles();
    //
    //         $testResultHeaderLabelBuilder->setObjectiveOrientedContainerId($this->testSession->getObjectiveOrientedContainerId());
    //         $testResultHeaderLabelBuilder->setUserId($this->testSession->getUserId());
    //         $testResultHeaderLabelBuilder->setTestObjId($this->object->getId());
    //         $testResultHeaderLabelBuilder->setTestRefId($this->object->getRefId());
    //         $testResultHeaderLabelBuilder->initObjectiveOrientedMode();
    //     }
    //
    //     $results = $this->object->getTestResult(
    //         $this->testSession->getActiveId(),
    //
    //         $this->testSession->getPass(),
    //
    //         false,
    //         !$this->getObjectiveOrientedContainer()->isObjectiveOrientedPresentationRequired()
    //     );
    //
    //     require_once 'class.ilTestEvaluationGUI.php';
    //     $testevaluationgui = new ilTestEvaluationGUI($this->object);
    //     $testevaluationgui->setContextResultPresentation(false);
    //
    //     $results_output = $testevaluationgui->getPassListOfAnswers(
    //         // uni-goettingen-patch: begin
    //         $results,
    //
    //         $this->testSession->getActiveId(),
    //
    //         $this->testSession->getPass(),
    //
    //         false,
    //
    //         false,
    //
    //         false,
    //
    //         false,
    //
    //         false,
    //         $objectivesList,
    //
    //         $testResultHeaderLabelBuilder,
    //
    //         true
    //         // uni-goettingen-patch: end
    //     );
    //
    //     // uni-goettingen-patch: begin
    //     $filename = "";
    //     // uni-goettingen-patch: end
    //     if ($this->object->getShowExamviewPdf()) {
    //         $template->setVariable("PDF_TEXT", $this->lng->txt("pdf_export"));
    //         global $ilSetting;
    //         $inst_id = $ilSetting->get('inst_id', null);
    //         $path =  ilUtil::getWebspaceDir() . '/assessment/'. $this->testOutputGUI->object->getId() . '/exam_pdf';
    //         if (!is_dir($path)) {
    //             ilUtil::makeDirParents($path);
    //         }
    //         $filename = ilUtil::removeTrailingPathSeparators(ILIAS_ABSOLUTE_PATH) . '/' . $path . '/exam_N' . $inst_id . '-' . $this->testOutputGUI->object->getId() . '-' . $this->testSession->getActiveId() . '-' . $this->testSession->getPass() . '.pdf';
    //
    //         require_once 'class.ilTestPDFGenerator.php';
    //         ilTestPDFGenerator::generatePDF($results_output, ilTestPDFGenerator::PDF_OUTPUT_FILE, $filename);
    //         require_once 'Services/WebAccessChecker/classes/class.ilWACSignedPath.php';
    //         $template->setVariable("PDF_FILE_LOCATION", ilWACSignedPath::signFile($filename));
    //     }
    //
    //     // uni-goettingen-patch: begin
    //     elseif ($this->object->getShowExamviewHtmlHash()) {
    //         global $ilSetting;
    //
    //         $inst_id = $ilSetting->get('inst_id', null);
    //         $path =  ilUtil::getWebspaceDir() . '/assessment/'. $this->testOutputGUI->object->getId() . '/exam_archive';
    //         if (!is_dir($path)) {
    //             ilUtil::makeDirParents($path);
    //         }
    //         $filename = $path . '/exam_N' . $inst_id . '-' .
    //             $this->testOutputGUI->object->getId() . '-' .
    //             $this->testSession->getActiveId() . '-' . $this->testSession->getPass();
    //         $test_participants = $this->getParticipantData();
    //         $student = $test_participants[$this->testSession->getActiveId()];
    //
    //         $filename .= ".html";
    //         $out_template = new ilTemplate("tpl.il_as_tst_finished_test_export.html", true, true, "Modules/Test");
    //         $out_template->setVariable("TITLE", $this->lng->txt("export_finished_html_title"));
    //         $out_template->setVariable("NAME_TXT", $this->lng->txt("name"));
    //         $out_template->setVariable("STUDENT_NAME", $uname);
    //         $out_template->setVariable("MAT_NUM_TXT", $this->lng->txt("matriculation"));
    //         $out_template->setVariable("STUDENT_MAT_NUM", $matNo);
    //         $out_template->setVariable("ILIAS_URL", ILIAS_HTTP_PATH);
    //         $modified_results = $this->processHtmlResultsForArchiving($results_output, $path);
    //         $out_template->setVariable("EXAM_DATA", $modified_results);
    //         $out_file  = fopen($filename, "w");
    //         fwrite($out_file, $out_template->get());
    //         fclose($out_file);
    //     }
    //     // uni-goettingen-patch: end
    //     else {
    //         $template->setCurrentBlock('prevent_double_form_subm');
    //         $template->touchBlock('prevent_double_form_subm');
    //         $template->parseCurrentBlock();
    //     }
    //
    //     if ($this->object->getShowExamviewHtml()) {
    //         if ($this->object->getListOfQuestionsEnd()) {
    //             $template->setVariable("CANCEL_CMD_BOTTOM", 'outQuestionSummary');
    //         } else {
    //             $template->setVariable("CANCEL_CMD_BOTTOM", ilTestPlayerCommands::BACK_FROM_FINISHING);
    //         }
    //         $template->setVariable("BUTTON_CONTINUE_BOTTOM", $this->lng->txt("btn_next"));
    //         $template->setVariable("BUTTON_BACK_BOTTOM", $this->lng->txt("btn_previous"));
    //         // uni-goettingen-patch: begin
    //         if ($show_back_button) {
    //             $template->setVariable("BUTTON_BACK_STYLE_BOTTOM", "");
    //         } else {
    //             $template->setVariable("BUTTON_BACK_STYLE_BOTTOM", "style=\"display: none;\"");
    //         }
    //         // uni-goettingen-patch: end
    //
    //         $template->setVariable('HTML_REVIEW', $results_output);
    //
    //         // uni-goettingen-patch: begin
    //         if ($this->object->getShowExamviewHtmlHash()) {
    //             require_once "./Services/Hashing/classes/class.Hashing.php";
    //             $hasher = new Hashing();
    //             $algo = "sha256";
    //             if (!$hasher->checkAlgorithm($algo)) {
    //                 $algo = "md5";
    //             }
    //             $hashes = $hasher->humanHashFile($filename, "sha256", 16);
    //             $testevaluationgui->setHashedOutputFile($active, $filename, $hashes[0]);
    //             $template->setCurrentBlock("hash_review");
    //             $template->setVariable("HASH_REVIEW", $hashes[0]);
    //             $template->setVariable("HUMANHASH_REVIEW", $hashes[1]);
    //             $this->ctrl->setParameterByClass("iltestoutputgui", "resultHash", $hashes[0]);
    //         }
    //         // uni-goettingen-patch: end
    //     }
    }
}
