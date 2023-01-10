import {Chart} from 'chart.js/auto';

export const createChart = (marker) => {

    const ctx = document.getElementById('myChart').getContext('2d');
    const chartData = document.getElementById('data-chart');
    const data = JSON.parse(chartData.dataset.points);
    const distances = [];

    for (let i = 0; i < data.length - 1; i++) {
        let firstElement = data[i];
        let secondElement = data[i + 1];
        let result = distanceInMeter(firstElement.latitude, firstElement.longitude, secondElement.latitude, secondElement.longitude);
        distances.push(result);
    }

    const metres = [];
    for (let i = 0; i < data.length - 1; i++) {
        let precedent = 0;
        if (i > 0) {
            precedent = parseFloat(metres[i - 1]);
        }
        const total = parseFloat(distances[i]) + precedent;
        metres.push(total);
    }

    const elevations = [];
    data.forEach(item => {
        elevations.push(item.elevation);
    });

    const footer = (tooltipItems) => {
        return 'Sum: ';
    };

    const options = {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    footer: footer,
                }
            },
            title: {
                display: true,
                text: 'Elevations en mètre'
            },
        },
        hover: {
            mode: 'index',//point ?
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
                max: 350
            },
            x: {
                ticks: {
                    callback: function(value)  {
                        return `${parseInt(value) / 10} km`
                    }
                }
            }
        }
    }

    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: metres.map(row => Math.floor(row)),
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

function distanceInMeter(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // metres
    const pi1 = lat1 * Math.PI / 180; // φ, λ in radians
    const pi2 = lat2 * Math.PI / 180;
    const pi3 = (lat2 - lat1) * Math.PI / 180;
    const pi4 = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(pi3 / 2) * Math.sin(pi3 / 2) +
        Math.cos(pi1) * Math.cos(pi2) *
        Math.sin(pi4 / 2) * Math.sin(pi4 / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

     // in metres
    return R * c;
}

function movePoint(marker, coordinates) {
    const latlng = L.latLng(coordinates['latitude'], coordinates['longitude']);
    marker.setLatLng(latlng);
}
