<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once 'Services/PDFGeneration/classes/class.ilHtmlToPdfTransformerFactory.php';
/**
 * Class ilTestPDFGenerator
 *
 * Class that handles PDF generation for test and assessment.
 *
 * @author Maximilian Becker <mbecker@databay.de>
 * @version $Id$
 *
 */
class ilTestPDFGenerator
{
	const PDF_OUTPUT_DOWNLOAD = 'D';
	const PDF_OUTPUT_INLINE = 'I';
	const PDF_OUTPUT_FILE = 'F';

	/**
	 * @param $html
	 * @return string
	 */
	private static function removeScriptElements($html)
	{
		if(!is_string($html) || !strlen(trim($html)))
		{
			return $html;
		}

		$dom = new DOMDocument("1.0", "utf-8");
		if(!@$dom->loadHTML('<?xml encoding="UTF-8">' . $html))
		{
			return $html;
		}

		$invalid_elements = array();

		#$script_elements     = $dom->getElementsByTagName('script');
		#foreach($script_elements as $elm)
		#{
		#	$invalid_elements[] = $elm;
		#}

		foreach($invalid_elements as $elm)
		{
			$elm->parentNode->removeChild($elm);
		}

		$dom->encoding = 'UTF-8';
		$cleaned_html = $dom->saveHTML();
		if(!$cleaned_html)
		{
			return $html;
		}

		return $cleaned_html;
	}

	public static function generatePDF($pdf_output, $output_mode, $filename=null)
	{
		$pdf_output = self::preprocessHTML($pdf_output);
		
		if (substr($filename, strlen($filename) - 4, 4) != '.pdf')
		{
			$filename .= '.pdf';
		}
		
		$pdf_factory = new ilHtmlToPdfTransformerFactory();
		$pdf_factory->deliverPDFFromHTMLString($pdf_output, $filename, $output_mode);
	}
	
	public static function preprocessHTML($html)
	{
		$html = self::removeScriptElements($html);
		$pdf_css_path = self::getTemplatePath('test_pdf.css');
		$mathJaxSetting = new ilSetting("MathJax");
		$mathjax = '';
		if($mathJaxSetting->get("enable") == 1)
		{
			$mathjax = '<script type="text/javascript" src="' . $mathJaxSetting->get("path_to_mathjax") . '"></script>';
			$mathjax .= '<script>MathJax.Hub.Config({messageStyle: "none",tex2jax: {preview: "none"}});</script>';
		}

		return $mathjax . '<style>' . file_get_contents($pdf_css_path)	. '</style>' . $html;
	}

	protected static function getTemplatePath($a_filename)
	{
		$module_path = "Modules/Test/";

		// use ilStyleDefinition instead of account to get the current skin
		include_once "Services/Style/classes/class.ilStyleDefinition.php";
		if (ilStyleDefinition::getCurrentSkin() != "default")
		{
			$fname = "./Customizing/global/skin/".
				ilStyleDefinition::getCurrentSkin()."/".$module_path.basename($a_filename);
		}

		if($fname == "" || !file_exists($fname))
		{
			$fname = "./".$module_path."templates/default/".basename($a_filename);
		}
		return $fname;
	}

}