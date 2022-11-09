document.addEventListener('alpine:init', () => {
    Alpine.data('refreshOffres', () => ({
            currentCategory: 0,
            filtreSelected: 0,
            language: '',
            isLoading: false,
            offres: [],
            async initOffres(categoryId) {
                this.currentCategory=categoryId
                this.launchRefresh(null)
            },
            async launchRefresh(e) {
                console.log(this.currentCategory)
                if(e !== null) {
                    this.filtreSelected = e.target.dataset.filtre
                }
                const url = `https://visit.marche.be/${this.language}/wp-json/pivot/offres/${this.currentCategory}/${this.filtreSelected}`;
                console.log(url)
                let response = await fetch(url)
                    .then(function (response) {
                        // The API call was successful!
                        return response.json();
                    })
                    .then(function (data) {
                        // This is the JSON from our response
                        return data;
                    })
                    .catch(function (err) {
                        // There was an error
                        console.warn("Something went wrong.", err);
                        return err
                    });
                console.log(response)
                this.offres=response
            }
        })
    )
})