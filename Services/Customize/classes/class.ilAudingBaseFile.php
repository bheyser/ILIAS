<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Customize/interfaces/interface.ilAudingRequestableFile.php';

/**
 * Class ilAudingBaseFile
 * @author Michael Jansen <mjansen@databay.de>
 */
abstract class ilAudingBaseFile implements ilAudingRequestableFile
{
	protected static $video_suffixes = array(
		'mp4',
		'm4v',
		'mov',
		'wmv',
		'flv',
		'webm'
	);
	protected static $audio_suffixes = array(
		'mp3',
		'aiff',
		'aif',
		'wav',
		'ogg'
	);

	/**
	 * @var assQuestion
	 */
	protected $question;

	/**
	 * @var ilObject
	 */
	protected $container;

	/**
	 * ilAudingBaseFile constructor.
	 * @param assQuestion $question
	 * @param ilObject    $container
	 */
	public function __construct(assQuestion $question, ilObject $container)
	{
		$this->question  = $question;
		$this->container = $container;

		$this->init();
	}

	/**
	 * 
	 */
	protected function init()
	{
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFile()
	{
		return $this->question->getAudingFilePath() . $this->question->getAudingFile();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMimeType()
	{
		require_once 'Services/Utilities/classes/class.ilMimeTypeUtil.php';
		$info = ilMimeTypeUtil::lookupMimeType($this->getFile(), ilMimeTypeUtil::APPLICATION__OCTET_STREAM);
		if(strlen($info) > 0)
		{
			return $info;
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$info  = finfo_file($finfo, $this->getFile());
		finfo_close($finfo);

		if(strlen($info) > 0)
		{
			return $info;
		}

		return '';
	}

	/**
	 * @return string
	 */
	protected function getSuffix()
	{
		return pathinfo($this->getFile(), PATHINFO_EXTENSION);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isVideo()
	{
		return in_array(strtolower($this->getSuffix()), self::$video_suffixes);
	}


	/**
	 * {@inheritdoc}
	 */
	public function isAudio()
	{
		return in_array(strtolower($this->getSuffix()), self::$audio_suffixes);
	}


	/**
	 * {@inheritdoc}
	 */
	public function isStreamable()
	{
		return ($this->isAudio() || $this->isVideo());
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists()
	{
		if(!$this->question->getAudingActivate())
		{
			return false;
		}

		if(strlen($this->question->getAudingFile()) == 0 || !is_file($this->question->getAudingFilePath() . $this->question->getAudingFile()))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	abstract protected function hasContainerSpecificAccess();

	/**
	 * {@inheritdoc}
	 */
	public function isAccessible()
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		global $ilAccess;

		if(!$ilAccess->checkAccess('read', '', $this->container->getRefId()))
		{
			return false;
		}

		return $this->hasContainerSpecificAccess();
	}

	/**
	 * {@inheritdoc}
	 */
	public function isPausable()
	{
		return (int)$this->question->getAudingMode() == 1;
	}
}