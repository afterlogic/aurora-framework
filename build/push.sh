#!/bin/bash

read -p "Commit message: " response

tenant=""
if [ "$response" != "" ]; 
then
	tenant=" --tenant $response";
fi

#Build static data
cd ../modules

Modules=();
for dir in *;
do
	if [ -d "$dir" ];then
		Modules=("${Modules[@]}" "$dir");
	fi
done