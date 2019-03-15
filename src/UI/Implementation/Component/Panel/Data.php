<?php

/* Copyright (c) 2019 Björn Heyser <info@bjoernheyser.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Panel;

use ILIAS\UI\Component\Component;

/**
 * Class Panel
 * @package ILIAS\UI\Implementation\Component\Panel
 */
class Data extends Panel implements \ILIAS\UI\Component\Panel\Data {
	
	/**
	 * @var array
	 */
	protected $entries;
	
	/**
	 * @var bool
	 */
	protected $dividerEnabled;
	
	/**
	 * @param string $title
	 */
	public function __construct($title) {
		$this->entries = array();
		$this->dividerEnabled = false;
		parent::__construct($title, array());
	}
	
	/**
	 * @param bool $enabled
	 * @return Data
	 */
	public function withDividerEnabled(bool $enabled)
	{
		$this->dividerEnabled = $enabled;
		
		return clone $this;
	}
	
	/**
	 * @return bool
	 */
	public function isDividerEnabled()
	{
		return $this->dividerEnabled;
	}
	
	/**
	 * @param Component $dataLabel
	 * @param Component $dataValue
	 * @return \ILIAS\UI\Component\Panel\Data|Data
	 */
	public function withAdditionalEntry(Component $dataLabel, Component $dataValue)
	{
		$this->entries[] = array($dataLabel, $dataValue);
		
		return clone $this;
	}
	
	/**
	 * @return array
	 */
	public function getEntries()
	{
		return $this->entries;
	}
}