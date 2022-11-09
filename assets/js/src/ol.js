import 'ol/ol.css';
import Map from 'ol/Map.js';
import OSM from 'ol/source/OSM.js';
import View from 'ol/View.js';
import {fromLonLat} from "ol/proj";
import Point from "ol/geom/Point";
import GPX from 'ol/format/GPX';
import Feature from "ol/Feature";
import {Icon, Style} from "ol/style";
import {Tile as TileLayer, Vector as VectorLayer} from "ol/layer";
import VectorSource from "ol/source/Vector";

const mapdiv = document.getElementById('openmap_offre')
let longitude = mapdiv.dataset.longitude;
let latitude = mapdiv.dataset.latitude;

const rome = new Feature({
    geometry: new Point(fromLonLat([latitude, longitude]))
});

rome.setStyle(
    new Style({
        image: new Icon({
            color: "#BADA55",
            crossOrigin: "anonymous",
            src: "/wp-content/themes/visittail/assets/images/map-pin.svg"
        })
    })
);

const vectorSource = new VectorSource({
    features: [rome]
});

const vectorLayer = new VectorLayer({
    source: vectorSource
});

const vectorGpx = new VectorLayer({
    source: new VectorSource({
        url: '/images/fells_loop.gpx',
        format: new GPX(),
    }),
    style: function (feature) {
        return style[feature.getGeometry().getType()];
    },
});

const mapjf = new Map({
    target: 'openmap_offre',
    layers: [
        new TileLayer({
            source: new OSM(),
        }),
        vectorLayer
    ],
    view: new View({
        center: fromLonLat([latitude, longitude]),
        zoom: 18
    })
});