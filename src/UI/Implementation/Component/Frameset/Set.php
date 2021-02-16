<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Frameset;

use ILIAS\UI\Component\Frameset\Frame;
use ILIAS\UI\Implementation\Component;
use http\Exception\InvalidArgumentException;

class Set implements \ILIAS\UI\Component\Frameset\Set
{
    use Component\ComponentHelper;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var Component\Frameset\Frame
     */
    protected $mainFrame;

    /**
     * @var Component\Frameset\Frame
     */
    protected $leftFrame;

    /**
     * @var Component\Frameset\Frame
     */
    protected $rightFrame;

    /**
     * @var string
     */
    protected $jsAfterResizeCallback;

    /**
     * Set constructor.
     * @param Frame $frame
     */
    public function __construct($id, Frame $frame)
    {
        if( !strlen($id) )
        {
            throw new \ilException('missing id for ui-frameset');
        }

        $this->id = $id;
        $this->mainFrame = $frame;
        $this->leftFrame = null;
        $this->rightFrame = null;
        $this->jsAfterResizeCallback = null;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Component\Frameset\Frame
     */
    public function getMainFrame()
    {
        return $this->mainFrame;
    }

    /**
     * @inheritDoc
     */
    public function withLeftFrame(Frame $frame)
    {
        $that = clone $this;
        $that->leftFrame = $frame;
        return $that;
    }

    /**
     * @return Component\Frameset\Frame
     */
    public function getLeftFrame()
    {
        return $this->leftFrame;
    }

    /**
     * @return bool
     */
    public function hasLeftFrame()
    {
        return $this->leftFrame instanceof Frame;
    }

    /**
     * @inheritDoc
     */
    public function withRightFrame(Frame $frame)
    {
        $that = clone $this;
        $that->rightFrame = $frame;
        return $that;
    }

    /**
     * @return Component\Frameset\Frame
     */
    public function getRightFrame()
    {
        return $this->rightFrame;
    }

    /**
     * @return bool
     */
    public function hasRightFrame()
    {
        return $this->rightFrame instanceof Frame;
    }

    /**
     * @param string $jsAfterResizeCallback
     * @return Set
     */
    public function withJavascriptAfterResizeCallback($jsAfterResizeCallback)
    {
        $that = clone $this;
        $that->jsAfterResizeCallback = $jsAfterResizeCallback;
        return $that;
    }

    /**
     * @return string
     */
    public function getJsAfterResizeCallback()
    {
        return $this->jsAfterResizeCallback;
    }

    /**
     * @return bool
     */
    public function hasJsAfterResizeCallback()
    {
        return $this->jsAfterResizeCallback !== null;
    }
}
