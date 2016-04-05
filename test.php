<?php

class MethodTest {
	
	private function runTest($aArguments)
	{
		echo $aArguments[0];
	}
	
    public function __call($name, $arguments) {
		
		call_user_func(array($this, $name), $arguments);
    }
}

$obj = new MethodTest;
$obj->runTest('!!!');