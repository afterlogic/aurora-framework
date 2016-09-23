#!/bin/bash

read -p "Tenant name: " response

tenant=""
if [ "$response" != "" ]; 
then
	tenant=" --tenant $response";
fi

#Build static data
cd ../modules

gulp js1:build --output app-files-pub $(echo "$tenant") --modules FilesWebclient
