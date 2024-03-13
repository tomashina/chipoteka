<?php

namespace Mpgdpr;

use \Controller as OpenCartController;

class Controller extends OpenCartController {
	public function __construct($registry) {
		parent :: __construct($registry);
		if (!$registry->has('mpgdpr')) {
			$registry->set('mpgdpr', new \Mpgdpr\Mpgdpr($registry));
		}
	}
}