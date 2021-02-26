<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilTestScoringEssayGUI
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @package     Services/AssessmentQuestion
 */
class ilTestScoringEssayGUI extends ilTestScoringGUI
{
    const INIT_SIDE_FRAME_WIDTH = '33%';

    /**
     * @var int
     */
    protected $curActiveId = null;

    /**
     * @var int
     */
    protected $curPassIndex = null;

    /**
     * @var int
     */
    protected $curQuestionId = null;

    /**
     * @var array
     */
    protected $questionGuiList = array();

    /**
     * @param ilObjTest $a_object
     */
    public function __construct(ilObjTest $a_object)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        parent::__construct($a_object);

        if( isset($_POST['pass']) )
        {
            $_GET['pass'] = $_POST['pass'];
        }

        $DIC->ctrl()->saveParameter($this, 'active_id');
        $DIC->ctrl()->saveParameter($this, 'pass');
        $DIC->ctrl()->saveParameter($this, 'question_id');
    }

    /**
     * @return string
     */
    protected function getDefaultCommand()
    {
        return 'showManualScoring';
    }

    protected function initialise()
    {
        $this->curActiveId = $this->fetchActiveIdParameter();
        $this->curPassIndex = $this->fetchPassParameter($this->curActiveId);

        $this->questionGuiList = $this->service->getManScoringQuestionGuiList($this->curActiveId, $this->curPassIndex);
        $this->curQuestionId = $this->fetchQuestionIdParameter($this->curActiveId, $this->curPassIndex);
    }

    protected function fetchQuestionIdParameter($activeId, $passIndex)
    {
        if( isset($_POST['question_id']) && 0 < (int)$_POST['question_id'] ) {
            $questionId = (int)$_POST['question_id'];
        } elseif( isset($_GET['question_id']) && 0 < (int)$_GET['question_id'] ) {
            $questionId = (int)$_GET['question_id'];
        } else {
            $questionGui = current($this->questionGuiList);
            $questionId = $questionGui->object->getId();
        }

        return $questionId;
    }

    protected function checkAccess(ilTestParticipantList $participantList, ilTestPassesSelector $passSelector)
    {
        if( !$participantList->isActiveIdInList($this->curActiveId) )
        {
            ilObjTestGUI::accessViolationRedirect();
        }

        if( !in_array($this->curPassIndex, $passSelector->getClosedPasses()) )
        {
            ilObjTestGUI::accessViolationRedirect();
        }

        foreach($this->questionGuiList as $questionGui)
        {
            if( $questionGui->object->getId() == $this->curQuestionId )
            {
                return;
            }
        }

        ilObjTestGUI::accessViolationRedirect();
    }

    public function executeCommand()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $DIC->tabs()->clearTargets();
        $DIC->tabs()->clearSubTabs();

        $DIC->tabs()->setBackTarget(
            $DIC->language()->txt('back'),
            $DIC->ctrl()->getLinkTargetByClass('ilTestScoringPilotGUI')
        );

        $this->initialise();

        switch( $DIC->ctrl()->getNextClass($this) )
        {
            default:
                $command = $DIC->ctrl()->getCmd($this->getDefaultCommand()).'Cmd';
                $this->{$command}();
        }
    }

    /**
     * @return ilTestParticipantList
     */
    protected function buildParticipantList()
    {
        $participantList = new ilTestParticipantList($this->object);

        $participantList->initializeFromDbRows(
            $this->object->getTestParticipantsForManualScoring()
        );

        $participantList = $participantList->getAccessFilteredList(
            ilTestParticipantAccessFilter::getScoreParticipantsUserFilter($this->ref_id)
        );

        return $participantList;
    }

    /**
     * @return ilTestPassesSelector
     */
    protected function buildPassSelector()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $passSelector = new ilTestPassesSelector($DIC->database(), $this->object);
        $passSelector->setActiveId($this->curActiveId);
        $passSelector->loadLastFinishedPass();
        return $passSelector;
    }

    /**
     * @return array
     */
    protected function buildPassDropdownOptions(ilTestPassesSelector $passSelector)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $passOptions = array();

        foreach ($passSelector->getClosedPasses() as $passIndex) {
            $passOptions[$passIndex] = $DIC->language()->txt('pass') . ' ' . ($passIndex + 1);
        }

        return $passOptions;
    }

    /**
     * @param array $questionGuiList
     * @return array
     */
    protected function buildQuestionsDropdownOptions($questionGuiList)
    {
        $qstOptions = array();

        foreach($this->questionGuiList as $questionGUI)
        {
            /* @var assQuestionGUI $questionGUI */
            $qstOptions[$questionGUI->object->getId()] = $questionGUI->object->getTitle();
        }

        return $qstOptions;
    }

    /**
     * @param ilTestPassesSelector $passSelector
     * @param array $questionGuiList
     */
    protected function buildToolbar(ilTestPassesSelector $passSelector, $questionGuiList)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $passOptions = $this->buildPassDropdownOptions($passSelector);
        $qstOptions = $this->buildQuestionsDropdownOptions($questionGuiList);

        $passSelect = new ilSelectInputGUI('', 'pass');
        $passSelect->setOptions($passOptions);
        $passSelect->setValue($this->curPassIndex);
        $passSelect->setRequired(true);
        $DIC->toolbar()->addInputItem($passSelect);

        $qstSelect = new ilSelectInputGUI('', 'question_id');
        $qstSelect->setOptions($qstOptions);
        $qstSelect->setValue($this->curQuestionId);
        $qstSelect->setRequired(true);
        $DIC->toolbar()->addInputItem($qstSelect);

        $submitBtn = ilSubmitButton::getInstance();
        $submitBtn->setCaption('open');
        $submitBtn->setCommand('changeQuestion');
        $DIC->toolbar()->addButtonInstance($submitBtn);

        $DIC->toolbar()->addSeparator();

        $scoringMarkingBtn = $this->buildParticipantScoringMarkButton();
        $DIC->toolbar()->addButtonInstance($scoringMarkingBtn);

        $DIC->toolbar()->addSeparator();

        $sendNotificationBtn = $this->buildSendNotificationButton();
        $DIC->toolbar()->addButtonInstance($sendNotificationBtn);

        $DIC->toolbar()->setFormAction($DIC->ctrl()->getFormAction($this));
    }

    /**
     * @return ilLinkButton
     */
    protected function buildSendNotificationButton()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $sendNotificationBtn = ilLinkButton::getInstance();
        $sendNotificationBtn->setCaption('tst_manscoring_user_notification');
        $sendNotificationBtn->setUrl($DIC->ctrl()->getLinkTarget($this, 'sendNotification'));

        return $sendNotificationBtn;
    }

    /**
     * @return ilLinkButton
     */
    protected function buildParticipantScoringMarkButton()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $scoringMarkBtn = ilLinkButton::getInstance();

        $DIC->ctrl()->setParameterByClass(ilTestScoringPilotGUI::class,
            'active_id', $this->curActiveId
        );

        if( ilTestService::isManScoringDone($this->curActiveId) )
        {
            $scoringMarkBtn->setCaption('tst_mark_unscored');
            $scoringMarkBtn->setUrl($DIC->ctrl()->getLinkTargetByClass(
                ilTestScoringPilotGUI::class, 'markParticipantUnscored'
            ));
        }
        else
        {
            $scoringMarkBtn->setCaption('tst_mark_scored');
            $scoringMarkBtn->setUrl($DIC->ctrl()->getLinkTargetByClass(
                ilTestScoringPilotGUI::class, 'markParticipantScored'
            ));
        }

        return $scoringMarkBtn;
    }

    protected function saveParameters()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $DIC->ctrl()->setParameter($this, 'active_id', $this->curActiveId);
        $DIC->ctrl()->setParameter($this, 'pass', $this->curPassIndex);
        $DIC->ctrl()->setParameter($this, 'question_id', $this->curQuestionId);
    }

    protected function changeQuestionCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $this->saveParameters();
        $DIC->ctrl()->redirect($this);
    }

    protected function showManualScoringCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $participantList = $this->buildParticipantList();
        $passSelector = $this->buildPassSelector();

        $this->checkAccess($participantList, $passSelector);
        $this->saveParameters();

        $questionGuiList = $this->service->getManScoringQuestionGuiList(
            $this->curActiveId, $this->curPassIndex
        );

        $this->buildToolbar($passSelector, $questionGuiList);

        $questionGui = $this->getCurrentQuestionGUI();

        $mainContent = $this->getMainFrameContent($questionGui);
        $leftContent = $this->getLeftFrameContent($questionGui);
        $rightContent = $this->getRightFrameContent($questionGui);

        $frameSet = $this->buildFrameset('msp'.$this->curQuestionId,
            $mainContent, $leftContent, $rightContent
        );

        $panel = $DIC->ui()->factory()->panel()->standard($this->buildPanelTitle(), $frameSet);

        $DIC->ui()->mainTemplate()->setContent(
            $DIC->ui()->renderer()->render($panel) . $this->getJavacript()
        );
    }

    /**
     * @return string
     */
    protected function getJavacript()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $tpl = new ilTemplate('tpl.manual_scoring_essay.html', true, true, 'Modules/Test');

        $tpl->setVariable('FEEDBACK_SAVE_URL', $DIC->ctrl()->getLinkTarget(
            $this, 'saveManualFeedbackAsync', '', true
        ));

        $tpl->setVariable('ID', $this->curQuestionId);

        return $tpl->get();
    }

    protected function saveManualFeedbackAsyncCmd()
    {
        /* assTextQuestionGUI $questionGui */
        $questionGui = $this->getCurrentQuestionGUI();

        $this->object->saveManualFeedback(
            $this->curActiveId, $this->curQuestionId, $this->curPassIndex,
            $questionGui->object->getHtmlQuestionContentPurifier()->purify($_POST['manual_feedback'])
        );

        exit;
    }

    protected function buildPanelTitle()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $userId = $this->object->_getUserIdFromActiveId($this->curActiveId);
        $userFullname = $this->object->userLookupFullName($userId, false, true);

        $title = $DIC->language()->txt('tst_participant').': '.$userFullname.'<br />';
        $title .= $DIC->language()->txt('pass') . ' ' . ($this->curPassIndex + 1);
        $title .= ': ' . $this->getCurrentQuestionGUI()->object->getTitle();

        return $title;
    }

    /**
     * @param string $identifier
     * @param string $mainContent
     * @param string $leftContent
     * @param string $rightContent
     * @return \ILIAS\UI\Component\Frameset\Set
     */
    protected function buildFrameset($identifier, $mainContent, $leftContent, $rightContent)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $f = $DIC->ui()->factory();

        $mainFrame = $f->frameset()->frame($DIC->ui()->factory()->legacy(
            $mainContent
        ))->withMinimalWidth('200px');

        $leftFrame = $f->frameset()->frame($DIC->ui()->factory()->legacy(
            $leftContent
        ))->withMinimalWidth('100px')->withInitialWidth(
            $this->getInitialLeftFrameWidth($this->curQuestionId)
        );

        if( $this->isLeftFrameInitiallyHidden($this->curQuestionId) )
        {
            $leftFrame = $leftFrame->withInitiallyHidden(true);
        }

        $rightFrame = $f->frameset()->frame($DIC->ui()->factory()->legacy(
            $rightContent
        ))->withMinimalWidth('100px')->withInitialWidth(
            $this->getInitialRightFrameWidth($this->curQuestionId)
        );

        if( $this->isRightFrameInitiallyHidden($this->curQuestionId) )
        {
            $rightFrame = $rightFrame->withInitiallyHidden(true);
        }

        $frameSet = $f->frameset()->set($identifier, $mainFrame);
        $frameSet = $frameSet->withLeftFrame($leftFrame);
        $frameSet = $frameSet->withRightFrame($rightFrame);

        $frameSet = $frameSet->withJavascriptAfterResizeCallback('resizeTinyMce');

        return $frameSet;
    }

    /**
     * @return assQuestionGUI
     */
    protected function getCurrentQuestionGUI()
    {
        foreach($this->questionGuiList as $questionGUI)
        {
            if( $questionGUI->object->getId() != $this->curQuestionId )
            {
                continue;
            }

            return $questionGUI;
        }
    }

    protected function getMainFrameContent(assTextQuestionGUI $questionGui)
    {
        return $questionGui->getUserSolutionSnippet($this->curActiveId, $this->curPassIndex);
    }

    protected function getLeftFrameContent(assTextQuestionGUI $questionGui)
    {
        return $questionGui->getQuestionTextSnippet();
    }

    protected function getRightFrameContent(assTextQuestionGUI $questionGui)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $editorId = "tinymce_manscoring_{$questionGui->object->getId()}";

        $DIC->ctrl()->setParameter($this, 'cmd', 'post');
        $formaction = $DIC->ctrl()->getFormAction($this);
        $DIC->ctrl()->setParameter($this, 'cmd', '');

        $rtestring = ilRTE::_getRTEClassname();
        $rte = new $rtestring(); /* @var ilTinyMCE $rte */
        $rte->addRteSupport($this->object->getId(), $this->object->getType(), $editorId);

        $manualFeedback = $this->object->getManualFeedback(
            $this->curActiveId, $this->curQuestionId, $this->curPassIndex
        );

        $manualPoints = assQuestion::_getReachedPoints($this->curActiveId, $this->curQuestionId, $this->curPassIndex);
        $manualPoints = $manualPoints ? $manualPoints : '';

        $maxPoints = assQuestion::_getMaximumPoints($this->curQuestionId);

        $pointsInput = new ilTextInputGUI('', 'manual_points');
        $pointsInput->setValue($manualPoints);

        $tpl = new ilTemplate('tpl.manual_scoring_rawform.html', true, true, 'Modules/Test');

        $tpl->setCurrentBlock('rawform');
        $tpl->setVariable('EDITOR_SELECTOR', $editorId);
        $tpl->setVariable('FORMACTION', $formaction);
        $tpl->setVariable('MANUAL_FEEDBACK', $manualFeedback);
        $tpl->setVariable('POINTS_LABEL', sprintf($DIC->language()->txt('granted_points'), $maxPoints));
        $tpl->setVariable('POINTS_INPUT', $pointsInput->render());
        $tpl->setVariable('SUBMIT_LABEL', $DIC->language()->txt('save'));
        $tpl->setVariable('SUBMIT_CMD', 'saveManualPoints');
        $tpl->parseCurrentBlock();

        return $tpl->get();
    }

    protected function saveManualPointsCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $this->saveParameters();

        $this->object->saveManualFeedback(
            $this->curActiveId, $this->curQuestionId, $this->curPassIndex,
            $this->getCurrentQuestionGUI()->object->getHtmlQuestionContentPurifier()->purify($_POST['manual_feedback'])
        );

        $manPoints = $_POST['manual_points'];
        $maxPoints = assQuestion::_getMaximumPoints($this->curQuestionId);

        if( is_numeric($manPoints) && $manPoints >= 0 && $manPoints <= $maxPoints )
        {
            assQuestion::_setReachedPoints(
                $this->curActiveId,
                $this->curQuestionId,
                $manPoints,
                $maxPoints,
                $this->curPassIndex,
                1,
                $this->object->areObligationsEnabled()
            );
        }
        else
        {
            $failureMessage = sprintf($DIC->language()->txt('invalid_man_scoring_points'), $maxPoints, $manPoints);
            ilUtil::sendFailure($failureMessage, true);

            assQuestion::_setReachedPoints(
                $this->curActiveId,
                $this->curQuestionId,
                0,
                $maxPoints,
                $this->curPassIndex,
                1,
                $this->object->areObligationsEnabled()
            );
        }

        $DIC->ctrl()->redirect($this, 'showManualScoring');
    }

    protected function getInitialLeftFrameWidth($id)
    {
        $name = $this->getLeftFrameWidthCookieName($id);

        if( isset($_COOKIE[$name]) && $this->isValidFrameWidth($_COOKIE[$name]) )
        {
            return $_COOKIE[$name];
        }

        return self::INIT_SIDE_FRAME_WIDTH;
    }

    protected function getInitialRightFrameWidth($id)
    {
        $name = $this->getRightFrameWidthCookieName($id);

        if( isset($_COOKIE[$name]) && $this->isValidFrameWidth($_COOKIE[$name]) )
        {
            return $_COOKIE[$name];
        }

        return self::INIT_SIDE_FRAME_WIDTH;
    }

    protected function getLeftFrameWidthCookieName($id)
    {
        return 'frameset_' . $id . '_leftFrame_width';
    }

    protected function getRightFrameWidthCookieName($id)
    {
        return 'frameset_' . $id . '_rightFrame_width';
    }

    protected function isValidFrameWidth($frameWidth)
    {
        if( preg_match('/^\d+px$/', $frameWidth) )
        {
            return true;
        }

        return false;
    }

    protected function isLeftFrameInitiallyHidden($id)
    {
        $name = $this->getLeftFrameInitiallyHiddenCookieName($id);
        return $this->isFrameInitiallyHidden($name);
    }

    protected function isRightFrameInitiallyHidden($id)
    {
        $name = $this->getRightFrameInitiallyHiddenCookieName($id);
        return $this->isFrameInitiallyHidden($name);
    }

    protected function isFrameInitiallyHidden($name)
    {
        if (isset($_COOKIE[$name]) && (bool)$_COOKIE[$name])
        {
            return true;
        }

        return false;
    }

    protected function getLeftFrameInitiallyHiddenCookieName($id)
    {
        return 'frameset_' . $id . '_leftFrame_hidden';
    }

    protected function getRightFrameInitiallyHiddenCookieName($id)
    {
        return 'frameset_' . $id . '_rightFrame_hidden';
    }

    protected function sendNotificationCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $notificationData[$this->curQuestionId] = array(
            'points' => assQuestion::_getReachedPoints($this->curActiveId, $this->curQuestionId),
            'feedback' => $this->object->getManualFeedback(
                $this->curActiveId, $this->curQuestionId, $this->curPassIndex
            )
        );

        $notification = new ilTestManScoringParticipantNotification(
            $this->object->_getUserIdFromActiveId($this->curActiveId),
            $this->object->getRefId()
        );

        $notification->setAdditionalInformation(array(
            'test_title' => $this->object->getTitle(),
            'test_pass' => $this->curPassIndex + 1,
            'questions_gui_list' => $this->questionGuiList,
            'questions_scoring_data' => $notificationData
        ));

        $notification->send();

        $DIC->ctrl()->redirect($this);
    }
}
