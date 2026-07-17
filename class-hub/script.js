document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("searchInput");
    const cards = document.querySelectorAll(".card-item");

    searchInput.addEventListener("keyup", function(e) {
        const query = e.target.value.toLowerCase();

        cards.forEach(card => {
            const searchData = card.getAttribute("data-search");
            // If the query exists in the title, course code, or type, show it; else hide it
            if (searchData.includes(query)) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    });
});