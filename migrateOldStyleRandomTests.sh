#!/bin/bash


echo "Bitte PHP Interpreter eingeben: "
read PHPCMD

echo "Bitte ILIAS Admin Benutzer eingeben: "
read ILUSER

echo "Bitte Passwort des Benutzers eingeben: "
read -s ILPASS

echo "Bitte ILIAS Client ID eingeben: "
read ILCLIENT


if [ "$PHPCMD" = "" ]; then
    PHPCMD=/usr/bin/php
fi

LOGFILE="migrateOldStyleRandomTests_`date +%F--%H-%M-%S`.log"


STATUSCODE=1

while [ $STATUSCODE -gt "0" ] && [ $STATUSCODE -lt "7" ]; do
    
    $PHPCMD migrateOldStyleRandomTests.php $ILUSER $ILPASS $ILCLIENT >> $LOGFILE
    STATUSCODE=$?
    
    CURTIME=`date +%F %H:%M:%S`
    
    if [ $STATUSCODE = "0" ]; then
        echo "finished to state 7 at $CURTIME"
    el
        echo "state $STATUSCODE in progress - $CURTIME"
    fi
    
done;