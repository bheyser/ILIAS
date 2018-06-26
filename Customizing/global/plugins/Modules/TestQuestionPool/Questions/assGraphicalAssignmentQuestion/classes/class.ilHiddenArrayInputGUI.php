<?php

include_once './Services/Form/classes/class.ilHiddenInputGUI.php';

/**
 * Date: 27.02.13
 * Time: 14:10
 * @author Thomas Joußen <tjoussen@databay.de>
 * @package assGraphicalAssignmentQuestion
 */
class ilHiddenArrayInputGUI extends ilHiddenInputGUI
{

	/**
	 * Get the Value from the $_POST variable by an array of keys which are represented in
	 * the postvar like "element[0][item]"
	 *
	 * @param array $a_values
	 */
	public function setValueByArray($a_values)
	{
		$keys = $this->createKeysArray();

		$value = $a_values;
		foreach($keys as $key){
			$value = $value[$key];
		}

		$this->setValue($value);
	}

	/**
	 * Creates an array of keys of the postvar
	 *
	 * @return array
	 */
	private function createKeysArray()
	{
		$postvar = str_replace("]", "", $this->postvar);
		return explode("[", $postvar);
	}
}
