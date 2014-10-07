<?php

App::uses('StreamtimeAppModel', 'Streamtime.Model');

class StreamtimeTask extends StreamtimeAppModel {

	public $name = 'Task';

	public $displayField = 'Notes';

	public $belongsTo = array(
		'StreamtimeJob' => array(
			'className' => 'Streamtime.StreamtimeJob',
			'foreignKey' => 'JobNumber',
//			'conditions' => array(
//				'StreamtimeJob.UID' => 'StreamtimeTask.JobNumber'
//			)
		)
	);

	public $_schema = array(
		'UID' => array(
			'type' => 'integer',
			'null' => false
		),
		'Material' => array(
			'type' => 'text',
			'null' => false
		),
		'Notes' => array(
			'type' => 'text',
			'null' => false
		),
		'DateStart' => array(
			'type' => 'date',
			'null' => false
		),
		'TimeStart' => array(
			'type' => 'time',
			'null' => false
		),
		'DateDue' => array(
			'type' => 'date',
			'null' => false
		),
		'TimeDue' => array(
			'type' => 'time',
			'null' => false
		),
		'DateActual' => array(
			'type' => 'date',
			'null' => false
		),
		'EstimatedTime' => array(
			'type' => 'float',
			'null' => false
		),
		'UsedTime' => array(
			'type' => 'float',
			'null' => false
		),
		'Billable' => array(
			'type' => 'boolean',
			'null' => false
		),
		'JobNumber' => array(
			'type' => 'integer',
			'null' => false
		),
		'JobStatus' => array(
			'type' => 'text',
			'null' => false
		),
	);

	public function afterFind($results, $primary = false) {
		if ($primary) {
			foreach ($results as &$result) {
				if (isset($result[$this->alias]['DateStart']) && isset($result[$this->alias]['TimeStart'])) {
					$date = DateTime::createFromFormat(
						$this->getDataSource()->columns['date']['format'] . ' ' . $this->getDataSource()->columns['time']['format'],
						str_replace('-', '/', $result[$this->alias]['DateStart']) . ' ' . $result[$this->alias]['TimeStart'],
						new DateTimeZone('Europe/Amsterdam')
					);
					$date->setTimezone(new DateTimeZone('UTC'));
					$result[$this->alias]['DateStart'] = $date->format($this->getDataSource()->columns['date']['format']);
					$result[$this->alias]['TimeStart'] = $date->format($this->getDataSource()->columns['time']['format']);
				}
				if (isset($result[$this->alias]['DateDue']) && isset($result[$this->alias]['TimeDue'])) {
					$date = DateTime::createFromFormat(
						$this->getDataSource()->columns['date']['format'] . ' ' . $this->getDataSource()->columns['time']['format'],
						str_replace('-', '/', $result[$this->alias]['DateDue']) . ' ' . $result[$this->alias]['TimeDue'],
						new DateTimeZone('Europe/Amsterdam')
					);

					$date->setTimezone(new DateTimeZone('UTC'));
					$result[$this->alias]['DateDue'] = $date->format($this->getDataSource()->columns['date']['format']);
					$result[$this->alias]['TimeDue'] = $date->format($this->getDataSource()->columns['time']['format']);
				}
			}
		}

		return $results;
	}

}