<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package     Modules/Test
 */
class ilTestQuestionSideListGUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	
	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * @var ilTestPlayerAbstractGUI
	 */
	private $targetGUI;
	
	/**
	 * @var array
	 */
	private $questionSummaryData;

	/**
	 * @var integer
	 */
	private $currentSequenceElement;

	/**
	 * @var string
	 */
	private $currentPresentationMode;

	/**
	 * @var bool
	 */
	private $disabled;

	/**
	 * @param ilCtrl $ctrl
	 * @param ilLanguage $lng
	 */
	public function __construct(ilCtrl $ctrl, ilLanguage $lng)
	{
		$this->ctrl = $ctrl;
		$this->lng = $lng;
			
		$this->questionSummaryData = array();
		$this->currentSequenceElement = null;
		$this->disabled = false;
	}

	/**
	 * @return ilTestPlayerAbstractGUI
	 */
	public function getTargetGUI()
	{
		return $this->targetGUI;
	}

	/**
	 * @param ilTestPlayerAbstractGUI $targetGUI
	 */
	public function setTargetGUI($targetGUI)
	{
		$this->targetGUI = $targetGUI;
	}

	/**
	 * @return array
	 */
	public function getQuestionSummaryData()
	{
		return $this->questionSummaryData;
	}

	/**
	 * @param array $questionSummaryData
	 */
	public function setQuestionSummaryData($questionSummaryData)
	{
		$this->questionSummaryData = $questionSummaryData;
	}

	/**
	 * @return int
	 */
	public function getCurrentSequenceElement()
	{
		return $this->currentSequenceElement;
	}

	/**
	 * @param int $currentSequenceElement
	 */
	public function setCurrentSequenceElement($currentSequenceElement)
	{
		$this->currentSequenceElement = $currentSequenceElement;
	}

	/**
	 * @return string
	 */
	public function getCurrentPresentationMode()
	{
		return $this->currentPresentationMode;
	}

	/**
	 * @param string $currentPresentationMode
	 */
	public function setCurrentPresentationMode($currentPresentationMode)
	{
		$this->currentPresentationMode = $currentPresentationMode;
	}

	/**
	 * @return boolean
	 */
	public function isDisabled()
	{
		return $this->disabled;
	}

	/**
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;
	}

	/**
	 * @return ilPanelGUI
	 */
	private function buildPanel()
	{
		require_once 'Services/UIComponent/Panel/classes/class.ilPanelGUI.php';
		$panel = ilPanelGUI::getInstance();
		$panel->setHeadingStyle(ilPanelGUI::HEADING_STYLE_SUBHEADING);
		$panel->setPanelStyle(ilPanelGUI::PANEL_STYLE_SECONDARY);
		//$panel->setHeading($this->lng->txt('list_of_questions'));
		return $panel;
	}
	
	private function renderWorkflow()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$uif = $DIC->ui()->factory();
		
		$steps = [];
		$activeStep = 0;
		
		foreach( array_values($this->getQuestionSummaryData()) as $i => $row )
		{
			$step = $uif->listing()->workflow()->step(
				ilUtil::prepareFormOutput($row['title']),
				strlen($row['description']) ? $row['description'] : '',
				$this->buildLink($row['sequence'])
			);
			
			$step = $step->withStatus(\ILIAS\UI\Component\Listing\Workflow\Step::NOT_STARTED);
			
			if( true && isset($row['answerstatus']) )
			{
				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionList.php';
				
				switch( $row['answerstatus'] )
				{
					case ilAssQuestionList::QUESTION_ANSWER_STATUS_CORRECT_ANSWERED:
						$step = $step->withStatus(\ILIAS\UI\Component\Listing\Workflow\Step::SUCCESSFULLY);
						break;
					case ilAssQuestionList::QUESTION_ANSWER_STATUS_WRONG_ANSWERED:
						$step = $step->withStatus(\ILIAS\UI\Component\Listing\Workflow\Step::UNSUCCESSFULLY);
						break;
				}
			}
			elseif( $row['worked_through'] )
			{
				$step = $step->withStatus(\ILIAS\UI\Component\Listing\Workflow\Step::IN_PROGRESS);
			}
			
			if( $this->isDisabled() || $row['disabled'] )
			{
				$step = $step->withAvailability(\ILIAS\UI\Component\Listing\Workflow\Step::NOT_AVAILABLE);
			}
			elseif( true )
			{
				$step = $step->withAvailability(\ILIAS\UI\Component\Listing\Workflow\Step::AVAILABLE);
			}
			
			if( $row['sequence'] == $this->getCurrentSequenceElement() )
			{
				$activeStep = $i;
			}
			
			$steps[] = $step;
		}
		
		$workflow = $uif->listing()->workflow()->linear($this->lng->txt('list_of_questions'), $steps);
		
		$workflow = $workflow->withActive($activeStep);
		
		return $DIC->ui()->renderer()->render($workflow);
	}

	/**
	 * @return string
	 */
	private function renderList()
	{
		if( true )
		{
			return $this->renderWorkflow();
		}
		
		$tpl = new ilTemplate('tpl.il_as_tst_list_of_questions_short.html', true, true, 'Modules/Test');

		foreach( $this->getQuestionSummaryData() as $row )
		{
			$title = ilUtil::prepareFormOutput($row['title']);

			if( strlen($row['description']) )
			{
				$description = " title=\"{$row['description']}\" ";
			}
			else
			{
				$description = "";
			}

			$active = ($row['sequence'] == $this->getCurrentSequenceElement()) ? ' active' : '';
			
			$class = (
				$row['worked_through'] ? 'answered'.$active : 'unanswered'.$active
			);
			
			if ($row['marked'])
			{
				$tpl->setCurrentBlock("mark_icon");
				$tpl->setVariable("ICON_SRC", ilUtil::getImagePath('marked.svg'));
				$tpl->setVariable("ICON_TEXT",  $this->lng->txt('tst_question_marked'));
				$tpl->setVariable("ICON_CLASS", 'ilTestMarkQuestionIcon');
				$tpl->parseCurrentBlock();
			}
			
			if( true && isset($row['answerstatus']) )
			{
				require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionList.php';
				
				switch( $row['answerstatus'] )
				{
					case ilAssQuestionList::QUESTION_ANSWER_STATUS_CORRECT_ANSWERED:
						$class .= ' passed';
						break;
					case ilAssQuestionList::QUESTION_ANSWER_STATUS_WRONG_ANSWERED:
						$class .= ' failed';
						break;
				}
			}
			
			if( $this->isDisabled() || $row['disabled'] )
			{
				$tpl->setCurrentBlock('disabled_entry');
				$tpl->setVariable('CLASS', $class);
				$tpl->setVariable('ITEM', $title);
				$tpl->setVariable('DESCRIPTION', $description);
				$tpl->parseCurrentBlock();
			}
			else
			{
// fau: testNav - show mark icon in side list
// fau.
				$tpl->setCurrentBlock('linked_entry');
				$tpl->setVariable('HREF', $this->buildLink($row['sequence']));
				$tpl->setVariable('NEXTCMD', ilTestPlayerCommands::SHOW_QUESTION);
				$tpl->setVariable('NEXTSEQ', $row['sequence']);
				$tpl->setVariable('CLASS', $class);
				$tpl->setVariable('ITEM', $title);
				$tpl->setVariable("DESCRIPTION", $description);
				$tpl->parseCurrentBlock();
			}

			$tpl->setCurrentBlock('item');
		}

		return $tpl->get();
	}

	/**
	 * @return string
	 */
	public function getHTML()
	{
		$panel = $this->buildPanel();
		$panel->setBody($this->renderList());
		return $panel->getHTML();
	}

	/**
	 * @param $row
	 * @return string
	 */
	private function buildLink($sequenceElement)
	{
		$this->ctrl->setParameter(
			$this->getTargetGUI(), 'pmode', ''
		);

		$this->ctrl->setParameter(
			$this->getTargetGUI(), 'sequence', $sequenceElement
		);

		$href = $this->ctrl->getLinkTarget($this->getTargetGUI(), ilTestPlayerCommands::SHOW_QUESTION);

		$this->ctrl->setParameter(
			$this->getTargetGUI(), 'pmode', $this->getCurrentPresentationMode()
		);
		$this->ctrl->setParameter(
			$this->getTargetGUI(), 'sequence', $this->getCurrentSequenceElement()
		);
		return $href;
	}
}