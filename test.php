google.charts.load("current", {packages:["corechart"]});
var data = [
	["", "", {role: "style"}],
	["Описание 1", 10, "#cccccc"],
	["Описание 2", 8, "#56b900"]
];
drawHistogramChart(data, 'capital');



function drawHistogramChart( info, id ) {
	var data = google.visualization.arrayToDataTable(info);

	var view = new google.visualization.DataView(data),
		max  = 0;

	for(itm in info ) {
		if( max < info[itm][1] ) {
			max = info[itm][1];
		}
	}

	var options = {
		title          : "",
		chartArea      : {left: 0, top: 0, width: '100%', height: '100%'},
		backgroundColor: 'transparent',
		bar            : {groupWidth: "95%"},
		width          : 280,
		vAxis          : {
			minValue: 0,
			maxValue: max
		}
	};
	var chart   = new google.visualization.ColumnChart(document.getElementById(id));
	chart.draw(view, options);
}
