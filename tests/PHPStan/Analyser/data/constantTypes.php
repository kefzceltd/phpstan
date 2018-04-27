<?php

namespace ConstantTypesIncrementDecrement;

$postIncrement = 1;
$postIncrement++;

$preIncrement = 1;
++$preIncrement;

$postDecrement = 5;
$postDecrement--;

$preDecrement = 5;
--$preDecrement;

$literalArray = [
	'a' => 1,
	'b' => 5,
	'c' => 1,
	'd' => 5,
];
$literalArray['a']++;
$literalArray['b']--;
++$literalArray['c'];
--$literalArray['d'];

$nullIncremented = null;
$nullDecremented = null;
$nullIncremented++;
$nullDecremented--;

$incrementInIf = 1;
$anotherIncrementInIf = 1;
$valueOverwrittenInIf = 1;
$anotherValueOverwrittenInIf = 10;
$appendingToArrayInBranches = [];
if (doFoo()) {
	$incrementInIf++;
	$anotherIncrementInIf++;
	$valueOverwrittenInIf = 2;
	$anotherValueOverwrittenInIf /= 2;
	$appendingToArrayInBranches[] = 1;
	$appendingToArrayInBranches[] = 2;
} elseif (doBar()) {
	$incrementInIf++;
	$incrementInIf++;
	$anotherIncrementInIf++;
	$anotherIncrementInIf++;
} else {
	$anotherIncrementInIf++;
}

$incrementInForLoop = 1;
$valueOverwrittenInForLoop = 1;
$arrayOverwrittenInForLoop = [
	'a' => 1,
	'b' => 'foo',
];
for ($i = 0; $i < 10; $i++) {
	$incrementInForLoop++;
	$valueOverwrittenInForLoop = 2;
	$arrayOverwrittenInForLoop['a']++;
	$arrayOverwrittenInForLoop['b'] = 'bar';
}

die;
