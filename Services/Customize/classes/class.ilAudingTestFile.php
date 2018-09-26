<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Customize/classes/class.ilAudingBaseFile.php';

/**
 * Class ilAudingTestFile
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilAudingTestFile extends ilAudingBaseFile
{
	/**
	 * @var int
	 */
	protected $test_pass = 0;

	/**
	 * @var int
	 */
	protected $active_id = 0;

	/**
	 * @var array
	 */
	protected $auding_settings = array();

	/**
	 * {@inheritdoc}
	 */
	protected function init()
	{
		$this->active_id = $this->container->getActiveIdOfUser($GLOBALS['ilUser']->getId());

		$class           = get_class($this->container);
		$this->test_pass = (int)$class::_getPass($this->active_id);

		$this->auding_settings = ilObjTest::getAudingSettingsForRequestCase($this->test_pass, $this->active_id, $this->question->getId());
	}

	/**
	 * {@inheritdoc}
	 */
	protected function hasContainerSpecificAccess()
	{
		if($this->container->endingTimeReached())
		{
			return false;
		}

		$starting_time = $this->container->getStartingTimeOfUser($this->active_id);
		if($starting_time)
		{
			if($this->container->isMaxProcessingTimeReached($starting_time, $this->active_id))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isPlayable()
	{
		$number_of_requests  = $this->question->getAudingRequestLog($this->test_pass, $this->active_id);

		$allowed_requests_in_pool = $this->question->getAudingNrOfSends();
		$allowed_requests_in_test = $this->auding_settings ? $this->auding_settings['nr_of_sends'] : 0;

		$sum_allowed_requests =  $allowed_requests_in_pool + $allowed_requests_in_test;
		if(($sum_allowed_requests == 0 || $number_of_requests < $sum_allowed_requests))
		{
			return true;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isPausable()
	{
		return(
			(int)(strlen($this->auding_settings['auding_mode']) ? $this->auding_settings['auding_mode'] : $this->question->getAudingMode()) == 1
		);
	}
}
// auding-patch: end