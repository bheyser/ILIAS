<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilAssSourceCodeLanguageHaskell implements ilAssSourceCodeLanguage
{
	const FILE_EXTENSION = 'hs';
	const EDITOR_MODE = 'haskell';
	const IDENTIFIER = 'hs';
	const LABEL_LANGVAR = 'source_code_lang_haskell';
	
	protected static $validFileExtensions = array(
		self::FILE_EXTENSION
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