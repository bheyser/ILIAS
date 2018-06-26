<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
class ilAssSourceCodeSolution implements ilFormInputValue
{
	const PRINT_TAB_REPLACEMENT = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	
	protected $content;
	
	public function getPlainContent()
	{
		return $this->content;
	}
	
	public function setPlainContent($content)
	{
		$this->content = $content;
	}
	
	public function getEncodedContent()
	{
		return $this->encode($this->getPlainContent());
	}
	
	public function setEncodedContent($content)
	{
		$this->setPlainContent($this->decode($content));
	}
	
	public function getEncryptedContent()
	{
		return $this->encrypt($this->getPlainContent());
	}
	
	public function setEncryptedContent($content)
	{
		$this->setPlainContent($this->decrypt($content));
	}
	
	public function getPrintableContent()
	{
		$content = $this->getPlainContent();
		$content = $this->escape($content);
		$content = $this->printable($content);
		
		return $content;
	}
	
	protected function printable($content)
	{
		return nl2br(str_replace("\t", self::PRINT_TAB_REPLACEMENT, $content));
	}
	
	protected function escape($content)
	{
		return htmlentities($content);
	}
	
	protected function encode($content)
	{
		return rawurlencode($content);
	}
	
	protected function decode($content)
	{
		return rawurldecode($content);
	}
	
	protected function encrypt($content)
	{
		return base64_encode($content);
	}
	
	protected function decrypt($content)
	{
		return base64_decode($content);
	}
	
	public function setValue($urlEncodedContent)
	{
		$this->setEncodedContent($urlEncodedContent);
	}
	
	public function getValue()
	{
		return $this->getEncodedContent();
	}
	
	public function getPrintableValue()
	{
		return $this->getPrintableContent();
	}
	
	public function getRowsAmount()
	{
		return count(explode("\n", $this->getPlainContent())) + 1;
	}
}