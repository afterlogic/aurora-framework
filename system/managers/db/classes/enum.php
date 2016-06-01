<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Db
 * @subpackage Enum
 */
class ESyncVerboseType extends AEnumeration
{
	const CreateTable = 0;
	const CreateField = 1;
	const DeleteField = 2;
	const CreateIndex = 3;
	const DeleteIndex = 4;
}
