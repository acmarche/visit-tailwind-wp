document.addEventListener('alpine:init', () => {
    Alpine.data('menuMobile', () => ({
            menuMobileIsOpen: false,
            searchMobileIsOpen: false,
            isLoading: false,
        })
    )
})