<?php
/*
CRONTAB:
# m h  dom mon dow   command
* * * * * /home/pi/terrasens/terrasens.py >> /home/pi/terrasens/logs/`date +\%Y\%m\%d`.log 2>&1
*/

error_reporting(E_ALL);

/* AJAX check  */
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
	define('AJAX', true);
else
	define('AJAX', false);

define('SENS_COLD', 0);
define('SENS_WARM', 1);
define('SENS_ROOM', 2);


class Sensor {
	public $timestamp;
	public $work;
	public $data = array();
	public $heater = 0;
	public $humidifier = 0;
	public $lamp = 0;

	public function __construct($row)
	{
		$data = explode(' ', $row);
		$this->timestamp	= strtotime(array_shift($data));
		$this->work 		= array_shift($data);

		//foreach($data as $k => $v)
		//	if(0 == $k%2)
		for($i = 0; $i < 6; $i+=2)
			$this->data[] = (object)array('T' => (float)$data[$i], 'H' => (float)$data[$i+1]);

		$this->heater = (int)$data[6];
		$this->humidifier = (int)$data[7];
		$this->lamp = isset($data[8]) ? (int)$data[8] : 0;

		/*
		if(4 == count($this->data))
		{
			$tmp = array_pop($this->data);
			$this->heater = $tmp->T;
			$this->humidifier = $tmp->H;
		}
		*/
	}

}	// end of class


$settings = parse_ini_file('terrasens.ini', true);
// todo : fix that stupid thing
foreach($settings as $k => $v)
	foreach($v as $kk => $vv)
		if(is_numeric($vv))
			$settings[$k][$kk] = (int)$vv;

$log = 'logs/'.date('Ymd').'.log';

