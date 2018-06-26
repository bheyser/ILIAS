<?php

include_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";

/**
 * Date: 16.01.13
 * Time: 10:37
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 */
class ilassGraphicalAssignmentQuestionPlugin extends ilQuestionsPlugin
{
	private static $PLUGIN_NAME = "assGraphicalAssignmentQuestion";
	private static $PLUGIN_ID = "grasqst";

	/**
	 * Static function for returning the QuestionTypeName
	 *
	 * @static
	 * @return string
	 */
	public static function getName()
	{
		return self::$PLUGIN_NAME;
	}

	/**
	 * Get the ID for the Plugin
	 *
	 * @static
	 * @return string
	 */
	public static function getPluginId()
	{
		return self::$PLUGIN_ID;
	}

	/**
	 * Get the directory location of the plugin the the ilias structur
	 *
	 * @static
	 * @return string
	 */
	final static function getLocation(){
		return "./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assGraphicalAssignmentQuestion/" ;
	}

	/**
	 * Get the pluginname
	 *
	 * @return string
	 */
	final function getPluginName()
	{
		return self::$PLUGIN_NAME;
	}

	/**
	 * Get the question type
	 *
	 * @return string
	 */
	final function getQuestionType()
	{
		return self::$PLUGIN_NAME;
	}

	/**
	 * Get the translated name of the question type
	 *
	 * @return string
	 */
	final function getQuestionTypeTranslation()
	{
		return $this->txt(self::$PLUGIN_NAME);
	}
}
