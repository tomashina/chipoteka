<?php
namespace DB;
final class MySQLi {
	private $connection;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
		$this->connection = new \mysqli($hostname, $username, $password, $database, $port);

		if ($this->connection->connect_error) {
			throw new \Exception('Error: ' . $this->connection->connect_error . '<br />Error No: ' . $this->connection->connect_errno);
		}

		/*$this->connection->set_charset("utf8");
		$this->connection->query("SET SQL_MODE = ''");
		$this->connection->query("SET SESSION sql_mode = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'");*/
        $this->connection->set_charset("utf8");
        $this->connection->query("SET SQL_MODE = ''");
	}

	public function query($sql) {
		$query = $this->connection->query($sql);
		$retries = 0;

		// Backward-compatible fallback for deployments where custom `oc_order` columns do not exist.
		while ($this->connection->errno == 1054 && $retries < 10) {
			$fallback_sql = $this->stripUnknownOrderColumnFromInsert($sql, $this->connection->error);

			if ($fallback_sql === $sql) {
				break;
			}

			$sql = $fallback_sql;
			$query = $this->connection->query($sql);
			$retries++;
		}

		if (!$this->connection->errno) {
			if ($query instanceof \mysqli_result) {
				$data = array();

				while ($row = $query->fetch_assoc()) {
					$data[] = $row;
				}

				$result = new \stdClass();
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();
				$result->rows = $data;

				$query->close();

				return $result;
			} else {
				return true;
			}
		} else {
			throw new \Exception('Error: ' . $this->connection->error  . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
		}
	}

	private function stripUnknownOrderColumnFromInsert($sql, $error) {
		$column = $this->extractUnknownColumnName($error);

		if (!$column || !$this->isOrderInsertSetQuery($sql)) {
			return $sql;
		}

		$quoted = preg_quote($column, '/');
		$value  = "(?:'(?:\\\\\\\\.|[^'])*'|[^,]+)";
		$updated = $sql;

		// First assignment after SET.
		$updated = preg_replace('/\bSET\s+`?' . $quoted . '`?\s*=\s*' . $value . '\s*,\s*/i', 'SET ', $updated, 1);

		// Assignment in the middle (prefixed by comma).
		if (is_string($updated)) {
			$updated = preg_replace('/,\s*`?' . $quoted . '`?\s*=\s*' . $value . '\s*/i', '', $updated, 1);
		}

		// Assignment before next comma.
		if (is_string($updated)) {
			$updated = preg_replace('/`?' . $quoted . '`?\s*=\s*' . $value . '\s*,\s*/i', '', $updated, 1);
		}

		if (is_string($updated)) {
			$updated = preg_replace('/\bSET\s*,\s*/i', 'SET ', $updated, 1);
			$updated = preg_replace('/\s{2,}/', ' ', $updated);
		}

		if (is_string($updated) && trim($updated) !== '' && $updated !== $sql) {
			return $updated;
		}

		return $sql;
	}

	private function extractUnknownColumnName($error) {
		if (!is_string($error) || $error === '') {
			return '';
		}

		if (preg_match("/Unknown column '([^']+)'/i", $error, $matches)) {
			return isset($matches[1]) ? $matches[1] : '';
		}

		return '';
	}

	private function isOrderInsertSetQuery($sql) {
		if (!is_string($sql) || $sql === '') {
			return false;
		}

		return (bool) preg_match('/^\s*INSERT\s+INTO\s+`?[^`\s]*order`?\s+SET\s+/i', $sql);
	}

	public function escape($value) {
		return $this->connection->real_escape_string($value);
	}
	
	public function countAffected() {
		return $this->connection->affected_rows;
	}

	public function getLastId() {
		return $this->connection->insert_id;
	}
	
	public function connected() {
		return $this->connection->ping();
	}
	
	public function __destruct() {
		$this->connection->close();
	}
}
