<?php

App::uses('AppModel', 'Model');

class StreamtimeAppModel extends AppModel {

	public $useDbConfig = 'streamtime';

	public $primaryKey = 'UID';

	public $recursive = -1;

}
