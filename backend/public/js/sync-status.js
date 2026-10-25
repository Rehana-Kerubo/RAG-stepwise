// Offline strip and "N steps waiting to sync" chip above the tab bar.
// It only READS the queue that the wizard (chat.blade.php) writes to IndexedDB.
// Syncing itself still happens inside the wizard, so the chip links there.
(function () {

    const strip = document.getElementById('sync-strip');

    if (!strip) return;

    const offlineText = document.getElementById('offline-text');
    const chip = document.getElementById('sync-chip');

    // Same database name, version and store as the wizard uses
    function readQueue() {
        return new Promise((resolve) => {

            if (!window.indexedDB) return resolve([]);

            const request = indexedDB.open('stepwise-offline', 1);

            request.onupgradeneeded = () => {
                request.result.createObjectStore('queue', { keyPath: 'key' });
            };
            request.onerror = () => resolve([]);
            request.onsuccess = () => {
                const db = request.result;
                const all = db.transaction('queue', 'readonly').objectStore('queue').getAll();

                all.onsuccess = () => { db.close(); resolve(all.result || []); };
                all.onerror = () => { db.close(); resolve([]); };
            };

        });
    }

    async function update() {

        const queue = await readQueue();
        const offline = !navigator.onLine;

        offlineText.classList.toggle('hidden', !offline);

        if (queue.length) {
            chip.textContent = queue.length === 1
                ? '1 step waiting to sync'
                : `${queue.length} steps waiting to sync`;

            // Opening that onboarding sends its queued steps
            chip.href = '/onboarding/' + queue[0].sessionId;
            chip.classList.remove('hidden');
        } else {
            chip.classList.add('hidden');
        }

        strip.classList.toggle('hidden', !offline && !queue.length);

    }

    window.addEventListener('online', update);
    window.addEventListener('offline', update);

    update();

})();
