<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\KioskMode\ControlBuilder;
use ILIAS\KioskMode\State;
use ILIAS\KioskMode\URLBuilder;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Factory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ilContentPageKioskModeView
 */
class ilContentPageKioskModeView extends ilKioskModeView
{
	const CMD_TOGGLE_LEARNING_PROGRESS = 'toggleManualLearningProgress';

	/** @var \ilObjContentPage */
	protected $contentPageObject;

	/** @var \ilObjUser */
	protected $user;

	/** @var Factory */
	protected $uiFactory;

	/** @var \ilCtrl */
	protected $ctrl;

	/** @var \ilTemplate */
	protected $mainTemplate;

	/** @var ServerRequestInterface */
	protected $httpRequest;

	/** @var \ilTabsGUI */
	protected $tabs;

	/**
	 * @inheritDoc
	 */
	protected function getObjectClass(): string
	{
		return \ilObjContentPage::class;
	}

	/**
	 * @inheritDoc
	 */
	protected function setObject(\ilObject $object)
	{
		global $DIC;

		$this->contentPageObject = $object;

		$this->ctrl = $DIC->ctrl();
		$this->mainTemplate = $DIC->ui()->mainTemplate();
		$this->uiFactory = $DIC->ui()->factory();
		$this->httpRequest = $DIC->http()->request();
		$this->tabs = $DIC->tabs();
	}

	/**
	 * @inheritDoc
	 */
	protected function hasPermissionToAccessKioskMode(): bool
	{
		return $this->access->checkAccess('read', '', $this->contentPageObject->getRefId());
	}

	/**
	 * @inheritDoc
	 */
	public function buildInitialState(State $empty_state): State
	{
	}

	/**
	 * @inheritDoc
	 */
	public function buildControls(State $state, ControlBuilder $builder)
	{
		$this->builtLearningProgressToggleControl($builder);
	}

	/**
	 * @param ControlBuilder $builder
	 */
	protected function builtLearningProgressToggleControl(ControlBuilder $builder)
	{
		$learningProgress = \ilObjectLP::getInstance($this->contentPageObject->getId());
		if ($learningProgress->getCurrentMode() == \ilLPObjSettings::LP_MODE_MANUAL) {
			$isCompleted = \ilLPMarks::_hasCompleted($this->user->getId(), $this->contentPageObject->getId());

			$this->lng->loadLanguageModule('copa');
			$learningProgressToggleCtrlLabel = $this->lng->txt('copa_btn_lp_toggle_state_completed');
			if (!$isCompleted) {
				$learningProgressToggleCtrlLabel = $this->lng->txt('copa_btn_lp_toggle_state_not_completed');
			}

			$builder->generic(
				$learningProgressToggleCtrlLabel,
				self::CMD_TOGGLE_LEARNING_PROGRESS,
				1
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function updateGet(State $state, string $command, int $param = null): State
	{
		$this->toggleLearningProgress($command);
	}

	/**
	 * @param string $command
	 */
	protected function toggleLearningProgress(string $command)
	{
		if (self::CMD_TOGGLE_LEARNING_PROGRESS === $command) {
			$learningProgress = \ilObjectLP::getInstance($this->contentPageObject->getId());
			if ($learningProgress->getCurrentMode() == \ilLPObjSettings::LP_MODE_MANUAL) {
				$marks = new \ilLPMarks($this->contentPageObject->getId(), $this->user->getId());
				$marks->setCompleted(!$marks->getCompleted());
				$marks->update();

				\ilLPStatusWrapper::_updateStatus($this->contentPageObject->getId(), $this->user->getId());
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function updatePost(State $state, string $command, array $post): State
	{
	}

	/**
	 * @inheritDoc
	 */
	public function render(
		State $state,
		Factory $factory,
		URLBuilder $url_builder,
		array $post = null
	): Component {
		\ilLearningProgress::_tracProgress(
			$this->user->getId(),
			$this->contentPageObject->getId(),
			$this->contentPageObject->getRefId(),
			$this->contentPageObject->getType()
		);

		$this->renderContentStyle();

		$forwarder = new \ilContentPagePageCommandForwarder(
			$this->httpRequest, $this->ctrl, $this->tabs, $this->lng, $this->contentPageObject
		);
		$forwarder->setPresentationMode(\ilContentPagePageCommandForwarder::PRESENTATION_MODE_EMBEDDED_PRESENTATION);

		$this->ctrl->setParameterByClass(\ilContentPagePageGUI::class, 'ref_id', $this->contentPageObject->getRefId());

		return $factory->legacy($forwarder->forward($this->ctrl->getLinkTargetByClass([
			\ilRepositoryGUI::class, \ilObjContentPageGUI::class, \ilContentPagePageGUI::class
		])));
	}

	/**
	 * Renders the content style of a ContentPage object into main template
	 */
	protected function renderContentStyle()
	{
		$this->mainTemplate->setVariable('LOCATION_CONTENT_STYLESHEET', \ilObjStyleSheet::getContentStylePath(
			$this->contentPageObject->getStyleSheetId()
		));
		$this->mainTemplate->setCurrentBlock('SyntaxStyle');
		$this->mainTemplate->setVariable('LOCATION_SYNTAX_STYLESHEET', \ilObjStyleSheet::getSyntaxStylePath());
		$this->mainTemplate->parseCurrentBlock();
	}
}