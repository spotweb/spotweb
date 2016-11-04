#!/bin/bash
rm -rf build/
mkdir build
cp -R src/Prowl build/
cat package.xml | sed s/#date#/`date +"%Y-%m-%d"`/ > build/package.xml
cd build/
pear package
