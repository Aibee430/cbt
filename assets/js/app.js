(function () {
    const flashes = document.querySelectorAll('[data-auto-dismiss]');
    flashes.forEach((flash) => {
        const explicitMs = parseInt(
            flash.getAttribute('data-auto-dismiss-ms') || flash.getAttribute('data-auto-dismiss'),
            10
        );
        const fallbackMs = flash.classList.contains('alert-danger') ? 15000 : 7000;
        const timeoutMs = Number.isFinite(explicitMs) && explicitMs > 0 ? explicitMs : fallbackMs;

        setTimeout(() => flash.remove(), timeoutMs);
    });
})();
