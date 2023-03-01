import {Chart} from 'chart.js/auto';

export const createChart = (marker) => {

    const ctx = document.getElementById('myChart').getContext('2d');
    const chartData = document.getElementById('data-chart');
    const data = JSON.parse(chartData.dataset.points);
    const miles = JSON.parse(chartData.dataset.metres);

    const elevations = [];
    data.forEach(item => {
        elevations.push(item.elevation);
    });

    //miles.forEach(element => console.log(element))

    const options = {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            },
            title: {
                display: true,
                text: 'Elevations en mètre'
            },
        },
        hover: {
            mode: 'index',
        },
        onHover: (e) => {
            const lm = (myChart.getElementsAtEventForMode(e, 'index', {intersec: false}, true))
            const index = lm[0].index
            const coordinates = data[index]
            movePoint(marker, coordinates)
        },
        // For instance you can format Y axis
        scales: {
            y: {
                beginAtZero: true,
                min: 200,
                max: 400
            },
            x: {
                ticks: {
                    callback: function (value, index, ticks) {
                        return miles[index] + ' km'
                    }
                }
            }
        }
    }

    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: miles,
            datasets: [{
                label: 'Elevation en mètre',
                data: elevations,
                borderWidth: 3,
                borderColor: '#487F89FF',
                backgroundColor: '#f78da7',
                pointRadius: 0,
                fill: 'start'
            }]
        },
        options: options
    });
}

function movePoint(marker, coordinates) {
    const latlng = L.latLng(coordinates['latitude'], coordinates['longitude']);
    marker.setLatLng(latlng);
}
