<?php

namespace LoopVariables;

function () {
	$foo = null;
	$key = null;
	$val = null;
	foreach ([1, 2, 3] as $key => $val) {
		'begin';
		$foo = new Foo();
		'afterAssign';

		if (something()) {
			$foo = new Bar();
			break;
		}
		if (something()) {
			$foo = new Baz();
			return;
		}
		if (something()) {
			$foo = new Lorem();
			continue;
		}

		'end';
	}

	$emptyForeachKey = null;
	$emptyForeachVal = null;
	foreach ([1, 2, 3] as $emptyForeachKey => $emptyForeachVal) {

	}

	'afterLoop';
};
