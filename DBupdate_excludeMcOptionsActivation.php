<?php /* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'include/inc.header.php';

/* @var ilAccess $ilAccess */
if( !$ilAccess->checkAccess('read', '', SYSTEM_FOLDER_ID) )
{
	die('administrative privileges only!');
}

/* @var \ILIAS\DI\Container $DIC */
$ilDB = $DIC->database();

try
{
    // use $ilDB for doing updates

    if( !$ilDB->tableColumnExists('tst_tests', 'exclude_mc_options') )
    {
        $ilDB->addTableColumn('tst_tests', 'exclude_mc_options', array(
            'type' => 'integer',
            'notnull' => false,
            'length' => 1,
            'default' => 0
        ));
    }

    echo '[ OK ]';
}
catch(ilException $e)
{
    echo "<pre>{$e}</pre>";
}
