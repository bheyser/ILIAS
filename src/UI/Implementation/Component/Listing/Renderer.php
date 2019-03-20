<?php

/* Copyright (c) 2016 Timon Amstutz <timon.amstutz@ilub.unibe.ch> Extended GPL, see docs/LICENSE */


namespace ILIAS\UI\Implementation\Component\Listing;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;

/**
 * Class Renderer
 * @package ILIAS\UI\Implementation\Component\Listing\Descriptive
 */
class Renderer extends AbstractComponentRenderer {
	/**
	 * @inheritdocs
	 */
	public function render(Component\Component $component, RendererInterface $default_renderer) {
		/**
		 * @var Component\Listing\Listing $component
		 */
		$this->checkComponent($component);

		if ($component instanceof Component\Listing\Descriptive) {
			return $this->render_descriptive($component, $default_renderer);
		}
		elseif ($component instanceof Component\Listing\Labeled) {
			return $this->render_labeled($component, $default_renderer);
		}
		else {
			return $this->render_simple($component, $default_renderer);
		}
	}

	/**
	 * @param Component\Listing\descriptive $component
	 * @param RendererInterface $default_renderer
	 * @return string
	 */
	protected function render_descriptive(Component\Listing\Descriptive $component, RendererInterface $default_renderer)
	{
		$tpl = $this->getTemplate("tpl.descriptive.html", true, true);

		foreach ($component->getItems() as $key => $item)
		{
			if (is_string($item))
			{
				$content = $item;
			} else
			{
				$content = $default_renderer->render($item);
			}

			if (trim($content) != "")
			{
				$tpl->setCurrentBlock("item");
				$tpl->setVariable("DESCRIPTION", $key);
				$tpl->setVariable("CONTENT", $content);
				$tpl->parseCurrentBlock();
			}
		}
		return $tpl->get();
	}
	
	/**
	 * @param Component\Listing\Labeled $component
	 * @param RendererInterface $default_renderer
	 */
	protected function render_labeled(Component\Listing\Labeled $component, RendererInterface $default_renderer) {
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		$divider = $DIC->ui()->factory()->divider()->horizontal();
		
		switch( $component->getSplitRatio() )
		{
			case Labeled::SPLIT_RATIO_1_3:
				
				$labelCssClass = 'col-md-3';
				$contentCssClass = 'col-md-9';
				break;
				
			case Labeled::SPLIT_RATIO_3_1:
				
				$labelCssClass = 'col-md-9';
				$contentCssClass = 'col-md-3';
				break;
			
			case Labeled::SPLIT_RATIO_1_1:
			default:
			
			$labelCssClass = 'col-md-6';
			$contentCssClass = 'col-md-6';
				break;
		}
		
		$tpl = $this->getTemplate("tpl.labeled.html", true, true);
		
		$first = true;
		
		foreach($component->getItems() as $label => $content)
		{
			if( $first )
			{
				$first = false;
			}
			elseif( $component->hasDivider() )
			{
				$tpl->setCurrentBlock('data-row-divider');
				$tpl->setVariable('DIVIDER', $default_renderer->render($divider));
				$tpl->parseCurrentBlock();
			}
			
			$content = is_string($content) ? $content : $default_renderer->render($content);
			
			$tpl->setCurrentBlock('data-row');
			$tpl->setVariable('LABEL_CLASS', $labelCssClass);
			$tpl->setVariable('VALUE_CLASS', $contentCssClass);
			$tpl->setVariable('LABEL', $label);
			$tpl->setVariable('VALUE', $content);
			$tpl->parseCurrentBlock();
		}
		
		return $tpl->get();
	}

	/**
	 * @param Component\Listing\Listing $component
	 * @param RendererInterface $default_renderer
	 * @return mixed
	 */
	protected function render_simple(Component\Listing\Listing $component, RendererInterface $default_renderer)
	{
		$tpl_name = "";

		if ($component instanceof Component\Listing\Ordered) {
			$tpl_name = "tpl.ordered.html";
		}
		if ($component instanceof Component\Listing\Unordered) {
			$tpl_name = "tpl.unordered.html";
		}

		$tpl = $this->getTemplate($tpl_name, true, true);

		if (count($component->getItems()) > 0)
		{
			foreach ($component->getItems() as $item)
			{
				$tpl->setCurrentBlock("item");
				if (is_string($item))
				{
					$tpl->setVariable("ITEM", $item);
				} else
				{
					$tpl->setVariable("ITEM", $default_renderer->render($item));
				}
				$tpl->parseCurrentBlock();
			}
		}
		return $tpl->get();
	}


	/**
	 * @inheritdocs
	 */
	protected function getComponentInterfaceName() {
		return [Component\Listing\Listing::class];
	}
}