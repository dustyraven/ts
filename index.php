<?php
include 'mods/common.php';


if(AJAX)
{
	$data = [];
	$ts = strtotime(date('YmdHi'));

	for($i = 0; $i < 25; $i++)
	{
		$mt = strtotime(date('YmdHi',filemtime($log)));

		if($mt == $ts || $mt < $ts - 120)
			break;

		sleep(1);
		clearstatcache(true, $log);
	}

	$raw = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	$raw = array_slice($raw, -10, 10, true);

	$ll = end($raw);

	$logs = array();
	foreach($raw as $row)
		if(preg_match('/^20[\d\s\.-]+$/', $row))
			$logs[] = new Sensor($row);

	$last = end($logs);


	$data['last'] = array(
			'ts' =>	date('H:i', $last->timestamp),
			'tc' =>	$last->data[SENS_COLD]->T,
			'th' =>	$last->data[SENS_WARM]->T,
			'tr' =>	$last->data[SENS_ROOM]->T,
			'ta' =>	round(($last->data[SENS_COLD]->T + $last->data[SENS_WARM]->T)/2, 1),
			'hc' =>	$last->data[SENS_COLD]->H,
			'hh' =>	$last->data[SENS_WARM]->H,
			'hr' =>	$last->data[SENS_ROOM]->H,
			'ha' =>	round(($last->data[SENS_COLD]->H + $last->data[SENS_WARM]->H)/2, 1),
			'll' => $ll,
		);


	$data['heater'] = $last->heater;
	$data['humidifier'] = $last->humidifier;
	$data['lamp'] = $last->lamp;
	$data['htop'] = $last->htop;

	$data['settings'] = $settings;

	foreach($data['settings'] as $k => $v)
		if(!in_array($k, $allowedSettings))
			unset($data['settings'][$k]);

	//$data['next'] = round((($last->timestamp + $last->work + 60) - time())*1000);
	$data['next'] = (60 - date('s') + 5) * 1000;

	ob_start('ob_gzhandler');
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;

}

include 'mods/head.php';
?>

<div class="container-fluid">

	<div class="row">
		<div id="info" class="col-xs-12">&nbsp;</div>
	</div>

	<div class="row">
		<div class="col-xs-12">

			<div class="progress">
				<div id="tempH" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%">Warm <b></b></div>
			</div>
			<div class="progress">
				<div id="tempC" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%">Cold <b></b></div>
			</div>
			<div class="progress">
				<div id="tempR" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%">Room <b></b></div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 sep"></div>
	</div>

	<div class="row">
		<div class="col-xs-12">
			<div class="progress">
				<div id="hmdtH" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%">Warm <b></b>%</div>
			</div>
			<div class="progress">
				<div id="hmdtC" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%">Cold <b></b>%</div>
			</div>
			<div class="progress">
				<div id="hmdtR" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%">Room <b></b>%</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12 sep"></div>
	</div>

	<div class="row">
		<div id="ll" class="col-xs-12"></div>
	</div>

</div>

	<script src="./js/jquery.min.js"></script>
	<script src="./js/bootstrap.min.js"></script>
	<script src="./js/howler.min.js"></script>
	<?php include 'mods/javascript.php';?>

</body>
</html>

