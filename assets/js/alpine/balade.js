/*
import Alpine from '/node_modules/alpinejs/dist/module.esm.js';
//import L from '/node_modules/leaflet/src/Leaflet.js';
import {Map} from "https://unpkg.com/leaflet@1.7.1/src/map";
import "https://unpkg.com/leaflet@1.7.1/src/layer/vector/Renderer.getRenderer";
import "https://unpkg.com/leaflet@1.7.1/src/control/Control.Attribution";
import "https://unpkg.com/leaflet@1.7.1/src/control/Control.Zoom";
*/

document.addEventListener('alpine:init', () => {
    Alpine.data('balades', () => ({
        isOpen: false,
        menuSelected: 'map',
        tabOpen: '',
        menuOpen: false,
        leafletLoaded: false,
        filtersSelected: {type: null, localite: null},
        toggle() {
            this.isOpen = !this.isOpen
            this.$refs.leaflet.innerHTML = 'Loading...'
            //  this.loadLeaflet()
        },
        jf() {
            console.log('jf')

        },
        init() {
            this.loadLeaflet()
        },
        manageFilters(name, value, event) {
            if (!event.target.checked) {
                if (name === 'localites') {
                    this.filtersSelected.localite = null
                }
                if (name === 'type') {
                    this.filtersSelected.type = 11
                }
            } else {
                if (name === 'localites') {
                    this.filtersSelected.localite = value
                }
                if (name === 'type') {
                    this.filtersSelected.type = value
                }
            }
            console.log(this.filtersSelected)
        },
        toggleCollapsation(id) {

        },
        isChecked(name, value) {
            if (name === 'localite') {
                if (this.filtersSelected.localite === null) {
                    return false
                }
                return this.filtersSelected.localite === value
            }
            if (name === 'type') {
                if (this.filtersSelected.type === null) {
                    return false
                }
                return this.filtersSelected.type === value
            }
            return false
        },
        loadLeaflet() {
            if (this.leafletLoaded) {
                return;
            }
            console.log('leafletLoaded' + this.leafletLoaded)
            this.leafletLoaded = true
            const center = [50.217845, 5.331049]
            const zoom = 13
            //const map = new Map("map").setView([51.505, -0.09], 13);
            const mapDiv = document.getElementById('walks_map');
            const map = L.map('walks_map').setView(center, zoom)

            L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
                minZoom: 1,
                maxZoom: 20
            })
                .addTo(map)
        }
    }));
});
