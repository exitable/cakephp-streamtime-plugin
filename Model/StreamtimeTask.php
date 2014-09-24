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

}