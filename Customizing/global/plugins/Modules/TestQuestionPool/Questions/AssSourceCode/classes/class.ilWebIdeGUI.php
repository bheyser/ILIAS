<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php';
require_once 'Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php';

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilWebIdeGUI extends ilSubEnabledFormPropertyGUI
{
	const STYLESHEET_FILENAME = 'il_web_ide.css';
	
	const TEMPLATE_FILENAME = 'tpl.il_web_ide.html';
	const MODAL_TEMPLATE_FILENAME = 'tpl.il_web_ide_upload_modal.html';
	
	const ACE_CUR_VERSION = '1.2.6';
	const ACE_SUBDIR_MASK = 'ace-builds-%s/src-noconflict/';

	protected $ACE_JS_LIB_FILES = array(
		'ace.js', 'ext-statusbar.js', 'ext-language_tools.js', 'ext-emmet.js', 'ext-keybinding_menu.js'
	);
	
	const SUBFIELD_EDITOR = 'editor';
	const SUBFIELD_UPLOAD = 'upload';
	
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	
	/**
	 * @var ilTemplate
	 */
	protected $uploadModalTpl;
	
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	
	/**
	 * @var string
	 */
	protected $libraryPath;
	
	/**
	 * @var string
	 */
	protected $stylesheetPath;
	
	/**
	 * @var string
	 */
	protected $instanceId;
	
	/**
	 * @var string
	 */
	protected $postVar;
	
	/**
	 * @var ilTemplate
	 */
	protected $template;
	
	/**
	 * @var bool
	 */
	protected $printModeEnabled;
	
	/**
	 * @var ilAssSourceCodeLanguage
	 */
	protected $sourceCodeLanguage;
	
	/**
	 * @var bool
	 */
	protected $readOnlyEnabled;
	
	/**
	 * @var ilFormInputValue
	 */
	protected $inputValue;
	
	/**
	 * @var ilFileInputGUI
	 */
	protected $fileUploadInputGui;
	
	/**
	 * @var string
	 */
	protected $uploadCommand;
	
	/**
	 * @var string
	 */
	protected $uploadHeaderLabel;
	
	/**
	 * @var string
	 */
	protected $uploadWarning;
	
	/**
	 * @var string
	 */
	protected $asyncSaveTarget;
	
	/**
	 * @var string
	 */
	protected $asyncSaveCommand;
	
	/**
	 * ilWebIdeGUI constructor.
	 *
	 * @param ilTemplate $tpl
	 * @param string $a_title
	 * @param string $a_postvar
	 */
	public function __construct($a_title = "", $a_postvar = "")
	{
		$this->ctrl = $GLOBALS['DIC'] ? $GLOBALS['DIC']['ilCtrl'] : $GLOBALS['ilCtrl'];
		$this->tpl = $GLOBALS['DIC'] ? $GLOBALS['DIC']['tpl'] : $GLOBALS['tpl'];
		$this->lng = $GLOBALS['DIC'] ? $GLOBALS['DIC']['lng'] : $GLOBALS['lng'];
		
		require_once 'Services/Form/classes/class.ilFileInputGUI.php';
		$this->fileUploadInputGui = new ilFileInputGUI($this->lng->txt('upload'), '');
		
		parent::__construct($a_title = "", $a_postvar = "");
		
		$this->lng->loadLanguageModule('form');
	}
	
	public function getLibraryPath()
	{
		return $this->libraryPath;
	}
	
	public function setLibraryPath($libraryPath)
	{
		$this->libraryPath = ilAssSourceCodeUtils::ensureTrailingPathSeparator($libraryPath);
	}
	
	public function getStylesheetPath()
	{
		return $this->stylesheetPath;
	}
	
	public function setStylesheetPath($stylesheetPath)
	{
		$this->stylesheetPath = ilAssSourceCodeUtils::ensureTrailingPathSeparator($stylesheetPath);
	}
	
	public function getInstanceId()
	{
		return $this->instanceId;
	}
	
	public function setInstanceId($instanceId)
	{
		$this->instanceId = $instanceId;
	}
	
	public function getPostVar()
	{
		return $this->postVar;
	}
	
	public function setPostVar($postVar)
	{
		$this->postVar = $postVar;
		
		$this->fileUploadInputGui->setPostVar($this->getUploadPostVar());
	}
	
	protected function getUploadPostVar()
	{
		return $this->getPostVar().'_'.self::SUBFIELD_UPLOAD;
	}
	
	protected function getEditorPostVar()
	{
		return $this->getPostVar().'_'.self::SUBFIELD_EDITOR;
	}
	
	public function setTemplate(ilTemplate $template)
	{
		$this->template = $template;
	}
	
	public function getTemplate()
	{
		return $this->template;
	}
	
	public function getUploadModalTpl()
	{
		return $this->uploadModalTpl;
	}
	
	public function setUploadModalTpl($uploadModalTpl)
	{
		$this->uploadModalTpl = $uploadModalTpl;
	}
	
	public function isPrintModeEnabled()
	{
		return $this->printModeEnabled;
	}
	
	public function setPrintModeEnabled($printModeEnabled)
	{
		$this->printModeEnabled = $printModeEnabled;
	}
	
	public function getSourceCodeLanguage()
	{
		return $this->sourceCodeLanguage;
	}
	
	public function setSourceCodeLanguage(ilAssSourceCodeLanguage $sourceCodeLanguage)
	{
		$this->sourceCodeLanguage = $sourceCodeLanguage;
		
		$this->fileUploadInputGui->setSuffixes(
			$sourceCodeLanguage->getFileExtensions()
		);
	}
	
	public function isReadOnlyEnabled()
	{
		return $this->readOnlyEnabled;
	}
	
	public function setReadOnlyEnabled($readOnlyEnabled)
	{
		$this->readOnlyEnabled = $readOnlyEnabled;
	}
	
	public function getInputValue()
	{
		return $this->inputValue;
	}
	
	public function setInputValue($inputValue)
	{
		$this->inputValue = $inputValue;
	}
	
	public function getUploadCommand()
	{
		return $this->uploadCommand;
	}
	
	public function setUploadCommand($uploadCommand)
	{
		$this->uploadCommand = $uploadCommand;
	}
	
	public function getUploadHeaderLabel()
	{
		return $this->uploadHeaderLabel;
	}
	
	public function setUploadHeaderLabel($uploadHeaderLabel)
	{
		$this->uploadHeaderLabel = $uploadHeaderLabel;
	}
	
	/**
	 * @return string
	 */
	public function getUploadWarning()
	{
		return $this->uploadWarning;
	}
	
	/**
	 * @param string $uploadWarning
	 */
	public function setUploadWarning($uploadWarning)
	{
		$this->uploadWarning = $uploadWarning;
	}
	
	/**
	 * @return string
	 */
	public function getAsyncSaveTarget()
	{
		return $this->asyncSaveTarget;
	}
	
	/**
	 * @param string $asyncSaveTarget
	 */
	public function setAsyncSaveTarget($asyncSaveTarget)
	{
		$this->asyncSaveTarget = $asyncSaveTarget;
	}
	
	/**
	 * @return string
	 */
	public function getAsyncSaveCommand()
	{
		return $this->asyncSaveCommand;
	}
	
	/**
	 * @param string $asyncSaveCommand
	 */
	public function setAsyncSaveCommand($asyncSaveCommand)
	{
		$this->asyncSaveCommand = $asyncSaveCommand;
	}
	
	protected function populateAceJsLibrary()
	{
		$aceDir = sprintf(self::ACE_SUBDIR_MASK, self::ACE_CUR_VERSION);
		
		foreach($this->ACE_JS_LIB_FILES as $jsFilename)
		{
			$aceFile = $this->getLibraryPath().$aceDir.$jsFilename;
			$this->tpl->addJavaScript($aceFile, true);
		}
	}
	
	protected function populateAceStylesheet()
	{
		$this->tpl->addCss($this->getStylesheetPath().self::STYLESHEET_FILENAME.(DEVMODE ? '?x='.microtime(true) : ''));
	}
	
	protected function isUploadAvailable($files)
	{
		if( !isset($files[$this->getUploadPostVar()]) )
		{
			return false;
		}
		
		if( !strlen($files[$this->getUploadPostVar()]['name']) )
		{
			return false;
		}
		
		if( !strlen($files[$this->getUploadPostVar()]['tmp_name']) )
		{
			return false;
		}
		
		if( !file_exists($files[$this->getUploadPostVar()]['tmp_name']) )
		{
			return false;
		}
		
		if( !is_readable($files[$this->getUploadPostVar()]['tmp_name']) )
		{
			return false;
		}
		
		if( !is_file($files[$this->getUploadPostVar()]['tmp_name']) )
		{
			return false;
		}
		
		return true;
	}
	
	protected function isEditorSubmissionAvailable($values)
	{
		if( !isset($values[$this->getEditorPostVar()]) )
		{
			return false;
		}
		
		if( !strlen($values[$this->getEditorPostVar()]) )
		{
			return false;
		}
		
		return true;
	}
	
	protected function getUploadFilename($files)
	{
		return $files[$this->getUploadPostVar()]['tmp_name'];
	}
	
	protected function getEditorSubmission($values)
	{
		return $values[$this->getEditorPostVar()];
	}
	
	public function setValueByArray($a_values)
	{
		if( $this->isUploadAvailable($_FILES) )
		{
			$this->getInputValue()->setValue( rawurlencode( file_get_contents(
				$this->getUploadFilename($_FILES)
			)));
		}
		else
		{
			$this->getInputValue()->setValue(
				$this->getEditorSubmission($a_values)
			);
		}
	}
	
	protected function isAsyncSavePossible()
	{
		return $this->getAsyncSaveTarget() && $this->getAsyncSaveCommand();
	}
	
	protected function areRequirementsMatched()
	{
		if( !$this->getRequired() )
		{
			return true;
		}
		
		if( $this->isEditorSubmissionAvailable($_POST) )
		{
			return true;
		}
		
		if( $this->isUploadAvailable($_FILES) )
		{
			return true;
		}
		
		return false;
	}
	
	public function checkInput()
	{
		$stripper = new ilFormSubmitRecursiveSlashesStripper();
		$_POST[$this->getUploadPostVar()] = current(
			$stripper->manipulateFormSubmitValues(array($_POST[$this->getUploadPostVar()]))
		);
		
		$stripper = new ilMultiFilesSubmitRecursiveSlashesStripper();
		$stripper->setPostVar($this->getUploadPostVar());
		$stripper->manipulateFormSubmitValues(array());
		
		if( !$this->fileUploadInputGui->checkInput() )
		{
			$this->setAlert($this->fileUploadInputGui->getAlert());
			return false;
		}
		
		if( !$this->areRequirementsMatched()  )
		{
			$this->setAlert($this->lng->txt('msg_input_is_required'));
			return false;
		}
		
		return true;
	}
	
	public function getHTML()
	{
		$this->populateAceJsLibrary();
		$this->populateAceStylesheet();
		
		if( $this->isReadOnlyEnabled() )
		{
			return $this->renderAceEditor();
		}
		
		return $this->renderUploadField().$this->renderAceEditor();
	}
	
	protected function renderUploadField()
	{
		require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
		$btnBack = ilLinkButton::getInstance();
		$btnBack->setId($this->fileUploadInputGui->getFieldId().'_BtnBack');
		$btnBack->setUrl('#');
		$btnBack->setCaption('back', true);
		
		require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
		$btnConfirm = ilLinkButton::getInstance();
		$btnConfirm->setId($this->fileUploadInputGui->getFieldId().'_BtnConfirm');
		$btnConfirm->setUrl('#');
		$btnConfirm->setCaption('confirm', true);
		
		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$btnUpload = ilSubmitButton::getInstance();
		$btnUpload->setCommand($this->getUploadCommand());
		$btnUpload->setCaption('upload', true);
		
		require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
		$btnCancel = ilLinkButton::getInstance();
		$btnCancel->setId($this->fileUploadInputGui->getFieldId().'_BtnCancel');
		$btnCancel->setUrl('#');
		$btnCancel->setCaption('cancel', true);
		
		require_once 'Services/UIComponent/Modal/classes/class.ilModalGUI.php';
		$modal = ilModalGUI::getInstance();
		$modal->setType(ilModalGUI::TYPE_LARGE);
		$modal->setId($this->fileUploadInputGui->getFieldId().'_Modal');
		$modal->setHeading($this->getUploadHeaderLabel());
		
		$this->getUploadModalTpl()->setVariable('CONFIRM_BTN', $btnConfirm->render());
		$this->getUploadModalTpl()->setVariable('BACK_BTN', $btnBack->render());
		$this->getUploadModalTpl()->setVariable('SUBMIT_BTN', $btnUpload->render());
		$this->getUploadModalTpl()->setVariable('CANCEL_BTN', $btnCancel->render());
		$this->getUploadModalTpl()->setVariable('UPLOAD_INP', $this->fileUploadInputGui->render());
		$this->getUploadModalTpl()->setVariable('UPLOAD_WARN', $this->getUploadWarning());
		$modal->setBody($this->getUploadModalTpl()->get());
		
		return $modal->getHTML();
	}
	
	protected function renderAceEditor()
	{
		if( !$this->isReadOnlyEnabled() && !$this->isPrintModeEnabled() )
		{
			$this->renderToolbarButtons();
		}
		
		$this->renderWebEditor();
		
		if( !$this->isPrintModeEnabled() )
		{
			$this->renderJsInitialisation();
		}
		
		return $this->getTemplate()->get();
	}
	
	protected function renderToolbarButtons()
	{
		$this->getTemplate()->setCurrentBlock('html_hotkeys_help_btn');
		$this->getTemplate()->setVariable('HOTKEYS_BTN', ilAssSourceCodeGlyphGUI::get(
			ilAssSourceCodeGlyphGUI::HELP
		));
		$this->getTemplate()->parseCurrentBlock();
		
		$this->getTemplate()->setCurrentBlock('html_upload_btn');
		$this->getTemplate()->setVariable('UPLOAD_BTN', ilAssSourceCodeGlyphGUI::get(
			ilAssSourceCodeGlyphGUI::ATTACHMENT
		));
		$this->getTemplate()->parseCurrentBlock();
		
		if( $this->isAsyncSavePossible() )
		{
			$this->getTemplate()->setCurrentBlock('html_save_btn');
			$this->getTemplate()->setVariable('SAVE_BTN', ilAssSourceCodeGlyphGUI::get(
				ilAssSourceCodeGlyphGUI::SAVE
			));
			$this->getTemplate()->parseCurrentBlock();
		}
	}
	
	protected function renderWebEditor()
	{
		$this->renderEditorContent();
		$this->renderEditorInterface();
	}
		
	protected function renderEditorContent()
	{
		if( $this->isPrintModeEnabled() )
		{
			$content = $this->getInputValue()->getPrintableValue();
		}
		else
		{
			$content = $this->getInputValue()->getValue();
		}
		
		$this->getTemplate()->setCurrentBlock('html_editor_content');
		$this->getTemplate()->setVariable('CONTENT', $content);
		$this->getTemplate()->parseCurrentBlock();
		
		$this->getTemplate()->setCurrentBlock('html_editor_value');
		$this->getTemplate()->setVariable('VALUE', $content);
		$this->getTemplate()->parseCurrentBlock();
	}

	protected function renderEditorInterface()
	{
		$this->getTemplate()->setCurrentBlock('html');
		$this->getTemplate()->setVariable('INSTANCE_ID', $this->getInstanceId());
		$this->getTemplate()->setVariable('POSTVAR', $this->getEditorPostVar());
		$this->getTemplate()->parseCurrentBlock();
	}
	
	protected function renderJsInitialisation()
	{
		if( $this->isReadOnlyEnabled() )
		{
			$this->getTemplate()->setCurrentBlock('js_readonly_ace');
			$this->getTemplate()->touchBlock('js_readonly_ace');
			$this->getTemplate()->parseCurrentBlock();
		}
		
		if( $this->isAsyncSavePossible() )
		{
			$this->getTemplate()->setCurrentBlock('js_autosave');
			$this->getTemplate()->setVariable('UPLOAD_MODAL_ID', $this->fileUploadInputGui->getFieldId());
			$this->getTemplate()->setVariable('AUTOSAVE_URL', $this->ctrl->getLinkTargetByClass(
				$this->getAsyncSaveTarget(), $this->getAsyncSaveCommand(), '', true, false
			));
			$this->getTemplate()->parseCurrentBlock();
		}
		
		$this->getTemplate()->setCurrentBlock('js');
		$this->getTemplate()->setVariable('INSTANCE_ID', $this->getInstanceId());
		$this->getTemplate()->setVariable('UPLOAD_MODAL_ID', $this->fileUploadInputGui->getFieldId());
		$this->getTemplate()->setVariable('LANG_MODE', $this->getSourceCodeLanguage()->getWebIdeMode());
		$this->getTemplate()->parseCurrentBlock();
	}
}