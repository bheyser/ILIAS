<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilAssSourceCodeLanguageFactory
{
	public function getAvailableSourceCodeLanguages()
	{
		return array(
			new ilAssSourceCodeLanguageJava(),
			new ilAssSourceCodeLanguageC(),
			new ilAssSourceCodeLanguagePython(),
			new ilAssSourceCodeLanguageHaskell(),
			new ilAssSourceCodeLanguagePlaintext()
		);
		
	}
	
	public function getAvailableSourceCodeLanguagePresentationLabelsByIdentifiers(ilAssSourceCodePlugin $plugin)
	{
		$presentationLabelsByIdentifiers = array();
		
		foreach($this->getAvailableSourceCodeLanguages() as $sourceCodeLanguage)
		{
			$identifier = $sourceCodeLanguage->getIdentifier();
			$label = $sourceCodeLanguage->getPresentationLabel($plugin);
			$presentationLabelsByIdentifiers[$identifier] = $label;
		}
		
		return $presentationLabelsByIdentifiers;
	}
	
	public function getAvailableSourceCodeLanguageIdentifiers()
	{
		$identifiers = array();
		
		foreach($this->getAvailableSourceCodeLanguages() as $sourceCodeLanguage)
		{
			$identifiers[] = $sourceCodeLanguage->getIdentifier();
		}
		
		return $identifiers;
	}
	
	public function getSourceCodeLanguageByIdentifier($identifier)
	{
		foreach($this->getAvailableSourceCodeLanguages() as $sourceCodeLanguage)
		{
			if( $identifier != $sourceCodeLanguage->getIdentifier() )
			{
				continue;
			}
			
			return $sourceCodeLanguage;
		}
		
		throw new ilAssSourceCodeException('unsupported source code language');
	}
	
	public function isValidSourceCodeLanguage($sourceCodeLanguage)
	{
		foreach($this->getAvailableSourceCodeLanguages() as $availableSourceCodeLanguage)
		{
			if( !($sourceCodeLanguage instanceof $availableSourceCodeLanguage) )
			{
				continue;
			}
			
			return true;
		}
		
		return false;
	}
}