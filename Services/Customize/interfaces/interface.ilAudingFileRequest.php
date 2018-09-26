<?php
// auding-patch: begin
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface ilAudingFileRequest
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilAudingFileRequest
{
	/**
	 * @param ilAudingRequestableFile $file
	 */
	public function handleRequest(ilAudingRequestableFile $file);
}
// auding-patch: end