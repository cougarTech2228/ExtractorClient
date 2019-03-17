#!/usr/bin/env bash
said="no"
clear
echo ">> Quick wipe <<"

if [[ -f "composer.json" ]] || [[ -d ".git" ]]
then
  echo "Please create a valid build dir."
  exit 1
fi

if [[ "$(which adb)" == "" ]]
then
  echo "Android debugger is missing. Please install the Android Studio."
  exit 1
fi

while true
do
  if [[ "$said" == "no" ]]
  then
    echo -n "> Searching"
    said="yes"
  fi
  echo -n "."
  if [[ "$(adb devices | tail -n 2 | head -n 1 | awk -F' ' '{ print $2 }')" == "device" ]]
  then
    echo
    echo "> Found."
    echo "> Clearing..."
    adb shell 'rm -rf /storage/emulated/0/www/public/*' > /dev/null
    echo "> Sending..."
    adb push ./* /storage/emulated/0/www/public/ > /dev/null
    echo -n "> Waiting for disconnect"
    afplay /System/Library/Sounds/Glass.aiff
    said="no"
    while [[ "$(adb devices | tail -n 2 | head -n 1 | awk -F' ' '{ print $2 }')" == "device" ]]
    do
      echo -n "."
      sleep 1
    done
    echo
  fi
  sleep 1
  done
