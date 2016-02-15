<?php
#error_reporting(E_ALL);
#ini_set('display_errors', 1);
include('keys.php');
?>

<html>
<head>
	<script type="text/javascript" src="lib/d3.min.js"></script>
	<style type="text/css">
		.axis path,
		.axis line {
		    fill: none;
		    stroke: black;
		    shape-rendering: crispEdges;
		}
		.tick line {
			stroke: #bbbbbb;
		}
		.axis line.minor{
			stroke: #eeeeee;
		}

		text {
		    font-family: sans-serif;
		    font-size: 11px;
		}

		.area {
		    fill: rgba(201, 227, 172, 0.4);
		    stroke-width: 0;
		}

		.nestline {
			stroke: #90BE6D;
		}
		.wuline {
			stroke: #EA9010;
		}
		.setpointline {
			stroke: #EAEFBD;
		}
	</style>
</head>	
<body>
<?php
$timestamp = str_pad(dechex(strtotime("-10 days")), 8, '0', STR_PAD_LEFT);
$mongoid = new MongoId($timestamp.substr(new MongoID(), 8));

$connection = new MongoClient();
$shorelogger = $connection->selectDB('thermostatdb'); 
$nestdata = $shorelogger->nestdata;

$cursor = $nestdata->find(array('_id' => array('$gt' => $mongoid) ) );
$data = array();
foreach ($cursor as $doc) {
	if ( $doc['thermostats'] ) {
		$thermostat = $doc['thermostats'][$thermostat_id];
	} else {
		$thermostat = $doc['devices']['thermostats'][$thermostat_id];
		$away = $doc['structures'][$structure_id]['away'];
	}

	if($away == 'away') {
		$setpoint = $thermostat['away_temperature_high_f'];
	} else {
		$setpoint = $thermostat['target_temperature_f'];
	}

    $data[] = array( 'timestamp' => $doc['_id']->getTimestamp(), 'temp_f' => $thermostat['ambient_temperature_f'] , 'setpoint' => $setpoint, 'hvac_state' => $thermostat['hvac_state']);

}

$wu_cursor = $shorelogger->wunderground_data->find(array('_id' => array('$gt' => $mongoid) ) );
$wu_data = array();

foreach ($wu_cursor as $doc) {
	$wu_data[] = array('timestamp' => $doc['_id']->getTimestamp(), 'temp_f' => $doc['current_observation']['temp_f'], 'icon' => $doc['current_observation']['icon'], 'icon_url' => $doc['current_observation']['icon_url']);
} 
?>
<script type="text/javascript">
	var theData = <?php echo json_encode($data); ?>;
	var wuData = <?php echo json_encode($wu_data); ?>;
	var height = 300;
	var width = 1000;
	var margin = {top: 50, right: 50, bottom: 50, left: 50};

	var svgContainer = d3.select("body").append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");


	var tempScale = d3.scale.linear()
		.domain([50, 90])
		.range([height,0]);


	svgContainer.selectAll("line.horizontalGrid").data(tempScale.ticks(8)).enter()
		.append('line')
		.attr(
		{
			"class":"horizontalGrid",
			"x1"	: 0,
			"x2"	: width,
			"y1"	: function(d) { return tempScale(d); },
			"y2"	: function(d) { return tempScale(d); },
			"fill"	: "none",
			"shape-rendering": "crispEdges",
			"stroke"	: "#eeeeee",
			"stroke-width": "1px"
		});

	var xScale = d3.time.scale()
		.domain([
			d3.min(theData, function(d) {
				return new Date(d.timestamp * 1000);
			})
			, 
			d3.max(theData, function(d) {
				return new Date(d.timestamp * 1000);
			})
		])
		.range([0, width])
		.nice(d3.time.hour);
	
	var yScale = d3.scale.linear()
		.domain([50, 90])
		.range([height, 0]);
	
	var xAxis = d3.svg.axis()
		.scale(xScale)
		.orient("bottom")
		.tickPadding(8)
		.tickSize(-height, 0);

	var yAxis = d3.svg.axis().scale(yScale).orient('left');

	var xAxisGroup = svgContainer
		.append("g")
		.attr('class', 'axis')
		.call(xAxis)
		.attr('transform' ,'translate(0,' + height + ')');

	var yAxisGroup = svgContainer
			.append("g")
			.attr('class', 'axis')
			.call(yAxis);

	xAxisGroup.selectAll("line").data(xScale.ticks(d3.time.hours, 1), function(d) { return d; })
	    .enter()
	    .append("line")
	    .attr("class", "minor")
	    .attr("y1", 0)
	    .attr("y2", -height)
	    .attr("x1", xScale)
	    .attr("x2", xScale);

	//Axis labels
	svgContainer.append("text")
		.attr("class", "x label")
    	.attr("x", 510)
   	 	.attr("y", height + 35)
   	 	.attr("text-anchor", "middle")
   		.text("Time");	

	svgContainer.append("text")
		.attr("class", "y label")
    	.attr("x", - (height) /2 )
   	 	.attr("y", -35 )
   	 	.attr("text-anchor", "middle")
   	 	.attr("transform", "rotate(-90)")
   		.text("temperature (f)");	

	//shaded areas
	var shadeDatum = [];
	var shadeData = [];

	for (var i = 0; i < theData.length; i++) {
		if (theData[i].hvac_state == 'cooling') {
			shadeData.push(theData[i]);
		} else {
			if (shadeData.length > 0) shadeDatum.push(shadeData);
			shadeData = [];
		}
	}

	var area = d3.svg.area()
		.x(function(d) { return xScale(new Date(d.timestamp * 1000) ); })
		.y0( height )
		.y1( function(d) { return tempScale(d.temp_f) });

	for ( var i = 0; i < shadeDatum.length; i++) {
		var shade = svgContainer.append("path")
			.datum(shadeDatum[i])
			.attr("class", "area")
			.attr("d", area);
	}
	var setpointFunction = d3.svg.line()
		.x(function(d) { return xScale( new Date (d.timestamp * 1000) ) })
		.y(function(d) { return tempScale ( d.setpoint )})
		.interpolate("basis");

	var setpointGraph = svgContainer.append("path")
		.attr("d", setpointFunction(theData))
		.attr("class", "setpointline")
		.attr("stroke-width",2)
		.attr("fill", "none");


	var lineFunction = d3.svg.line()
		.x(function(d) { return xScale( new Date(d.timestamp * 1000)) })
		.y(function(d) { return tempScale(d.temp_f) })
		.interpolate("basis");
	
	var lineGraph = svgContainer.append("path")
		.attr("d", lineFunction(theData))
		.attr("class", "nestline")
		.attr("stroke-width",2)
		.attr("fill","none")

	var tempGraphFunction = d3.svg.line()
		.x(function(d) { return xScale( new Date(d.timestamp * 1000) ) })
		.y(function(d){ return tempScale( d.temp_f) })
		.interpolate("basis");

	var wuGraph = svgContainer.append("path")
		.attr("d", tempGraphFunction(wuData))
		.attr("class", "wuline")
		.attr("stroke-width",2)
		.attr("fill", "none")

	//weather icons
		//shaded areas
	var conditionDatum = [];
	var conditionData = [];
	var oldIcon = "none";
	var lastxpos = -10;

	for (var i = 0; i < wuData.length; i++) {
		newIcon = wuData[i].icon_url;
		xpos = xScale( new Date(wuData[i].timestamp * 1000) ) - 10;
		if (newIcon != oldIcon && xpos >  lastxpos + 22) {
			
			var weatherIcons = svgContainer
				.append("image")
				.attr("xlink:href",wuData[i].icon_url)
				.attr("x", xpos )
				.attr("y",-20)
				.attr("width",20)
				.attr("height",20);
			lastxpos = xpos;
			oldIcon = newIcon;
		} 
	}



</script>

</body>
</html>
