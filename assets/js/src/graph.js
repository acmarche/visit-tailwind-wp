import Chart from 'chart.js/auto';

const data = [];

const ctx = document.getElementById('myChart').getContext('2d');
const distances = [];
let current = 0;

for (let i = 0; i < data.length - 1; i++) {
    current = i;
    let firstElement = data[i];
    let secondElement = data[i + 1];
    let result = distanceInMeter(firstElement.latitude, firstElement.longitude, secondElement.latitude, secondElement.longitude);

    distances.push(result + ' metres');
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

const myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: metres.map(row => Math.floor(row)),
        datasets: [{
            label: 'Elevation',
            data: elevations,
            borderWidth: 3,
            borderColor: '#487F89FF',
            backgroundColor: '#f78da7',
            pointRadius: 0,
            fill: 'start'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false
            },
            title: {
                display: true,
                text: 'Chart.js Line Chart'
            }
        },
        hover: {
            mode: 'index',
            intersec: false
        },
        scales: {
            y: {
                beginAtZero: true,
                min: 100,
                max: 350
            },
            x: {
                ticks: {
                    callback: value => `${parseInt(value) / 10} km`
                }
            }
        }
    }
});

function distanceInMeter(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // metres
    const φ1 = lat1 * Math.PI / 180; // φ, λ in radians
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    const d = R * c; // in metres

    return d;
}