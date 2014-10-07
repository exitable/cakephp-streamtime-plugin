<?php

class StreamtimeSource extends DataSource {

	public $columns = array(
		'primary_key' => array('name' => 'NOT NULL AUTO_INCREMENT'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'biginteger' => array('name' => 'bigint', 'limit' => '20'),
		'integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'decimal' => array('name' => 'decimal', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'm/d/Y H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'm/d/Y H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'm/d/Y', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'tinyint', 'limit' => '1')
	);

	private $__queries = array();
	private $__queriesTotalTime = 0;

	/**
	 * Caches/returns cached results for child instances
	 *
	 * @param mixed $data Unused in this class.
	 * @return array Array of sources available in this datasource.
	 */
	public function listSources($data = null) {
		if ($this->cacheSources === false) {
			return null;
		}

		if ($this->_sources !== null) {
			return $this->_sources;
		}

		$key = ConnectionManager::getSourceName($this) . '_list';
		$key = preg_replace('/[^A-Za-z0-9_\-.+]/', '_', $key);
		$sources = Cache::read($key, '_cake_model_');

		if (empty($sources)) {
			$sources = $data;
			Cache::write($key, $data, '_cake_model_');
		}

		return $this->_sources = $sources;
	}

	public function calculate(Model $model, $func, $params = array()) {
		return 'COUNT';
	}

	public function read(Model $Model, $queryData = array(), $recursive = null) {
		$api_key        = $this->config['app_key'];
		$api_secret     = $this->config['app_secret'];
		$api_url        = 'https://exitable.mystreamtime.com/api/streamtime/1.1/' . $Model->table;

		if (isset($_GET['week'])) {
			$week = $_GET['week'];
		} else {
			$week = date('W');
		}
		if (isset($_GET['staff'])) {
			$staff = $_GET['staff'];
		} else {
			$staff = '';
		}

		$begin = strtotime(date('Y') . 'W' . $week);
		$end = strtotime(date('Y') . 'W' . $week . '7');

		if (Cache::read($this->__generateCacheKey($Model, $queryData)) === false) {
			$time_start = microtime(true);

			$post_data = array(
				'key' => $api_key,
				'xml' => ''
			);

			$post_data += $this->__rebuildConditions($Model, $queryData['conditions']);

			$post_body = http_build_query($post_data);

			$api_signature = base64_encode(hash_hmac("sha1", $post_body, $api_secret, true));

			$headers = array(
				'Accept: application/json',
				'X-Request-Signature: ' . $api_signature
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_url . '?' . $post_body);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$response = curl_exec($ch);

			curl_close($ch);

			$data = json_decode($response, true);
			if ($data === null) {
				throw new CakeException(__d('streamtime', 'Streamtime error: %s', $response));
			}

			if ($data['Result'] === 'Error') {
				if ($data['Message'] === 'No Records found.') {
					$data[Inflector::camelize($Model->table)][Inflector::camelize(Inflector::singularize($Model->table))] = array();
				} else {
					$this->__logQuery($api_url, $post_data, 0, 0, (microtime(true) - $time_start) * 1000, $data['Message']);

					throw new CakeException(__d('streamtime', 'Streamtime error: %s [%d]', $data['Message'], $data['Code']));
				}
			}

			$entities = $data[Inflector::camelize($Model->table)][Inflector::camelize(Inflector::singularize($Model->table))];

			$this->__logQuery($api_url, $post_data, 0, count($entities), (microtime(true) - $time_start) * 1000);

			Cache::write($this->__generateCacheKey($Model, $queryData), $entities);
		} else {
			$entities = Cache::read($this->__generateCacheKey($Model, $queryData));
		}

		if ($queryData['fields'] === 'COUNT') {
			return array(array(array('count' => count($entities))));
		}

		$modelData = array();
		foreach ($entities as $entity) {
			$modelEntry = array();
			$modelEntry[$Model->alias] = $entity;
			foreach ($Model->schema() as $field => $options) {
				if (!isset($modelEntry[$Model->alias][$field])) {
					$modelEntry[$Model->alias][$field] = null;
				}
				if ($options['type'] === 'boolean') {
					$modelEntry[$Model->alias][$field] = ($modelEntry[$Model->alias][$field] === 'Yes') ? true : false;
				}
				if ($options['type'] === 'integer') {
					$modelEntry[$Model->alias][$field] = (int) $modelEntry[$Model->alias][$field];
				}
				if ($options['type'] === 'float') {
					$modelEntry[$Model->alias][$field] = (float) $modelEntry[$Model->alias][$field];
				}
				if (($options['type'] === 'text') || ($options['type'] === 'varchar')) {
					$modelEntry[$Model->alias][$field] = html_entity_decode($modelEntry[$Model->alias][$field]);
				}
			}

			if (isset($queryData['limit'])) {
				$modelData = array_slice($modelData, (isset($queryData['offset'])) ? $queryData['offset'] : 0, $queryData['limit'] - 1);
			}

			if (is_array($queryData['order'][0])) {
				$sortFields = array();
				foreach ($queryData['order'][0] as $field => $direction) {
					foreach ($modelData as $index => $entry) {
						$sortFields[$index][$field] = Hash::get($entry, $field);
					}
				}
				foreach ($modelData as $index => $entry) {
					$sortFields[$index]['id'] = $index;
				}

				if (isset($queryData['order'][0][0])) {
					$queryData['order'][0] = $queryData['order'][0][0];
					unset($queryData['order'][0][0]);
				}

				$sortOptions = array($sortFields);
				foreach ($queryData['order'][0] as $field => $direction) {

					$sortOptions[] = $field;
					$sortOptions[] = (strtoupper($direction) === 'ASC') ? SORT_ASC : SORT_DESC;
				}

				$sortData = call_user_func_array(array($this, 'array_orderby'), $sortOptions);

				$sortedModelData = array();
				foreach ($sortData as $data) {
					$sortedModelData[] = $modelData[$data['id']];
				}

				$modelData = $sortedModelData;
			}

			if (isset($queryData['recursive'])) {
				$Model->recursive = $queryData['recursive'];
			}

			if ($Model->recursive > -1) {
				foreach ($Model->getAssociated('belongsTo') as $association) {
					$associatedModel = $Model->{$association};
					if (!empty($Model->belongsTo[$association]['conditions'])) {
						$associationFindConditions = array();
						foreach ($Model->belongsTo[$association]['conditions'] as $field => $condition) {
							if (Hash::check($modelEntry, $condition)) {
								$associationFindConditions[$field] = Hash::get($modelEntry, $condition);
							} else {
								$associationFindConditions[$field] = $condition;
							}
						}
					} else {
						$associationFindConditions = array(
							$associatedModel->primaryKey => $modelEntry[$Model->alias][$Model->belongsTo[$association]['foreignKey']]
						);
					}

					$associationData = $associatedModel->find('first', array(
						'conditions' => $associationFindConditions
					));
					if (!isset($associationData[$association])) {
						$modelEntry[$association] = array();
					} else {
						$modelEntry[$association] = $associationData[$association];
					}


				}
			}

			$modelData[] = $modelEntry;
		}

		if (!empty($queryData['fields'])) {
			foreach ($modelData as $index => $result) {
				$flat = Hash::flatten($result);

				foreach ($flat as $field => $value) {
					if (!strstr($field, '.')) {
						$field = $Model->alias . '.' . $field;
					}
					if (!in_array($field, $queryData['fields'])) {
						unset($flat[$field]);
					}
				}

				$modelData[$index] = Hash::expand($flat);
			}
		}

		return $modelData;
	}

	private function __rebuildConditions(Model $Model, $conditions) {
		if (!is_array($conditions)) {
			return array($conditions);
		}

		$queryConditions = array();
		foreach ($conditions as $field => $condition) {
			list ($field, $model) = array_reverse(explode('.', 'no-model.' . $field));

			if (strstr($field, ' ')) {
				$fieldParts = explode(' ', $field);
				if ($fieldParts[1] === 'BETWEEN') {
					$dates = array();
					foreach ($condition as $date) {
						$dates[] = date($this->columns['date']['format'], strtotime($date));
					}
					$queryConditions[$fieldParts[0]] = implode('...', $dates);
				} elseif ($Model->hasField($fieldParts[0])) {
					$queryConditions[$fieldParts[0] . ' ' . $fieldParts[1]] = $condition;
				}
			} else {
				if ($Model->hasField($field)) {
					$queryConditions[$field] = $condition;
				} else {
					$queryConditions[$field] = $condition;
//					throw new CakeException(__d('streamtime', 'Streamtime error. There\'s no field named %s in model %s.', $field, $Model->name));
				}
			}
		}

		return $queryConditions;
	}

	public function getLog($sorted = false, $clear = true) {
		$log = $this->__queries;
		if ($clear) {
			$this->__queries = array();
		}

		return array('log' => $log, 'count' => count($log), 'time' => $this->__queriesTotalTime);
	}

	function array_orderby() {
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
				$args[$n] = $tmp;
			}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}

	private function __generateCacheKey(Model $Model, $queryData) {
		$cacheData = $queryData;

		unset(
			$cacheData['fields'], $cacheData['limit'], $cacheData['offset'], $cacheData['order'], $cacheData['page'],
			$cacheData['recursive'], $cacheData['sort'], $cacheData['direction'], $cacheData['maxLimit'],
			$cacheData['paramType']
		);

		return 'streamtime-' . $Model->alias . '-data-' . md5(serialize($cacheData));
	}

	private function __logQuery($url, $params, $affected, $rowCount, $time, $error = null) {
		$loggedParams = array();
		foreach ($params as $param => $value) {
			$loggedParams[] = $param . ' = ' . $value;
		}

		$this->__queries[] = array(
			'query' => $url,
			'params' => $loggedParams,
			'affected' => $affected,
			'numRows' => $rowCount,
			'error' => $error,
			'took' => $time
		);

		$this->__queriesTotalTime += $time;
	}

}