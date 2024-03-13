<?php

namespace Mpgdpr;

use \Model as OpenCartModel;

class Model extends OpenCartModel {
	public function __construct($registry) {
		parent :: __construct($registry);
		if (!$registry->has('mpgdpr')) {
			$registry->set('mpgdpr', new \Mpgdpr\Mpgdpr($registry));
		}
	}
}
