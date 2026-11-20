(function () {
    function install() {
        if (document.getElementById('ga-settings')) return;
        var mobileMenu = document.getElementById('gaMobileAdminMenuPanel');
        function menu(id, label, parent) {
            var node = document.createElement('details'); node.id = id; node.className = 'ga-clean-menu';
            var summary = document.createElement('summary'); summary.textContent = label;
            var panel = document.createElement('div'); panel.className = 'ga-clean-panel';
            node.append(summary, panel); parent.append(node); return panel;
        }
        var settings = menu('ga-settings', 'Settings', mobileMenu || document.body);
        if (!mobileMenu) settings.parentElement.classList.add('ga-floating');
        ['ga-field-display', 'gaShortcutDock', 'gaMobileShortcutPanel', 'cardMotionToggleBtn'].forEach(function (id) {
            var node = document.getElementById(id); if (node) settings.append(node);
        });
        if (window.GABoardTheme) {
            var label = document.createElement('label'); label.className = 'ga-theme-label'; label.textContent = 'Board theme';
            var select = document.createElement('select'); select.setAttribute('aria-label', 'Board theme');
            [['paradise','Paradise'],['dawn','Dawn of Ashes'],['classic','Classic blue']].forEach(function (entry) {
                var option = document.createElement('option'); option.value = entry[0]; option.textContent = entry[1]; select.append(option);
            });
            select.value = window.GABoardTheme.get();
            select.addEventListener('change', function () { window.GABoardTheme.set(select.value); });
            label.append(select); settings.append(label);
        }
        var history = menu('ga-history', 'History', document.body);
        ['ga-reveals', 'chatWidget'].forEach(function (id) {
            var node = document.getElementById(id); if (node) history.append(node);
        });
        // Toasts must remain visible even while History is closed.
        var toast = document.getElementById('chatToastHost');
        if (toast) document.body.append(toast);
        if (!mobileMenu) {
            var area = document.createElement('div'); area.id = 'ga-action-area'; area.setAttribute('aria-label', 'Current action');
            document.body.append(area);
            var pass = document.getElementById('myHealthSlot'); if (pass) area.append(pass);
            window.SelectionMessageSettings = {embedTargetId:'ga-action-area', shouldEmbed:function () { return true; }};
        }
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            ['ga-settings', 'ga-history'].forEach(function (id) { document.getElementById(id).open = false; });
        });
    }
    // Run after the existing layout's DOM-ready setup has registered its controls.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { setTimeout(install, 0); });
    else setTimeout(install, 0);
})();
