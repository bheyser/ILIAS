<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Frameset;

use ILIAS\UI\Component\Component;

class Frame implements \ILIAS\UI\Component\Frameset\Frame
{
    /**
     * @var Component
     */
    protected $content;

    /**
     * Frame constructor.
     * @param Component $content
     */
    public function __construct(Component $content)
    {
        $this->content = $content;
    }

    /**
     * @return Component
     */
    public function getContent()
    {
        return $this->content;
    }
}
