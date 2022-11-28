document.addEventListener('alpine:init', () => {
    Alpine.data('refreshOffres', () => ({
            currentCategory: 0,
            filtreSelected: 0,
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
            async changeOffres(f) {
                this.filtreSelected = f
                this.launchRefresh(null)
            },
            async launchRefresh(e) {
                this.isLoading = true
                console.log(this.currentCategory)
                if (e !== null) {
                    this.filtreSelected = e.target.dataset.filtre
                }
                const url = `https://visitmarche.be/${this.language}/wp-json/pivot/offres/${this.currentCategory}/${this.filtreSelected}`;
                console.log(url)
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