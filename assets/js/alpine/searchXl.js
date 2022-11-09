document.addEventListener('alpine:init', () => {
    Alpine.data('searchXl', () => ({
            searchIsOpen: false,
            isLoading: false,
            noResult: false,
            query: '',
            totalHits: 0,
            results: [],
            async launchSearch() {
                if (this.query === '') return
                this.noResults = false
                this.isLoading = true
                console.log(`search for ${this.query}`)
                let response = await fetch("https://visitmarche.be/api/search.php?s=" + this.query)
                    .then(function (response) {
                        // The API call was successful!
                        return response.json()
                    })
                    .then(function (data) {
                        // This is the JSON from our response
                        return data
                    })
                    .catch(function (err) {
                        // There was an error
                        console.warn("Something went wrong.", err)
                        this.isLoading = false
                        return err
                    });
                this.results = response
                this.totalHits = response.length
                console.log(this.totalHits)
                this.isLoading = false
            }
        })
    )
})