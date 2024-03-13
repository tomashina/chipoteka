<?php

namespace Mpgdpr;

class output_modification {
	public static function modify($route, $content, $xml) {
		// $xml = "{
		// 	* name: Contact us Change,
		// 	* operation : [{
		// 		attr: {
		// 			error: abort | skip
		// 		},
		// 		tag: {
		// 			ignoreif: {
		// 				text: ingore_if_string_to_add_already_exists,
		// 				attr: {
		// 					regex: true|false, // default is false
		// 					trim: true|false, // default is true
		// 				}
		// 			}
		// 			* search: {
		// 				text: string_to_search,
		// 				attr: {
		// 					regex: true|false, // default is false
		// 					limit: -1 // only use if regex is true
		// 					trim: true|false, // default is true
		// 					index: 0,  // default is 0
		// 				}
		// 			},
		// 			* add: {
		// 				text: string_to_add,
		// 				attr: {
		// 					regex: true|false // default is false
		// 					trim: true|false, // default is true
		// 					index: 0, // default is 0
		// 					position:before | after | replace, // default is replace
		// 					offset: 0, // default is 0
		// 				}
		// 			}
		// 		}
		// 	}]
		// }";


		//Log
		$log = array();

		$modification = array();

		$key = str_replace('/', '_', $route);

		if (!empty($xml)) {
			// Log
			$log[] = 'MOD: ' . $xml['name'];

			// Wipe the past modification store in the backup array
			$recovery = array();

			// Set the a recovery of the modification code in case we need to use it if an abort attribute is used.
			if (isset($modification)) {
				$recovery = $modification;
			}

			$operations = $xml['operation'];

			// If file contents is not already in the modification array we need to load it.
			if (!isset($modification[$key])) {

				$modification[$key] = preg_replace('~\r?\n~', "\n", $content);
				$original[$key] = preg_replace('~\r?\n~', "\n", $content);

				// Log
				$log[] = PHP_EOL . 'FILE: ' . $key;
			}

			foreach ($operations as $operation) {
				$error = '';
				// try to set default value instead of isset
				if (isset($operation['attr']['error'])) {
					$error = $operation['attr']['error'];
				}

				// Ignoreif
				$ignoreif = '';
				if (isset($operation['tag']['ignoreif'])) {
					$ignoreif = $operation['tag']['ignoreif'];
				}

				if ($ignoreif) {
					if (isset($ignoreif['attr']['regex']) && $ignoreif['attr']['regex'] === 'true') {
						if (preg_match($ignoreif['text'], $modification[$key])) {
							continue;
						}
					} else {
						if (strpos($modification[$key], $ignoreif['text']) !== false) {
							continue;
						}
					}
				}

				$status = false;

				// Search and replace

				$operation_search = '';
				if (isset($operation['tag']['search'])) {
					$operation_search = $operation['tag']['search'];
				}
				$operation_add = '';
				if (isset($operation['tag']['add'])) {
					$operation_add = $operation['tag']['add'];
				}

				if (isset($operation_search['attr']['regex']) && $operation_search['attr']['regex'] === 'true') {

					$search = trim($operation_search['text']);
					$limit = '';
					if (isset($operation_search['attr']['limit'])) {
						$limit = $operation_search['attr']['limit'];
					}

					$replace = '';
					if (isset($operation_add['text'])) {
						$replace = trim($operation_add['text']);
					}

					// Limit
					if (!$limit) {
						$limit = -1;
					}

					// Log
					$match = array();

					preg_match_all($search, $modification[$key], $match, PREG_OFFSET_CAPTURE);

					// Remove part of the the result if a limit is set.
					if ($limit > 0) {
						$match[0] = array_slice($match[0], 0, $limit);
					}

					if ($match[0]) {
						$log[] = 'REGEX: ' . $search;

						for ($i = 0; $i < count($match[0]); $i++) {
							$log[] = 'LINE: ' . (substr_count(substr($modification[$key], 0, $match[0][$i][1]), "\n") + 1);
						}

						$status = true;
					}

					// Make the modification
					$modification[$key] = preg_replace($search, $replace, $modification[$key], $limit);

				} else {


					// Search
					$search = '';
					if (isset($operation_search['text'])) {
						$search = $operation_search['text'];
					}

					$trim = '';
					if (isset($operation_search['attr']['trim'])) {
						$trim = $operation_search['attr']['trim'];
					}

					$index = '';
					if (isset($operation_search['attr']['index'])) {
						$index = $operation_search['attr']['index'];
					}

					// Trim line if no trim attribute is set or is set to true.
					if (!$trim || $trim == 'true') {
						$search = trim($search);
					}

					// Add
					$add = '';
					if (isset($operation_add['text'])) {
						$add = $operation_add['text'];
					}

					$trim = '';
					if (isset($operation_add['attr']['trim'])) {
						$trim = $operation_add['attr']['trim'];
					}

					$position = '';
					if (isset($operation_add['attr']['position'])) {
						$position = $operation_add['attr']['position'];
					}

					$offset = '';
					if (isset($operation_add['attr']['offset'])) {
						$offset = $operation_add['attr']['offset'];
					}

					if ($offset == '') {
						$offset = 0;
					}

					// Trim line if is set to true.
					if ($trim == 'true') {
						$add = trim($add);
					}

					// Log
					$log[] = 'CODE: ' . $search;

					// Check if using indexes
					if ($index !== '') {
						$indexes = explode(',', $index);
					} else {
						$indexes = array();
					}

					// Get all the matches
					$i = 0;

					$lines = explode("\n", $modification[$key]);

					for ($line_id = 0; $line_id < count($lines); $line_id++) {
						$line = $lines[$line_id];

						// Status
						$match = false;

						// Check to see if the line matches the search code.
						if (stripos($line, $search) !== false) {
							// If indexes are not used then just set the found status to true.
							if (!$indexes) {
								$match = true;
							} elseif (in_array($i, $indexes)) {
								$match = true;
							}

							$i++;
						}

						// Now for replacing or adding to the matched elements
						if ($match) {
							switch ($position) {
								default:
								case 'replace':
									$new_lines = explode("\n", $add);

									if ($offset < 0) {
										array_splice($lines, $line_id + $offset, abs($offset) + 1, array(str_replace($search, $add, $line)));

										$line_id -= $offset;
									} else {
										array_splice($lines, $line_id, $offset + 1, array(str_replace($search, $add, $line)));
									}

									break;
								case 'before':
									$new_lines = explode("\n", $add);

									array_splice($lines, $line_id - $offset, 0, $new_lines);

									$line_id += count($new_lines);
									break;
								case 'after':
									$new_lines = explode("\n", $add);

									array_splice($lines, ($line_id + 1) + $offset, 0, $new_lines);

									$line_id += count($new_lines);
									break;
							}

							// Log
							$log[] = 'LINE: ' . $line_id;

							$status = true;
						}
					}

					$modification[$key] = implode("\n", $lines);

				}

				if (!$status) {
					// Abort applying this modification completely.
					if ($error == 'abort') {
						// $modification = $recovery;
						// Log
						$log[] = 'NOT FOUND - ABORTING!';
						// break 5;
					}
					// Skip current operation or break
					elseif ($error == 'skip') {
						// Log
						$log[] = 'NOT FOUND - OPERATION SKIPPED!';
						// continue;
					}
					// Break current operations
					else {
						// Log
						$log[] = 'NOT FOUND - OPERATIONS ABORTED!';
					 	// break;
					}
				}
			}
			// Log
			$log[] = '----------------------------------------------------------------';
		}

		// Log
		// $ocmod = new Log('ocmod.log');
		// $ocmod->write(implode("\n", $log));

		return array(
			'log' => $log,
			'modification' => isset($modification[$key]) ? $modification[$key] : '',
			'content' => $content
		);
	}
}