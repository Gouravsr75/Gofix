(function () {
    "use strict";

    /*
    |--------------------------------------------------------------------------
    | HISTORY LOCK — Customer Dashboard (Main Page)
    |--------------------------------------------------------------------------
    | User ek baar login kar le to browser back button, mouse back button ya
    | keyboard shortcuts se dashboard.php se bahar nahi ja sakta jab tak wo
    | logout na kar le.
    |--------------------------------------------------------------------------
    */

    var dashboardUrl = window.location.href.split("#")[0];

    // Set auth indicators in cookie, sessionStorage and localStorage
    try {
        document.cookie = "user_logged_in=1; path=/; max-age=2592000; SameSite=Lax";
        sessionStorage.setItem("user_logged_in", "1");
        localStorage.setItem("user_logged_in", "1");
    } catch (e) {}

    function pushLock() {
        try {
            window.history.pushState({ gofixLocked: true, t: Date.now() }, document.title, dashboardUrl);
        } catch (e) {}
    }

    // Initialize lock
    try {
        window.history.replaceState({ gofixLocked: true, t: Date.now() }, document.title, dashboardUrl);
    } catch (e) {}
    pushLock();

    /* =========================================================
       1. BROWSER UI BACK BUTTON / HISTORY NAVIGATION (popstate)
       ========================================================= */
    window.addEventListener("popstate", function (event) {
        // Immediately re-push and replace so back navigation is trapped
        pushLock();
        try {
            window.history.replaceState({ gofixLocked: true, t: Date.now() }, document.title, dashboardUrl);
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
