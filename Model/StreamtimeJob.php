<?php

App::uses('StreamtimeAppModel', 'Streamtime.Model');

class StreamtimeJob extends StreamtimeAppModel {

	public $name = 'Job';

	public $displayField = 'Name';

	public $_schema = array(
		'UID' => array(
			'type' => 'integer',
			'null' => false,
		),
		'JobNumber' => array(
			'type' => 'integer',
			'null' => false,
		),
		'Name' => array(
			'type' => 'text',
			'null' => false,
		),
		'Status' => array(
			'type' => 'text',
			'null' => false,
		),
		'Billable' => array(
			'type' => 'boolean',
			'null' => false,
		),
		'AccountManager' => array(
			'type' => 'text',
			'null' => false,
		),
		'Details' => array(
			'type' => 'text',
			'null' => false,
		),
		'DateIn' => array(
			'type' => 'date',
			'null' => false,
		),
		'DateDue' => array(
			'type' => 'date',
			'null' => false,
		),
		'PricingTierUID' => array(
			'type' => 'integer',
			'null' => false,
		),
		'WIP' => array(
			'type' => 'boolean',
			'null' => false,
		),
	);

}