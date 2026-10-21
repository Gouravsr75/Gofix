// Hinglish: Har 10 second mein new request check karke provider ko page par alert dikhate hain.
(function () {
    const inbox = document.querySelector('[data-request-inbox]');
    const countBox = document.getElementById('newRequestCount');
    let initialized = Boolean(inbox || countBox);

    let currentCount = Number(
        inbox ? inbox.dataset.requestCount : (countBox ? countBox.dataset.requestCount : 0)
    ) || 0;
    let currentLatestId = Number(
        inbox ? inbox.dataset.requestLatest : (countBox ? countBox.dataset.requestLatest : 0)
    ) || 0;

    function showNewRequestToast() {
        if (
            document.hidden &&
            'Notification' in window &&
            Notification.permission === 'granted'
        ) {
            const browserNotice = new Notification('Gofix: New service request', {
                body: 'A new request is available in your service area.'
            });
            browserNotice.onclick = function () { window.focus(); };
        }

        let toast = document.getElementById('newRequestToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'newRequestToast';
            toast.className = 'provider-request-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }
        toast.textContent = 'New service request in your area';
        toast.classList.add('show');
        window.setTimeout(function () { toast.classList.remove('show'); }, 5000);
    }

    function updateNavigationBadges(count) {
        document.querySelectorAll('a[href$="new_requests.php"]').forEach(function (link) {
            let badge = link.querySelector('.provider-request-count');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'provider-request-count';
                    link.appendChild(badge);
                }
                badge.textContent = String(count);
            } else if (badge) {
                badge.remove();
            }
        });
    }

    async function checkForRequests() {
        try {
            const response = await fetch('request_count.php?_=' + Date.now(), {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) return;

            const result = await response.json();
            const nextCount = Number(result.count) || 0;
            const nextLatestId = Number(result.latest_id) || 0;

            if (!initialized) {
                currentCount = nextCount;
                currentLatestId = nextLatestId;
                updateNavigationBadges(nextCount);
                initialized = true;
                return;
            }

            const hasChanged = nextCount !== currentCount || nextLatestId !== currentLatestId;
            const hasNewRequest = nextLatestId > currentLatestId;

            if (hasNewRequest) showNewRequestToast();
            updateNavigationBadges(nextCount);

            if (inbox && hasChanged) {
                window.location.reload();
                return;
            }

            if (countBox) {
                countBox.textContent = String(nextCount);
                countBox.dataset.requestCount = String(nextCount);
                countBox.dataset.requestLatest = String(nextLatestId);
            }

            currentCount = nextCount;
            currentLatestId = nextLatestId;
        } catch (error) {
            // Network ya session issue ho to agle polling cycle mein dobara check hoga.
        }
    }

    updateNavigationBadges(currentCount);
    checkForRequests();
    window.setInterval(checkForRequests, 10000);
})();
