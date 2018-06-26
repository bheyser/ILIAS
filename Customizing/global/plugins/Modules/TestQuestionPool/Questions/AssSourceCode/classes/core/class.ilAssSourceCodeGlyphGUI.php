<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php';

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Plugins/AssSourceCode
 */
class ilAssSourceCodeGlyphGUI extends ilGlyphGUI
{
	const HELP = 'help';
	const SAVE = 'save';
	const UPLOAD = 'upload';
	
	const GLYPH_CONTAINER_CLASS = 'sr-only';
	
	/**
	 * @var array
	 */
	private static $extMap = array(
		
		self::HELP => array(
			'class' => 'glyphicon glyphicon-question-sign',
			'container' => self::GLYPH_CONTAINER_CLASS,
			'txt' => self::HELP
		),
		
		self::SAVE => array(
			'class' => 'glyphicon glyphicon-floppy-disk',
			'container' => self::GLYPH_CONTAINER_CLASS,
			'txt' => self::SAVE
		)
		
	);
	
	const MASK_GLYPH_HTMLSNIPPET = '<span class="%s">%s</span><span class="%s"></span>';
	const MASK_GLYPH_FALLBACK = '=%s=';

	/**
	 * @param string $a_glyph
	 * @param string $a_text
	 * @return string
	 */
	public static function get($a_glyph, $a_text = "")
	{
		if( self::coreImplementationAvailable($a_glyph) )
		{
			return self::getCoreImplementation($a_glyph, $a_text);
		}
		
		if( self::localImplementationAvailable($a_glyph) )
		{
			return self::getLocalImplementation($a_glyph, $a_text);
		}
		
		return self::getFallbackGlyph($a_glyph);
	}
	
	/**
	 * @param $a_glyph
	 * @return bool
	 */
	protected static function coreImplementationAvailable($a_glyph)
	{
		return isset( parent::$map[$a_glyph] );
	}
	
	/**
	 * @param $a_glyph
	 * @param $a_text
	 * @return string
	 */
	protected static function getCoreImplementation($a_glyph, $a_text)
	{
		return parent::get($a_glyph, $a_text);
	}
	
	/**
	 * @param $a_glyph
	 * @return bool
	 */
	protected static function localImplementationAvailable($a_glyph)
	{
		return isset( self::$extMap[$a_glyph] );
	}
	
	/**
	 * @param $a_glyph
	 * @param $a_text
	 * @return string
	 */
	protected static function getLocalImplementation($a_glyph, $a_text)
	{
		return sprintf(
			self::MASK_GLYPH_HTMLSNIPPET,
			self::GLYPH_CONTAINER_CLASS,
			ilAssSourceCodePlugin::getInstance()->txt(self::$extMap[$a_glyph]['txt']),
			self::$extMap[$a_glyph]['class']
		);
	}
	
	/**
	 * @return string
	 */
	protected static function getFallbackGlyph($a_glyph)
	{
		return sprintf(self::MASK_GLYPH_FALLBACK, $a_glyph);
	}
}