<?php

/**
 * Only serving as Example
 */
function basic_use_2() {
	
	global $DIC;
	
	$factory = $DIC->ui()->factory();
	$renderer = $DIC->ui()->renderer();
	
	$items = [
		'Realy Long and even Longer First Label' => $factory->legacy('Good Value'),
		'Realy Long and even Longer Second Label' => $factory->legacy('More Well Value'),
		'Realy Long and even Longer Last Label' => 'Any Last Value'
	];
	
	$labeledListing = $factory->listing()->labeled($items)->withSplitRatio_3_1();
	
	$html = $renderer->render($labeledListing);
	
	return $html;
}