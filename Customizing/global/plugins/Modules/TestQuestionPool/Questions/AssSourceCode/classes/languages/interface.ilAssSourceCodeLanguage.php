<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Created by PhpStorm.
 * User: bheyser
 * Date: 23.01.17
 * Time: 09:44
 */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
interface ilAssSourceCodeLanguage
{
	/**
	 * @return string $identifier
	 */
	public function getIdentifier();
	
	/**
	 * @return array $fileExtension
	 */
	public function getFileExtensions();
	
	/**
	 * @return string $webIdeMode
	 */
	public function getWebIdeMode();
	
	/**
	 * @param ilPlugin $plugin
	 * @return string $presentationLabel
	 */
	public function getPresentationLabel(ilPlugin $plugin);
}