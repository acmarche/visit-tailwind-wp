document.addEventListener('alpine:init', () => {
    Alpine.data('refreshOffres', () => ({
            currentCategory: 0,
            filtreSelected: 0,
            filtreType: 'wp',
            language: '',
            isLoading: false,
            offres: [],
            async initOffres(categoryId, language) {
                this.isLoading = true
                this.language = language
                this.currentCategory = categoryId
                //pour mode dev
                //if (this.language === 'fr')
                //    this.language = ''
                console.log(this.language)
                this.launchRefresh(null)
            },
            async changeOffres(f, type) {
                this.filtreSelected = f
                this.filtreType = type
                this.launchRefresh(null)
            },
            async launchRefresh(e) {
                this.isLoading = true
                if (e !== null) {
                    this.filtreSelected = e.target.dataset.filtre
                    this.filtreType = e.target.dataset.filtretype
                }
                const url = `https://visitmarche.be/wp-json/pivot/offres/${this.currentCategory}/${this.filtreSelected}/${this.filtreType}`;
                this.offres = await fetch(url)
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
                        this.isLoading = false
                        console.warn("Something went wrong.", err);
                        return err
                    })

                this.isLoading = false
            }
        })
    )
})