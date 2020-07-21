<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\Frameset;

interface Factory
{
    /**
     * @param string $id
     * @param Frame $frame
     * @return Set
     */
    public function set($id, Frame $frame);

    /**
     * @param string $content
     * @return Frame
     */
    public function frame($content);
}