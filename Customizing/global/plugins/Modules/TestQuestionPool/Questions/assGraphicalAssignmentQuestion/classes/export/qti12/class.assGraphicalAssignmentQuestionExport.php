<?php

include_once "./Modules/TestQuestionPool/classes/export/qti12/class.assQuestionExport.php";

/**
 * Date: 19.02.13
 * Time: 12:44
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 */
class assGraphicalAssignmentQuestionExport extends assQuestionExport
{
	/**
	 * Returns a QTI xml representation of the question
	 *
	 * Returns a QTI xml representation of the question and sets the internal
	 * domxml variable with the DOM XML representation of the QTI xml representation
	 *
	 * @return string The QTI xml representation of the question
	 */
	public function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		global $ilias;

		include_once "./Services/Xml/classes/class.ilXmlWriter.php";

		$xml_writer = new ilXmlWriter();
		// Set xml Header
		$xml_writer->xmlHeader();

		// Set questestinterop
		$xml_writer->xmlStartTag("questestinterop");
		$attributes = array(
			"ident" => "il_".IL_INST_ID."_qst_".$this->object->getId(),
			"title" => $this->object->getTitle(),
			"maxattempts" => $this->object->getNrOfTries()
		);
		$xml_writer->xmlStartTag("item", $attributes);

		// set question description
		$xml_writer->xmlElement('qticomment', null, $this->object->getComment());

		// set estimated working time
		$workingtime = $this->object->getEstimatedWorkingTime();
		$duration = sprintf("P0Y0M0DT%dH%dM%dS", $workingtime["h"], $workingtime["m"], $workingtime["s"]);
		$xml_writer->xmlElement("duration", NULL, $duration);

		// set ILIAS metadata
		$xml_writer->xmlStartTag("itemmetadata");
		$xml_writer->xmlStartTag("qtimetadata");
		$xml_writer->xmlStartTag("qtimetadatafield");
		$xml_writer->xmlElement("fieldlabel", NULL, "ILIAS_VERSION");
		$xml_writer->xmlElement("fieldentry", NULL, $ilias->getSetting("ilias_version"));
		$xml_writer->xmlEndTag("qtimetadatafield");
		$xml_writer->xmlStartTag("qtimetadatafield");
		$xml_writer->xmlElement("fieldlabel", NULL, "QUESTIONTYPE");
		$xml_writer->xmlElement("fieldentry", NULL, $this->object->getQuestionType());
		$xml_writer->xmlEndTag("qtimetadatafield");
		$xml_writer->xmlStartTag("qtimetadatafield");
		$xml_writer->xmlElement("fieldlabel", NULL, "CANVAS_SIZE");
		$xml_writer->xmlElement("fieldentry", NULL, $this->object->getCanvasSize());
		$xml_writer->xmlEndTag("qtimetadatafield");
		$xml_writer->xmlStartTag("qtimetadatafield");
		$xml_writer->xmlElement("fieldlabel", NULL, "COLOR");
		$xml_writer->xmlElement("fieldentry", NULL, $this->object->getColor());
		$xml_writer->xmlEndTag("qtimetadatafield");
		$xml_writer->xmlStartTag("qtimetadatafield");
		$xml_writer->xmlElement("fieldlabel", NULL, "AUTHOR");
		$xml_writer->xmlElement("fieldentry", NULL, $this->object->getAuthor());
		$xml_writer->xmlEndTag("qtimetadatafield");
		//auding-patch: start
		$this->addAudingData($xml_writer);
		//auding-patch: end
		$xml_writer->xmlEndTag("qtimetadata");
		$xml_writer->xmlEndTag("itemmetadata");

		// PART I: qti presentation
		$attributes = array(
			'label' => $this->object->getTitle()
		);
		$xml_writer->xmlStartTag("presentation", $attributes);
		// set flow to presentation
		$xml_writer->xmlStartTag("flow");
		// set matirial with question text to presentation
		$this->object->addQTIMaterial($xml_writer, $this->object->getQuestion());

		// set answers to presentation
		$attributes = array(
			"ident" => "MCMR",
			"rcardinality" => "Multiple"
		);
		$xml_writer->xmlStartTag("response_num", $attributes);
		$solution = $this->object->getSuggestedSolution(0);

		if(count($solution))
		{
			if (preg_match("/il_(\d*?)_(\w+)_(\d+)/", $solution["internal_link"], $matches))
			{
				$xml_writer->xmlStartTag("material");
				$intlink = "il_" . IL_INST_ID . "_" . $matches[2] . "_" . $matches[3];
				if (strcmp($matches[1], "") != 0)
				{
					$intlink = $solution["internal_link"];
				}
				$attributes = array(
					"label" => "suggested_solution"
				);
				$xml_writer->xmlElement("mattext", $attributes, $intlink);
				$xml_writer->xmlEndTag("material");
			}
		}

		
		$xml_writer->xmlStartTag("render_hotspot");
		$xml_writer->xmlStartTag("material");

		$imagetype = "image/jpeg";
		if (preg_match("/.*\.(png|gif)$/", $this->object->getImage(), $matches))
		{
			$imagetype = "image/" . $matches[1];
		}
		$attributes = array(
			"imagtype" => $imagetype,
			"label" => $this->object->getImage()
		);

		if($a_include_binary)
		{
			if($force_image_references)
			{
				$attributes["uri"] = $this->object->getImagePathWeb() . $this->object->getImage();
				$xml_writer->xmlElement("matimage", $attributes);
			}
			else
			{
				$attributes["embedded"] = "base64";
				$imagepath = $this->object->getImagePath() . $this->object->getImage();
				$fh = fopen($imagepath, "rb");
				if($fh == false)
				{
					global $ilErr;
					$ilErr->raiseError($this->object->lng->txt("error_open_image_file"), $ilErr->MESSAGE);
					return false;
				}
				$imagefile = fread($fh, filesize($imagepath));
				fclose($fh);
				$base64 = base64_encode($imagefile);
				$xml_writer->xmlElement("matimage", $attributes, $base64, false, false);
			}
		}
		else
		{
			$xml_writer->xmlElement("matimage", $attributes);
		}
		$xml_writer->xmlEndTag("material");

		// add answers
		foreach($this->object->getAnswers() as $answer_key => $answer)
		{
			$attributes = array(
				"ident" => $answer_key,
				"match_group" => $answer->getType(),
				"rshuffle" => ($answer->getShuffle()) ? "Yes" : "No"
			);
			$xml_writer->xmlStartTag("response_label", $attributes);

			$xml_writer->xmlData($answer->getCoords());

			$xml_writer->xmlStartTag("material");
			foreach($answer->getItems() as $item_key => $item)
			{
				$attributes = array(
					"ident" => $item_key
				);
				$xml_writer->xmlElement("mattext", $attributes, $item->getAnswertext());
			}
			$xml_writer->xmlEndTag("material");

			$xml_writer->xmlEndTag("response_label");
		}

		$xml_writer->xmlEndTag("render_hotspot");
		$xml_writer->xmlEndTag("response_num");
		$xml_writer->xmlEndTag("flow");
		$xml_writer->xmlEndTag("presentation");

		// PART II: qti resprocessing
		$xml_writer->xmlStartTag("resprocessing");
		$xml_writer->xmlStartTag("outcomes");
		$xml_writer->xmlStartTag("decvar");
		$xml_writer->xmlEndTag("decvar");
		$xml_writer->xmlEndTag("outcomes");
		// add response conditions
		foreach($this->object->getAnswers() as $answer_index => $answer)
		{
			foreach($answer->getItems() as $item_index => $item)
			{
				$attributes = array(
					"continue" => "yes"
				);
				$xml_writer->xmlStartTag("respcondition", $attributes);
				$xml_writer->xmlStartTag("conditionvar");

				$attributes = array(
					"respident" => "answer_$answer_index",
					"areatype" => $answer->getType()
				);
				$xml_writer->xmlElement("varequal", $attributes, $item->getAnswertext());

				$xml_writer->xmlEndTag("conditionvar");


				// qti setvar
				$attributes = array(
					'action' => "ADD"
				);
				$xml_writer->xmlElement("setvar", $attributes, $item->getPoints());

				$linkrefid = "response_$answer_index";
				$attributes = array(
					"feedbacktype" => "Response",
					"linkrefid" => $linkrefid
				);
				$xml_writer->xmlElement("displayfeedback", $attributes);

				$xml_writer->xmlEndTag("respcondition");
			}
		}

		$feedback_allcorrect = $this->object->getFeedbackGeneric(1);
		if(strlen($feedback_allcorrect))
		{
			$attributes = array(
				"continue" => "Yes"
			);
			$xml_writer->xmlStartTag("respcondition", $attributes);
			// qti conditionvar
			$xml_writer->xmlStartTag("conditionvar");

			foreach($this->object->getAnswers() as $answer_index => $answer)
			{
				$best_item = null;
				$max_points = 0;
				foreach($answer->getItems() as  $item)
				{
					if($item->getPoints() > $max_points)
					{
						$best_item = $item;
					}
				}

				$attributes = array(
					"respident" => "answer_$answer_index",
					"areatype" => $answer->getType()
				);
				$xml_writer->xmlElement("varequal", $attributes, $best_item->getAnswertext());
			}

			$xml_writer->xmlEndTag("conditionvar");
			// qti displayfeedback
			$attributes = array(
				"feedbacktype" => "Response",
				"linkrefid" => "response_allcorrect"
			);
			$xml_writer->xmlElement("displayfeedback", $attributes);
			$xml_writer->xmlEndTag("respcondition");
		}

		$feedback_onenotcorrect = $this->object->getFeedbackGeneric(0);
		if(strlen($feedback_onenotcorrect))
		{
			$attributes = array(
				"continue" => "Yes"
			);
			$xml_writer->xmlStartTag("respcondition", $attributes);
			// qti conditionvar
			$xml_writer->xmlStartTag("conditionvar");

			$counter = 0;
			foreach($this->object->getAnswers() as $answer_index => $answer)
			{
				if($counter > 0)
				{
					$xml_writer->xmlStartTag("or");
				}
				$xml_writer->xmlStartTag("not");

				$best_item = null;
				$max_points = 0;
				foreach($answer->getItems() as  $item)
				{
					if($item->getPoints() > $max_points)
					{
						$best_item = $item;
					}
				}

				$attributes = array(
					"respident" => "answer_$answer_index",
					"areatype" => $answer->getType()
				);
				$xml_writer->xmlElement("varequal", $attributes, $best_item->getAnswertext());

				$xml_writer->xmlEndTag("not");
				if($counter > 0)
				{
					$xml_writer->xmlEndTag("or");
				}

				$counter++;
			}

			$xml_writer->xmlEndTag("conditionvar");
			// qti displayfeedback
			$attributes = array(
				"feedbacktype" => "Response",
				"linkrefid" => "response_onenotcorrect"
			);
			$xml_writer->xmlElement("displayfeedback", $attributes);
			$xml_writer->xmlEndTag("respcondition");
		}


		$xml_writer->xmlEndTag("resprocessing");

		// PART III: qti itemfeedback
		$attributes = array(
			"view" => "ALL"
		);

		foreach($this->object->getAnswers() as $answer_index => $answer)
		{
			$attributes["ident"] = "response_$answer_index";

			$xml_writer->xmlStartTag("itemfeedback", $attributes);
			$xml_writer->xmlStartTag("flow_mat");

			$this->object->addQTIMaterial($xml_writer, $this->object->getFeedbackSingleAnswer($answer_index));

			$xml_writer->xmlEndTag("flow_mat");
			$xml_writer->xmlEndTag("itemfeedback");
		}

		if(strlen($feedback_allcorrect))
		{
			$attributes["ident"] = "response_allcorrect";

			$xml_writer->xmlStartTag("itemfeedback", $attributes);
			$xml_writer->xmlStartTag("flow_mat");

			$this->object->addQTIMaterial($xml_writer, $feedback_allcorrect);

			$xml_writer->xmlEndTag("flow_mat");
			$xml_writer->xmlEndTag("itemfeedback");
		}

		if(strlen($feedback_onenotcorrect))
		{
			$attributes["ident"] = "response_onenotcorrect";

			$xml_writer->xmlStartTag("itemfeedback", $attributes);
			$xml_writer->xmlStartTag("flow_mat");

			$this->object->addQTIMaterial($xml_writer, $feedback_onenotcorrect);

			$xml_writer->xmlEndTag("flow_mat");
			$xml_writer->xmlEndTag("itemfeedback");
		}

		$xml_writer->xmlEndTag("item");
		$xml_writer->xmlEndTag("questestinterop");

		// Render XML
		$xml = $xml_writer->xmlDumpMem(FALSE);
		if (!$a_include_header)
		{
			$pos = strpos($xml, "?>");
			$xml = substr($xml, $pos + 2);
		}

		return $xml;
	}
}
