<?php
/*
CRONTAB:
# m h  dom mon dow   command
* * * * * /home/pi/terrasens/terrasens.py >> /home/pi/terrasens/logs/`date +\%Y\%m\%d`.log 2>&1
*/
include 'mods/common.php';

$raw = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

//$raw = array_slice($raw, -180, 180, true);


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
	);


	$data['next'] = round((($last->timestamp + $last->work + 60) - time())*1000);

	$data['settings'] = $settings;

	ob_start('ob_gzhandler');
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;

}


include 'mods/head.php';
?>

<div class="container-fluid">

	<div class="row">
		<div class="col-xs-12">
			<div id="container2" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">&nbsp;</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			<div id="container1" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		</div>
	</div>

</div>

	<script src="./js/jquery.min.js"></script>
	<script src="./js/bootstrap.min.js"></script>
	<script src="./js/howler.min.js"></script>

	<script src="./js/highcharts.js"></script>
	<script src="./js/highcharts-more.js"></script>
	<!-- <script src="./js/exporting.js"></script> -->


<script>
var chartH, chartT, chartNow, tOut, updBtn, info;
var SENS_COLD = 0, SENS_WARM = 1, SENS_ROOM = 2;


function reChart()
{
	updBtn.hide();

	var jqxhr = $.getJSON( "", function(data) {

		if(data.reload)
			location.reload(true);


		$("#ts").text(data.last.ts);
		updBtn.show();



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

		console.log(Date.now())
		//console.log( data );

		clearTimeout(tOut);

		var ts = 60000, now = new Date().getTime();

		if(data.next && data.next > 0)
			ts = data.next;
		else
			console.log(data.next);

		tOut = setTimeout(reChart, ts);
		//console.log(tOut);
	});

}


//	T(°F) = T(°C) × 1.8 + 32
function ctof(c)
{
	return Math.round((c*1.8 + 32) * 10) / 10;
}


function snd(s)
{
	new Howl({urls: ["snd/"+s+".ogg", "snd/"+s+".mp3"]}).play();
}

	$(function () {

		updBtn = $("#updBtn");


		Highcharts.setOptions({
			colors: ['blue', 'red', 'green', 'yellow'],
			plotOptions: {
				series: {
					animation: true
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



		reChart();

	});







</script>

</body>
</html>

