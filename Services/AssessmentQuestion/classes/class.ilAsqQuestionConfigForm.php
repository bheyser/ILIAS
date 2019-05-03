<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqQuestionConfigForm
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
abstract class ilAsqQuestionConfigForm extends ilPropertyFormGUI
{
	/**
	 * @var ilAsqSingleChoiceQuestion
	 */
	protected $question;
	
	/**
	 * @var int[]
	 */
	protected $taxonomies;
	
	/**
	 * @var bool
	 */
	protected $rteEnabled;
	
	/**
	 * @var bool
	 */
	protected $learningModuleContext;
	
	/**
	 * ilAsqQuestionConfigForm constructor.
	 * @param ilAsqSingleChoiceQuestion $question
	 * @param int[] $taxonomies
	 */
	public function __construct(ilAsqSingleChoiceQuestion $question, array $taxonomies)
	{
		parent::__construct();
		$this->setQuestion($question);
		$this->setTaxonomies($taxonomies);
		$this->setRteEnabled(false);
		$this->setLearningModuleContext(false);
	}
	
	/**
	 * @return ilAsqSingleChoiceQuestion
	 */
	public function getQuestion(): ilAsqSingleChoiceQuestion
	{
		return $this->question;
	}
	
	/**
	 * @param ilAsqSingleChoiceQuestion $question
	 */
	public function setQuestion(ilAsqSingleChoiceQuestion $question)
	{
		$this->question = $question;
	}
	
	/**
	 * @return int[]
	 */
	public function getTaxonomies(): array
	{
		return $this->taxonomies;
	}
	
	/**
	 * @param int[] $taxonomies
	 */
	public function setTaxonomies(array $taxonomies)
	{
		$this->taxonomies = $taxonomies;
	}
	
	/**
	 * @return bool
	 */
	public function isRteEnabled(): bool
	{
		return $this->rteEnabled;
	}
	
	/**
	 * @param bool $rteEnabled
	 */
	public function setRteEnabled(bool $rteEnabled)
	{
		$this->rteEnabled = $rteEnabled;
	}
	
	/**
	 * @return bool
	 */
	public function isLearningModuleContext(): bool
	{
		return $this->learningModuleContext;
	}
	
	/**
	 * @param bool $learningModuleContext
	 */
	public function setLearningModuleContext(bool $learningModuleContext)
	{
		$this->learningModuleContext = $learningModuleContext;
	}
	
	/**
	 * Initialise form
	 */
	public function init(ilAsqQuestionAuthoring $qstAuthoring)
	{
		$this->setFormAction($this->ctrl->getFormAction($qstAuthoring));
		$this->setTableWidth('100%');
		$this->setMultipart(true);
		
		$this->setId($this->getQuestion()->getQuestionType()->getIdentifier());
		$this->setTitle($this->getQuestion()->getQuestionType()->getIdentifier());
		
		$this->addBasicQuestionProperties();
		$this->addQuestionSpecificProperties();
		$this->addAnswerSpecificProperties();
		
		$this->addTaxonomyFormSection();
		
		$this->addCommandButtons();
	}
	
	protected function addBasicQuestionProperties()
	{
		// title
		$title = new ilTextInputGUI($this->lng->txt("title"), "title");
		$title->setMaxLength(100);
		$title->setValue($this->getQuestion()->getTitle());
		$title->setRequired(TRUE);
		$this->addItem($title);
		
		if( !$this->isLearningModuleContext() )
		{
			// author
			$author = new ilTextInputGUI($this->lng->txt("author"), "author");
			$author->setValue($this->getQuestion()->getAuthor());
			$author->setRequired(TRUE);
			$this->addItem($author);
			
			// description
			$description = new ilTextInputGUI($this->lng->txt("description"), "comment");
			$description->setValue($this->getQuestion()->getComment());
			$description->setRequired(FALSE);
			$this->addItem($description);
		}
		else
		{
			// author as hidden field
			$hi = new ilHiddenInputGUI("author");
			$author = ilUtil::prepareFormOutput($this->getQuestion()->getAuthor());
			if (trim($author) == "")
			{
				$author = "-";
			}
			$hi->setValue($author);
			$this->addItem($hi);
			
		}
		
		// lifecycle
		$lifecycle = new ilSelectInputGUI($this->lng->txt('qst_lifecycle'), 'lifecycle');
		$lifecycle->setOptions($this->getQuestion()->getLifecycle()->getSelectOptions($this->lng));
		$lifecycle->setValue($this->getQuestion()->getLifecycle()->getIdentifier());
		$this->addItem($lifecycle);
		
		// questiontext
		$question = new ilTextAreaInputGUI($this->lng->txt("question"), "question");
		$question->setValue($this->getQuestion()->getQuestionText());
		$question->setRequired(TRUE);
		$question->setRows(10);
		$question->setCols(80);
		
		if( $this->isLearningModuleContext() )
		{
			require_once 'Modules/TestQuestionPool/classes/questions/class.ilAssSelfAssessmentQuestionFormatter.php';
			$question->setRteTags(ilAssSelfAssessmentQuestionFormatter::getSelfAssessmentTags());
			$question->setUseTagsForRteOnly(false);

		}
		elseif( $this->isRteEnabled() )
		{
			$question->setUseRte(TRUE);
			include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
			$question->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
			$question->addPlugin("latex");
			$question->addButton("latex");
			$question->addButton("pastelatex");
			$question->setRTESupport($this->getQuestion()->getId(), "qpl", "assessment");
		}
		$this->addItem($question);
		
		if( !$this->isLearningModuleContext() )
		{
			// duration
			$duration = new ilDurationInputGUI($this->lng->txt("working_time"), "Estimated");
			$duration->setShowHours(TRUE);
			$duration->setShowMinutes(TRUE);
			$duration->setShowSeconds(TRUE);
			list($ewtH, $ewtM, $ewtS) = explode(
				':', $this->getQuestion()->getEstimatedWorkingTime()->format('H:I:S')
			);
			$duration->setHours($ewtH);
			$duration->setMinutes($ewtM);
			$duration->setSeconds($ewtS);
			$duration->setRequired(FALSE);
			$this->addItem($duration);
		}
		else
		{
			// number of tries
			if (strlen($this->getQuestion()->getNrOfTries()))
			{
				$nr_tries = $this->getQuestion()->getNrOfTries();
			}
			else
			{
				$nr_tries = $this->getQuestion()->getDefaultNrOfTries();
			}
			if ($nr_tries < 1)
			{
				$nr_tries = "";
			}
			
			$ni = new ilNumberInputGUI($this->lng->txt("qst_nr_of_tries"), "nr_of_tries");
			$ni->setValue($nr_tries);
			$ni->setMinValue(0);
			$ni->setSize(5);
			$ni->setMaxLength(5);
			$this->addItem($ni);
		}
	}
	
	abstract protected function addQuestionSpecificProperties();

	abstract protected function addAnswerSpecificProperties();
	
	protected function addTaxonomyFormSection()
	{
		if( count($this->getTaxonomies()) )
		{
			$sectHeader = new ilFormSectionHeaderGUI();
			$sectHeader->setTitle($this->lng->txt('qpl_qst_edit_form_taxonomy_section'));
			$this->addItem($sectHeader);
			
			foreach($this->getTaxonomies() as $taxonomyId)
			{
				$taxonomy = new ilObjTaxonomy($taxonomyId);
				$label = sprintf($this->lng->txt('qpl_qst_edit_form_taxonomy'), $taxonomy->getTitle());
				$postvar = "tax_node_assign_$taxonomyId";
				
				$taxSelect = new ilTaxSelectInputGUI($taxonomy->getId(), $postvar, true);
				$taxSelect->setTitle($label);
				
				$taxNodeAssignments = new ilTaxNodeAssignment(ilObject::_lookupType($this->getQuestion()->getParentId()),
					$this->getQuestion()->getParentId(), 'quest', $taxonomyId
				);
				$assignedNodes = $taxNodeAssignments->getAssignmentsOfItem($this->getQuestion()->getId());
				
				$taxSelect->setValue(array_map(function($assignedNode) {
					return $assignedNode['node_id'];
				}, $assignedNodes));
				
				$this->addItem($taxSelect);
			}
		}
	}
	
	public function addCommandButtons()
	{
		if( !$this->isLearningModuleContext() )
		{
			$this->addCommandButton('saveReturn', $this->lng->txt('save_return'));
		}
		
		$this->addCommandButton('save', $this->lng->txt('save'));
	}
}
