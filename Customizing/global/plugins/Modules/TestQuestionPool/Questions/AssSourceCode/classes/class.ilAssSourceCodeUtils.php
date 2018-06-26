<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilAssSourceCodeUtils
{
	/**
	 * @param string $pathStringPossiblyWithoutTrailingPathSeparator
	 * @return string $pathStringWithTrailingPathSeparatorForSure
	 */
	public static function ensureTrailingPathSeparator($pathString)
	{
		if( !strlen($pathString) )
		{
			return $pathString;
		}
		
		if( substr($pathString, -1, 1) != DIRECTORY_SEPARATOR )
		{
			$pathString .= DIRECTORY_SEPARATOR;
		}
		
		return $pathString;
	}
}