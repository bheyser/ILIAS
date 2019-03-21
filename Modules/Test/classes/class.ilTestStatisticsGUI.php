<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilTestStatisticsGUI
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Modules/Test
 */
class ilTestStatisticsGUI
{
	const DEFAULT_CMD = 'showAggregatedTestResults';
	
	/**
	 * @var ilTestTabsManager
	 */
	protected $tabsManager;
	
	/**
	 * @var ilObjTest
	 */
	protected $testObj;
	
	/**
	 * ilTestParticipantsGUI constructor.
	 * @param ilTestTabsManager $tabsManager
	 * @param ilObjTest $testObj
	 */
	public function __construct(ilTestTabsManager $tabsManager, ilObjTest $testObj)
	{
		$this->tabsManager = $tabsManager;
		$this->testObj = $testObj;
	}
	
	/**
	 * Execute Command
	 */
	public function executeCommand()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		
		$this->tabsManager->activateTab(ilTestTabsManager::TAB_ID_STATISTICS);
		$this->tabsManager->getStatisticsSubTabs();
		
		$this->addToolbar();
		$this->addContentStyles();
		
		switch( $DIC->ctrl()->getNextClass() )
		{
			case strtolower(__CLASS__):
			default:
				
				$command = $DIC->ctrl()->getCmd(self::DEFAULT_CMD).'Cmd';
				$this->{$command}();
		}
	}
	
	protected function addContentStyles()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$DIC->ui()->mainTemplate()->setCurrentBlock("ContentStyle");
		$DIC->ui()->mainTemplate()->setVariable("LOCATION_CONTENT_STYLESHEET",
			ilObjStyleSheet::getContentStylePath(0)
		);
		$DIC->ui()->mainTemplate()->parseCurrentBlock();
	}
	
	protected function addToolbar()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		require_once 'Services/Form/classes/class.ilSelectInputGUI.php';
		$export_type = new ilSelectInputGUI($DIC->language()->txt('exp_eval_data'), 'export_type');
		$export_type->setOptions(array(
			'excel' => $DIC->language()->txt('exp_type_excel'),
			'csv'   => $DIC->language()->txt('exp_type_spss')
		));
		$DIC->toolbar()->addInputItem($export_type, true);
		
		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$button = ilSubmitButton::getInstance();
		$button->setCommand('exportAggregatedResults');
		$button->setCaption('export');
		$button->getOmitPreventDoubleSubmission();
		$DIC->toolbar()->addButtonInstance($button);
	}
	
	protected function showAggregatedTestResultsCmd()
	{
		$this->tabsManager->activateSubTab('agg_tst_results');
		
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$uif = $DIC->ui()->factory();
		
		$data = $this->buildAggregatedTestResultsData( $this->getEvaluationData() );
		
		try
		{
			$cardTpl = new ilTemplate("tpl.svy_results_details_card.html", true, true, "Modules/Survey");
			foreach($data as $row)
			{
				$cardTpl->setCurrentBlock("question_statistics_card");
				$cardTpl->setVariable("QUESTION_STATISTIC_KEY", $row['result']);
				$cardTpl->setVariable("QUESTION_STATISTIC_VALUE", $row['value']);
				$cardTpl->parseCurrentBlock();
			}
			$cardHtml = $cardTpl->get();

			$panelTpl = new ilTemplate("tpl.svy_results_details_panel.html", true, true, "Modules/Survey");
			
			$stats = $this->getStatisticObject();
			$evaluations = $stats->getEvaluations(ilExtendedTestStatistics::LEVEL_TEST);
			foreach($this->buildOrderedTestEvalData($stats, $evaluations) as $evalData)
			{
				$panelTpl->setCurrentBlock('grid_col_bl');
				$panelTpl->setVariable('COL_CAPTION', $evalData['label']);
				$panelTpl->parseCurrentBlock();
				
				$panelTpl->setCurrentBlock('grid_col_bl');
				$panelTpl->setVariable('COL_CAPTION', $evalData['value']);
				$panelTpl->parseCurrentBlock();
				
				$panelTpl->setCurrentBlock('grid_row_bl');
				$panelTpl->parseCurrentBlock();
			}
			
			$panelTpl->setCurrentBlock('grid_foot');
			$panelTpl->setVariable('NUM_COLS', 2);
			$panelTpl->parseCurrentBlock();
			
			$panelHtml = $panelTpl->get();
		}
		catch(ilTemplateException $e)
		{
			$DIC->ui()->mainTemplate()->setContent($e);
			return;
		}
		
		
		$statisticPanel = $uif->panel()->sub('Test Statistics', $uif->legacy($panelHtml))->withCard(
			$DIC->ui()->factory()->card()->standard('Participant Information')->withSections([
				$DIC->ui()->factory()->legacy($cardHtml)
			])
		);
		
		$subPanels = [$this->buildTestResultsKeyFigureExplanation(), $statisticPanel];
		
		$subPanels[] = $uif->panel()->sub('Points Distribution', $uif->legacy(
			$this->buildPointsDistributionChartHtml()
		));
		
		$subPanels[] = $uif->panel()->sub('Overview for Item Difficulty / Facility Index', $uif->legacy(
			$this->buildItemDifficultyChartHtml()
		));
		
		$subPanels[] = $uif->panel()->sub('Overview for Item Discrimination / Discrimination Index', $uif->legacy(
			$this->buildItemDiscriminationChartHtml()
		));
		
		$report = $uif->panel()->report('Aggregated Test Results', $subPanels);
		
		$DIC->ui()->mainTemplate()->setContent(
			$DIC->ui()->renderer()->render($report)
			//.'<pre>'.print_r($data, true).'</pre>'
			//.'<pre>'.print_r($srcData, true).'</pre>'
		);
	}
	
	protected function buildItemDiscriminationChartHtml()
	{
		$stats = $this->getStatisticObject();
		
		foreach($stats->getEvaluations(ilExtendedTestStatistics::LEVEL_QUESTION) as $eval)
		{
			if( $eval instanceof ilExteEvalQuestionDiscriminationIndex )
			{
				$eval->setData($stats->getSourceData());
				break;
			}
		}
		
		$qstData = $this->buildAggregatedQuestionResultsData( $this->getEvaluationData() );
		
		$data = [];
		
		foreach($qstData as $qst)
		{
			if( !$qst['qid'] ) continue;
			
			$data[] = [
				'label' => $qst['title'],
				'value' => $eval->calculateValue($qst['qid'])->value
			];
		}
		
		return $this->buildChartHtml($data, 'item_discrimination');
	}
	
	protected function buildItemDifficultyChartHtml()
	{
		$stats = $this->getStatisticObject();
		
		foreach($stats->getEvaluations(ilExtendedTestStatistics::LEVEL_QUESTION) as $eval)
		{
			if( $eval instanceof ilExteEvalQuestionFacilityIndex )
			{
				$eval->setData($stats->getSourceData());
				break;
			}
		}
		
		$qstData = $this->buildAggregatedQuestionResultsData( $this->getEvaluationData() );

		$data = array();
		
		foreach($qstData as $qst)
		{
			if( !$qst['qid'] ) continue;
			
			$data[] = [
				'label' => $qst['title'],
				'value' => $eval->calculateValue($qst['qid'])->value
			];
		}
		
		return $this->buildChartHtml($data, 'item_difficulty');
	}
	
	protected function buildPointsDistributionChartHtml()
	{
		$data = [
			['label' => '9.0 Points', 'value' => 1],
			['label' => '7.0 Points', 'value' => 2],
			['label' => '2.0 Points', 'value' => 1],
			['label' => '1.0 Points', 'value' => 1]
		];
		
		return $this->buildChartHtml($data, 'points_distribution');
	}

	protected function buildOrderedTestEvalData($stats, $evaluations)
	{
		$data = array();
		
		foreach($evaluations as $eval)
		{
			/* @var ilExteEvalTest $eval */
			
			$eval->setData($stats->getSourceData());
			
			switch( true )
			{
				case $eval instanceof ilExteEvalTestMean:
					
					$data[0] = [
						'label' => 'Mean Points',
						'value' => sprintf("%.2f", $eval->calculateValue()->value)
					];
					break;
					
				case $eval instanceof ilExteEvalTestMedian:
					
					$data[1] = [
						'label' => 'Median Points',
						'value' => sprintf("%.2f", $eval->calculateValue()->value)
					];
					break;
				case $eval instanceof ilExteEvalTestStandardDeviation:
					
					$data[2] = [
						'label' => $eval->getShortTitle(),
						'value' => sprintf("%.2f %%", $eval->calculateValue()->value)
					];
					break;
				
				case $eval instanceof ilExteEvalTestCIC:
					
					$data[3] = [
						'label' => $eval->getShortTitle(),
						'value' => sprintf("%.2f %%", $eval->calculateValue()->value)
					];
					break;
			}
		}
		
		ksort($data);
		
		return $data;
	}
	
	/**
	 * @param ilTestEvaluationData $evalData
	 * @return array
	 */
	protected function buildAggregatedTestResultsData(ilTestEvaluationData $evalData)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$data = array();
		
		if (count($evalData->getParticipants()))
		{
			array_push($data, array(
				//'result' => $DIC->language()->txt("tst_eval_total_persons"),
				'result' => 'Total number that started test',
				'value'  => count($evalData->getParticipants())
			));
			$total_finished = $evalData->getTotalFinishedParticipants();
			array_push($data, array(
				//'result' => $DIC->language()->txt("tst_eval_total_finished"),
				'result' => 'Total number that finished test',
				'value'  => $total_finished
			));
			$average_time = $this->testObj->evalTotalStartedAverageTime(
				$evalData->getParticipantIds()
			);
			$diff_seconds = $average_time;
			$diff_hours    = floor($diff_seconds/3600);
			$diff_seconds -= $diff_hours   * 3600;
			$diff_minutes  = floor($diff_seconds/60);
			$diff_seconds -= $diff_minutes * 60;
			array_push($data, array(
				'result' => $DIC->language()->txt("tst_eval_total_finished_average_time"),
				'value'  => sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds)
			));
			$total_passed = 0;
			$total_passed_reached = 0;
			$total_passed_max = 0;
			$total_passed_time = 0;
			foreach ($evalData->getParticipants() as $userdata)
			{
				if ($userdata->getPassed())
				{
					$total_passed++;
					$total_passed_reached += $userdata->getReached();
					$total_passed_max += $userdata->getMaxpoints();
					$total_passed_time += $userdata->getTimeOfWork();
				}
			}
			$average_passed_reached = $total_passed ? $total_passed_reached / $total_passed : 0;
			$average_passed_max = $total_passed ? $total_passed_max / $total_passed : 0;
			$average_passed_time = $total_passed ? $total_passed_time / $total_passed : 0;
			array_push($data, array(
				'result' => $DIC->language()->txt("tst_eval_total_passed"),
				'value'  => $total_passed
			));
			array_push($data, array(
				'result' => $DIC->language()->txt("tst_eval_total_passed_average_points"),
				'value'  => sprintf("%2.2f", $average_passed_reached) . " " . strtolower($DIC->language()->txt("of")) . " " . sprintf("%2.2f", $average_passed_max)
			));
			$average_time = $average_passed_time;
			$diff_seconds = $average_time;
			$diff_hours    = floor($diff_seconds/3600);
			$diff_seconds -= $diff_hours   * 3600;
			$diff_minutes  = floor($diff_seconds/60);
			$diff_seconds -= $diff_minutes * 60;
			array_push($data, array(
				'result' => $DIC->language()->txt("tst_eval_total_passed_average_time"),
				'value'  => sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds)
			));
		}
		
		return $data;
	}
	
	protected function showAggregatedQuestionResultsCmd()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$uif = $DIC->ui()->factory();
		
		$this->tabsManager->activateSubTab('agg_qst_results');
		
		$data = $this->buildAggregatedQuestionResultsData( $this->getEvaluationData() );
		
		$panels = array();
		
		$panels[] = $this->buildQuestionResultsKeyFigureExplanation();
		
		foreach($data as $qstData)
		{
			if( !$qstData['qid'] ) continue;
			
			$qstStatsData = $this->buildQuestionStatisticData($qstData);
			
			$cardTpl = new ilTemplate("tpl.svy_results_details_card.html", true, true, "Modules/Survey");
			
			foreach($qstStatsData as $qstStatLabel => $qstStatValue)
			{
				$cardTpl->setCurrentBlock("question_statistics_card");
				$cardTpl->setVariable("QUESTION_STATISTIC_KEY", $qstStatLabel);
				$cardTpl->setVariable("QUESTION_STATISTIC_VALUE", $qstStatValue);
				$cardTpl->parseCurrentBlock();
			}
			
			$cardHtml = $cardTpl->get();
			
			$questionGui = assQuestion::instantiateQuestionGUI($qstData['qid']);
			
			$panelHtml = $questionGui->getSolutionOutput(
				0, null, false, false,
				true, false, true
			);
			$panelHtml = '<div class="ilc_question_Standard">'.$panelHtml.'</div>';
			
			$panels[] = $uif->panel()->sub($questionGui->object->getTitle(), $uif->legacy($panelHtml))->withCard(
				$uif->card()->standard($DIC->language()->txt($questionGui->object->getQuestionType()))->withSections([
					$uif->legacy($cardHtml)
				])
			);
			
			$solutions = $this->getSolutions($questionGui->object);
			foreach($questionGui->getSubQuestionsIndex() as $subQuestionIndex)
			{
				$table = $questionGui->getAnswerFrequencyTableGUI(
					$this, '', $solutions, $subQuestionIndex
				);
				
				$chartHtml = $this->buildAnswerStatisticChartHtml(
					$table->getData(), $qstData['qid'].'_'.$subQuestionIndex
				);
				
				$table->setTitle('');
				$table->disable('footer');
				
				$panels[] = $uif->panel()->sub('', $uif->legacy(
					$table->getHTML() . $chartHtml
				));
			}
		}
		
		$report = $uif->panel()->report('Aggregated Question Results', $panels);
		
		$DIC->ui()->mainTemplate()->setContent(
			$DIC->ui()->renderer()->render($report)
			//.'<pre>'.print_r($data, true).'</pre>'
		);
	}
	
	protected function buildQuestionStatisticData($qstData)
	{
		$data = array();
		
		$data['Question ID'] = $qstData['qid'];
		
		$data['Average Points'] = sprintf(
			"%.2f of %.2f", $qstData['points_reached'], $qstData['points_max']
		);
		
		foreach($this->buildOrderedQuestionEvaluationData($qstData['qid']) as $row)
		{
			$data[$row['label']] = $row['value'];
		}
		
		return $data;
	}
	
	protected function buildOrderedQuestionEvaluationData($qid)
	{
		$stats = $this->getStatisticObject();
		
		$data = array();
		
		foreach($stats->getEvaluations(ilExtendedTestStatistics::LEVEL_QUESTION) as $eval)
		{
			$eval->setData($stats->getSourceData());
			
			switch( true )
			{
				case $eval instanceof ilExteEvalQuestionFacilityIndex:
					
					$data[0] = [
						'label' => $eval->getShortTitle(),
						'value' => sprintf("%.2f %%", $eval->calculateValue($qid)->value)
					];
					break;
				
				case $eval instanceof ilExteEvalQuestionDiscriminationIndex:
					
					$data[1] = [
						'label' => $eval->getShortTitle(),
						'value' => sprintf("%.2f %%", $eval->calculateValue($qid)->value)
					];
					break;
				
				case $eval instanceof ilExteEvalQuestionStandardDeviation:
					
					$data[2] = [
						'label' => $eval->getShortTitle(),
						'value' => sprintf("%.2f %%", $eval->calculateValue($qid)->value)
					];
					break;
			}
		}
		
		ksort($data);
		
		return $data;
	}
	
	/**
	 * @param assQuestion $question
	 * @return array
	 */
	protected function getSolutions(assQuestion $question)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$solutionRows = array();
		
		foreach($this->testObj->getParticipants() as $activeId => $participantData)
		{
			$passesSelector = new ilTestPassesSelector($DIC->database(), $this->testObj);
			$passesSelector->setActiveId($activeId);
			$passesSelector->loadLastFinishedPass();
			
			foreach($passesSelector->getClosedPasses() as $pass)
			{
				foreach($question->getSolutionValues($activeId, $pass) as $row)
				{
					$solutionRows[] = $row;
				}
			}
		}
		
		return $solutionRows;
	}
	
	/**
	 * @param ilTestEvaluationData $evalData
	 * @return array
	 */
	protected function buildAggregatedQuestionResultsData(ilTestEvaluationData $evalData)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		
		$rows = array();
		foreach ($evalData->getQuestionTitles() as $question_id => $question_title)
		{
			$answered = 0;
			$reached = 0;
			$max = 0;
			foreach ($evalData->getParticipants() as $userdata)
			{
				for ($i = 0; $i <= $userdata->getLastPass(); $i++)
				{
					if (is_object($userdata->getPass($i)))
					{
						$question =& $userdata->getPass($i)->getAnsweredQuestionByQuestionId($question_id);
						if (is_array($question))
						{
							$answered++;
							$reached += $question["reached"];
							$max += $question["points"];
						}
					}
				}
			}
			$percent = $max ? $reached/$max * 100.0 : 0;

			$DIC->ctrl()->setParameter($this, "qid", $question_id);
			
			$points_reached = ($answered ? $reached / $answered : 0);
			$points_max     = ($answered ? $max / $answered : 0);
			array_push($rows,
				array(
					'qid'            => $question_id,
					'title'          => $question_title,
					'points'         => $points_reached,
					'points_reached' => $points_reached,
					'points_max'     => $points_max,
					'percentage'     => (float)$percent,
					'answers'        => $answered
				)
			);
		}
		
		return $rows;
	}
	
	/**
	 * @return ilTestEvaluationData
	 */
	protected function getEvaluationData()
	{
		$this->testObj->setAccessFilteredParticipantList(
			$this->testObj->buildStatisticsAccessFilteredParticipantList()
		);
		
		return $this->testObj->getCompleteEvaluationData();
	}
	
	/**
	 * @return ilExtendedTestStatistics
	 */
	protected function getStatisticObject()
	{
		$plugin = ilPluginAdmin::getPluginObject(
			'Services', 'UIComponent', 'uihk', 'ExtendedTestStatistics'
		);
		
		require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ExtendedTestStatistics/classes/class.ilExtendedTestStatistics.php';
		return new ilExtendedTestStatistics($this->testObj, $plugin);
	}
	
	protected function buildTestResultsKeyFigureExplanation()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$uif = $DIC->ui()->factory();
		
		return $uif->panel()->sub('Key Figure Explanation', $uif->listing()->descriptive(
			$this->getKeyFigureExplanationItems(true)
		));
	}
	
	protected function buildQuestionResultsKeyFigureExplanation()
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$uif = $DIC->ui()->factory();
		
		return $uif->panel()->sub('Key Figure Explanation', $uif->listing()->descriptive(
			$this->getKeyFigureExplanationItems(false)
		));
	}
	
	protected function getKeyFigureExplanationItems($withCronbach)
	{
		global $DIC; /* @var ILIAS\DI\Container $DIC */
		$uif = $DIC->ui()->factory();
		
		$items = [];
		
		if($withCronbach)
		{
			$items['Internal consistency (Cronbachs alpha)'] = $uif->legacy(
				'Indicates how well items in the test measure the same concept. Values range from 0 to 1. '.
				'If your test assesses highly related topics,  than Cronbach\'s alpha is an important key figure and higher values are desirable. If your tests assesses a broad range of different topics, then you must expect low and meaningless figures.'
				.$DIC->ui()->renderer()->render($uif->listing()->unordered([
					'0 to 0,7 Arbitrary This test should not be the only source of input to assign a fair grade to a student. The quality of the test is too low, the grade assigned to a student will be kind of arbitrary. This arbitrariness will be balanced out if the result of this test contributes to the overall grade along with other assessments i.e. this test + presentation + oral exam combined would more be more likely to produce a fair grade. To improve this test compute the correlation of each test item with the total score test; delete items with low correlations add questions.',
					'0,7 to 0,8 Relevant Feedback This test should not be used as the single source of input to assign a fair grade to a student. The quality of the test is good enough to inform students within a semester about their progress. It cannot be used for a high-stakes or low-stakes exams. To improve this test compute the correlation of each test item with the total score test; delete items with low correlations add questions',
					'0,8 to 0,85 Good This test is well developed and can be used for low stakes exams that will assign fair grades to students even if the test is the only source of grading. It is not fit to be used in a high-stakes scenario.',
					'0,85 to 0,9 Excellent This is a professionally developed standardized test that can be deployed for high - stakes  exams: It is fit to be administered only once to a student and will produce a grade so sound that decisions about an entire career can be taken on the basis of this grade.',
					'0,9 to 1,0 Redundant test questions Shorten you test, it contains questions that are mearuring identical things. These \'doubles\' should be removed from the test.'
				]))
			);
		}
		
		$items['Item Difficulty / Facility Index'] = $uif->legacy(
			'The item difficulty is the proportion of persons who answer a particular itemcorrectly to the total attempting the item. The item difficulty index ranges from 0 to 100. The higher the value, the easier the question. '.
			'The value of desirable difficulty changes with the number of answer options a question has. This is not taken into account by the following classification.'
			.$DIC->ui()->renderer()->render($uif->listing()->unordered([
				'0 to 20  Hard question, few students could answer that question correctly',
				'20 to 80  Medium difficulty, many students could answer that question correctly',
				'80 to 100 Easy question, almost all could answer that question correctly'
			]))
		);
		
		$items['Item Discrimination / Discrimination Index'] = $uif->legacy(
			'Measures how well a question differentiates among students on the basis of how well they know the material being tested. Item discrimination index has no fixed range. '.
			'The values of the coefficient will tend to be lower for tests measuring a wide range of content areas than for more homogeneous tests.'
			.$DIC->ui()->renderer()->render($uif->listing()->unordered([
				'Below 0,1 Poor Delete questions from test do not bother to improve it it is beyond repair and not worth the effort',
				'0,1 to 0,3  In need of revision  Revise question for ambiguous wording',
				'Above 0,3 Good  Keep question',
				'Not computable'
			]))
		);
		
		return $items;
	}
	
	protected function getChartColors()
	{
		return array(
			// flot "default" theme
			"#edc240", "#afd8f8", "#cb4b4b", "#4da74d", "#9440ed",
			// http://godsnotwheregodsnot.blogspot.de/2012/09/color-distribution-methodology.html
			"#1CE6FF", "#FF34FF", "#FF4A46", "#008941", "#006FA6", "#A30059",
			"#FFDBE5", "#7A4900", "#0000A6", "#63FFAC", "#B79762", "#004D43", "#8FB0FF", "#997D87",
			"#5A0007", "#809693", "#FEFFE6", "#1B4400", "#4FC601", "#3B5DFF", "#4A3B53", "#FF2F80",
			"#61615A", "#BA0900", "#6B7900", "#00C2A0", "#FFAA92", "#FF90C9", "#B903AA", "#D16100",
			"#DDEFFF", "#000035", "#7B4F4B", "#A1C299", "#300018", "#0AA6D8", "#013349", "#00846F",
			"#372101", "#FFB500", "#C2FFED", "#A079BF", "#CC0744", "#C0B9B2", "#C2FF99", "#001E09",
			"#00489C", "#6F0062", "#0CBD66", "#EEC3FF", "#456D75", "#B77B68", "#7A87A1", "#788D66",
			"#885578", "#FAD09F", "#FF8A9A", "#D157A0", "#BEC459", "#456648", "#0086ED", "#886F4C",
			"#34362D", "#B4A8BD", "#00A6AA", "#452C2C", "#636375", "#A3C8C9", "#FF913F", "#938A81",
			"#575329", "#00FECF", "#B05B6F", "#8CD0FF", "#3B9700", "#04F757", "#C8A1A1", "#1E6E00",
			"#7900D7", "#A77500", "#6367A9", "#A05837", "#6B002C", "#772600", "#D790FF", "#9B9700",
			"#549E79", "#FFF69F", "#201625", "#72418F", "#BC23FF", "#99ADC0", "#3A2465", "#922329",
			"#5B4534", "#FDE8DC", "#404E55", "#0089A3", "#CB7E98", "#A4E804", "#324E72", "#6A3A4C"
		);
	}
	
	protected function buildChartHtml($data, $id)
	{
		$colors = $this->getChartColors();

		$chart = ilChart::getInstanceByType(ilChart::TYPE_GRID, $id);
		$chart->setColors($colors);
		
		$width = 0;
		foreach($data as $idx => $row)
		{
			$label = $row['label'];
			$value = $row['value'];
			
			$data = $chart->getDataInstance(ilChartGrid::DATA_BARS);
			$chart->setAutoResize(true);
			$data->setBarOptions(0.5, "center");
			$data->setFill(0);
			$data->setLabel($label);
			$data->addPoint($idx, $value);
			$chart->addData($data);
			$labels[] = '';
			$legend[] = [
				'label' => $label,
				'color' => $colors[$idx]
			];
			
			$width += 100;
		}
		$chart->setSize($width, 120);
		$chart->setAutoResize(true);
		$chart->setTicks($labels, false, true);
		
		$tpl = new ilTemplate("tpl.svy_results_details_panel.html", true, true, "Modules/Survey");
		
		foreach($legend as $row)
		{
			$tpl->setCurrentBlock('legend_bl');
			$tpl->setVariable('LEGEND_CAPTION', $row['label']);
			$tpl->setVariable('LEGEND_COLOR', $row['color']);
			$tpl->parseCurrentBlock();
		}
		
		$tpl->setCurrentBlock('chart_bl');
		$tpl->setVariable('CHART', $chart->getHTML());
		$tpl->parseCurrentBlock();
		
		$tpl->setCurrentBlock('question_panel_bl');
		$tpl->parseCurrentBlock();
		
		return $tpl->get();
	}
	
	protected function buildAnswerStatisticChartHtml($data, $id)
	{
		$rows = [];
		
		foreach($data as $row)
		{
			$rows[] = [
				'label' => $row['answer'],
				'value' => $row['frequency']
			];
		}
		
		return $this->buildChartHtml($rows, $id);
	}
}