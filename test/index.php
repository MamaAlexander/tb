<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Market Time Series</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body>
    <h1>Financial Market Time Series</h1>
    <input type="file" id="my_file_input" />
    <div id='output'></div>
    <div class="container">
        <div id="chart_div" style="width: 900px; height: 500px;"></div>
        <div id="gisto_div" style="width: 900px; height: 100px; margin-top: -40px;"></div>
    </div>
    <button id="stop_button">Stop</button>
    <button class="step_forward"> > </button>
    <input type="checkbox" id="bollinger">Полосы Боллинджера</input>
    <input type="checkbox" id="EMA">EMA</input>
    <div>
        <select id="scale_select" class="scale_select">
            <option value="0">Scale</option>
            <option value="5">5M</option>
            <option value="10">10M</option>
            <option value="15">15M</option>
            <option value="30">30M</option>
            <option value="40">40M</option>
            <option value="60">1H</option>
        </select>
    </div>
    <script>
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(initChart);

        let Tiker = 'SBER';
        let linesArray = [];
        let stop_flag = 0;
        let myChart;
        let data;
        let options;
        let i = 0; 
        let array_period = []; 
        let indexes = [];
        let scale_value = 0;
        let c0_data = [];
        let c_data = [];
        let bollinger_flag = 0;
        let EMA_flag = 0;

        function readFile(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = event => {
                    const fileContent = event.target.result;
                    const lines = fileContent.split('\n');
                    linesArray = lines.map(line => line.split(','));
                    resolve(linesArray);
                    stop_flag = 0;
                };
                reader.onerror = error => reject(error);
                reader.readAsText(file);
            });
        }

        function initChart() {
            data = google.visualization.arrayToDataTable([
                ['Date', 'Low', 'Open', 'Close', 'High', 'SMA'],
            ]);

            options = {
                legend:'top',
                candlestick: {
                    risingColor: {stroke: 'green', fill: 'green'},
                    fallingColor: {stroke: 'red', fill: 'red'}
                },
                series: {
                    1: {type: 'line', color: 'blue'}
                },
                colors: ['black'],
                height: 450, // Adjusted to leave space for the histogram
                chartArea: {left: 50, top: 20, width: '90%', height: '75%'}
            };

            myChart = new google.visualization.CandlestickChart(document.getElementById('chart_div'));
        }

        function drawHistogramChart(info, id) {
            var my_data = google.visualization.arrayToDataTable(info);
            var view = new google.visualization.DataView(my_data);

            var options = {
                title: "",
                chartArea: {left: 50, top: 0, width: '90%', height: '100%'},
                backgroundColor: 'transparent',
                bar: {groupWidth: "95%"},
                width: 900,
                height: 100,
                vAxis: {minValue: 0},
                hAxis: {slantedText: false, textPosition: 'none'}
            };

            var chart = new google.visualization.ColumnChart(document.getElementById(id));
            chart.draw(view, options);
        }

        document.querySelector('input[type="file"]').addEventListener('change', async function() {
            if (this.files[0]) {
                try {
                    await readFile(this.files[0]);

                    i = Math.floor(Math.random() * (linesArray.length / 2));
                    array_period = [];

                    updateChart();

                } catch (error) {
                    console.error('Ошибка чтения файла:', error);
                }
            }
        });

        function calculateSMA(data, windowSize) {
            const sma = [];
            for (let i = 0; i < data.length; i++) {
                if (i < windowSize) {
                    sma.push(null); // Not enough data points yet
                } else {
                    const slice = data.slice(i - windowSize, i);
                    const sum = slice.reduce((a, b) => a + b, 0);
                    sma.push(sum / windowSize);
                }
            }
            return sma;
        }

        function Bollinger_lines(windowSize, closePrices, smaValues) {
            if (closePrices.length < windowSize) {
                return [null, null]; // Not enough data points yet
            } else {
                let sma = smaValues[smaValues.length - 1];
                let variance = 0;
                for (let i = 0; i < windowSize; i++) {
                    let price = closePrices[closePrices.length - 1 - i];
                    let diff = price - sma;
                    variance += diff * diff;
                }
                variance /= windowSize;
                let sigma = Math.sqrt(variance);
                
                let upperBand = sma + 2 * sigma;
                let lowerBand = sma - 2 * sigma;
                return [lowerBand, upperBand];
            }
        }

        function updateChart() {
            if (i < linesArray.length && stop_flag === 0) {
                let closePrices = array_period.map(row => row[3]); // Extract only previous closing prices
                let smaValues = calculateSMA(closePrices, 6); // Calculate SMA without the current closing price
                let bol_lines = Bollinger_lines(6, closePrices, smaValues);

                if (linesArray[i][2].slice(11) === '07:00:00') {
                    c0_data.push([linesArray[i][2], parseFloat(linesArray[i][7])]);
                    indexes.push(i);
                    array_period.push([
                        linesArray[i][2],
                        parseFloat(linesArray[i][6]),
                        parseFloat(linesArray[i][3]),
                        parseFloat(linesArray[i][4]),
                        parseFloat(linesArray[i][5]),
                        smaValues[smaValues.length - 1]
                    ]);
                } else {
                    c0_data.push([linesArray[i][2].slice(11, 16), parseFloat(linesArray[i][7])]);
                    indexes.push(i);
                    array_period.push([
                        linesArray[i][2].slice(11, 16),
                        parseFloat(linesArray[i][6]),
                        parseFloat(linesArray[i][3]),
                        parseFloat(linesArray[i][4]),
                        parseFloat(linesArray[i][5]),
                        smaValues[smaValues.length - 1]
                    ]);
                }

                if (array_period.length > scale_value) {
                    array_period.shift();
                    c0_data.shift();
                }

                data = google.visualization.arrayToDataTable([
                    ['Date', 'Low', 'Open', 'Close', 'High', 'SMA'],
                    ...array_period
                ]);

                c_data = [
                    ["Date", "Volume"],
                    ...c0_data
                ];

                myChart.draw(data, options);
                drawHistogramChart(c_data, 'gisto_div');

                i++;
            }
            if (stop_flag === 0) {
                setTimeout(updateChart, 500); 
            }
        }

        document.getElementById('stop_button').addEventListener('click', function() {
            stop_flag = stop_flag === 0 ? 1 : 0;
            if (stop_flag === 0) {
                updateChart();
            }
        });

        document.getElementById('scale_select').addEventListener('change', function() {
            const selectedValue = this.value;
            console.log('Selected value:', selectedValue);
            scale_value = Number(selectedValue);
        });

        document.querySelectorAll('.step_forward').forEach(button => {
            button.addEventListener('click', function() {
                i = indexes[indexes.length - 1];
                i++;
                array_period.shift();

                let closePrices = array_period.map(row => row[3]);
                closePrices.push(parseFloat(linesArray[i][4])); // Добавление текущей цены закрытия
                let smaValues = calculateSMA(closePrices, 6);
                if (linesArray[i][2].slice(11) == '07:00:00') {
                    c0_data.push([linesArray[i][2], parseFloat(linesArray[i][7])]);
                    start_over_flag = 1;
                    indexes.push(i);
                    array_period.push([
                        linesArray[i][2],
                        parseFloat(linesArray[i][6]),
                        parseFloat(linesArray[i][3]),
                        parseFloat(linesArray[i][4]),
                        parseFloat(linesArray[i][5]),
                        smaValues[smaValues.length - 1]
                    ]);
                } else {
                    c0_data.push([linesArray[i][2].slice(11, 16), parseFloat(linesArray[i][7])]);
                    indexes.push(i);
                    array_period.push([
                        linesArray[i][2].slice(11, 16),
                        parseFloat(linesArray[i][6]),
                        parseFloat(linesArray[i][3]),
                        parseFloat(linesArray[i][4]),
                        parseFloat(linesArray[i][5]),
                        smaValues[smaValues.length - 1]
                    ]);
                }

                if (array_period.length > scale_value) {
                    array_period.shift(); 
                    c0_data.shift();
                }

                data = google.visualization.arrayToDataTable([
                    ['Date', 'Low', 'Open', 'Close', 'High', 'SMA'],
                    ...array_period
                ]);

                c_data = [
                    ["Date", "Volume"],
                    ...c0_data
                ];

                myChart.draw(data, options);
                drawHistogramChart(c_data, 'gisto_div');
                i++;
            });
        });

        document.getElementById('bollinger').addEventListener('change', function() {
            bollinger_flag = this.checked ? 1 : 0;
        });

        document.getElementById('EMA').addEventListener('change', function() {
            EMA_flag = this.checked ? 1 : 0;
        });

    </script>
</body>
</html>