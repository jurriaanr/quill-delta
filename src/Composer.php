<?php
/**
 * User: Jurriaan Ruitenberg
 * Date: 24-7-2018
 * Time: 12:20
 */

namespace Oberon\Quill\Delta;

class Composer {
	/**
	 * @param $fullChanges
	 * @return Delta
	 */
	public function compose($fullChanges) {
		return array_reduce($fullChanges, function (Delta $delta, $ops) {
			$comp = $delta->compose(new Delta($ops));
			
			return $comp;
		}, new Delta());
	}
}
