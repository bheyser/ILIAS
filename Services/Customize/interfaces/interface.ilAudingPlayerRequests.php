<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface ilAudingPlayerRequests
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilAudingPlayerRequests
{
	/**
	 * @param string $url
	 * @return ilAudingPlayerRequests
	 */
	public function withAccessTrackingUrl($url);

	/**
	 * @param string $url
	 * @return ilAudingPlayerRequests
	 */
	public function withPlayingEndedUrl($url);

	/**
	 * @return string
	 */
	public function getRequestUrl();

	/**
	 * @return string
	 */
	public function getAccessTrackingUrl();

	/**
	 * @return string
	 */
	public function getPlayingEndedUrl();
}
// auding-patch: end