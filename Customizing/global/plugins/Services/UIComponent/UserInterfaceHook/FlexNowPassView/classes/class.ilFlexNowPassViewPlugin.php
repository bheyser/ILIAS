<?php
require_once ("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

class ilFlexNowPassViewPlugin extends ilUserInterfaceHookPlugin
{
	/**
	 * @return string
	 */
	final public function getPluginName()
	{
		return "FlexNowPassView";
	}
}

?>
