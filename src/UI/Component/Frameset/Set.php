<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\Frameset;

use ILIAS\UI\Component\Component as Component;

interface Set extends Component
{
    /**
     * @param Frame $frame
     * @return Set
     */
    public function withLeftFrame(Frame $frame);

    /**
     * @param Frame $frame
     * @return Set
     */
    public function withRightFrame(Frame $frame);

    /**
     * @param bool $respectCookies
     * @return Set
     */
    public function withRespectCookies($respectCookies);

    /**
     * @param string $jsAfterResizeCallback
     * @return Set
     */
    public function withJavascriptAfterResizeCallback($jsAfterResizeCallback);
}
