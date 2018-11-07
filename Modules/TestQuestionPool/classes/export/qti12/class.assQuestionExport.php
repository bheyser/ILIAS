<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Class for question exports
*
* exportQuestion is a basis class question exports
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
*/
class assQuestionExport
{
	/**
	* The question object
	*
	* The question object
	*
	* @var assQuestion
	*/
	var $object;

	/**
	* assQuestionExport constructor
	*
	* @param object $a_object The question object
	* @access public
	*/
	public function __construct($a_object)
	{
		$this->object = $a_object;
	}

	/**
	 * @param ilXmlWriter $a_xml_writer
	 */
	protected function addAnswerSpecificFeedback(ilXmlWriter $a_xml_writer, $answers)
	{
		foreach ($answers as $index => $answer)
		{
			$linkrefid = "response_$index";
			$attrs = array(
				"ident" => $linkrefid,
				"view" => "All"
			);
			$a_xml_writer->xmlStartTag("itemfeedback", $attrs);
			// qti flow_mat
			$a_xml_writer->xmlStartTag("flow_mat");
			$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackExportPresentation(
				$this->object->getId(), $index
			);
			$this->object->addQTIMaterial($a_xml_writer, $fb);
			$a_xml_writer->xmlEndTag("flow_mat");
			$a_xml_writer->xmlEndTag("itemfeedback");
		}
	}

	/**
	 * @param ilXmlWriter $a_xml_writer
	 */
	protected function addGenericFeedback(ilXmlWriter $a_xml_writer)
	{
		$this->exportFeedbackOnly($a_xml_writer);
	}

	function exportFeedbackOnly($a_xml_writer)
	{
		$feedback_allcorrect = $this->object->feedbackOBJ->getGenericFeedbackExportPresentation(
				$this->object->getId(), true
		);
		$feedback_onenotcorrect = $this->object->feedbackOBJ->getGenericFeedbackExportPresentation(
				$this->object->getId(), false
		);
		if (strlen($feedback_allcorrect . $feedback_onenotcorrect))
		{
			$a_xml_writer->xmlStartTag("resprocessing");
			$a_xml_writer->xmlStartTag("outcomes");
			$a_xml_writer->xmlStartTag("decvar");
			$a_xml_writer->xmlEndTag("decvar");
			$a_xml_writer->xmlEndTag("outcomes");

			if (strlen($feedback_allcorrect))
			{
				$attrs = array(
					"continue" => "Yes"
				);
				$a_xml_writer->xmlStartTag("respcondition", $attrs);
				// qti conditionvar
				$a_xml_writer->xmlStartTag("conditionvar");
				$attrs = array(
					"respident" => "points"
				);
				$a_xml_writer->xmlElement("varequal", $attrs, $this->object->getPoints());
				$a_xml_writer->xmlEndTag("conditionvar");
				// qti displayfeedback
				$attrs = array(
					"feedbacktype" => "Response",
					"linkrefid" => "response_allcorrect"
				);
				$a_xml_writer->xmlElement("displayfeedback", $attrs);
				$a_xml_writer->xmlEndTag("respcondition");
			}

			if (strlen($feedback_onenotcorrect))
			{
				$attrs = array(
					"continue" => "Yes"
				);
				$a_xml_writer->xmlStartTag("respcondition", $attrs);
				// qti conditionvar
				$a_xml_writer->xmlStartTag("conditionvar");
				$a_xml_writer->xmlStartTag("not");
				$attrs = array(
					"respident" => "points"
				);
				$a_xml_writer->xmlElement("varequal", $attrs, $this->object->getPoints());
				$a_xml_writer->xmlEndTag("not");
				$a_xml_writer->xmlEndTag("conditionvar");
				// qti displayfeedback
				$attrs = array(
					"feedbacktype" => "Response",
					"linkrefid" => "response_onenotcorrect"
				);
				$a_xml_writer->xmlElement("displayfeedback", $attrs);
				$a_xml_writer->xmlEndTag("respcondition");
			}
			$a_xml_writer->xmlEndTag("resprocessing");
		}

		if (strlen($feedback_allcorrect))
		{
			$attrs = array(
				"ident" => "response_allcorrect",
				"view" => "All"
			);
			$a_xml_writer->xmlStartTag("itemfeedback", $attrs);
			// qti flow_mat
			$a_xml_writer->xmlStartTag("flow_mat");
			$this->object->addQTIMaterial($a_xml_writer, $feedback_allcorrect);
			$a_xml_writer->xmlEndTag("flow_mat");
			$a_xml_writer->xmlEndTag("itemfeedback");
		}
		if (strlen($feedback_onenotcorrect))
		{
			$attrs = array(
				"ident" => "response_onenotcorrect",
				"view" => "All"
			);
			$a_xml_writer->xmlStartTag("itemfeedback", $attrs);
			// qti flow_mat
			$a_xml_writer->xmlStartTag("flow_mat");
			$this->object->addQTIMaterial($a_xml_writer, $feedback_onenotcorrect);
			$a_xml_writer->xmlEndTag("flow_mat");
			$a_xml_writer->xmlEndTag("itemfeedback");
		}
	}

	/**
	* Returns a QTI xml representation of the question
	*
	* Returns a QTI xml representation of the question and sets the internal
	* domxml variable with the DOM XML representation of the QTI xml representation
	*
	* @return string The QTI xml representation of the question
	* @access public
	*/
	function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
	}

	/**
	 * adds a qti meta data field with given name and value to the passed xml writer
	 * (xml writer must be in context of opened "qtimetadata" tag)
	 *
	 * @final
	 * @access protected
	 * @param ilXmlWriter $a_xml_writer
	 * @param string $fieldLabel
	 * @param string $fieldValue
	 */
	final protected function addQtiMetaDataField(ilXmlWriter $a_xml_writer, $fieldLabel, $fieldValue)
	{
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, $fieldLabel);
		$a_xml_writer->xmlElement("fieldentry", NULL, $fieldValue);
		$a_xml_writer->xmlEndTag("qtimetadatafield");
	}

	/**
	 * adds a qti meta data field for ilias specific information of "additional content editing mode"
	 * (xml writer must be in context of opened "qtimetadata" tag)
	 *
	 * @final
	 * @access protected
	 * @param ilXmlWriter $a_xml_writer
	 */
	final protected function addAdditionalContentEditingModeInformation(ilXmlWriter $a_xml_writer)
	{
		$this->addQtiMetaDataField(
			$a_xml_writer, 'additional_cont_edit_mode', $this->object->getAdditionalContentEditingMode()
		);
	}

	/**
	 * @param ilXmlWriter $xmlwriter
	 */
	protected function addGeneralMetadata(ilXmlWriter $xmlwriter)
	{
		$this->addQtiMetaDataField($xmlwriter, 'externalId', $this->object->getExternalId());
		// PATCH BEGIN testtransfer
		$this->populateTransferId($xmlwriter);
		// PATCH END testtransfer
	}
	//auding-patch: start
	protected function addAudingData(ilXmlWriter $a_xml_writer)
	{
		$auding_path =  $this->object->getAudingFilePath()."/".$this->object->getAudingFile();
		$fh = @fopen($auding_path, "rb");
		if($fh == false)
		{
			return;
		}

		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "AUDINGOLDID");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getId());
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "AUDININSTID");
		$a_xml_writer->xmlElement("fieldentry", NULL, IL_INST_ID);
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "AUDINGFILE");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getAudingFile());
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "AUDINGACTIVATE");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getAudingActivate());
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "AUDINGMODE");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getAudingMode());
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "AUDINGNROFSENDS");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getAudingNrOfSends());
		$a_xml_writer->xmlEndTag("qtimetadatafield");
	}
	//auding-patch: end

	// PATCH BEGIN testtransfer
	protected function buildTransferId()
	{
		require_once 'Modules/TestQuestionPool/exceptions/class.ilTestQuestionPoolException.php';
		if( !($this->object->getId() > 0) )
		{
			throw new ilTestQuestionPoolException('could not build transfer id for question without id');
		}

		if( !(IL_INST_ID > 0) )
		{
			throw new ilTestQuestionPoolException(
				'could not build transfer id for question with id "'.$this->object->getId().'" '.
				'because of missing installation registration (no nic id available)'
			);
		}

		$originalQuestionId = (
			$this->object->getOriginalId() ? $this->object->getOriginalId() : 0
		);

		return 'i'.IL_INST_ID.'_q'.$this->object->getId().'_o'.$originalQuestionId;
	}
	protected function populateTransferId(ilXmlWriter $xmlWriter)
	{
		$transferId = $this->object->getTransferId();

		if( $transferId === null )
		{
			$transferId = $this->buildTransferId();
		}

		$this->addQtiMetaDataField($xmlWriter, 'TransferId', $transferId);
	}
	// PATCH END testtransfer
}

?>
