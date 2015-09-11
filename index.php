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

$raw = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$raw = array_slice($raw, -180, 180, true);


$data = array();
foreach($raw as $row)
	if(preg_match('/^20[\d\s\.]+$/', $row))
		$data[] = new Sensor($row);

$last = end($data);


$ts = $h1 = $h2 = $ha = $hOn = $t1 = $t2 = $tOn = array();

foreach($data as $d)
{
	$ts[] = date('H:i',$d->timestamp);
	$h1[] = $d->data[SENS_COLD]->H;
	$h2[] = $d->data[SENS_WARM]->H;
	$h3[] = $d->data[SENS_ROOM]->H;
	$ha[] = round(($d->data[SENS_COLD]->H + $d->data[SENS_WARM]->H)/2, 1);
	$t1[] = $d->data[SENS_COLD]->T;
	$t2[] = $d->data[SENS_WARM]->T;
	$t3[] = $d->data[SENS_ROOM]->T;
	$tOn[] = $d->heater ? 30 : 20;
	$hOn[] = $d->humidifier ? 80 : 70;
}


if(AJAX)
{
	$data = [];

	foreach(array('h1','h2','h3','ha','hOn','t1','t2','t3','tOn') as $key)
		$data[$key] = $$key;

	$data['ts'] = $ts;
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
		);

	$data['next'] = round((($last->timestamp + $last->work + 60) - time())*1000);

	$data['heater'] = $last->heater;
	$data['humidifier'] = $last->humidifier;

	$data['settings'] = $settings;

	ob_start('ob_gzhandler');
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;

}

/*
$ts = '"'.implode('","', $ts).'"';
$h1 = implode(',', $h1);
$h2 = implode(',', $h2);
$h3 = implode(',', $h3);
$t1 = implode(',', $t1);
$t2 = implode(',', $t2);
$t3 = implode(',', $t3);
*/

	//echo '<pre>'; print_r($data); echo '</pre>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">

	<meta http-equiv="refresh" content="600">
	<title>TerraSens</title>

	<link rel="apple-touch-icon-precomposed" sizes="57x57" href="icons/apple-icon-57x57.png">
	<link rel="apple-touch-icon-precomposed" sizes="60x60" href="icons/apple-icon-60x60.png">
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="icons/apple-icon-72x72.png">
	<link rel="apple-touch-icon-precomposed" sizes="76x76" href="icons/apple-icon-76x76.png">
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="icons/apple-icon-114x114.png">
	<link rel="apple-touch-icon-precomposed" sizes="120x120" href="icons/apple-icon-120x120.png">
	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="icons/apple-icon-144x144.png">
	<link rel="apple-touch-icon-precomposed" sizes="152x152" href="icons/apple-icon-152x152.png">
	<link rel="apple-touch-icon-precomposed" sizes="180x180" href="icons/apple-icon-180x180.png">
	<link rel="apple-touch-startup-image" 					 href="icons/startup.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="icons/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="icons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="icons/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="icons/favicon-16x16.png">

	<link rel="manifest" href="manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="icons/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">



	<!-- Bootstrap -->
	<link rel="stylesheet" href="./css/bootstrap.min.css">
	<link rel="stylesheet" href="./css/bootstrap-theme.min.css">
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<style type="text/css">
		body {background:black;}
		h1, h2, a#navbar-brand {color:white;text-shadow: 1px 1px 0 red;font-weight:bold;}
		a#navbar-brand {font-size:2em;padding:15px 10px 15px 15px;}
		h1 button.btn {position:relative; top:-5px;}

		#updBtn {padding:5px;font-weight:bold;}

		#info span {white-space:nowrap;color:white;text-shadow: 1px 1px 0 red;font-weight:bold;padding-right:1em;position:relative;top:-10px;}
		#info span.temp:before {text-shadow:none;font-weight:normal;content:"TAvg: ";}
		#info span.heat:before {text-shadow:none;font-weight:normal;content:"Heater: ";}
		#info span.hmdt:before {text-shadow:none;font-weight:normal;content:"HAvg: ";}
		#info span.wet:before  {text-shadow:none;font-weight:normal;content:"Humidifier: ";}

		.sep {height:1.5em;border-top:1px dotted #444;}

		.progress {height:28px;text-shadow: 1px 1px 1px black, -1px -1px 1px black;font-family:Consolas, 'Lucida Console', 'DejaVu Sans Mono', monospace;}
		.progress-bar {font-size:26px;line-height:30px;text-align:left;padding-left:.5em;background-color:#AAA;white-space:nowrap;}
		.progress-bar-success {background-color:green;}
		.progress-bar-info {background-color:blue;}
		.progress-bar-warning {background-color:yellow;}
		.progress-bar-danger {background-color:red;}

		 .highcharts-tooltip {font-family:Consolas, 'Lucida Console', 'DejaVu Sans Mono', monospace;}
	</style>
</head>
<body>


<nav class="navbar navbar-inverse navbar-static-top">
	<div class="container-fluid">
		<!-- Brand and toggle get grouped for better mobile display -->
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbarcollapse" aria-expanded="false">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a id="navbar-brand" class="navbar-brand">TerraSens</a>
			<button id="updBtn" class="btn btn-sm btn-default navbar-btn navbar-left" onclick="reData();">
				<span id="ts"></span>
				<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
			</button>
		</div>


		<!-- Collect the nav links, forms, and other content for toggling -->
		<div class="collapse navbar-collapse" id="navbarcollapse">
			<ul class="nav navbar-nav navbar-right">
				<li><a href="#">Charts</a></li>
			</ul>
		</div><!-- /.navbar-collapse -->
	</div><!-- /.container-fluid -->
</nav>


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
	<!--
	<div class="row">
		<div class="well col-xs-12" id="gauge1" 	style="min-width: 310px; height: 240px; margin: 0 auto"></div>
	</div>
	-->
	<h2 class="text-center">History</h2>

	<div class="row">
		<div id="container2" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	</div>
	<div class="row">
		<div class="col-xs-12">&nbsp;</div>
	</div>
	<div class="row">
		<div id="container1" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	</div>

</div>

	<script src="./js/jquery.min.js"></script>
	<script src="./js/bootstrap.min.js"></script>

	<script src="./js/highcharts.js"></script>
	<script src="./js/highcharts-more.js"></script>
	<!-- <script src="./js/exporting.js"></script> -->
	<script src="./js/howler.min.js"></script>


<script>
var chartH, chartT, chartNow, tOut, updBtn, info;
var SENS_COLD = 0, SENS_WARM = 1, SENS_ROOM = 2;


function reData()
{
	updBtn.hide();
	info.html("&nbsp;");
	//$("#ts").text("");


	var jqxhr = $.getJSON( "", function(data) {

		if(data.reload)
			location.reload(true);

		var tc = percRange(data.last.tc, 10, 40);// 2* ,
			th = percRange(data.last.th, 10, 40);// 2* ,
			tr = percRange(data.last.tr, 10, 40);// 2* ,
			hc = percRange(data.last.hc, 20, 90);// ,
			hh = percRange(data.last.hh, 20, 90);// ,
			hr = percRange(data.last.hr, 20, 60);// ;


		$("#ts").text(data.last.ts);
		updBtn.show();
		info.empty().append(
				$("<span>").attr("class","temp").html(data.last.ta + "°C / " + ctof(data.last.ta) + "F"),
				$("<span>").attr("class","heat").html((1 == data.heater ? 'On' : 'Off')),
				$("<span>").attr("class","hmdt").html(data.last.ha + "%"),
				$("<span>").attr("class", "wet").html((1 == data.humidifier ? 'On' : 'Off'))
			);

//		heater: 1
//humidifier: 0

		$("#tempH").css("width", th + '%').find("b").text(data.last.th + "°C / " + ctof(data.last.th) + "F");
		$("#tempC").css("width", tc + '%').find("b").text(data.last.tc + "°C / " + ctof(data.last.tc) + "F");
		$("#tempR").css("width", tr + '%').find("b").text(data.last.tr + "°C / " + ctof(data.last.tr) + "F");
		$("#hmdtH").css("width", hh + '%').find("b").text(data.last.hh);
		$("#hmdtC").css("width", hc + '%').find("b").text(data.last.hc);
		$("#hmdtR").css("width", hr + '%').find("b").text(data.last.hr);


		$(".progress-bar").each(function( index ) {
			$(this).removeClass("progress-bar-success progress-bar-info progress-bar-warning progress-bar-danger")
					//.css("width", "1px");
		});

		// ideal - 32.2 (90F) .... cut some degrees for sensor height
		if(data.last.th < data.settings.temperature.warm_min)
			$("#tempH").addClass("progress-bar-info")
		else if(data.last.th > data.settings.temperature.warm_max)
			$("#tempH").addClass("progress-bar-danger")
		else
			$("#tempH").addClass("progress-bar-success")

		// ideal - 25.5 (78F) .... cut some degrees for sensor height
		if(data.last.tc < data.settings.temperature.cold_min)
			$("#tempC").addClass("progress-bar-info")
		else if(data.last.tc > data.settings.temperature.cold_max && data.last.tc > data.last.tr)
			$("#tempC").addClass("progress-bar-danger")
		else
			$("#tempC").addClass("progress-bar-success")

		// room
		if(data.last.tr < data.settings.temperature.room_min)
			$("#tempR").addClass("progress-bar-info")
		else if(data.last.tr > data.settings.temperature.room_max)
			$("#tempR").addClass("progress-bar-danger")
		else
			$("#tempR").addClass("progress-bar-success")




		if(data.last.hh < data.settings.humidity.warm_min)
			$("#hmdtH").addClass("progress-bar-danger")
		else if(data.last.hh > data.settings.humidity.warm_max)
			$("#hmdtH").addClass("progress-bar-info")
		else
			$("#hmdtH").addClass("progress-bar-success")

		if(data.last.hc < data.settings.humidity.cold_min)
			$("#hmdtC").addClass("progress-bar-danger")
		else if(data.last.hc > data.settings.humidity.cold_max)
			$("#hmdtC").addClass("progress-bar-info")
		else
			$("#hmdtC").addClass("progress-bar-success")

		if(data.last.hr < data.settings.humidity.room_min)
			$("#hmdtR").addClass("progress-bar-danger")
		else if(data.last.hr > data.settings.humidity.room_max)
			$("#hmdtR").addClass("progress-bar-info")
		else
			$("#hmdtR").addClass("progress-bar-success")

		//chartNow.setTitle({text: data.last.ts});

		/*
		for(var i = 0; i < 6; i++)
			chartNow.series[i].setData([data.last[i]]);
		chartNow.redraw();
		*/

		chartH.xAxis[0].update({categories: data.ts});
		chartH.series[SENS_COLD].setData(data.h1);
		chartH.series[SENS_WARM].setData(data.h2);
		chartH.series[SENS_ROOM].setData(data.h3);
		chartH.series[3].setData(data.ha);
		chartH.series[4].setData(data.hOn);
		chartH.redraw();

		chartT.xAxis[0].update({categories: data.ts});
		chartT.series[SENS_COLD].setData(data.t1);
		chartT.series[SENS_WARM].setData(data.t2);
		chartT.series[SENS_ROOM].setData(data.t3);
		chartT.series[3].setData(data.tOn);
		chartT.redraw();


		snd("click");

		//console.log(Date.now())
		//console.log( data );

		clearTimeout(tOut);

		var ts = 60000, now = new Date().getTime();

		if(data.next && data.next > 0)
			ts = data.next;
		else
			console.log(data.next)

		tOut = setTimeout(reData, ts);
		//console.log(tOut);
	});

}


//	T(°F) = T(°C) × 1.8 + 32
function ctof(c)
{
	return Math.round((c*1.8 + 32) * 10) / 10;
}

function percRange(v, mi, ma)
{
	if(!mi) mi = 0;
	if(!ma) ma = 100;
	var p = Math.round( 100 * ( (v - mi) / (ma - mi) ) );
	if(p < 0) p = 0;
	if(p > 100) p = 100;
	return p;
}

function snd(s)
{
	new Howl({urls: ["snd/"+s+".ogg", "snd/"+s+".mp3"]}).play();
}

	$(function () {

		updBtn = $("#updBtn");
		info = $("#info");

		//snd("sound");


		Highcharts.setOptions({
			colors: ['blue', 'red', 'green', 'yellow'],
			plotOptions: {
				series: {
					animation: false
				}
			},
			exporting: {
				enabled: false
			}
    	});

//*

		chartH = new Highcharts.Chart({
			colors: ['blue', 'red', 'green', "brown", "gray"],
			chart: {
				renderTo: 'container1',
				type: 'spline',
				zoomType: 'x'
			},
			title: {
				text: 'Humidity'
			},
			xAxis: {
				labels: {
					formatter: function () {
						return this.value;
					}
				},
				categories: []
			},

			yAxis: {
				title: {text: null},
				labels: {
					formatter: function () {
						return this.value + "%";
					}
				}
			},
			tooltip: { shared: true},

			plotOptions: {
				areaspline: {
					fillOpacity: 0.5
				}
			},

			series: [
				{
					name: 'Cold',
					tooltip: {
						pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y:.2f}</b>%</span><br />'
					},
					data: []
				},
				{
					name: 'Warm',
					tooltip: {
						pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y:.2f}</b>%</span><br />'
					},
					data: []
				},
				{
					name: 'Room',
					tooltip: {
						pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y:.2f}</b>%</span><br />'
					},
					data: []
				},
				{
					name: 'AVG',
					tooltip: {},
					data: []
				},
				{
					name: 'Ctrl',
					tooltip: {},
					data: []
				}
			]
		});








		chartT = new Highcharts.Chart({
			colors: ['blue', 'red', 'green', "gray"],
			chart: {
				renderTo: "container2",
				type: 'spline',
				zoomType: 'x'
			},
			title: {
				text: 'Temperature'
			},
			xAxis: {
				labels: {
					formatter: function () {
						return this.value;
					}
				},
				categories: []
			},

			yAxis: {
				title: {text: null},
				labels: {
					formatter: function () {
						return this.value + '°';
					}
				}
			},
			tooltip: { shared: true},

			plotOptions: {
				areaspline: {
					fillOpacity: 0.5
				}
			},

			series: [
				{
					name: 'Cold',
					tooltip: {
						pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y:.2f}</b>°C</span><br />'
					},
					data: []
				},
				{
					name: 'Warm',
					tooltip: {
						pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y:.2f}</b>°C</span><br />'
					},
					data: []
				},
				{
					name: 'Room',
					tooltip: {
						pointFormat: '<span style="color:{series.color}">{series.name}: <b>{point.y:.2f}</b>°C</span><br />'
					},
					data: []
				},
				{
					name: 'Ctrl',
					tooltip: {enabled: false},
					data: []
				}
			]
		});






//*/


















	/*
	chartNow = new Highcharts.Chart({

		colors: ['blue', 'red', 'green'],

		chart: {
			renderTo: "gauge1",
			type: 'gauge',
			plotBorderWidth: 0,
			plotBackgroundColor: null,
			plotBackgroundImage: null,
			height: 200
		},

		tooltip: {
			shared: true
		},

		title: {
			text: ""
		},

		pane: [{
			startAngle: -90,
			endAngle: 90,
			background: null,
			center: ['25%', '100%'],
			size: 300
		}, {
			startAngle: -90,
			endAngle: 90,
			background: null,
			center: ['75%', '100%'],
			size: 300
		}],

		yAxis: [{
			min: 20,
			max: 40,
			minorTickPosition: 'outside',
			tickPosition: 'outside',
			labels: {
				rotation: 'auto',
				distance: 20
			},
			plotBands: [{
				from: 30,
				to: 40,
				color: '#C02316',
				innerRadius: '100%',
				outerRadius: '105%'
			},{
				from: 20,
				to: 25,
				color: '#0000FF',
				innerRadius: '100%',
				outerRadius: '105%'
			}],
			pane: 0,
			title: {
				text: 'Temperature',
				y: -40
			}
		},
		{
			min: 40,
			max: 100,
			minorTickPosition: 'outside',
			tickPosition: 'outside',
			labels: {
				rotation: 'auto',
				distance: 20
			},
			plotBands: [{
				from: 40,
				to: 60,
				color: '#C02316',
				innerRadius: '100%',
				outerRadius: '105%'
			}],
			pane: 1,
			title: {
				text: 'Humidity',
				y: -40
			}
		}],

		plotOptions: {
			gauge: {
				dataLabels: {
					enabled: false
				},
				dial: {
					radius: '100%'
					//backgroundColor: 'red',
					//borderColor: 'black',
					//borderWidth: 1
				}
			}
		},


		series: [
		{
			name: 'Cold',
			data: [],
			yAxis: 0,
			tooltip: {valueSuffix: '°C'}
		},
		{
			name: 'Warm',
			data: [],
			yAxis: 0,
			tooltip: {valueSuffix: '°C'}
		},
		{
			name: 'Room',
			data: [],
			yAxis: 0,
			tooltip: {valueSuffix: '°C'}
		},
		{
			name: 'Cold',
			data: [],
			yAxis: 1,
			tooltip: {valueSuffix: ' %'}
		},
		{
			name: 'Warm',
			data: [],
			yAxis: 1,
			tooltip: {valueSuffix: ' %'}
		},
		{
			name: 'Room',
			data: [],
			yAxis: 1,
			tooltip: {valueSuffix: ' %'}
		}

        ]

	});
	//*/


		reData();

	});







</script>

</body>
</html>

