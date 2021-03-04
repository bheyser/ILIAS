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
     * @var integer
     */
    protected $minimalWidth;

    /**
     * @var integer
     */
    protected $initialWidth;

    /**
     * @var bool
     */
    protected $initiallyHidden;

    /**
     * Frame constructor.
     * @param Component $content
     */
    public function __construct(Component $content)
    {
        $this->content = $content;
        $this->minimalWidth = null;
        $this->initialWidth = null;
        $this->initiallyHidden = false;
    }

    /**
     * @param $minimalWidth
     * @return Frame
     */
    public function withMinimalWidth($minimalWidth)
    {
        $that = clone $this;
        $that->minimalWidth = $minimalWidth;
        return $that;
    }

     /**
     * @param $initialWidth
     * @return Frame
     */
    public function withInitialWidth($initialWidth)
    {
        $that = clone $this;
        $that->initialWidth = $initialWidth;
        return $that;
    }

    /**
     * @param $initiallyHidden
     * @return Frame
     */
    public function withInitiallyHidden($initiallyHidden)
    {
        $that = clone $this;
        $that->initiallyHidden = $initiallyHidden;
        return $that;
    }

    /**
     * @return Component
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return bool
     */
    public function hasMinimalWidth()
    {
        return $this->minimalWidth !== null;
    }

    /**
     * @return int
     */
    public function getMinimalWidth()
    {
        return $this->minimalWidth;
    }

    /**
     * @return bool
     */
    public function hasInitialWidth()
    {
        return $this->initialWidth !== null;
    }

    /**
     * @return int
     */
    public function getInitialWidth()
    {
        return $this->initialWidth;
    }

     /**
     * @return bool
     */
    public function isInitiallyHidden()
    {
        return $this->initiallyHidden;
    }

     /**
     * @return bool
     */
    public function hasRespectCookies()
    {
        return $this->respectCookies;
    }
}
