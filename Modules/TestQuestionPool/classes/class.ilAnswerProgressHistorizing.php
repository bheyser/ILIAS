<?php
/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Services/HistorizingStorage/classes/class.ilHistorizingStorage.php';

/**
 * Class ilAnswerProgressHistorizing
 *
 * @author Maximilian Becker <mbecker@databay.de>
 *
 * @version $Id$
 */
class ilAnswerProgressHistorizing extends ilHistorizingStorage
{

	/**
	 * Returns the defined name of the table to be used for historizing.
	 *
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example 'generic_history'
	 *
	 *
	 * @return string Name of the table which contains the historized records.
	 */
	protected static function getHistorizedTableName()
	{
		return 'hist_answer_progress';
	}

	/**
	 * Returns the column name which holds the current records version.
	 *
	 * The column is required to be able to hold an integer: Integer,4, not null, default 1
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example 'version'
	 *
	 *
	 * @return string Name of the column which is used to track the records version.
	 */
	protected static function getVersionColumnName()
	{
		return 'hist_version';
	}

	/**
	 * Returns the column name which holds the current records historic state.
	 *
	 * The column is required to be able to hold an integer representation of a boolean: Integer, 1, not null, default 0
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example 'is_historic'
	 *
	 *
	 * @return string Name of the column which holds the current records historic state.
	 */
	protected static function getHistoricStateColumnName()
	{
		return 'hist_historic';
	}

	/**
	 * Returns the column name which holds the current records creator id.
	 *
	 * The column is required to be able to hold an integer reference to the creator of the record.
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example 'creator_fi'
	 *
	 *
	 * @return string Name of the column which holds the current records creator.
	 */
	protected static function getCreatorColumnName()
	{
		return 'creator_user_id';
	}

	/**
	 * Returns the column name which holds the current records creation timestamp is integer.
	 *
	 * The column is required to be able to hold an integer unix-timestamp of the records creation.
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example 'created_ts'
	 *
	 *
	 * @return string Name of the column which holds the current records creator.
	 */
	protected static function getCreatedColumnName()
	{
		return 'created_ts';
	}

	/**
	 * Defines the content columns for the historized records.
	 *
	 * The array holds a definition so the parent class can check for correct parameters and place proper escaping.
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example
	 *      array(
	 *          'participation_state' => 'string',
	 *          'course_ref' => 'integer',
	 *          'creator' => 'string',
	 *          'creation' => 'integer',
	 *          'changed' => 'integer'
	 *      )
	 *
	 *
	 * @return Array Array with field definitions in the format "fieldname" => "datatype".
	 */
	protected static function getContentColumnsDefinition()
	{
		$definition =  array(
			'solution_id'		=> 'integer',
			'value1'			=> 'text',
			'value2'			=> 'text',
			'tstamp'			=> 'integer'
		);

		return $definition;
	}

	/**
	 * Returns the column name which holds the current records unique record id.
	 *
	 * The column is required to be able to hold an integer records db-id. This field needs a sequence and
	 * gets populated by the sequence.
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example 'row_id'
	 *
	 *
	 * @return string Name of the column which holds the current records database id.
	 */
	protected static function getRecordIdColumn()
	{
		return 'row_id';
	}

	/**
	 * Returns the column name which holds the current records case db-id.
	 *
	 * The column is required to be able to hold an integer. This integer gets populated during the initial setup of
	 * a case with the first versions record id. The first version of every case has case_id = row_id, subsequent
	 * rows differ from that.
	 * Please note that database abstraction layer constraints are not checked.
	 *
	 * @example 'case_id'
	 *
	 *
	 * @return array Name of the column which holds the current records case id.
	 */
	protected static function getCaseIdColumns()
	{
		return array(
			'active_fi'	 		=> 'integer',
			'question_fi'		=> 'integer',
			'pass'				=> 'integer'
		);
	}

}
