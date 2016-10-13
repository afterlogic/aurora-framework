#!/bin/bash

#Build static data
cd ../modules

Modules=();
for dir in *;
do
	if [ -d "$dir" ];then
		Modules=("${Modules[@]}" "$dir");
	fi
done

gulp js1:watch --output app --modules $(IFS=, ; echo "${Modules[*]}")
