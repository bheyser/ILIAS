<?php

require_once 'Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php';
require_once 'Services/Utilities/classes/class.ilUtil.php';
	
/**
 * Question plugin Infotext
 *
 * @author	Björn Heyser <bheyser@databay.de>
 * @version	$Id$
 * @package Modules/TestQuestionPool
 */
class ilAssSourceCodePlugin extends ilQuestionsPlugin
{
	const PLUGIN_CODE_NAME = 'AssSourceCode';
	const QUESTION_TYPE_TAG = 'assSourceCode';
	
	const LIBRARY_SUBDIR_NAME = 'lib/';
	const STYLESHEET_SUBDIR_NAME = 'css/';
	
	protected function init()
	{
		$this->includeCoreClasses();
		$this->includeClasses();
	}
	
	protected function includeCoreClasses()
	{
		if( file_exists('Services/Form/interfaces/interface.ilFormInputValue.php') )
				require_once 'Services/Form/interfaces/interface.ilFormInputValue.php';
		else	$this->includeClass('core/interface.ilFormInputValue.php');
		
		if( file_exists('Services/Form/interfaces/interface.ilFormValuesManipulator.php') )
				require_once 'Services/Form/interfaces/interface.ilFormValuesManipulator.php';
		else	$this->includeClass('core/interface.ilFormValuesManipulator.php');
		
		if( file_exists('Services/Form/classes/class.ilFormSubmitRecursiveSlashesStripper.php') )
				require_once 'Services/Form/classes/class.ilFormSubmitRecursiveSlashesStripper.php';
		else	$this->includeClass('core/class.ilFormSubmitRecursiveSlashesStripper.php');
		
		if( file_exists('Services/Form/classes/class.ilMultiFilesSubmitRecursiveSlashesStripper.php') )
				require_once 'Services/Form/classes/class.ilMultiFilesSubmitRecursiveSlashesStripper.php';
		else	$this->includeClass('core/class.ilMultiFilesSubmitRecursiveSlashesStripper.php');
		
		$this->includeClass('core/class.ilAssSourceCodeGlyphGUI.php');
	}
	
	protected function includeClasses()
	{
		$this->includeClass('exception.ilAssSourceCodeException.php');
		$this->includeClass('exception.ilAssSourceCodeInvalidSubmitException.php');
		
		$this->includeClass('class.ilAssSourceCodeUtils.php');
		$this->includeClass('class.ilAssSourceCodeSolution.php');
		
		$this->includeClass('languages/interface.ilAssSourceCodeLanguage.php');
		
		$this->includeClass('languages/class.ilAssSourceCodeLanguageJava.php');
		$this->includeClass('languages/class.ilAssSourceCodeLanguageC.php');
		$this->includeClass('languages/class.ilAssSourceCodeLanguagePython.php');
		$this->includeClass('languages/class.ilAssSourceCodeLanguageHaskell.php');
		$this->includeClass('languages/class.ilAssSourceCodeLanguagePlaintext.php');
		
		$this->includeClass('languages/class.ilAssSourceCodeLanguageFactory.php');
		
		$this->includeClass('class.ilWebIdeGUI.php');
	}
	
	final function getPluginName()
	{
		return self::PLUGIN_CODE_NAME;
	}
	
	final function getQuestionType()
	{
		return self::QUESTION_TYPE_TAG;
	}
	
	final function getQuestionTypeTranslation()
	{
		return $this->txt($this->getQuestionType());
	}
	
	/**
	 * @var self|null
	 */
	private static $instance = null;
	
	/**
	 * @return self
	 */
	public static function getInstance()
	{
		if( self::$instance === null )
		{
			self::$instance = ilPlugin::getPluginObject(
				IL_COMP_MODULE, 'TestQuestionPool', 'qst', self::PLUGIN_CODE_NAME
			);
		}
		
		return self::$instance;
	}
	
	public function getPluginPath()
	{
		return ilAssSourceCodeUtils::ensureTrailingPathSeparator($this->getDirectory());
	}
	
	public function getLibraryPath()
	{
		return $this->getPluginPath().ilAssSourceCodeUtils::ensureTrailingPathSeparator(
			self::LIBRARY_SUBDIR_NAME
		);
	}
	
	public function getStylesheetPath()
	{
		return $this->getPluginPath().ilAssSourceCodeUtils::ensureTrailingPathSeparator(
			self::STYLESHEET_SUBDIR_NAME
		);
	}
}