(function () {
    var scrollPositions = {}, preference = 'auto', expanded = false;
    try { preference = localStorage.getItem('ga-field-layout') || 'auto'; } catch (_) {}
    if (!['auto', '2', '3'].includes(preference)) preference = 'auto';
    // Decide from viewport space, never from the number of cards in play.
    function rowCount() { return preference === 'auto' ? (window.innerHeight >= 1300 ? 3 : 2) : Number(preference); }
    function compact() { return !expanded && (window.innerHeight < 740 || !!document.getElementById('gaMobileRoot')); }
    function reveal(card) {
        if (!card) return;
        card.scrollIntoView({block: 'nearest', inline: 'nearest', behavior: 'instant'});
    }
    function groupField(id, force) {
        var field = document.getElementById(id);
        if (!field) return;
        var pattern = new RegExp('^' + id + '-\\d+$');
        var fresh = Array.from(field.children).some(function (node) { return pattern.test(node.id); });
        if (!fresh && !force) return;
        // Move the original nodes, retaining all engine handlers and attachments.
        var cards = Array.from(field.querySelectorAll('[id]')).filter(function (node) { return pattern.test(node.id); });
        if (!cards.length) return;
        var rows = rowCount(), buckets = {allies: [], champion: [], weapons: [], support: []};
        cards.forEach(function (card) {
            var data = {};
            try { data = GetZoneCard(card.id) || {}; } catch (_) {}
            var types = String((window.typeData || {})[data.CardID] || '').split(',').map(function (s) { return s.trim(); });
            var role = types.includes('CHAMPION') ? 'champion' : types.includes('WEAPON') ? 'weapons' : types.includes('ALLY') ? 'allies' : 'support';
            card.classList.toggle('ga-pinned-champion', role === 'champion' && rows === 2);
            buckets[role].push(card);
        });
        cards.forEach(function (card) { card.remove(); });
        field.querySelectorAll('.ga-field-row').forEach(function (row) { row.remove(); });
        field.classList.add('ga-field-rows');
        field.dataset.rows = rows;
        field.classList.toggle('ga-compact-opponent', id === 'theirField' && compact());
        var groups = rows === 2 ? [['champion', 'allies'], ['weapons', 'support']] : [['allies'], ['champion', 'weapons'], ['support']];
        if (id === 'theirField') groups.reverse();
        var labels = {allies: 'Allies', champion: 'Champion', weapons: 'Weapons', support: 'Support'};
        groups.forEach(function (roles) {
            var active = roles.filter(function (role) { return buckets[role].length; });
            if (!active.length) return;
            var row = document.createElement('section'); row.className = 'ga-field-row';
            var title = document.createElement('h3');
            var heading = roles.join('-') === 'champion-allies' ? 'Units' : roles.join('-') === 'weapons-support' ? 'Support' : roles.map(function (role) { return labels[role]; }).join(' & ');
            title.textContent = heading + ' · ' + active.reduce(function (count, role) { return count + buckets[role].length; }, 0);
            var lane = document.createElement('div'); lane.className = 'ga-field-lane';
            lane.tabIndex = 0; lane.setAttribute('role', 'region');
            lane.setAttribute('aria-label', (id === 'myField' ? 'My ' : 'Opponent ') + title.textContent + '. Scroll for more cards.');
            active.forEach(function (role, index) {
                buckets[role].forEach(function (card, i) {
                    card.classList.toggle('ga-group-start', index > 0 && i === 0);
                    lane.appendChild(card);
                });
            });
            row.append(title, lane); field.appendChild(row);
            var key = id + ':' + roles.join('-');
            lane.scrollLeft = scrollPositions[key] || 0;
            lane.addEventListener('scroll', function () { scrollPositions[key] = lane.scrollLeft; }, {passive: true});
        });
        // Reveal the active combat card, or a sole legal target; don't jump between candidates.
        var activeCard = field.querySelector('.combat-attacker-card, .combat-targeted-card');
        var targets = field.querySelectorAll('.selectable-card');
        reveal(activeCard || (targets.length === 1 ? targets[0] : null));
    }
    function refresh() { ['myField', 'theirField'].forEach(function (id) { groupField(id, true); }); }
    function install() {
        var controls = document.createElement('details'); controls.id = 'ga-field-display';
        controls.innerHTML = '<summary>Field display</summary><label>Rows <select aria-label="Field rows"><option value="auto">Auto</option><option value="2">Two rows</option><option value="3">Three rows</option></select></label><label><input type="checkbox"> Expand opponent cards</label>';
        var menu = document.getElementById('gaMobileAdminMenuPanel');
        (menu || document.body).appendChild(controls);
        controls.classList.toggle('ga-field-display-floating', !menu);
        var select = controls.querySelector('select'); select.value = preference;
        select.addEventListener('change', function () {
            preference = select.value;
            try { localStorage.setItem('ga-field-layout', preference); } catch (_) {}
            refresh();
        });
        controls.querySelector('input').addEventListener('change', function (event) { expanded = event.target.checked; refresh(); });
        ['myField', 'theirField'].forEach(function (id) {
            var slot = document.getElementById(id + 'Slot');
            if (!slot) return;
            var pending = false;
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    if (m.type === 'attributes' && m.target.matches('.combat-attacker-card, .combat-targeted-card')) reveal(m.target);
                });
                if (pending) return;
                pending = true;
                requestAnimationFrame(function () { pending = false; groupField(id); });
            }).observe(slot, {childList: true, subtree: true, attributes: true, attributeFilter: ['class']});
            slot.addEventListener('focusin', function (event) { reveal(event.target); });
            groupField(id);
        });
        var resizeFrame;
        window.addEventListener('resize', function () {
            cancelAnimationFrame(resizeFrame);
            resizeFrame = requestAnimationFrame(refresh);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install);
    else install();
})();
