(function () {
    const flash = document.querySelector('[data-auto-dismiss]');
    if (flash) {
        setTimeout(() => flash.remove(), 5000);
    }
})();
