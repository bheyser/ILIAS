<?php /* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'include/inc.header.php';

/* @var ilAccess $ilAccess */
if( !$ilAccess->checkAccess('read', '', SYSTEM_FOLDER_ID) )
{
	die('administrative privileges only!');
}

/* @var \ILIAS\DI\Container $DIC */
try
{
	$query = "SELECT
		tst_tests.obj_fi,
		object_data.title test_title,
		usr_data.login,
		usr_data.firstname,
		usr_data.lastname,
		usr_data.usr_id,
		tst_active.active_id,
		tst_active.tries,
		tst_active.last_started_pass,
		tst_active.last_finished_pass,
		count(tst_sequence.active_fi) num_sequences,
		max(tst_sequence.pass) last_sequence
					   
		FROM tst_active
		LEFT JOIN usr_data
		ON usr_id = user_fi
		LEFT JOIN tst_sequence
		ON tst_sequence.active_fi = tst_active.active_id
		LEFT JOIN tst_tests
		ON tst_tests.test_id = tst_active.test_fi
		LEFT JOIN object_data
		ON obj_id = obj_fi
		
		GROUP BY
		tst_tests.obj_fi,
		tst_active.active_id,
		tst_active.tries,
		tst_active.last_finished_pass,
		usr_data.login,
		usr_data.firstname,
		usr_data.lastname
		
		HAVING num_sequences > 0
		AND last_finished_pass > (num_sequences - 1)
		AND last_sequence < last_finished_pass
		AND last_finished_pass = last_started_pass
	";
	
	$res = $DIC->database()->query($query);
	
	$rows = array();
	
	while($row = $DIC->database()->fetchAssoc($res))
	{
		$rows[] = $row;
	}
	
	if( !isset($_GET['fix']) )
	{
		echo "<div><a href='?fix'>CLICK HERE</a> to start repairing (!)</div>";
		
		echo "<pre>".print_r($rows, true)."</pre>";
		exit;
	}
	else
	{
		require_once 'Modules/Test/classes/class.ilObjTestAccess.php';
		require_once 'Services/Tracking/classes/class.ilLPStatusWrapper.php';

		foreach($rows as $row)
		{
			$objId = $row['obj_fi'];
			$usrId = $row['usr_id'];
			$activeId = $row['active_id'];
			
			$tries = $row['last_sequence'] + 1;
			$last_started_pass = $row['last_sequence'];
			$last_finished_pass = $row['last_sequence'];
			
			$DIC->database()->update('tst_active',
				array(
					'tries' => array('integer', $tries),
					'last_started_pass' => array('integer', $last_started_pass),
					'last_finished_pass' => array('integer', $last_finished_pass)
				),
				array(
					'active_id' => array('integer', $activeId)
				)
			);
			
			ilLPStatusWrapper::_updateStatus($objId, $usrId);
		}
	}
}
catch(ilException $e)
{
	echo "<pre>".$e."</pre>";
}
