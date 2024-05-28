document.addEventListener('alpine:init', () => {
    Alpine.data('balades', () => ({
        map: null,
        isOpen: false,
        menuSelected: 'map',
        tabOpen: '',
        menuOpen: false,
        leafletLoaded: false,
        polyline: null,
        markers: null,
        walksCount: 0,
        filtersSelected: {type: 11, localite: null},
        offerSelected: null,
        codeCgtSelected: null,
        previewOpen: false,
        toggle() {
            this.isOpen = !this.isOpen
            this.$refs.leaflet.innerHTML = 'Loading...'
        },
        init() {
            this.$watch('filtersSelected', value => this.addMarkersGrouped())
            this.loadLeaflet()
        },
        initialized() {
            console.log('initialized')
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
        },
        toggleCollapsation(id) {
            if (this.tabOpen === id) {
                this.tabOpen = -1 //close
            } else {
                this.tabOpen = id
            }
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
            this.leafletLoaded = true
            const center = [50.217845, 5.331049]
            const zoom = 13
            this.map = L.map('walks_map').setView(center, zoom)

            L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
                minZoom: 1,
                maxZoom: 20
            })
                .addTo(this.map)
            this.markers = L.markerClusterGroup();
            this.addMarkersGrouped()
        },
        addMarkersGrouped() {
            this.markers.clearLayers();
            let iconSize = [25, 41]
            let allWalks = JSON.parse(document.getElementById('maincarto').dataset.markers)
            let data = allWalks.filter(this.checkFiltered(this.filtersSelected))
            this.walksCount = data.length
            data.forEach((offer) => {
                const marker = L.marker(new L.LatLng(offer.address.latitude, offer.address.longitude), {
                    title: offer.nom,
                    icon: new L.Icon({iconUrl: this.iconMarker(offer), iconSize: iconSize})
                });
                marker.addEventListener('mouseover', () => {
                    this.drawPolyline(offer)
                });
                marker.addEventListener('mouseout', () => {
                    this.removePolyline()
                });
                marker.addEventListener('click', () => {
                    this.walkPreview(offer.codeCgt)
                });
                this.markers.addLayer(marker);
            })
            this.map.addLayer(this.markers);
        },
        checkFiltered(filtersSelected) {
            return function (offer) {
                if (filtersSelected.localite === null && filtersSelected.type === 11) {
                    return true
                }
                if (filtersSelected.localite) {
                    if (offer.localite == filtersSelected.localite) {
                        return true
                    }
                }
                if (filtersSelected.type) {
                    if (offer.type == filtersSelected.type) {
                        return true
                    }
                }
                return false
            }
        },
        walkPreview(codeCgtSelected) {
            this.codeCgtSelected = codeCgtSelected
            let allWalks = JSON.parse(document.getElementById('maincarto').dataset.markers)
            let offerSelected = allWalks.filter(offer => offer.codeCgt == codeCgtSelected)
            if (offerSelected.length > 0)
                this.offerSelected = offerSelected[0]
            console.log(this.offerSelected.gpx_distance) + ' km'
            this.previewOpen = true
            //   scrollUp()
        },
        drawPolyline(offer) {
            if (offer.locations.length > 0) {
                const myStyle = {
                    "color": "#487F89FF",
                    "weight": 5,
                    "opacity": 1
                };
                this.polyline = L.polyline(offer.locations, myStyle).addTo(this.map)
            }
        },
        removePolyline() {
            if (this.polyline != null) {
                this.polyline.remove()
            }
        },
        iconMarker(offer) {
            return `/wp-content/themes/visittail/assets/images/map/marker-icon.png`
        }
    }));
});