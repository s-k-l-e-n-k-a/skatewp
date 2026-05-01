document.addEventListener("DOMContentLoaded", function () {
    var header = document.querySelector(".navbar");
    var DEFAULT_OFFSET = 280;
    var EXTRA_OFFSET = 150; // <-- additional offset

    function setAnchorOffsetVar() {
        var offset = header ? header.offsetHeight + EXTRA_OFFSET : DEFAULT_OFFSET;
        document.documentElement.style.setProperty("--anchor-offset", offset + "px");
        return offset;
    }

    function getOffset() {
        return header ? header.offsetHeight + EXTRA_OFFSET : DEFAULT_OFFSET;
    }

    function scrollToTarget(target, behavior) {
        if (!target) return;
        var offset = getOffset();
        var top = target.getBoundingClientRect().top + window.pageYOffset - offset;

        // Avoid shorthand { top, behavior } for broader compatibility
        window.scrollTo({
            top: top,
            behavior: behavior || "smooth"
        });
    }

    // Internal link clicks (same-page anchors)
    document.addEventListener("click", function (e) {
        var a = e.target.closest ? e.target.closest('a[href*="#"]') : null;
        if (!a) return;

        // Avoid using new URL(); handle href as plain string
        var href = a.getAttribute("href");
        var hashIndex = href.indexOf("#");
        if (hashIndex === -1) return;

        var path = href.substring(0, hashIndex) || "";
        var hash = href.substring(hashIndex);
        if (!hash || hash === "#") return;

        // Only scroll if the path matches the current page
        if (path && path.replace(window.location.origin, "") !== window.location.pathname) return;

        var targetID = decodeURIComponent(hash.slice(1));
        var target = document.getElementById(targetID) || document.querySelector('[name="' + targetID + '"]');
        if (!target) return;

        e.preventDefault();
        scrollToTarget(target, "smooth");

        if (window.history && window.history.pushState) {
            window.history.pushState(null, "", "#" + targetID);
        }
    });

    // Initial load with hash (coming from another page)
    setAnchorOffsetVar();
    if (window.location.hash && window.location.hash !== "#") {
        window.requestAnimationFrame(function () {
            setTimeout(function () {
                var targetID = decodeURIComponent(window.location.hash.slice(1));
                var target = document.getElementById(targetID) || document.querySelector('[name="' + targetID + '"]');
                scrollToTarget(target, "auto"); // no animation
            }, 50);
        });
    }

    // Update offset variable on resize
    window.addEventListener("resize", setAnchorOffsetVar, { passive: true });
});
