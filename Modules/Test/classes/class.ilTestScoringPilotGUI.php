<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * ilTestScoringByQuestionsGUI
 * @author     Björn Heyser <info@bjoernheyser.de>
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

        $DIC->tabs()->activateTab(ilTestTabsManager::TAB_ID_MANUAL_SCORING);
        $this->buildSubTabs($this->getActiveSubTabId());

        switch( $DIC->ctrl()->getNextClass($this) )
        {
            default:
                $command = $DIC->ctrl()->getCmd($this->getDefaultCommand()).'Cmd';
                $this->{$command}();
        }
    }

    protected function showParticipantsCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $table = $this->buildManScoringParticipantsTable(true);

        $DIC->ui()->mainTemplate()->setContent($table->getHTML());
    }

    protected function getFirstQuestionId($activeId)
    {
        return 4711;
    }

    protected function showManScoringParticipantScreenCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $activeId = $this->fetchActiveIdParameter();
        $questionId = $this->getFirstQuestionId($activeId);

        $question = assQuestionGUI::_getQuestionGUI('', $questionId);

        $DIC->tabs()->clearTargets();
        $DIC->tabs()->clearSubTabs();

        $DIC->tabs()->setBackTarget(
            $DIC->language()->txt('back'),
            $DIC->ctrl()->getLinkTarget($this, 'showParticipants')
        );

        $r = $DIC->ui()->renderer();
        $f = $DIC->ui()->factory()->frameset();


        $mainFrame = $f->frame($DIC->ui()->factory()->legacy(
            "MAIN"
        ));

        $leftFrame = $f->frame($DIC->ui()->factory()->legacy(
            "LEFT"
        ));

        $rightFrame = $f->frame($DIC->ui()->factory()->legacy(
            "RIGHT"
        ));

        $frameSet = $f->set($questionId, $mainFrame);
        $frameSet = $frameSet->withLeftFrame($leftFrame);
        $frameSet = $frameSet->withRightFrame($rightFrame);

        $DIC->ui()->mainTemplate()->setContent(
            $r->render($frameSet)
        );
    }
}
