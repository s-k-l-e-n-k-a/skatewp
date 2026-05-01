document.addEventListener("DOMContentLoaded", function () {
    // Run only on single seo_page posts
    if (!document.body.classList.contains("single-seo_page")) return;

    const paragraphs = document.querySelectorAll(".entry-content p");
    if (!paragraphs.length) return;

    // Prevent duplicates
    if (document.querySelector(".reading-time")) return;

    let text = "";
    paragraphs.forEach(p => {
        if (!p.classList.contains("reading-time")) {
            text += " " + (p.innerText || p.textContent || "");
        }
    });

    const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
    const readingTime = Math.max(1, Math.ceil(wordCount / 200));

    const readingTimeElement = document.createElement("p");
    readingTimeElement.className = "reading-time";
    readingTimeElement.textContent = `Lesezeit: ${readingTime} Min.`;

    const main = document.querySelector("main");
    if (main) {
        main.insertBefore(readingTimeElement, main.firstChild);
    } else {
        const firstP = document.querySelector(".entry-content p");
        if (firstP && firstP.parentNode) {
            firstP.parentNode.insertBefore(readingTimeElement, firstP);
        }
    }
});