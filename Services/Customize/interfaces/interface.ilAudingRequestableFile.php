<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface ilAudingRequestableFile
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilAudingRequestableFile
{
	/**
	 * @return bool
	 */
	public function isAccessible();

	/**
	 * @return bool
	 */
	public function exists();

	/**
	 * @return string
	 */
	public function getFile();

	/**
	 * @return string
	 */
	public function getMimeType();

	/**
	 * @return bool
	 */
	public function isPausable();

	/**
	 * @return bool
	 */
	public function isPlayable();

	/**
	 * @return bool
	 */
	public function isVideo();

	/**
	 * @return bool
	 */
	public function isAudio();

	/**
	 * @return bool
	 */
	public function isStreamable();
}
// auding-patch: emd