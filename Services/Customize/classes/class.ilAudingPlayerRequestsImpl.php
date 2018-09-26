<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Customize/interfaces/interface.ilAudingPlayerRequests.php';

/**
 * Class ilAudingPlayerActionImpl
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilAudingPlayerRequestsImpl implements ilAudingPlayerRequests
{
	/**
	 * @var string
	 */
	protected $request_url = '';

	/**
	 * @var string
	 */
	protected $access_tracking_url = '';

	/**
	 * @var string
	 */
	protected $playing_ended_url = '';

	/**
	 * ilAudingPlayerActionImpl constructor.
	 * @param string $request_url
	 */
	public function __construct($request_url)
	{
		$this->request_url = $request_url;
	}

	/**
	 * {@inheritdoc}
	 */
	public function withAccessTrackingUrl($access_tracking_url)
	{
		$this->access_tracking_url = $access_tracking_url;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function withPlayingEndedUrl($playing_ended_url)
	{
		$this->playing_ended_url = $playing_ended_url;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRequestUrl()
	{
		return $this->request_url;
	}

	/**
	 * @return string
	 */
	public function getAccessTrackingUrl()
	{
		return $this->access_tracking_url;
	}

	/**
	 * @return string
	 */
	public function getPlayingEndedUrl()
	{
		return $this->playing_ended_url;
	}
}