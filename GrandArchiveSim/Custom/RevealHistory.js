(function () {
    function install() {
        const panel = document.getElementById('ga-reveals');
        if (!panel || window.gaRevealHistoryInstalled) return;
        window.gaRevealHistoryInstalled = true;
        const params = new URLSearchParams(location.search);
        const key = 'ga-reveals-v1:' + JSON.stringify([location.pathname, params.get('gameName'), params.get('playerID') || 'spectator']);
        const validID = id => typeof id === 'string' && /^[A-Za-z0-9_-]+$/.test(id);
        let entries = [];
        try {
            const saved = JSON.parse(localStorage.getItem(key) || '[]');
            if (Array.isArray(saved)) entries = saved.filter(e => e && Array.isArray(e.cards) && e.cards.every(validID) && typeof e.time === 'string');
        } catch (_) { /* History remains usable when browser storage is unavailable. */ }
        const originalSingle = window.showCardReveal;
        const originalMultiple = window.showCardRevealMultiple;
        function render() {
            document.getElementById('ga-reveals-count').textContent = entries.length ? '(' + entries.length + ')' : '';
            const list = document.getElementById('ga-reveals-list');
            list.replaceChildren();
            if (!entries.length) { list.textContent = 'No reveals recorded yet.'; return; }
            entries.slice().reverse().forEach(entry => {
                const group = document.createElement('section');
                const title = document.createElement('h4');
                title.textContent = 'Revealed at ' + entry.time;
                group.append(title);
                const cards = document.createElement('div');
                cards.className = 'ga-reveal-cards';
                entry.cards.forEach(id => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    const name = typeof CardName === 'function' ? CardName(id) : (typeof nameData !== 'undefined' ? nameData[id] : null);
                    const image = document.createElement('img');
                    const art = typeof resolveCardImageID === 'function' ? resolveCardImageID(id) : id;
                    image.src = './GrandArchiveSim/WebpImages/' + art + '.webp';
                    image.alt = name || 'Revealed card';
                    image.loading = 'lazy';
                    button.append(image);
                    const label = document.createElement('span'); label.textContent = name || id; button.append(label);
                    button.addEventListener('click', () => { if (typeof originalSingle === 'function') originalSingle(id); });
                    cards.append(button);
                });
                group.append(cards); list.append(group);
            });
        }
        function record(cards) {
            cards = cards.filter(validID);
            if (!cards.length) return;
            entries.push({cards: cards.slice(), time: new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'})});
            try { localStorage.setItem(key, JSON.stringify(entries)); }
            catch (_) { document.getElementById('ga-reveals-note').textContent = 'Browser storage unavailable. This history lasts until you leave or refresh the page.'; }
            if (panel.open) render();
            else document.getElementById('ga-reveals-count').textContent = '(' + entries.length + ')';
        }
        if (typeof originalSingle === 'function') window.showCardReveal = function(id) { record([id]); return originalSingle.apply(this, arguments); };
        if (typeof originalMultiple === 'function') window.showCardRevealMultiple = function(ids) { record(ids); return originalMultiple.apply(this, arguments); };
        panel.addEventListener('toggle', () => { if (panel.open) render(); });
        panel.addEventListener('keydown', e => { if (e.key === 'Escape') { panel.open = false; panel.querySelector('summary').focus(); } });
        render();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install, {once:true});
    else install();
})();
