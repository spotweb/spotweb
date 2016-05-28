#!/bin/sh

set -e

#change SPOT_PATH to your root spotweb install
#change SPOT_SLEEP_TIME to the amount of seconds to wait between loops (300sec is 5 mins)

export SPOT_PATH="/var/www/spotweb/"
export SPOT_SLEEP_TIME="300" # in seconds
LASTOPTIMIZE=`date +%s`

while :

 do
CURRTIME=`date +%s`
cd ${SPOT_PATH}
/usr/bin/php5 ${SPOT_PATH}/retrieve.php

#DIFF=$(($CURRTIME-$LASTOPTIMIZE))
#if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
#then
#	LASTOPTIMIZE=`date +%s`
#	if [ -f /tmp/.spotweb-upgrade ]
#	then
#	echo "Upgrade already running. Not running again"
#  exit
#fi
# Creating Tempfile since the git update/upgrade isn't running yet
#touch /tmp/.spotweb-upgrade
#wait
#echo "git update"

#change to your spotweb install path 
#cd /var/www/spotweb
#git pull
#echo "Waiting till pull is done.."
#wait
#echo "Upgradeding db/clean up "
#/usr/bin/php5 ${SPOT_PATH}/upgrade-db.php
#wait
# removing tempfile
#rm /tmp/.spotweb-upgrade
	
	
#fi

echo "waiting ${SPOT_SLEEP_TIME} seconds..."
sleep ${SPOT_SLEEP_TIME}

done
