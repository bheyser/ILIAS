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

    protected function showManScoringParticipantScreenCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        

        $DIC->ui()->mainTemplate()->setContent('<pre>'.print_r($_GET, 1).'</pre>');
    }
}
