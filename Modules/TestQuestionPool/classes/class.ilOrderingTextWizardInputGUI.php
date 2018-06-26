<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * // uni-goettingen-patch
 * 
 * 
* This class represents a text wizard property in a property form.
*
* @author Helmut Schottmüller <ilias@aurealis.de> 
* @version $Id$
* @ingroup	ServicesForm
*/
class ilOrderingTextWizardInputGUI extends ilTextInputGUI
{
	protected $values = array();
	protected $allowMove = false;

	protected $disable_text = false;
	protected $disable_actions = false;

	/**
	* Constructor
	*
	* @param	string	$a_title	Title
	* @param	string	$a_postvar	Post Variable
	*/
	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
		$this->validationRegexp = "";
	}

	/**
	 * @param boolean $disable_actions
	 */
	public function setDisableActions($disable_actions)
	{
		$this->disable_actions = $disable_actions;
	}

	/**
	 * @param boolean $disable_text
	 */
	public function setDisableText($disable_text)
	{
		$this->disable_text = $disable_text;
	}

	/**
	* Set Values
	*
	* @param	array	$a_value	Value
	*/
	function setValues($a_values)
	{
		$this->values = $a_values;
	}
	
	/**
	* Set Value
	*
	* @param	array	$a_value	Value
	*/
	function setValue($a_value)
	{
		$this->values = $a_value;
	}

	/**
	* Get Values
	*
	* @return	array	Values
	*/
	function getValues()
	{
		return $this->values;
	}

	/**
	* Set allow move
	*
	* @param	boolean	$a_allow_move Allow move
	*/
	function setAllowMove($a_allow_move)
	{
		$this->allowMove = $a_allow_move;
	}

	/**
	* Get allow move
	*
	* @return	boolean	Allow move
	*/
	function getAllowMove()
	{
		return $this->allowMove;
	}

	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		global $lng;
		
		$foundvalues = $_POST[$this->getPostVar()];
		if (is_array($foundvalues))
		{
			foreach ($foundvalues as $idx => $value)
			{
				$_POST[$this->getPostVar()][$idx] = ilUtil::stripSlashes($value);
				if ($this->getRequired() && trim($value) == "")
				{
					$this->setAlert($lng->txt("msg_input_is_required"));

					return false;
				}
				else if (strlen($this->getValidationRegexp()))
				{
					if (!preg_match($this->getValidationRegexp(), $value))
					{
						$this->setAlert($lng->txt("msg_wrong_format"));
						return FALSE;
					}
				}
			}
		}
		else
		{
			$this->setAlert($lng->txt("msg_input_is_required"));
			return FALSE;
		}
		
		return $this->checkSubItemsInput();
	}

	/**
	* Insert property html
	*
	* @return	int	Size
	*/
	function insert($a_tpl)
	{
		$tpl = new ilTemplate("tpl.prop_textwizardinput.html", true, true, "Modules/TestQuestionPool");
		$i = 0;
		foreach ($this->values as $value)
		{
			if (strlen($value))
			{
				$tpl->setCurrentBlock("prop_text_propval");
				$tpl->setVariable("PROPERTY_VALUE", ilUtil::prepareFormOutput($value));
				$tpl->parseCurrentBlock();
			}

			if ($this->getAllowMove())
			{
				$tpl->setCurrentBlock("move");
				$tpl->setVariable("MOVE_ID", $this->getFieldId() . "[$i]");
				$tpl->setVariable("CMD_UP", "cmd[up" . $this->getFieldId() . "][$i]");
				$tpl->setVariable("CMD_DOWN", "cmd[down" . $this->getFieldId() . "][$i]");
				$tpl->setVariable("ID", $this->getFieldId() . "[$i]");
				include_once("./Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php");
				$tpl->setVariable("UP_BUTTON", ilGlyphGUI::get(ilGlyphGUI::UP));
				$tpl->setVariable("DOWN_BUTTON", ilGlyphGUI::get(ilGlyphGUI::DOWN));
				$tpl->parseCurrentBlock();
			}
			$tpl->setCurrentBlock("row");
			$class = ($i % 2 == 0) ? "even" : "odd";
			if ($i == 0) $class .= " first";
			if ($i == count($this->values)-1) $class .= " last";
			$tpl->setVariable("ROW_CLASS", $class);
			$tpl->setVariable("POST_VAR", $this->getPostVar() . "[$i]");
			$tpl->setVariable("ID", $this->getFieldId() . "[$i]");
			$tpl->setVariable("SIZE", $this->getSize());
			$tpl->setVariable("MAXLENGTH", $this->getMaxLength());

			if($this->getDisabled())
			{
				$tpl->setVariable("DISABLED", " disabled=\"disabled\"");
			}

			if(!$this->disable_actions)
			{
				$tpl->setVariable("ID_ADD_BUTTON", $this->getFieldId() . "[$i]");
				$tpl->setVariable("ID_REMOVE_BUTTON", $this->getFieldId() . "[$i]");
				$tpl->setVariable("CMD_ADD", "cmd[add" . $this->getFieldId() . "][$i]");
				$tpl->setVariable("CMD_REMOVE", "cmd[remove" . $this->getFieldId() . "][$i]");
				include_once("./Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php");
				$tpl->setVariable("ADD_BUTTON", ilGlyphGUI::get(ilGlyphGUI::ADD));
				$tpl->setVariable("REMOVE_BUTTON", ilGlyphGUI::get(ilGlyphGUI::REMOVE));
			}

			if($this->disable_text)
			{
				$tpl->setVariable('DISABLED_TEXT', ' readonly="readonly"');
			}

			$tpl->parseCurrentBlock();
			$i++;
		}
		$tpl->setVariable("ELEMENT_ID", $this->getFieldId());

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $tpl->get());
		$a_tpl->parseCurrentBlock();
		
		global $tpl;
		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDomEvent();
		$tpl->addJavascript("./Modules/TestQuestionPool/js/ServiceFormWizardInput.js");
		$tpl->addJavascript("./Services/Form/templates/default/textwizard.js");
	}
}
