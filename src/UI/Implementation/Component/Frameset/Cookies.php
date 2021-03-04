<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Frameset;

class Cookies
{
    const FORMAT = '%s_%s_%s_%s';

    const BASE_NAME = 'frameset';
    const NAME_HIDDEN = 'hidden';
    const NAME_WIDTH = 'width';

    protected $setId;

    protected $frameName;

    /**
     * @param string $setId
     * @param string $frameName
     */
    public function __construct($setId, $frameName)
    {
        $this->setId = $setId;
        $this->frameName = $frameName;
    }

    /**
     * @return string
     */
    protected function buildHiddenCookieName()
    {
        return sprintf(self::FORMAT, self::BASE_NAME, $this->setId, $this->frameName, self::NAME_HIDDEN);
    }

    /**
     * @return string
     */
    protected function buildWidthCookieName()
    {
        return sprintf(self::FORMAT, self::BASE_NAME, $this->setId, $this->frameName, self::NAME_WIDTH);
    }

    /**
     * @return bool
     */
    public function hasHiddenCookie()
    {
        return isset($_COOKIE[$this->buildHiddenCookieName()]) && (bool)$_COOKIE[$this->buildHiddenCookieName()];
    }

    /**
     * @param $width
     * @return bool
     */
    protected function isValidFrameWidth($width)
    {
        return preg_match('/^\d+px$/', $width);
    }

    /**
     * @return bool
     */
    public function hasWidthCookie()
    {
        if( !isset($_COOKIE[$this->buildWidthCookieName()]) )
        {
            return false;
        }

        return $this->isValidFrameWidth($_COOKIE[$this->buildWidthCookieName()]);
    }

    /**
     * @return string
     */
    public function getWidthCookie()
    {
        return $_COOKIE[$this->buildWidthCookieName()];
    }
}
