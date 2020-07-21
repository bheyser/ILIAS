<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Frameset;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;
use ILIAS\UI\Implementation\Render\Template;

class Renderer extends AbstractComponentRenderer
{
    protected function getComponentInterfaceName()
    {
        return array(
            Component\Frameset\Set::class,
            Component\Frameset\Frame::class
        );
    }

    public function render(Component\Component $component, RendererInterface $renderer)
    {
        /* @var \ILIAS\UI\Implementation\Component\Frameset\Set $component */

        $tpl = $this->getTemplate('tpl.frameset.html', true, true);

        if( $component->hasLeftFrame() || $component->hasRightFrame() )
        {
            $this->renderJavascript($renderer, $tpl, $component);
        }

        if( $component->hasLeftFrame() )
        {
            $this->renderLeftFrame($renderer, $tpl, $component->getLeftFrame());
        }

        if( $component->hasRightFrame() )
        {
            $this->renderRightFrame($renderer, $tpl, $component->getRightFrame());
        }

        $this->renderMainFrame($renderer, $tpl, $component->getMainFrame());

        $this->renderFrameset($renderer, $tpl, $component);

        return $tpl->get();
    }

    protected function renderLeftFrame(RendererInterface $renderer, Template $tpl, Frame $frame)
    {
        $tpl->setCurrentBlock('left_frame');
        $tpl->setVariable('LEFT_FRAME_CONTENT', $renderer->render($frame->getContent()));
        $tpl->parseCurrentBlock();

        $tpl->setCurrentBlock('has_left');
        $tpl->touchBlock('has_left');
        $tpl->parseCurrentBlock();
    }

    protected function renderRightFrame(RendererInterface $renderer, Template $tpl, Frame $frame)
    {
        $tpl->setCurrentBlock('right_frame');
        $tpl->setVariable('RIGHT_FRAME_CONTENT', $renderer->render($frame->getContent()));
        $tpl->parseCurrentBlock();

        $tpl->setCurrentBlock('has_right');
        $tpl->touchBlock('has_right');
        $tpl->parseCurrentBlock();
    }

    protected function renderMainFrame(RendererInterface $renderer, Template $tpl, Frame $frame)
    {
        $tpl->setCurrentBlock('main_frame');
        $tpl->setVariable('MAIN_FRAME_CONTENT', $renderer->render($frame->getContent()));
        $tpl->parseCurrentBlock();
    }

    protected function renderFrameset(RendererInterface $renderer, Template $tpl, Set $set)
    {
        $tpl->setCurrentBlock('frameset');
        $tpl->setVariable('ID', $set->getId());
        $tpl->parseCurrentBlock();
    }

    protected function renderJavascript(RendererInterface $renderer, Template $tpl, Set $set)
    {
        $tpl->setCurrentBlock('javascript');
        $tpl->setVariable('ID', $set->getId());
        $tpl->parseCurrentBlock();
    }
}
