document.addEventListener('alpine:init', () => {
    Alpine.data('map', () => ({
            latitude: false,
            longitude: false,
            isLoading: false,
            init() {
                console.log(`map for ${this.latitude}`);
                console.log(window.oljf)
                const mapdiv = document.getElementById('openmap_offre')
            }
        })
    )
})