<?php

/**
 * Date: 24.01.13
 * Time: 13:57
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 */
class assAnswerGraphicalAssignment
{

	public static $ANSWER_TYPE_SELECTION = 'answer_type_selection';
	public static $ANSWER_TYPE_TEXT = 'answer_type_text';

	/**
	 * The assAnswerGraphicalAssignment id
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The assAnswerGraphicalAssignment type
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The items, representing different answer possibilities of an assAnserGraphicalAssignment
	 *
	 * @var ASS_AnswerSimple[]
	 */
	private $items;

	/**
	 * The x coord of the destination in the canvas container
	 *
	 * @var int
	 */
	private $destination_x;

	/**
	 * The y coord of the destination in the canvas container
	 *
	 * @var int
	 */
	private $destination_y;

	/**
	 * The x coord of the target location in the canvas container
	 *
	 * @var int
	 */
	private $target_x;

	/**
	 * The y coord of the target location in the canvas container
	 *
	 * @var int
	 */
	private $target_y;

	/**
	 * Boolean to enable the shuffle mode for the answer items
	 *
	 * @var boolean
	 */
	private $shuffle;

	/**
	 * @param $type
	 */
	public function __construct($type)
	{
		$this->checkType($type);
		$this->id = -1;
		$this->type = $type;
		$this->items = array();
		$this->destination_x = 0.0;
		$this->destination_y = 0.0;
		$this->target_x = 0.0;
		$this->target_y = 0.0;
		$this->shuffle = false;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}


	/**
	 * @param int $destination_x
	 */
	public function setDestinationX($destination_x)
	{
		$this->destination_x = $destination_x;
	}

	/**
	 * @return int
	 */
	public function getDestinationX()
	{
		return $this->destination_x;
	}

	/**
	 * @param int $destination_y
	 */
	public function setDestinationY($destination_y)
	{
		$this->destination_y = $destination_y;
	}

	/**
	 * @return int
	 */
	public function getDestinationY()
	{
		return $this->destination_y;
	}

	/**
	 * @param ASS_AnswerSimple[] $items
	 */
	public function setItems($items)
	{
		$this->items = $items;
	}

	/**
	 * @return ASS_AnswerSimple[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * Get an array with shuffled items from the assAnswerGraphicalAssignment
	 *
	 * @return ASS_AnswerSimple[]
	 */
	public function getItemsShuffled(){
		if($this->shuffle)
		{
			return $this->arrayShuffle($this->items);
		}
		return $this->items;
	}

	/**
	 * @param ASS_AnswerSimple $item
	 */
	public function addItem($item){
		$this->items[] = $item;
	}

	/**
	 * @param int $target_x
	 */
	public function setTargetX($target_x)
	{
		$this->target_x = $target_x;
	}

	/**
	 * @return int
	 */
	public function getTargetX()
	{
		return $this->target_x;
	}

	/**
	 * @param int $target_y
	 */
	public function setTargetY($target_y)
	{
		$this->target_y = $target_y;
	}

	/**
	 * @return int
	 */
	public function getTargetY()
	{
		return $this->target_y;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param boolean $shuffle
	 */
	public function setShuffle($shuffle)
	{
		$this->shuffle = $shuffle;
	}

	/**
	 * @return boolean
	 */
	public function getShuffle()
	{
		return $this->shuffle;
	}

	public function getMaximumPoints(){
		$points = 0;
		foreach($this->items as $item){
			if($item->getPoints() > $points){
				$points = $item->getPoints();
			}
		}
		return $points;
	}

	/**
	 * Get a string with the destination and target coords of the assAnswerGraphicalAssignment
	 *
	 * @example destination_x,destination_y,target_x,target_y
	 *
	 * @return string
	 */
	public function getCoords(){
		$coords = array(
			$this->destination_x,
			$this->destination_y,
			$this->target_x,
			$this->target_y
		);

		return \implode(",", $coords);
	}

	/**
	 * Set a string with the destination and target coords of the assAnswerGraphicalAssignment
	 *
	 * @param string $coords
	 */
	public function setCoords($coords){
		$coord_array = \explode(",", $coords);

		$this->destination_x = $coord_array[0];
		$this->destination_y = $coord_array[1];
		$this->target_x = $coord_array[2];
		$this->target_y = $coord_array[3];
	}

	/**
	 * Create a new Item for the assAnswerGraphicalAssignment
	 *
	 * @return ASS_AnswerSimple
	 */
	public function createItem()
	{
		include_once("./Modules/TestQuestionPool/classes/class.assAnswerSimple.php");

		return new ASS_AnswerSimple();
	}

	/**
	 * Check if the delivered type is supported by the assAnswerGraphicalAssignment
	 *
	 * @see assAnswerGraphicalAssignment::$ANSWER_TYPE_SELECTION The type of an selection answer
	 * @see assAnswerGraphicalAssignment::$ANSWER_TYPE_TEXT The type of an text input answer
	 *
	 * @param string $type
	 */
	private function checkType($type){
		if($type != self::$ANSWER_TYPE_SELECTION && $type != self::$ANSWER_TYPE_TEXT){
			require_once 'class.ilUnsupportedException.php';
			throw new ilUnsupportedException(sprintf("The type '%s' is not supported by assGraphicalAssignmentQuestion", $type), 1360850598);
		}
	}

	/**
	 * Shuffles the item array with a randomizer
	 *
	 * @return array
	 */
	private function arrayShuffle($array){
		$keys = array_keys($array);

		mt_srand((double)microtime()*1000000);
		$i = count($keys);
		if ($i > 0)
		{
			while(--$i)
			{
				$j = mt_rand(0, $i);
				if ($i != $j)
				{
					// swap elements
					$tmp = $keys[$j];
					$keys[$j] = $keys[$i];
					$keys[$i] = $tmp;
				}
			}
		}

		$shuffled = array();
		foreach($keys as $key){
			$shuffled[$key] = $array[$key];
		}

		return $shuffled;
	}
}
