<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilAsqSingleChoiceConfigFormGUI
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
class ilAsqSingleChoiceConfigFormGUI extends ilAsqQuestionConfigForm
{
	/**
	 * @var ilAsqSingleChoiceQuestion
	 */
	protected $question;
	
	protected function addQuestionSpecificProperties()
	{
		// shuffle
		$shuffle = new ilCheckboxInputGUI($this->lng->txt( "shuffle_answers" ), "shuffle");
		$shuffle->setValue( 1 );
		$shuffle->setChecked( $this->getQuestion()->isShuffleEnabled() );
		$shuffle->setRequired( false );
		$this->addItem($shuffle);
		
		if( !$this->isLearningModuleContext() )
		{
			// Answer types
			$types = new ilSelectInputGUI($this->lng->txt( "answer_types" ), "types");
			$types->setRequired( false );
			$types->setValue( ($this->getQuestion()->isSingleLineAnswers()) ? 0 : 1 );
			$types->setOptions( array(
					0 => $this->lng->txt( 'answers_singleline' ),
					1 => $this->lng->txt( 'answers_multiline' ),
				)
			);
			$this->addItem( $types );
		}
		
		if( $this->getQuestion()->isSingleLineAnswers() )
		{
			// thumb size
			$thumb_size = new ilNumberInputGUI($this->lng->txt( "thumb_size" ), "thumb_size");
			$thumb_size->setSuffix($this->lng->txt("thumb_size_unit_pixel"));
			$thumb_size->setMinValue( 20 );
			$thumb_size->setDecimals( 0 );
			$thumb_size->setSize( 6 );
			$thumb_size->setInfo( $this->lng->txt( 'thumb_size_info' ) );
			$thumb_size->setValue( $this->getQuestion()->getThumbnailSize() );
			$thumb_size->setRequired( false );
			$this->addItem( $thumb_size );
		}
	}
	
	protected function addAnswerSpecificProperties()
	{
		// Choices
		include_once "./Modules/TestQuestionPool/classes/class.ilSingleChoiceWizardInputGUI.php";
		$choices = new ilSingleChoiceWizardInputGUI($this->lng->txt( "answers" ), "choice");
		$choices->setRequired( true );
		$choices->setQuestionObject( $this->getQuestion() );
		$choices->setSingleline( $this->getQuestion()->isSingleLineAnswers() );
		$choices->setAllowMove( false );
		
		if( $this->isLearningModuleContext() )
		{
			$choices->setSize( 40 );
			$choices->setMaxLength( 800 );
		}
		
		#if ($this->getQuestion()->getAnswerCount() == 0)
		#{
		#	$this->getQuestion()->addAnswer( "", 0, 0 );
		#}
		#
		#$choices->setValues( $this->getQuestion()->getAnswers() );
		#
		#$this->addItem( $choices );
	}
}
