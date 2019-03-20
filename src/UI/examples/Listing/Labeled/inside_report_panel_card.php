<?php

/**
 * Only serving as Example
 */
function inside_report_panel_card() {
	
	global $DIC;
	
	$factory = $DIC->ui()->factory();
	$renderer = $DIC->ui()->renderer();
	
	$items = [
		'A Longer First Label' => $factory->legacy('20'),
		'A Longer Second Label' => $factory->legacy('8'),
		'A Longer A Last Label' => '12'
	];
	
	$labeledListing = $factory->listing()->labeled($items)->withSplitRatio_3_1();
	
	$report = $factory->panel()->report('Any Report Title', [
		$factory->panel()->sub('Any Panel Title', $factory->legacy('Any Report Panel Content'))->withCard(
			$factory->card()->standard('Any Card Title')->withSections([$labeledListing])
		)
	]);
	
	$html = $renderer->render($report);
	
	return $html;
}