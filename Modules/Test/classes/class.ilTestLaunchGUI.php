<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilTestLaunchGUI
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Modules/Test
 */
class ilTestLaunchGUI
{
    const CMD_SHOW_LAUNCH_SCREEN = 'showLaunchScreen';

    /**
     * @var ilObjTest
     */
    protected $testOBJ = null;


    /**
     * ilTestLaunchGUI constructor.
     *
     * @param ilObjTest $testOBJ
     */
    public function __construct(ilObjTest $testOBJ)
    {
        $this->testOBJ = $testOBJ;
    }


    /**
     * Execute Command
     */
    public function executeCommand()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        switch( $DIC->ctrl()->getNextClass() )
        {
            default:

                $command = $DIC->ctrl()->getCmd(self::CMD_SHOW_LAUNCH_SCREEN);
                $command .= 'Cmd';

                $this->{$command}();
        }
    }

    protected function showLaunchScreenCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $html = '';

        if( $this->testOBJ->isIntroductionEnabled() )
        {
            $panel = $this->getIntroductionPanel();
            $html .= $DIC->ui()->renderer()->render($panel);
        }

        $DIC->ui()->mainTemplate()->setContent($html);
    }

    protected function getIntroductionPanel()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $tpl = new ilTemplate('tpl.intro_panel.html', true, true, 'Modules/Test');

        $tpl->setVariable('INTRODUCTION', $this->testOBJ->getIntroduction());

        $panel = $DIC->ui()->factory()->panel()->standard(
            $DIC->language()->txt('tst_introduction'),
            $DIC->ui()->factory()->legacy($tpl->get())
        );

        return $panel;
    }
}
