<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\Frameset;

interface Frame
{
    /**
     * @param integer $minimalWidth
     * @return Frame
     */
    public function withMinimalWidth($minimalWidth);

    /**
     * @param integer $initialWidth
     * @return Frame
     */
    public function withInitialWidth($initialWidth);
}