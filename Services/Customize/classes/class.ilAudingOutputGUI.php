<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Customize/classes/class.ilAudingPlayerRequestsImpl.php';

/**
 * Class ilAudingOutputGUI
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilAudingOutputGUI
{
	/**
	 * @var ilAudingRequestableFile
	 */
	protected $file;

	/**
	 * @var ilAudingPlayerRequests 
	 */
	protected $actions;

	/**
	 * ilAudingOutputGUI constructor.
	 * @param ilAudingRequestableFile $file
	 */
	public function __construct(ilAudingRequestableFile $file)
	{
		$this->file    = $file;
		$this->actions = new ilAudingPlayerRequestsImpl('');
	}

	/**
	 * @param string $request_url
	 * @return ilAudingPlayerRequests
	 */
	public function addRequestUrl($request_url)
	{
		$this->actions = new ilAudingPlayerRequestsImpl($request_url);
		return $this->actions;
	}

	/**
	 * @return bool
	 */
	public function hasHtml()
	{
		return $this->file->exists() && $this->file->isAccessible() && $this->file->isStreamable();
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		$tpl = new ilTemplate('tpl.auding.html', true, true, 'Services/Customize');

		$tpl->setVariable('CONFIG', json_encode(array(
			'playAllowed'       => $this->file->isPlayable(),
			'pauseAllowed'      => $this->file->isPausable(),
			'isVideo'           => $this->file->isVideo(),
			'requestUrl'        => $this->actions->getRequestUrl(),
			'mimeType'          => $this->file->getMimeType(),
			'accessTrackingUrl' => $this->actions->getAccessTrackingUrl(),
			'playingEndedUrl'   => $this->actions->getPlayingEndedUrl(),
			'txt'               => array(
				'noRequestsLeft' => $GLOBALS['lng']->txt('auding_no_more_replays')
			)
		)));

		require_once 'Services/jQuery/classes/class.iljQueryUtil.php';
		require_once 'Services/MediaObjects/classes/class.ilObjMediaObjectGUI.php';
		iljQueryUtil::initjQuery();
		iljQueryUtil::initjQueryUI();
		ilObjMediaObjectGUI::includePresentationJS();
		
		$GLOBALS['tpl']->addCss('./Services/Customize/css/auding.css');
		$GLOBALS['tpl']->addJavaScript('./Services/Customize/js/auding.js');

		return $tpl->get();
	}
}