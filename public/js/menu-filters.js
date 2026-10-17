const form = document.querySelector("#menu-filters");
const results = document.querySelector("#menu-results");
const status = document.querySelector("#filter-status");

if (form && results && status) {
    let loading = false;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (loading) {
            return;
        }

        loading = true;
        results.setAttribute("aria-busy", "true");
        status.textContent = "Chargement des menus…";

        const button = form.querySelector('button[type="submit"]');

        if (button) {
            button.disabled = true;
        }

        const url = new URL(form.action, window.location.href);
        const parameters = new URLSearchParams(new FormData(form));

        url.search = parameters.toString();

        try {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error("La recherche a échoué.");
            }

            const html = await response.text();
            const documentReceived = new DOMParser().parseFromString(
                html,
                "text/html",
            );

            const newResults = documentReceived.querySelector("#menu-results");

            if (!newResults) {
                throw new Error("Les résultats sont introuvables.");
            }

            results.innerHTML = newResults.innerHTML;
            window.history.replaceState(null, "", url);

            if (results.querySelector(".form-error")) {
                status.textContent = "Corrige les filtres indiqués.";
            } else {
                const count = results.querySelectorAll(".menu-card").length;

                status.textContent = `${count} menu(s) trouvé(s).`;
            }
        } catch (error) {
            status.textContent =
                "Impossible d’actualiser les menus. Les résultats précédents sont conservés. Réessaie.";
        } finally {
            loading = false;
            results.setAttribute("aria-busy", "false");

            if (button) {
                button.disabled = false;
            }
        }
    });
}
