<?php
include 'mods/common.php';


if(AJAX)
{
	$raw = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	$raw = array_slice($raw, -10, 10, true);


	$data = array();
	foreach($raw as $row)
		if(preg_match('/^20[\d\s\.]+$/', $row))
			$data[] = new Sensor($row);

	$last = end($data);

	$data = [];

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
	$data['lamp'] = $last->lamp;

	$data['settings'] = $settings;

	$allowedSettings = ['humidity', 'temperature'];
	foreach($data['settings'] as $k => $v)
		if(!in_array($k, $allowedSettings))
			unset($data['settings'][$k]);

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

</div>

	<script src="./js/jquery.min.js"></script>
	<script src="./js/bootstrap.min.js"></script>
	<script src="./js/howler.min.js"></script>

<script>
var tOut, updBtn, info;
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
				" ",
				$("<span>").attr("class","hmdt").html(data.last.ha + "%"),
				" ",
				$("<span>").attr("class","heat").html((1 == data.heater ? 'On' : 'Off')),
				" ",
				$("<span>").attr("class","lamp").html((1 == data.lamp ? 'On' : 'Off')),
				" ",
				$("<span>").attr("class", "wet").html((1 == data.humidifier ? 'On' : 'Off')),
				" ",
				$("<span>").attr("class", "next").html("")
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



		//snd("click");

		clearTimeout(tOut);

		var ts = 60000, now = new Date().getTime();

		if(data.next && data.next > 0)
			ts = data.next;
		else
			console.log(data.next)

		$("span.next").html(Math.round(ts/1000))
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

function tNext()
{
	var c, n = $("span.next");
	if(n)
	{
		c = parseInt(n.text()) - 1;
		n.html(c > 0 ? c : '-');
	}
	setTimeout(tNext, 1000);
}

$(function () {

	updBtn = $("#updBtn");
	info = $("#info");
	reData();
	tNext();
});







</script>

</body>
</html>

