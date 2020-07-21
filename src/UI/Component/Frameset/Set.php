<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\Frameset;

use ILIAS\UI\Component\Component as Component;

interface Set extends Component
{
    public function withLeftFrame(Frame $frame);

    public function withRightFrame(Frame $frame);
}
