<?php

/**
 * Only serving as Example
 */
function inside_panel() {
	
	global $DIC;
	
	$factory = $DIC->ui()->factory();
	$renderer = $DIC->ui()->renderer();
	
	$items = [
		'First Label' => $factory->legacy('Good Value'),
		'Second Label' => $factory->legacy('More Well Value'),
		'A Last Label' => 'Any Last Value'
	];
	
	$labeledListing = $factory->listing()->labeled($items)->withDevider()->withSplitRatio_1_1();
	
	$panel = $factory->panel()->standard('Any Panel', $labeledListing);
	
	$html = $renderer->render($panel);
	
	return $html;
}