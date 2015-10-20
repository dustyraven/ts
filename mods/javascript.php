
<script>
var tOut, updBtn, info;
var SENS_COLD = 0, SENS_WARM = 1, SENS_ROOM = 2;


function reData()
{
	updBtn.hide();
	info.html("&nbsp;");

	var jqxhr = $.getJSON( "", function(data) {

		if(data.reload)
			location.reload(true);

		var tc = percRange(data.last.tc, 10, 40);
			th = percRange(data.last.th, 10, 40);
			tr = percRange(data.last.tr, 10, 40);
			hc = percRange(data.last.hc, 20, 90);
			hh = percRange(data.last.hh, 20, 90);
			hr = percRange(data.last.hr, 20, 60);


		$("#ts").text(data.last.ts);
		updBtn.show();
		info.empty().append(
				$("<span>").attr("class","temp").html(data.last.ta + "° / " + ctof(data.last.ta) + "F"),
				" ",
				$("<span>").attr("class","hmdt").html(data.last.ha + "%"),
				" ",
				$("<span>").attr("class","heat " + (1 == data.heater ? 'on' : 'off')),
				" ",
				$("<span>").attr("class","lamp " + (1 == data.lamp ? 'on' : 'off')),
				" ",
				$("<span>").attr("class","mist " + (1 == data.humidifier ? 'on' : 'off')),
				" ",
				$("<span>").attr("class","next")
			);

		$("#tempH").css("width", th + '%').find("b").text(data.last.th + "° / " + ctof(data.last.th) + "F");
		$("#tempC").css("width", tc + '%').find("b").text(data.last.tc + "° / " + ctof(data.last.tc) + "F");
		$("#tempR").css("width", tr + '%').find("b").text(data.last.tr + "° / " + ctof(data.last.tr) + "F");
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
