<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Frameset;

use ILIAS\UI\Component\Frameset\Frame as FrameInterface;

class Factory implements \ILIAS\UI\Component\Frameset\Factory
{
    /**
     * @inheritDoc
     */
    public function set($id, FrameInterface $frame)
    {
        return new Set($id, $frame);
    }

    /**
     * @inheritDoc
     */
    public function frame($content)
    {
        return new Frame($content);
    }
}
