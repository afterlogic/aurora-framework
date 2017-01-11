#!/bin/bash

read -p "Tenant name: " response

tenant=""
if [ "$response" != "" ]; 
then
	tenant=" --tenant $response";
fi

#Build static data
cd ../modules

gulp js:build --output app-files-pub $(echo "$tenant") --modules FilesWebclient
