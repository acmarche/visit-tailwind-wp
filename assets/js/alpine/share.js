document.addEventListener('alpine:init', () => {
    Alpine.data('share', () => ({
            currentUrl: null,
            copyLink() {
                navigator.clipboard.writeText(this.currentUrl);
                alert("L'adresse de la page a été copiée dans votre presse papier");
            }
        })
    )
})