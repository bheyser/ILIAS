#!/bin/bash


echo "Bitte PHP Interpreter eingeben [/usr/bin/php]: "
read PHPCMD
echo
if [ "$PHPCMD" = "" ]; then
    PHPCMD=/usr/bin/php
fi


echo "Bitte ILIAS Client ID eingeben [default]: "
read ILCLIENT
echo
if [ "$ILCLIENT" = "" ]; then
    ILCLIENT=default
fi

echo "Bitte ILIAS Admin Benutzer eingeben [root]: "
read ILUSER
echo
if [ "$ILUSER" = "" ]; then
    ILUSER=root
fi

echo "Bitte Passwort des Benutzers eingeben [homer]: "
read -s ILPASS
echo
if [ "$ILPASS" = "" ]; then
    ILPASS=homer
fi



LOGFILE="migrateOldStyleRandomTests_`date +%F--%H-%M-%S`.log"

CURTIME=`date "+%F %H:%M:%S"`
echo "startet migration at ${CURTIME}"
echo "SH: startet migration at ${CURTIME}" >> $LOGFILE

STATUSCODE=1

while [ $STATUSCODE -gt "0" ] && [ $STATUSCODE -lt "7" ]; do
    
    $PHPCMD migrateOldStyleRandomTests.php $ILUSER $ILPASS $ILCLIENT >> $LOGFILE
    STATUSCODE=$?
    
    CURTIME=`date "+%F %H:%M:%S"`
    
    if [ $STATUSCODE -eq "0" ]; then
        CURSTATE="7"
    else
        CURSTATE="$STATUSCODE"
    fi
    
    echo "current state is ${CURSTATE} - ${CURTIME}"
    
done;

CURTIME=`date "+%F %H:%M:%S"`
echo "finished migration at ${CURTIME}"
echo "SH: finished migration at ${CURTIME}" >> $LOGFILE
