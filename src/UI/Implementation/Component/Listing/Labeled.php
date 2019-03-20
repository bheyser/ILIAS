<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


namespace ILIAS\UI\Implementation\Component\Listing;

use ILIAS\UI\Component as C;
use ILIAS\UI\Implementation\Component\ComponentHelper;


/**
 * Class Labeled
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 */
class Labeled implements C\Listing\Labeled
{
	use ComponentHelper;
	
	/**
	 * Constants for split ratio of lables and values
	 */
	const SPLIT_RATIO_1_1 = 'split_1:1';
	const SPLIT_RATIO_3_1 = 'split_3:1';
	const SPLIT_RATIO_1_3 = 'split_1:3';
	
	/**
	 * @var	array
	 */
	private $items;
	
	/**
	 * @var bool
	 */
	private $withDivider;
	
	/**
	 * @var bool
	 */
	private $splitRatio;
	
	/**
	 * Constructor
	 * @param array $items string => Component | string
	 */
	public function __construct(array $items) {
		$this->checkItemsArg($items);
		$this->items = $items;
	}
	
	/**
	 * Returns the allowed split ratios
	 * @return string[]
	 */
	private function getValidSplitRatios() {
		return [ self::SPLIT_RATIO_1_1, self::SPLIT_RATIO_3_1, self::SPLIT_RATIO_1_3 ];
	}
	
	/**
	 * Checks for a valid split ratio
	 * @param string $splitRatio
	 */
	private function checkSplitRatioArg(string $splitRatio) {
		if( !in_array($splitRatio, $this->getValidSplitRatios()) )
		{
			$this->checkArg('split ratio', false, $splitRatio);
		}
	}
	
	/**
	 * Checks for a valid items array
	 * @param array $items string => Component | string
	 */
	private function checkItemsArg(array $items)
	{
		$checkCallback = function($k,$v) {
			return is_string($k) && (is_string($v) || $v instanceof C\Component);
		};
		
		$errmsgCallback = function($k, $v) {
			return "expected keys of type string and values of type string|Component, got ($k => $v)";
		};
		
		$this->checkArgList("Labeled List items", $items, $checkCallback, $errmsgCallback);
	}
	
	/**
	 * @inheritdoc
	 */
	public function withItems(array $items) {
		$this->checkItemsArg($items);
		$clone = clone $this;
		$clone->items = $items;
		return $clone;
	}
	
	/**
	 * @inheritdoc
	 */
	public function getItems() {
		return $this->items;
	}
	
	/**
	 * @inheritdoc
	 */
	public function withDevider() {
		$clone = clone $this;
		$clone->withDivider = true;
		return $clone;
	}
	
	/**
	 * @inheritdoc
	 */
	public function hasDivider() {
		return $this->withDivider;
	}
	
	/**
	 * @inheritdoc
	 */
	public function withSplitRatio_1_1() {
		return $this->setSplitRatio(self::SPLIT_RATIO_1_1);
	}
	
	/**
	 * @inheritdoc
	 */
	public function withSplitRatio_3_1() {
		return $this->setSplitRatio(self::SPLIT_RATIO_3_1);
	}
	
	/**
	 * @inheritdoc
	 */
	public function withSplitRatio_1_3() {
		return $this->setSplitRatio(self::SPLIT_RATIO_1_3);
	}
	
	/**
	 * @inheritdoc
	 */
	public function getSplitRatio() {
		return $this->splitRatio;
	}
	
	/**
	 * sets the split ratio to be used for spliting the columns for label and content
	 */
	public function setSplitRatio(string $splitRatio) {
		$this->checkSplitRatioArg($splitRatio);
		$clone = clone $this;
		$clone->splitRatio = $splitRatio;
		return $clone;
	}
}
