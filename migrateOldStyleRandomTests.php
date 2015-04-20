<?php

// ---------------------------------------------------------------------------------------------------------------------

if( PHP_SAPI == 'cli' )
{
	include_once "Services/Context/classes/class.ilContext.php";
	ilContext::init(ilContext::CONTEXT_CRON);

	include_once 'Services/Authentication/classes/class.ilAuthFactory.php';
	ilAuthFactory::setContext(ilAuthFactory::CONTEXT_CRON);

	$_COOKIE["ilClientId"] = $_SERVER['argv'][3];
	$_POST['username'] = $_SERVER['argv'][1];
	$_POST['password'] = $_SERVER['argv'][2];

	if($_SERVER['argc'] < 4)
	{
		die("Usage: cron.php username password client\n");
	}
}

try
{
	require_once 'include/inc.header.php';
}
catch(Exception $e)
{
	echo 'Unknown trouble during ilInit!';
	exit(126);
}


if(!$rbacsystem->checkAccess('visible,read', SYSTEM_FOLDER_ID))
{
	echo 'Sorry, this script requires administrative privileges!';
	exit(125);
}

// ---------------------------------------------------------------------------------------------------------------------

class ilOldStyleRandomTestMigration
{
	// -----------------------------------------------------------------------------------------------------------------

	const MAX_QUESTION_DUPLICATIONS = 1000;
	
	const LOCK_FILE = 'migrateOldStyleRandomTests.lock';
	
	/**
	 * @var ilDB
	 */
	private $db;

	/**
	 * @var int
	 */
	private $migrationState;

	/**
	 * @var ressource
	 */
	private $lockFileHandle;

	/**
	 * @var integer
	 */
	private $startTime;

	/**
	 * @param integer
	 */
	private $memoryPeak;

	/**
	 * @var string
	 */
	private $flushString;

	/**
	 * @var string
	 */
	private $newLine;
	
	// -----------------------------------------------------------------------------------------------------------------

	public function __construct(ilDB $db)
	{
		$this->db = $db;
		$this->migrationState = 0;
		$this->startTime = null;
		$this->memoryPeak = null;

		if( PHP_SAPI == 'cli' )
		{
			$this->flushString = '';
			$this->newLine = "\n";
		}
		else
		{
			$this->flushString = "\n";
			$this->newLine = '<br />';
		}
	}

	// -----------------------------------------------------------------------------------------------------------------

	private function checkPreconditions()
	{
		// check ilias code base
		
		if( !$this->db->tableColumnExists('tst_tests', 'broken') )
		{
			throw new Exception(
				'ILIAS database version too old, please update your installation and run required dbupdates/hotfixes! '.
				'(Required DB version: 4.4.x - Hotfix #67, 5.0.x - Hotfix #3, 5.1.x and above - DBupdate #4478)'
			);
		}
		
		// check user abort behaviour
		
		if( ignore_user_abort() )
		{
			ignore_user_abort(false);
			
			if( ignore_user_abort() )
			{
				throw new Exception(
					'PHP will ignore user aborts, the setting could not be changed at runtime! '.
					'Please adjust your PHP settings to stop PHP processes on user aborts!'
				);
			}
		}
		
		// check php timeout
		
		if( ini_get('max_execution_time') )
		{
			set_time_limit(0);
			
			if( ini_get('max_execution_time') )
			{
				throw new Exception(
					'PHP execution time is limited, the setting could not be changed at runtime! '.
					'Please adjust your PHP settings to allow unlimited execution!'
				);
			}
		}
	}

	// -----------------------------------------------------------------------------------------------------------------

	private function requestMigrationLock()
	{
		$this->lockFileHandle = fopen(self::LOCK_FILE, 'w');
		flock($this->lockFileHandle, LOCK_EX);
	}

	private function releaseMigrationLock()
	{
		flock($this->lockFileHandle, LOCK_UN);
		fclose($this->lockFileHandle);
	}
	
	// -----------------------------------------------------------------------------------------------------------------

	private function getMigrationState()
	{
		return $this->migrationState;
	}

	private function initMigrationState()
	{
		$setting = new ilSetting();
		$this->migrationState = (int)$setting->get('tst_mig_rnd_tst_state', 0);
	}

	private function updateMigrationState()
	{
		$this->migrationState++;
		
		$setting = new ilSetting();
		$setting->set('tst_mig_rnd_tst_state', $this->migrationState);
	}
	
	private function getMigrationStateString()
	{
		return $this->getMigrationState() . '/7';
	}

	// -----------------------------------------------------------------------------------------------------------------

	private function getRuntime()
	{
		$runTime = time() - $this->startTime;

		$hours = floor($runTime / 3600);
		$mins = floor(($runTime - ($hours*3600)) / 60);
		$secs = floor($runTime % 60);

		return "$hours:$mins:$secs";
	}
	
	private function getFormatedMemoryPeak()
	{
		$bytes = $this->memoryPeak;
		
		$memoryUnits = array('', 'kilobyte(s)', 'megabyte(s)', 'gigabyte(s)');

		$i = 0;
		while(1023 < $bytes)
		{
			$bytes /= 1024;
			++$i;
		}

		return  $i ? (round($bytes, 2).' '.$memoryUnits[$i]) : ($bytes.' byte(s)');
	}
	
	private function trackMemoryUsagePeak()
	{
		$usedMemory = memory_get_usage();
		
		if( $usedMemory > $this->memoryPeak )
		{
			$this->memoryPeak = $usedMemory;
		}
	}
	
	private function printProgress($uselessLoop = false)
	{
		$this->trackMemoryUsagePeak();
		echo ($uselessLoop ? ',' : '.').$this->flushString; flush(); ob_flush();
	}
	
	private function printLine($message)
	{
		echo $this->newLine . $message . $this->newLine . $this->flushString; flush(); ob_flush();
	}

	private function printStartState()
	{
		$this->startTime = time();
		$this->printLine("Started with migration state ".$this->getMigrationStateString());
	}

	private function printFinishState()
	{
		$this->printLine(
			"Finished with migration state ".$this->getMigrationStateString().
			" - Memory Peak: ".$this->getFormatedMemoryPeak().
			" - Runtime: ".$this->getRuntime()
		);
	}

	private function printLastStateReached()
	{
		$this->printLine("The last migration state has allready been reached!");
	}

	// -----------------------------------------------------------------------------------------------------------------
	
	public function perform()
	{
		$this->checkPreconditions();

		$this->requestMigrationLock();

		$this->initMigrationState();
		
		$this->printStartState();
		
		switch( $this->getMigrationState() )
		{
			case 0: $this->initMigrationWorkTables();
					$this->updateMigrationState();
					break;
			
			case 1: $this->determineOldStyleRandomTests();
					$this->updateMigrationState();
					break;
			
			case 2: $this->determineBrokenTestsFixability();
					$this->updateMigrationState();
					break;
			
			case 3: $this->collectRequiredPoolToTestClonings();
					$this->updateMigrationState();
					break;
				
			case 4: $this->collectRequiredQuestionDuplicates();
					$this->updateMigrationState();
					break;
				
			case 5: if( $this->performQuestionDuplication() )
					{
						$this->updateMigrationState();
					}
					break;
			
			case 6: $this->markFinalBrokenTests();
					$this->updateMigrationState();
					break;
			
			case 7: $this->printLastStateReached();
					break;
			
			default: throw new Exception(
				'invalid migration state ('.$this->getMigrationState().')'
			);
		}

		$this->printFinishState();
		
		$this->releaseMigrationLock();
		
		if( $this->getMigrationState() == 7 )
		{
			return 0;
		}
		
		return $this->getMigrationState();
	}

	// -----------------------------------------------------------------------------------------------------------------

	private function initMigrationWorkTables() // state 0 -> 1
	{
		// create tables required for working
		
		if( !$this->db->tableExists('tmp_mig_rnd_tst') )
		{
			$this->db->createTable('tmp_mig_rnd_tst', array(
				'tst' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
				),
				'broken' => array(
					'type' => 'integer',
					'length' => 1,
					'notnull' => false,
					'default' => null
				)
			));

			$this->db->addPrimaryKey('tmp_mig_rnd_tst', array('tst'));
		}

		if( !$this->db->tableExists('tmp_mig_pool_cloning') )
		{
			$this->db->createTable('tmp_mig_pool_cloning', array(
				'tst' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
				),
				'pool' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
				),
				'cloned' => array(
					'type' => 'integer',
					'length' => 1,
					'notnull' => false,
					'default' => null
				)
			));

			$this->db->addPrimaryKey('tmp_mig_pool_cloning', array('tst', 'pool'));
		}

		if( !$this->db->tableExists('tmp_mig_qst_duplic') )
		{
			$this->db->createTable('tmp_mig_qst_duplic', array(
				'tst' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
				),
				'pool' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
				),
				'qst' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
				)
			));

			$this->db->addPrimaryKey('tmp_mig_qst_duplic', array('tst', 'pool', 'qst'));
		}
		
		return true;
	}

	// -----------------------------------------------------------------------------------------------------------------
	
	private function determineOldStyleRandomTests() // state 1 -> 2
	{
		// get all random tests ...
		// - with existing entries in tst_rnd_quest_set_qpls
		// - but no existing entries in tst_rnd_cpy 
		// - not yet inserted to tmp_mig_rnd_tst

		$res = $this->db->query("
			SELECT DISTINCT test_id FROM tst_tests
			LEFT JOIN tmp_mig_rnd_tst ON tst = test_id
			LEFT JOIN tst_rnd_cpy ON tst_fi = test_id
			LEFT JOIN tst_rnd_quest_set_qpls ON test_fi = test_id
			WHERE tst IS NULL
			AND copy_id IS NULL
			AND def_id IS NOT NULL
		");
		
		$this->printProgress();
		
		$registerStmt = $this->db->prepareManip("
			INSERT INTO tmp_mig_rnd_tst (tst, broken) VALUES (?, ?)
			", array('integer', 'integer')
		);

		$this->printProgress();

		while( $row = $this->db->fetchAssoc($res) )
		{
			$this->db->execute(
				$registerStmt, array($row['test_id'], null)
			);

			$this->printProgress();
		}

		return true;
	}

	// -----------------------------------------------------------------------------------------------------------------
	
	private function determineBrokenTestsFixability() // state 2 -> 3
	{
		// determine missing fixable/final-broken states ...
		// - for tests without yet determined state
		// - by checking the deletion state of corresponding pools

		$checkStmt = $this->db->prepare("
			SELECT COUNT(pool_fi)
			FROM tst_rnd_quest_set_qpls
			INNER JOIN object_reference ON obj_id = pool_fi
			WHERE test_fi = ? AND deleted IS NOT NULL
			", array('integer')
		);

		$this->printProgress();
		
		$persistStmt = $this->db->prepareManip("
			UPDATE tmp_mig_rnd_tst SET broken = ? WHERE tst = ?
			", array('integer', 'integer')
		);

		$this->printProgress();
		
		$res1 = $this->db->query("SELECT tst FROM tmp_mig_rnd_tst WHERE broken IS NULL");

		$this->printProgress();

		while( $row1 = $this->db->fetchAssoc($res1) )
		{
			$row2 = $this->db->fetchAssoc(
				$this->db->execute($checkStmt, array($row1['tst']))
			);

			$this->printProgress();
			
			$broken = (
				$row2['cnt'] > 0 ? 1 : 0
			);

			$this->db->execute($persistStmt, array($broken, $row1['tst']));

			$this->printProgress();
		}

		return true;
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	private function collectRequiredPoolToTestClonings() // state 3 -> 4
	{
		// determine all required test pool clonings not registered yet
		 
		$res = $this->db->query("
			SELECT defs.test_fi, defs.pool_fi
			
			FROM tmp_mig_rnd_tst rndtst
			
			INNER JOIN tst_rnd_quest_set_qpls defs
			ON defs.test_fi = rndtst.tst
			
			LEFT JOIN tmp_mig_pool_cloning poolclone
			ON poolclone.tst = defs.test_fi
			AND poolclone.pool = defs.pool_fi
			
			WHERE poolclone.tst IS NULL
			AND poolclone.pool IS NULL
		");

		$this->printProgress();
		
		$registerStmt = $this->db->prepareManip("
			INSERT INTO tmp_mig_pool_cloning (tst, pool, cloned) VALUES (?, ?, ?)
			", array('integer', 'integer', 'integer')
		);

		$this->printProgress();
		
		while( $row = $this->db->fetchAssoc($res) )
		{
			$this->db->execute(
				$registerStmt, array($row['test_fi'], $row['pool_fi'], 0)
			);

			$this->printProgress();
		}

		return true;
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	private function collectRequiredQuestionDuplicates() // state 4 -> 5
	{
		// - collect all not yet registered questions from pools required for cloning
		// - store them as tst/pool/qst combination
		
		$res1 = $this->db->queryF(
			"SELECT tst, pool FROM tmp_mig_pool_cloning WHERE cloned = %s", array('integer'), array(0)
		);

		$this->printProgress();
		
		$fetchStmt = $this->db->prepare("
			SELECT question_id FROM qpl_questions
			LEFT JOIN tmp_mig_qst_duplic ON tst = ? AND pool = obj_fi AND qst = question_id
			WHERE obj_fi = ? AND original_id IS NULL
			AND qst IS NULL
			", array('integer', 'integer')
		);

		$this->printProgress();
		
		$registerStmt = $this->db->prepareManip("
			INSERT INTO tmp_mig_qst_duplic (tst, pool, qst) VALUES (?, ?, ?)
			", array('integer', 'integer', 'integer')
		);

		$this->printProgress();
		
		$clonedStmt = $this->db->prepareManip("
			UPDATE tmp_mig_pool_cloning SET cloned = ? WHERE tst = ? AND pool = ?
			", array('integer', 'integer', 'integer')
		);

		$this->printProgress();
		
		while( $row1 = $this->db->fetchAssoc($res1) )
		{
			$res2 = $this->db->execute(
				$fetchStmt, array($row1['tst'], $row1['pool'])
			);

			$this->printProgress();

			while( $row2 = $this->db->fetchAssoc($res2) )
			{
				$this->db->execute(
					$registerStmt, array($row1['tst'], $row1['pool'], $row2['question_id'])
				);

				$this->printProgress();
			}
			
			$this->db->execute(
				$clonedStmt, array(1, $row1['tst'], $row1['pool'])
			);

			$this->printProgress();
		}

		return true;
	}
	
	// -----------------------------------------------------------------------------------------------------------------

	private function performQuestionDuplication() // state 5 -> 6
	{
		// duplicate all questions registered for duplication
		// without any duplicate stored in stage of corresponding test

		require_once 'Modules/TestQuestionPool/classes/class.assQuestion.php';

		$this->printProgress();
		
		$res = $this->db->query("
			SELECT tst, pool, qst FROM tmp_mig_qst_duplic

			INNER JOIN qpl_questions orig ON orig.question_id = qst
			LEFT JOIN qpl_questions dups ON dups.original_id = orig.question_id AND dups.obj_fi = tst

			LEFT JOIN tst_rnd_cpy ON tst_fi = tst AND qpl_fi = pool AND qst_fi = dups.question_id

			WHERE copy_id IS NULL
			
			GROUP BY tst, pool, qst
		");

		$this->printProgress();

		$storeStmt = $this->db->prepareManip(
			"INSERT INTO tst_rnd_cpy (copy_id, tst_fi, qst_fi, qpl_fi) VALUES (?, ?, ?, ?)",
			array('integer', 'integer', 'integer', 'integer')
		);

		$this->printProgress();

		$handled = array();
		
		$i = 0;

		while( $row = $this->db->fetchAssoc($res) )
		{
			if($i > self::MAX_QUESTION_DUPLICATIONS)
			{
				return false;
			}
			
			$key = "{$row['tst']}::{$row['pool']}::{$row['qst']}";

			if(isset($handled[$key]))
			{
				$this->printProgress(true);
				continue;
			}

			$handled[$key] = $key;

			$question = assQuestion::_instantiateQuestion($row['qst']);
			$dupId = $question->duplicate(true, null, null, null, $row['tst']);

			$this->printProgress();

			$nextId = $this->db->nextId('tst_rnd_cpy');

			$this->printProgress();

			$this->db->execute($storeStmt, array($nextId, $row['tst'], $dupId, $row['pool']));

			$this->printProgress();
			
			$i++;
		}

		return true;
	}

	// -----------------------------------------------------------------------------------------------------------------

	private function markFinalBrokenTests() // state 6 -> 7
	{
		// mark all final broken random tests within new column 'broken' in core table tst_tests

		$this->db->manipulateF("
				UPDATE tst_tests SET broken = %s WHERE test_id IN(
					SELECT tst FROM tmp_mig_rnd_tst WHERE broken = %s
				)
			", array('integer', 'integer'), array(1, 1)
		);

		$this->printProgress();

		return true;
	}

	// -----------------------------------------------------------------------------------------------------------------
}

try
{
	$migration = new ilOldStyleRandomTestMigration($ilDB);
	$exitCode = $migration->perform();
}
catch(Exception $e)
{
	$exitCode = 127;
}

exit((int)$exitCode);