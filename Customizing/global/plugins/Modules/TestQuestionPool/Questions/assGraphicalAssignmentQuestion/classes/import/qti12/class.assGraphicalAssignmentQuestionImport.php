<?php

include_once "./Modules/TestQuestionPool/classes/import/qti12/class.assQuestionImport.php";

/**
 * Date: 21.02.13
 * Time: 10:41
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 */
class assGraphicalAssignmentQuestionImport extends assQuestionImport
{

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
	 *
	 * @global ilUser $ilUser
	 */
	public function fromXML(&$item, $questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		global $ilUser;

		//empty session variable for imported xhtml mobs
		unset($_SESSION["import_mob_xhtml"]);

		$presentation = $item->getPresentation();
		$question_image = array();
		$answers = array();
		foreach($presentation->order as $entry)
		{
			if($entry["type"] == "response")
			{
				$response = $presentation->response[$entry["index"]];
				$rendertype = $response->getRenderType();

				if(strtolower(get_class($rendertype)) == "ilqtirenderhotspot")
				{
					foreach($rendertype->material as $material)
					{
						for($i = 0; $i < $material->getMaterialCount(); $i++)
						{
							$m = $material->getMaterial($i);
							if(strcmp($m["type"], "matimage") == 0)
							{
								$question_image = array(
									"imagetype" => $m["material"]->getImageType(),
									"label" => $m["material"]->getLabel(),
									"content" => $m["material"]->getContent()
								);
							}
						}
					}

						foreach($rendertype->material as $material)
						{
							for($i = 0; $i < $material->getMaterialCount(); $i++)
							{
								$m = $material->getMaterial($i);


							}
						}
					foreach($rendertype->response_labels as $response_label)
					{
						$ident = $response_label->getIdent();
						foreach ($response_label->material as $mat)
						{
							$answers[$ident] = array(
								"answer_type" => $response_label->getMatchGroup(),
								"coordinates" => $response_label->getContent(),
								"shuffle" => $response_label->getRshuffle(),
								"answerorder" => $response_label->getIdent(),
								"correctness" => "1",
								"items" => array()
							);
							foreach($mat->materials as $item_mat)
							{
								$answers[$ident]["items"][] = array(
									"answerhint" => $item_mat["material"]->getContent(),
									"points" => 0,
									"action" => "",
								);
							}
						}
					}
				}
			}
		}

		$feedbacks = array();
		$feedbacksgeneric = array();
		foreach($item->resprocessing as $resprocessing)
		{
			foreach($resprocessing->respcondition as $respcondition)
			{
				$answer_text = "";
				$correctness = 1;
				$conditionvar = $respcondition->getConditionvar();

				foreach($conditionvar->order as $order)
				{
					switch ($order["field"])
					{
						case "arr_not":
							$correctness = 0;
							break;
						case "varinside":
							$answer_text = $conditionvar->varinside[$order["index"]]->getContent();
							break;
						case "varequal":
							$answer_text = $conditionvar->varequal[$order["index"]]->getContent();
							break;
					}
				}

				foreach($respcondition->setvar as $setvar)
				{
					foreach($answers as $ident => $answer)
					{
						foreach($answer["items"] as $item_index => $answer_item)
						{
							if (strcmp($answer_item["answerhint"], $answer_text) == 0)
							{
								if($correctness)
								{
									$answers[$ident]["items"][$item_index]["action"] = $setvar->getAction();
									$answers[$ident]["items"][$item_index]["points"] = $setvar->getContent();

									if(count($respcondition->displayfeedback))
									{
										foreach($respcondition->displayfeedback as $feedbackpointer)
										{
											if(strlen($feedbackpointer->getLinkrefid()))
											{
												foreach($item->itemfeedback as $ifb)
												{
													if(strcmp($ifb->getIdent(), "response_allcorrect") == 0)
													{
														if(count($ifb->material))
														{
															foreach($ifb->material as $material)
															{
																$feedbacksgeneric[1] = $material;
															}
														}
														if(count($ifb->flow_mat) > 0)
														{
															foreach($ifb->flow_mat as $fmat)
															{
																if(count($fmat->material))
																{
																	foreach($fmat->material as $material)
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
													if (strcmp($ifb->getIdent(), $feedbackpointer->getLinkrefid()) == 0)
													{
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
		}

		$this->addGeneralMetadata($item);
		$this->object->setTitle($item->getTitle());
		$this->object->setNrOfTries($item->getMaxattempts());
		$this->object->setComment($item->getComment());
		$this->object->setAuthor($item->getAuthor());

		foreach($item->getMetadata() as $md)
		{
			switch($md['label'])
			{
				case 'CANVAS_SIZE':
					$this->object->setCanvasSize($md['entry']);
					break;

				case 'COLOR':
					$this->object->setColor($md['entry']);
					break;
			}
		}

		$this->object->setOwner($ilUser->getId());
		$this->object->setQuestion($this->object->QTIMaterialToString($item->getQuestiontext()));
		$this->object->setObjId($questionpool_id);
		$duration = $item->getDuration();
		$this->object->setEstimatedWorkingTime($duration["h"], $duration["m"], $duration["s"]);
		$this->object->setImage($question_image["label"]);
		

		foreach($answers as $ident => $answer)
		{
			include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assGraphicalAssignmentQuestion/classes/class.assAnswerGraphicalAssignment.php';
			$answerObj = $this->object->createAnswer(assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT);
			$answerObj->setType($answer['answer_type']);
			$answerObj->setCoords($answer['coordinates']);
			$answerObj->setShuffle(($answer['shuffle'])? 1 : 0);

			foreach($answer["items"] as $order => $answer_item)
			{
				$itemObj = $answerObj->createItem();
				$itemObj->setAnswertext($answer_item['answerhint']);
				$itemObj->setPoints($answer_item["points"]);
				$itemObj->setOrder($order);
				$answerObj->addItem($itemObj);
			}
			$this->object->addAnswer($answerObj);

			$answers[$ident] = $answerObj;
		}

		// additional content editing mode information
		$this->object->setAdditionalContentEditingMode(
			$this->fetchAdditionalContentEditingModeInformation($item)
		);
		$this->object->saveToDb();
		//auding-patch: start
		$this->importAudingData($this->object->getId(), $item);
		//auding-patch: end
		if(count($item->suggested_solutions))
		{
			foreach($item->suggested_solutions as $suggested_solution)
			{
				/** @todo ADD SUGGESTED SOLUTIONS */
			}
			/** @todo SAVE TO DB */
			//$this->object->saveToDb();
		}
		
		$image =& base64_decode($question_image["content"]);
		$imagepath = $this->object->getImagePath();
		if(!file_exists($imagepath))
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			ilUtil::makeDirParents($imagepath);
		}
		$imagepath .= $question_image["label"];
		$fh = fopen($imagepath, "wb");

		fwrite($fh, $image);
		fclose($fh);


		foreach($feedbacks as $ident => $material)
		{
			$m = $this->object->QTIMaterialToString($material);
			$feedbacks[$ident] = $m;
		}

		foreach($feedbacksgeneric as $correctness => $material)
		{
			$m = $this->object->QTIMaterialToString($material);
			$feedbacksgeneric[$correctness] = $m;
		}

		$questiontext = $this->object->getQuestion();
		if(is_array($_SESSION["import_mob_xhtml"]))
		{
			include_once "./Services/MediaObject/classes/class.ilObjMediaObject.php";
			include_once "./Services/RTE/classes/class.ilRTE.php";
			foreach($_SESSION["import_mob_xhtml"] as $mob)
			{
				if($tst_id > 0)
				{
					include_once "./Modules/Test/classes/class.ilObjTest.php";
					$importfile = ilObjTest::_getImportDirectory();
				}
				else
				{
					include_once "./Modules/TestQuestionPool/classes/class.ilQuestionPool.php";
					$importfile = ilObjQuestionPool::_getImportDirectory();
				}

				$media_object =& ilObjMediaObject::_saveTempFileAsMediaObject(basename($importfile), $importfile, FALSE);
				ilObjMediaObject::_saveUsage($media_object->getId(), "qpl:html", $this->object->getId());

				$questiontext = str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $questiontext);
				foreach ($feedbacks as $ident => $material)
				{
					$feedbacks[$ident] = str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $material);
				}
				foreach ($feedbacksgeneric as $correctness => $material)
				{
					$feedbacksgeneric[$correctness] = str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $material);
				}
			}
		}

		$this->object->setQuestion(ilRTE::_replaceMediaObjectImageSrc($questiontext, 1));

		foreach ($feedbacks as $ident => $material)
		{
			$this->object->saveFeedbackSingleAnswer($ident, ilRTE::_replaceMediaObjectImageSrc($material, 1));
		}

		foreach ($feedbacksgeneric as $correctness => $material)
		{
			$this->object->saveFeedbackGeneric($correctness, ilRTE::_replaceMediaObjectImageSrc($material, 1));
		}

		$this->object->saveToDb();

		if($tst_id > 0)
		{
			$q_id = $this->object->getId();
			$question_id = $this->object->duplicate(true, null, null, null, $tst_id);
			$tst_object->questions[$question_counter++] = $question_id;
			$import_mapping[$item->getIdent()] = array("pool" => $q_id, "test" => $question_id);
		}
		else
		{
			$import_mapping[$item->getIdent()] = array("pool" => $this->object->getId(), "test" => 0);
		}
	}
}
