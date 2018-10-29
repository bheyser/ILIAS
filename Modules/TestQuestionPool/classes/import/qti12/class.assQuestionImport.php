<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Class for question imports
*
* assQuestionImport is a basis class question imports
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
*/
class assQuestionImport
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
	* assQuestionImport constructor
	*
	* @param object $a_object The question object
	* @access public
	*/
	public function __construct($a_object)
	{
		$this->object = $a_object;
	}

	function getFeedbackGeneric($item)
	{
		$feedbacksgeneric = array();
		foreach ($item->resprocessing as $resprocessing)
		{
			foreach ($resprocessing->respcondition as $respcondition)
			{
				foreach ($respcondition->displayfeedback as $feedbackpointer)
				{
					if (strlen($feedbackpointer->getLinkrefid()))
					{
						foreach ($item->itemfeedback as $ifb)
						{
							if (strcmp($ifb->getIdent(), "response_allcorrect") == 0)
							{
								// found a feedback for the identifier
								if (count($ifb->material))
								{
									foreach ($ifb->material as $material)
									{
										$feedbacksgeneric[1] = $material;
									}
								}
								if ((count($ifb->flow_mat) > 0))
								{
									foreach ($ifb->flow_mat as $fmat)
									{
										if (count($fmat->material))
										{
											foreach ($fmat->material as $material)
											{
												$feedbacksgeneric[1] = $material;
											}
										}
									}
								}
							}
							else if (strcmp($ifb->getIdent(), "response_onenotcorrect") == 0)
							{
								// found a feedback for the identifier
								if (count($ifb->material))
								{
									foreach ($ifb->material as $material)
									{
										$feedbacksgeneric[0] = $material;
									}
								}
								if ((count($ifb->flow_mat) > 0))
								{
									foreach ($ifb->flow_mat as $fmat)
									{
										if (count($fmat->material))
										{
											foreach ($fmat->material as $material)
											{
												$feedbacksgeneric[0] = $material;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		// handle the import of media objects in XHTML code
		foreach ($feedbacksgeneric as $correctness => $material)
		{
			$m = $this->object->QTIMaterialToString($material);
			$feedbacksgeneric[$correctness] = $m;
		}
		return $feedbacksgeneric;
	}

	/**
	 * @param ilQTIItem $item
	 */
	protected function getFeedbackAnswerSpecific(ilQTIItem $item)
	{
		$feedbacks = array();

		foreach ($item->itemfeedback as $ifb)
		{
			if( substr($ifb->getIdent(), 0, strlen('response_')) != 'response_' )
			{
				continue;
			}

			$ident = $ifb->getIdent();

			// found a feedback for the identifier

			if (count($ifb->material))
			{
				foreach ($ifb->material as $material)
				{
					$feedbacks[$ident] = $material;
				}
			}

			if ((count($ifb->flow_mat) > 0))
			{
				foreach ($ifb->flow_mat as $fmat)
				{
					if (count($fmat->material))
					{
						foreach ($fmat->material as $material)
						{
							$feedbacks[$ident] = $material;
						}
					}
				}
			}
		}

		foreach($feedbacks as $ident => $material)
		{
			$m = $this->object->QTIMaterialToString($material);
			$feedbacks[$ident] = $m;
		}

		return $feedbacks;
	}

	/**
	* Creates a question from a QTI file
	*
	* Receives parameters from a QTI parser and creates a valid ILIAS question object
	*
	* @param object $item The QTI item object
	* @param integer $questionpool_id The id of the parent questionpool
	* @param integer $tst_id The id of the parent test if the question is part of a test
	* @param object $tst_object A reference to the parent test object
	* @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	* @param array $import_mapping An array containing references to included ILIAS objects
	* @access public
	*/
	function fromXML(&$item, $questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
	}

	/**
	 * @param ilQTIItem $item
	 */
	protected function addGeneralMetadata(ilQTIItem $item)
	{
		$this->object->setExternalId($item->getMetadataEntry('externalID'));
	}

	/**
	 * returns the full path to extracted qpl import archiv (qpl import dir + qpl archiv subdir)
	 */
	protected function getQplImportArchivDirectory()
	{
		include_once "./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php";
		return ilObjQuestionPool::_getImportDirectory() . '/' . $_SESSION["qpl_import_subdir"];
	}

	/**
	 * returns the full path to extracted tst import archiv (tst import dir + tst archiv subdir)
	 */
	protected function getTstImportArchivDirectory()
	{
		include_once "./Modules/Test/classes/class.ilObjTest.php";
		return ilObjTest::_getImportDirectory() . '/' . $_SESSION["tst_import_subdir"];
	}

	protected function processNonAbstractedImageReferences($text, $sourceNic)
	{
		$reg = '/<img.*src=".*\\/mm_(\\d+)\\/(.*?)".*>/m';
		$matches = null;

		if( preg_match_all($reg, $text, $matches) )
		{
			for($i = 0, $max = count($matches[1]); $i < $max; $i++)
			{
				$mobSrcId = $matches[1][$i];
				$mobSrcName = $matches[2][$i];
				$mobSrcLabel = 'il_'.$sourceNic.'_mob_'.$mobSrcId;

				if (!is_array($_SESSION["import_mob_xhtml"]))
				{
					$_SESSION["import_mob_xhtml"] = array();
				}

				$_SESSION["import_mob_xhtml"][] = array(
					"mob" => $mobSrcLabel, "uri" => 'objects/'.$mobSrcLabel.'/'.$mobSrcName
				);
			}
		}

		include_once "./Services/RTE/classes/class.ilRTE.php";
		return ilRTE::_replaceMediaObjectImageSrc($text, 0, $sourceNic);
	}

	/**
	 * fetches the "additional content editing mode" information from qti item
	 * and falls back to ADDITIONAL_CONTENT_EDITING_MODE_DEFAULT when no or invalid information is given
	 *
	 * @final
	 * @access protected
	 * @param type $qtiItem
	 * @return string $additionalContentEditingMode
	 */
	final protected function fetchAdditionalContentEditingModeInformation($qtiItem)
	{
		$additionalContentEditingMode = $qtiItem->getMetadataEntry('additional_cont_edit_mode');

		if( !$this->object->isValidAdditionalContentEditingMode($additionalContentEditingMode) )
		{
			$additionalContentEditingMode = assQuestion::ADDITIONAL_CONTENT_EDITING_MODE_DEFAULT;
		}

		return $additionalContentEditingMode;
	}

	//auding-patch: start
	public function importAudingData($id,$qtiItem)
	{
		$auding_id="";
		$auding_inst_id="";
		$auding_name="";
		$auding_activate="";
		$auding_mode="";
		$auding_nr_of_sends="";
		foreach($qtiItem->itemmetadata as $metadata)
		{
			if($metadata['label'] == 'CANVAS_SIZE')
			{
				$this->object->setCanvasSize($metadata["entry"]);
			}
			else if($metadata['label'] == 'AUDINGOLDID')
			{
				$auding_id=$metadata["entry"];
			}
			else if($metadata['label'] == 'AUDININSTID')
			{
				$auding_inst_id=$metadata["entry"];
			}
			else if($metadata['label'] == 'AUDINGFILE')
			{
				$auding_name=$metadata["entry"];
			}
			else if($metadata['label'] == 'AUDINGACTIVATE' && $auding_name!="")
			{
				$auding_activate=$metadata["entry"];
			}
			else if($metadata['label'] == 'AUDINGMODE' && $auding_name!="")
			{
				$auding_mode=$metadata["entry"];
			}
			else if($metadata['label'] == 'AUDINGNROFSENDS' && $auding_name!="")
			{
				$auding_nr_of_sends=$metadata["entry"];
			}
		}
		if($auding_name != "")
		{
			$audingpath = ilUtil::getDataDir() . "/assessment_auding/".(int) $id."/auding/";
			if(!file_exists($audingpath))
			{
				include_once "./Services/Utilities/classes/class.ilUtil.php";
				ilUtil::makeDirParents($audingpath);
			}
			$import_dir="";
			$import_dir_question=ilObjQuestionPool::_getImportDirectory();
			$import_dir_test=ilObjTest::_getImportDirectory();
			if(file_exists($import_dir_question))
			{
				$import_dir=$import_dir_question;
			}
			else if(file_exists($import_dir_test))
			{
				$import_dir=$import_dir_test;
			}
			if(file_exists($import_dir) && $import_dir != "")
			{
				$scanned_directory = array_diff(scandir($import_dir), array('..', '.'));
				foreach($scanned_directory as $dir)
				{
					$file_path = $import_dir . "/" . $dir . "/objects/il_".$auding_inst_id."_auding_".$auding_id;
					$file_name = $file_path . "/" . $auding_name;
					if(file_exists($file_name))
					{
						ilUtil::rCopy($file_path, $audingpath);
					}
				}
				$this->object->setAudingActivate($auding_activate);
				$this->object->setAudingMode($auding_mode);
				$this->object->setAudingNrOfSends($auding_nr_of_sends);
				$this->object->setAudingFile($auding_name);
			}
		}
	}
	//auding-patch: end
	
	// PATCH BEGIN: testtransfer
	/**
	 * @param $item
	 * @param $tst_id
	 * @param $tst_object
	 * @param $question_counter
	 * @param $import_mapping
	 */
	protected function handleMappingAndDuplication(&$item, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		if( $tst_id > 0 )
		{
			$q_1_id = $this->object->getId();
			$question_id = $this->object->duplicate(true, null, null, null, $tst_id);
			$tst_object->questions[$question_counter++] = $question_id;
			$import_mapping[$item->getIdent()] = array("pool" => $q_1_id, "test" => $question_id);
		}
		elseif( $tst_object !== null )
		{
			$tst_object->questions[$question_counter++] = $this->object->getId();
			$import_mapping[$item->getIdent()] = array("pool" => 0, "test" => $this->object->getId());
		}
		else
		{
			$import_mapping[$item->getIdent()] = array("pool" => $this->object->getId(), "test" => 0);
		}
	}
	// END PATCH: testtransfer
}

?>
