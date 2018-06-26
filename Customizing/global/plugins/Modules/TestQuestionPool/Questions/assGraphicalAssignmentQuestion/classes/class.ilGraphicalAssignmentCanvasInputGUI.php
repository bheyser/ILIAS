<?php

require_once "./Services/Form/classes/class.ilImageFileInputGUI.php";

/**
 * Date: 16.01.13
 * Time: 16:41
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 */
class ilGraphicalAssignmentCanvasInputGUI extends ilImageFileInputGUI
{

	private static $DEFAULT_PROPERTY_TEMPLATE = "tpl.prop_grasqst_image.html";
	private static $DEFAULT_TEMPLATE_PATH = "Customizing/global/plugins/Modules/TestQuestionPool/Questions/assGraphicalAssignmentQuestion";

	/**
	 * The width for the canvas container
	 * @var int
	 */
	private $canvas_width;

	/**
	 * The height for the canvas container
	 * @var int
	 */
	private $canvas_height;

	/**
	 * The path to the image rendered in the canvas container
	 *
	 * @var string
	 */
	private $image_path;

	/**
	 * The color of the elements rendered in the canvas container
	 *
	 * @var string
	 */
	private $color;

	/**
	 * @param string $title
	 * @param string $postvar
	 */
	public function __construct($title = "", $postvar = "")
	{
		parent::__construct($title, $postvar);
	}

	/**
	 * Set the size for the canvas container
	 *
	 * @param int $width
	 * @param int $height
	 */
	public function setSize($width, $height)
	{
		$this->canvas_width = $width;
		$this->canvas_height = $height;
	}

	/**
	 * Set the size from an array for the canvas container
	 * array(
	 * 		0: width,
	 * 		1: height
	 * )
	 *
	 * @param int $width
	 * @param int $height
	 */
	public function setSizeArray($size)
	{
		$this->canvas_width = $size[0];
		$this->canvas_height = $size[1];
	}

	/**
	 * Set the path of the image represented in the canvas container
	 *
	 * @param string $path
	 */
	public function setImagePath($path)
	{
		$this->image_path = $path;
	}

	/**
	 * @param string $color
	 */
	public function setColor($color)
	{
		$this->color = $color;
	}

	/**
	 * @return string
	 */
	public function getColor()
	{
		return $this->color;
	}

	/**
	 * Renders the template for the ilGraphicalAssignmentCanvasInputGUI
	 *
	 * @param $a_tpl
	 *
	 * @global ilLanguage $lng
	 */
	public function insert(&$a_tpl)
	{
		global $lng;

		$template = new ilTemplate(self::$DEFAULT_PROPERTY_TEMPLATE, true, true, self::$DEFAULT_TEMPLATE_PATH);
		$this->outputSuffixes($template, "allowed_image_suffixes");

		$template->setVariable("TEXT_IMAGE_NAME", $this->getValue());
		$template->setVariable("POST_VAR_D", $this->getPostVar());
		$template->setVariable("SRC_IMAGE", $this->getImage());
		$template->setVariable("TXT_DELETE_EXISTING", $lng->txt("delete_existing_file"));
		$template->setVariable("CANVAS_COLOR", $this->getColor());
		$template->setVariable("UPLOAD", $lng->txt('qpl_qst_grasqst_upload'));

		if($this->getValue() != ''){
			$template->setCurrentBlock("canvas_wrapper");
			$template->setVariable("CANVAS_WIDTH", $this->canvas_width);
			$template->setVariable("CANVAS_HEIGHT", $this->canvas_height);
			if($this->getDisabled())
			{
				$template->touchBlock("correction_block");
			}
			$template->setVariable("ADD_ELEMENT", $lng->txt('qpl_qst_grasqst_add_element'));
			$template->parseCurrentBlock();
		}

		$template->setVariable("POST_VAR", $this->getPostVar());
		$template->setVariable("ID", $this->getFieldId());
		$template->setVariable("TXT_MAX_SIZE", $lng->txt("file_notice") . " " . $this->getMaxFileSizeString());

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $template->get());
		$a_tpl->parseCurrentBlock();
	}
}
