<?php /* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilPilotTinyMCE
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @package     Services/RTE
 */
class ilPilotTinyMCE extends ilTinyMCE
{
    const TINY_MCE_VERSION = '5.4.2';
    const TINY_MCE_TEMPLATE = 'tpl.tinymce_pilot.html';
    const TINY_MCE_LANG_DEFAULT = 'en';

    /**
     * ilPilotTinyMCE constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->version = self::TINY_MCE_VERSION;
    }

    /**
     * @return string
     */
    protected function getTinyMceLibPath()
    {
        return 'Services/RTE/tiny_mce_'.str_replace('.', '_', $this->version);
    }

    /**
     * @return string
     */
    protected function getTinyMceLibJs()
    {
        return $this->getTinyMceLibPath().'/tinymce.min.js';
    }

    /**
     * @param $languageCode
     * @return string
     */
    protected function getTinyMceLangJs($languageCode)
    {
        return $this->getTinyMceLibPath()."/langs/{$languageCode}.js";
    }

    /**
     * @return ilTemplate
     */
    protected function getTemplate()
    {
        $tpl = new ilTemplate(self::TINY_MCE_TEMPLATE, true, true, 'Services/RTE');

        return $tpl;
    }

    /**
     * @return bool
     */
    protected function isRteActive()
    {
        return ( ilObjAdvancedEditing::_getRichTextEditorUserState()
            && ilObjAdvancedEditing::_getRichTextEditorUserState()
        );
    }

    protected function handleRteActivationUserPreference()
    {
        if ($this->browser->isMobile())
        {
            ilObjAdvancedEditing::_setRichTextEditorUserState(0);
        }
        else
        {
            ilObjAdvancedEditing::_setRichTextEditorUserState(1);
        }
    }

    protected function getEditorLanguageCode()
    {
        $languageCode = $this->user->getLanguage();

        if( !file_exists($this->getTinyMceLangJs($languageCode)) )
        {
            $languageCode = self::TINY_MCE_LANG_DEFAULT;
        }

        return $languageCode;
    }

    /**
     * {@inheritdoc}
     */
    public function enableRteSupport($objId, $objType, $editorId)
    {
        $this->handleRteActivationUserPreference();

        if( !$this->isRteActive() )
        {
            return false;
        }

        $tpl = $this->getTemplate();

        $this->handleImgContextMenuItem($tpl);
        $tags = ilObjAdvancedEditing::_getUsedHTMLTags($a_module);
        $this->handleImagePluginsBeforeRendering($tags);
        if ($allowFormElements) {
            $tpl->touchBlock("formelements");
        }
        if ($this->getInitialWidth() !== null && $tpl->blockExists('initial_width')) {
            $tpl->setCurrentBlock("initial_width");
            $tpl->setVariable('INITIAL_WIDTH', $this->getInitialWidth());
            $tpl->parseCurrentBlock();
        }
        $tpl->setCurrentBlock("tinymce");
        $tpl->setVariable("JAVASCRIPT_LOCATION", $this->getTinyMceLibJs());
        include_once "./Services/Object/classes/class.ilObject.php";
        $tpl->setVariable("OBJ_ID", $objId);
        $tpl->setVariable("OBJ_TYPE", $objType);
        $tpl->setVariable("CLIENT_ID", CLIENT_ID);
        $tpl->setVariable("SESSION_ID", $_COOKIE[session_name()]);
        $tpl->setVariable("EDITOR_SELECTOR", $editorId);
        $tpl->setVariable("BLOCKFORMATS", $this->_buildAdvancedBlockformatsFromHTMLTags($tags));
        $tpl->setVariable("VALID_ELEMENTS", $this->_getValidElementsFromHTMLTags($tags));

        $buttons_1 = $this->_buildAdvancedButtonsFromHTMLTags(1, $tags);
        $buttons_2 = $this->_buildAdvancedButtonsFromHTMLTags(2, $tags)
            . ',' . $this->_buildAdvancedTableButtonsFromHTMLTags($tags)
            . ($this->getStyleSelect() ? ',styleselect' : '');
        $buttons_3 = $this->_buildAdvancedButtonsFromHTMLTags(3, $tags);
        $tpl->setVariable('BUTTONS_1', self::removeRedundantSeparators($buttons_1));
        $tpl->setVariable('BUTTONS_2', self::removeRedundantSeparators($buttons_2));
        $tpl->setVariable('BUTTONS_3', self::removeRedundantSeparators($buttons_3));

        $tpl->setVariable("ADDITIONAL_PLUGINS", join(",", $this->plugins));
        include_once "./Services/Utilities/classes/class.ilUtil.php";
        //$tpl->setVariable("STYLESHEET_LOCATION", $this->getContentCSS());
        $tpl->setVariable("STYLESHEET_LOCATION", ilUtil::getNewContentStyleSheetLocation() . "," . ilUtil::getStyleSheetLocation("output", "delos.css"));
        $tpl->setVariable("LANG", $this->_getEditorLanguage());

        if ($this->getRTERootBlockElement() !== null) {
            $tpl->setVariable('FORCED_ROOT_BLOCK', $this->getRTERootBlockElement());
        }

        $tpl->parseCurrentBlock();

        $this->tpl->setVariable("CONTENT_BLOCK", $tpl->get());

        return true;
    }
}
