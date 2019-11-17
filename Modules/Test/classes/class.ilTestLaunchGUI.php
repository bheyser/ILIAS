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

    }
}
