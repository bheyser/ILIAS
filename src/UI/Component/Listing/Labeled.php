<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


namespace ILIAS\UI\Component\Listing;


interface Labeled extends Listing {
	
	/**
	 * Sets a key value pair as items for the listing. Key is used as label and value as content.
	 * @param array $items string => Component | string
	 * @return \ILIAS\UI\Component\Listing\Labeled
	 */
	public function withItems(array $items);
	
	/**
	 * Gets the key value pair as items for the listing. Key is used as label and value as content.
	 * @return array $items string => Component | string
	 */
	public function getItems();
	
	/**
	 * Enables a devider between the items of the listing
	 * @return \ILIAS\UI\Component\Listing\Labeled
	 */
	public function withDevider();
	
	
	/**
	 * Returns wether divider is enabled or not
	 * @return bool
	 */
	public function hasDivider();
	
	/**
	 * Defines a 1:1 split ratio for the columns of labels and content
	 *
	 * @return \ILIAS\UI\Component\Listing\Labeled
	 */
	public function withSplitRatio_1_1();
	
	/**
	 * Defines a 3:1 split ratio for the columns of labels and content
	 *
	 * @return \ILIAS\UI\Component\Listing\Labeled
	 */
	public function withSplitRatio_3_1();
	
	/**
	 * Defines a 1:3 split ratio for the columns of labels and content
	 *
	 * @return \ILIAS\UI\Component\Listing\Labeled
	 */
	public function withSplitRatio_1_3();
	
	/**
	 * Returns the split ratio for labels and values
	 *
	 * @return string
	 */
	public function getSplitRatio();
}