import L from 'leaflet'
import {createChart} from './chart.js'

export var hello = function (who) {

    const chartData = document.getElementById('data-chart');
    const mapDiv = document.getElementById('openmap_offre');
    const latitude = mapDiv.dataset.latitude;
    const longitude = mapDiv.dataset.longitude;
    const locations = JSON.parse(mapDiv.dataset.locations);
    const pois = JSON.parse(mapDiv.dataset.pois);

    let zoom = 15;
    const name = "zeze";
    const center = [latitude, longitude];

    if (typeof pois === 'object' && pois.length > 0) {
        zoom = 13;
    }

    const map = L.map('openmap_offre')
        .setView(center, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
        minZoom: 1,
        maxZoom: 20
    })
        .addTo(map);

    const marker = L.marker(center, {
        title: name,
        draggable: true
    })
        .addTo(map);

    const LeafIcon = L.Icon.extend({
        options: {
            shadowUrl: '/wp-content/themes/visittail/assets/images/leaf-shadow.png',
            iconSize: [38, 95],
            shadowSize: [50, 64],
            iconAnchor: [22, 94],
            shadowAnchor: [4, 62],
            popupAnchor: [-3, -76]
        }
    });

    const greenIcon = new LeafIcon({iconUrl: '/wp-content/themes/visittail/assets/images/leaf-green.png'});

    pois.forEach(function (poi, index) {
        L.marker([poi.adresse1.latitude, poi.adresse1.longitude], {
            title: poi.nom,
            icon: greenIcon,
            draggable: true
        })
            .bindPopup(poi.nom).addTo(map);
    });

    if (typeof locations === 'object' && locations.length > 0) {
        const myStyle = {
            "color": "#487F89FF",
            "weight": 5,
            "opacity": 1
        };
        L.polyline(locations, myStyle).addTo(map);
    }

    createChart(marker);
    return "Hello " + who + "!";
}

hello('jf')