<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * ilTestScoringByQuestionsGUI
 * @author     Björn Heyser <info@bjoernheyser.de>
 * @ilCtrl_Calls ilTestScoringPilotGUI: ilTestScoringEssayGUI
 */
class ilTestScoringPilotGUI extends ilTestScoringGUI
{
    /**
     * @param ilObjTest $a_object
     */
    public function __construct(ilObjTest $a_object)
    {
        parent::__construct($a_object);
    }

    /**
     * @return string
     */
    protected function getDefaultCommand()
    {
        return 'showParticipants';
    }

    /**
     * @return string
     */
    protected function getActiveSubTabId()
    {
        return 'man_scoring_pilot';
    }

    public function executeCommand()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        if (!$this->getTestAccess()->checkScoreParticipantsAccess()) {
            ilObjTestGUI::accessViolationRedirect();
        }

        require_once 'Modules/Test/classes/class.ilObjAssessmentFolder.php';
        if (!ilObjAssessmentFolder::_mananuallyScoreableQuestionTypesExists()) {
            // allow only if at least one question type is marked for manual scoring
            ilUtil::sendFailure($this->lng->txt("manscoring_not_allowed"), true);
            $this->ctrl->redirectByClass("ilobjtestgui", "infoScreen");
        }

        $DIC->tabs()->activateTab(ilTestTabsManager::TAB_ID_MANUAL_SCORING);
        $this->buildSubTabs($this->getActiveSubTabId());

        switch( $DIC->ctrl()->getNextClass($this) )
        {
            case strtolower(ilTestScoringEssayGUI::class):
                $gui = new ilTestScoringEssayGUI($this->object);
                $DIC->ctrl()->forwardCommand($gui);
                break;

            default:
                $command = $DIC->ctrl()->getCmd($this->getDefaultCommand()).'Cmd';
                $this->{$command}();
        }
    }

    protected function showParticipantsCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $table = $this->buildManScoringParticipantsTable(true);
        $table->setEditScoringPilot(true);

        $DIC->ui()->mainTemplate()->setContent($table->getHTML());
    }
}
