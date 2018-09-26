<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Customize/classes/class.ilAudingBaseFile.php';

/**
 * Class ilAudingPreviewFile
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilAudingPreviewFile extends ilAudingBaseFile
{
	/**
	 * {@inheritdoc}
	 */
	protected function hasContainerSpecificAccess()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isPlayable()
	{
		return true;
	}
}
// auding-patch: end