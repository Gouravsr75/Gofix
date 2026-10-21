(function () {
    "use strict";

    /*
    |--------------------------------------------------------------------------
    | HISTORY LOCK — Provider Dashboard
    |--------------------------------------------------------------------------
    | Provider ek baar login kar le to browser back button se dashboard se
    | bahar nahi ja sakta jab tak logout na kare.
    |--------------------------------------------------------------------------
    */

    var dashboardUrl = window.location.href.split("#")[0];

    try {
        document.cookie = "provider_logged_in=1; path=/; max-age=2592000; SameSite=Lax";
        sessionStorage.setItem("provider_logged_in", "1");
        localStorage.setItem("provider_logged_in", "1");
    } catch (e) {}

    function pushLock() {
        try {
            window.history.pushState({ providerLocked: true, t: Date.now() }, document.title, dashboardUrl);
        } catch (e) {}
    }

    try {
        window.history.replaceState({ providerLocked: true, t: Date.now() }, document.title, dashboardUrl);
    } catch (e) {}
    pushLock();

    /* =========================================================
       1. BROWSER UI BACK BUTTON / HISTORY NAVIGATION (popstate)
       ========================================================= */
    window.addEventListener("popstate", function () {
        pushLock();
        try {
            window.history.replaceState({ providerLocked: true, t: Date.now() }, document.title, dashboardUrl);
        } catch (e) {}
    });

    /* =========================================================
       2. MOUSE HARDWARE BACK BUTTON (Side button: button === 3)
       ========================================================= */
    function blockMouseBackButton(e) {
        if (e.button === 3 || (e.buttons && (e.buttons & 8))) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            pushLock();
            return false;
        }
    }

    window.addEventListener("mousedown", blockMouseBackButton, true);
    window.addEventListener("mouseup", blockMouseBackButton, true);
    window.addEventListener("pointerdown", blockMouseBackButton, true);
    window.addEventListener("pointerup", blockMouseBackButton, true);

    /* =========================================================
       3. KEYBOARD BACK SHORTCUTS (Alt + Left Arrow, Cmd + [, etc.)
       ========================================================= */
    window.addEventListener("keydown", function (e) {
        if (
            (e.altKey && (e.key === "ArrowLeft" || e.keyCode === 37)) ||
            ((e.metaKey || e.ctrlKey) && (e.key === "[" || e.keyCode === 219 || e.key === "ArrowLeft"))
        ) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            pushLock();
            return false;
        }
    }, true);

    /* =========================================================
       4. RE-ARM ON USER CLICKS & TOUCHES (User Gesture Activation)
       ========================================================= */
    var lastArm = 0;
    function rearm() {
        var now = Date.now();
        if (now - lastArm > 500) {
            lastArm = now;
            pushLock();
        }
    }

    ["click", "keydown", "touchstart", "pointerdown"].forEach(function (eventType) {
        window.addEventListener(eventType, rearm, { capture: true, passive: true });
    });

    /* =========================================================
       5. BFCACHE RESTORE (pageshow)
       ========================================================= */
    window.addEventListener("pageshow", function () {
        pushLock();
    });

})();
