<?php

namespace ConstantCondition;

class BooleanOr
{

	public function doFoo(int $i, bool $j, \stdClass $std, ?\stdClass $nullableStd)
	{
		if ($i || $j) {

		}

		$one = 1;
		if ($one || $i) {

		}

		if ($i || $std) {

		}

		$zero = 0;
		if ($zero || $i) {

		}
		if ($i || $zero) {

		}
		if ($one === 0 || $one) {

		}
		if ($one === 1 || $one) {

		}
		if ($nullableStd || $nullableStd) {

		}
		if ($nullableStd !== null || $nullableStd) {

		}
	}

}
