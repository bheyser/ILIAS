<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilAssSourceCodeLanguageC implements ilAssSourceCodeLanguage
{
	const FILE_EXTENSION_1 = 'c';
	const FILE_EXTENSION_2 = 'h';
	const EDITOR_MODE = 'c';
	const IDENTIFIER = 'c';
	const LABEL_LANGVAR = 'source_code_lang_c';
	
	protected static $validFileExtensions = array(
		self::FILE_EXTENSION_1, self::FILE_EXTENSION_2
	);
	
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}
	
	public function getFileExtensions()
	{
		return self::$validFileExtensions;
	}
	
	public function getWebIdeMode()
	{
		return self::EDITOR_MODE;
	}
	
	public function getPresentationLabel(ilPlugin $plugin)
	{
		return $plugin->txt(self::LABEL_LANGVAR);
	}
	
	
}