class AppShell {
    constructor() {
        this.state = {
            activeScreen: 'games',
            activeGameId: null,
            activeSetId: null,
            activeTemplateId: null,
            activeCardId: null,
            activeTemplateDetail: null,
            activeCardDetail: null,
            games: [],
            sets: [],
            templates: [],
            templateDetails: {},
            cards: [],
            cardTagFilter: { tagId: '', mode: 'has' },
            assets: [],
            assetUsageHighlight: null,
            currentUser: { loggedIn: false },
            abilityRoots: {},
            abilityRoot: '',
            abilityCard: ''
        };
        this.autosave = new AutoSaveManager(this);
        this.screens = {
            games: new GameScreen(this),
            sets: new SetScreen(this),
            templates: new TemplateScreen(this),
            cards: new CardScreen(this),
            assets: new AssetScreen(this),
            export: new ExportScreen(this)
        };
        this.templateCanvas = new TemplateCanvas(this);
        this.assetPicker = new AssetPicker(this);
    }

    async init() {
        await this.refreshAll();
        this.bindNav();
        this.show('games');
        const params = new URLSearchParams(window.location.search);
        const oauthError = params.get('oauth_error');
        if (oauthError) {
            this.toast(oauthError, 'error');
            params.delete('oauth_error');
            const query = params.toString();
            history.replaceState(null, '', window.location.pathname + (query ? `?${query}` : '') + window.location.hash);
        }
    }

    bindNav() {
        document.querySelectorAll('[data-screen]').forEach(button => {
            button.addEventListener('click', async () => {
                await this.autosave.flushAll();
                this.show(button.dataset.screen);
            });
        });
    }

    async refreshAll() {
        this.state.currentUser = await ApiClient.getCurrentUser().catch(() => ({ loggedIn: false }));
        this.state.games = await ApiClient.listGames().catch(() => []);
        if (!this.state.activeGameId && this.state.games[0]) this.state.activeGameId = this.state.games[0].id;
        await this.refreshGameScoped();
    }

    async refreshGameScoped() {
        await Promise.all([this.refreshSets(), this.refreshTemplates(), this.refreshAssets()]);
        await this.preloadTemplateDetails();
    }

    async refreshSets() {
        this.state.sets = this.state.activeGameId ? await ApiClient.listSets(this.state.activeGameId).catch(() => []) : [];
        if (this.state.activeSetId && !this.state.sets.some(set => set.id === this.state.activeSetId)) this.state.activeSetId = null;
        if (!this.state.activeSetId && this.state.sets[0]) this.state.activeSetId = this.state.sets[0].id;
        await this.refreshCards();
    }

    async refreshTemplates() {
        this.state.templates = this.state.activeGameId ? await ApiClient.listTemplates(this.state.activeGameId).catch(() => []) : [];
        if (this.state.activeTemplateId && !this.state.templates.some(template => template.id === this.state.activeTemplateId)) {
            this.state.activeTemplateId = null;
            this.state.activeTemplateDetail = null;
        }
        if (!this.state.activeTemplateId && this.state.templates[0]) this.state.activeTemplateId = this.state.templates[0].id;
        if (this.state.activeTemplateId) {
            this.state.activeTemplateDetail = await ApiClient.getTemplate(this.state.activeTemplateId).catch(() => null);
        }
    }

    async preloadTemplateDetails() {
        this.state.templateDetails = {};
        await Promise.all(this.state.templates.map(async template => {
            this.state.templateDetails[template.id] = await ApiClient.getTemplate(template.id).catch(() => null);
        }));
    }

    async refreshCards() {
        this.state.cards = this.state.activeSetId ? await ApiClient.listCards(this.state.activeSetId).catch(() => []) : [];
        if (this.state.activeCardId && !this.state.cards.some(card => card.id === this.state.activeCardId)) {
            this.state.activeCardId = null;
            this.state.activeCardDetail = null;
        }
    }

    async refreshAssets() {
        this.state.assets = this.state.activeGameId ? await ApiClient.listAssets(this.state.activeGameId).catch(() => []) : [];
    }

    activeGame() {
        return this.state.games.find(game => game.id === this.state.activeGameId) || null;
    }

    activeSet() {
        return this.state.sets.find(set => set.id === this.state.activeSetId) || null;
    }

    setContent(html) {
        document.getElementById('content').innerHTML = html;
        this.renderContext();
    }

    renderContext() {
        const game = this.activeGame();
        const set = this.activeSet();
        document.getElementById('contextLabel').textContent = game ? `${game.name}${set ? ' / ' + set.name : ''}` : 'No game selected';
        const userLabel = document.getElementById('userLabel');
        if (userLabel) {
            const user = this.state.currentUser;
            userLabel.innerHTML = user.loggedIn
                ? `
                    <span>${PreviewRenderer.escape(user.userName)}${user.teamId ? ' / team ' + PreviewRenderer.escape(user.teamId) : ''}</span>
                    <a href="/TCGEngine/SharedUI/Profile.php">Profile</a>
                    <button type="button" class="link-button" onclick="app.logout()">Log Out</button>
                `
                : `
                    <span>Not logged in</span>
                    <button type="button" class="link-button" onclick="app.openAuthModal('login')">Log In</button>
                    <button type="button" class="link-button" onclick="app.openAuthModal('signup')">Create Account</button>
                `;
        }
        document.querySelectorAll('[data-screen]').forEach(button => {
            button.classList.toggle('active', button.dataset.screen === this.state.activeScreen);
        });
    }

    setSaveState(label) {
        const host = document.getElementById('saveState');
        if (!host) return;
        host.textContent = label || '';
        host.className = 'save-state ' + String(label || '').toLowerCase();
    }

    openAuthModal(mode = 'login') {
        const host = document.getElementById('authModalHost');
        if (!host) return;
        const isSignup = mode === 'signup';
        host.innerHTML = `
            <div class="modal-backdrop" onclick="app.closeAuthModal(event)">
                <section class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="authTitle">
                    <div class="modal-head">
                        <h2 id="authTitle">${isSignup ? 'Create Account' : 'Log In'}</h2>
                        <button type="button" class="icon-button" onclick="app.closeAuthModal()">x</button>
                    </div>
                    <button type="button" class="discord-auth-button" onclick="app.startDiscordAuth('${isSignup ? 'signup' : 'login'}')">
                        <img src="/TCGEngine/Assets/Images/icons/discord.svg" alt="" aria-hidden="true">
                        <span>Continue with Discord</span>
                    </button>
                    <div class="auth-separator"><span>or</span></div>
                    <form id="authForm" class="stack-form" onsubmit="app.submitAuth(event, '${isSignup ? 'signup' : 'login'}')">
                        <label>Username<input name="username" autocomplete="username" required></label>
                        ${isSignup ? '<label>Email<input name="email" type="email" autocomplete="email" required></label>' : ''}
                        <label>Password<input name="password" type="password" autocomplete="${isSignup ? 'new-password' : 'current-password'}" required></label>
                        ${isSignup ? '<label>Repeat Password<input name="passwordRepeat" type="password" autocomplete="new-password" required></label>' : '<label class="inline-filter"><input type="checkbox" name="rememberMe" checked> Remember Me</label>'}
                        <div id="authError" class="error-message" hidden></div>
                        <button type="submit">${isSignup ? 'Create Account' : 'Log In'}</button>
                    </form>
                    <div class="modal-switch">
                        ${isSignup
                            ? '<button type="button" class="link-button" onclick="app.openAuthModal(\'login\')">Log In</button>'
                            : '<button type="button" class="link-button" onclick="app.openAuthModal(\'signup\')">Create Account</button>'}
                    </div>
                </section>
            </div>
        `;
        host.querySelector('[name="username"]')?.focus();
    }

    startDiscordAuth(action = 'login') {
        const redirect = window.location.pathname.startsWith('/TCGEngine/')
            ? window.location.pathname
            : '/TCGEngine/CardEditor/UI/';
        const params = new URLSearchParams({ action, site: 'CardEditor', redirect });
        window.location.assign(`/TCGEngine/AccountFiles/DiscordOAuthStart.php?${params.toString()}`);
    }

    closeAuthModal(event = null) {
        if (event && !event.target.classList.contains('modal-backdrop')) return;
        const host = document.getElementById('authModalHost');
        if (host) host.innerHTML = '';
    }

    async submitAuth(event, mode) {
        event.preventDefault();
        const form = event.target;
        const errorHost = document.getElementById('authError');
        if (errorHost) {
            errorHost.hidden = true;
            errorHost.textContent = '';
        }
        const values = Object.fromEntries(new FormData(form).entries());
        try {
            if (mode === 'signup') {
                await ApiClient.signup({
                    userId: values.username,
                    email: values.email,
                    password: values.password,
                    passwordRepeat: values.passwordRepeat
                });
                await ApiClient.passwordLogin({
                    userID: values.username,
                    password: values.password,
                    rememberMe: true
                });
            } else {
                const result = await ApiClient.passwordLogin({
                    userID: values.username,
                    password: values.password,
                    rememberMe: Boolean(values.rememberMe)
                });
                if (!result.isUserLoggedIn) throw new Error('Login failed; please check your username and password.');
            }
            this.closeAuthModal();
            await this.refreshAll();
            this.show(this.state.activeScreen);
        } catch (error) {
            if (errorHost) {
                errorHost.hidden = false;
                errorHost.textContent = error.message;
            } else {
                this.toast(error.message, 'error');
            }
        }
    }

    async logout() {
        await this.autosave.flushAll();
        await ApiClient.logout();
        this.state.currentUser = { loggedIn: false };
        this.state.activeGameId = null;
        this.state.activeSetId = null;
        this.state.activeTemplateId = null;
        this.state.activeTemplateDetail = null;
        this.state.activeCardId = null;
        this.state.activeCardDetail = null;
        await this.refreshAll();
        this.show('games');
    }

    async selectGame(id) {
        await this.autosave.flushAll();
        this.state.activeGameId = Number(id);
        this.state.activeSetId = null;
        this.state.activeTemplateId = null;
        this.state.activeTemplateDetail = null;
        this.state.activeCardId = null;
        this.state.activeCardDetail = null;
        this.state.cardTagFilter = { tagId: '', mode: 'has' };
        this.state.assetUsageHighlight = null;
        await this.refreshGameScoped();
        this.show(this.state.activeScreen);
    }

    async selectSet(id) {
        await this.autosave.flushAll();
        this.state.activeSetId = Number(id);
        this.state.activeCardId = null;
        this.state.activeCardDetail = null;
        await this.refreshCards();
        this.show(this.state.activeScreen);
    }

    requireGame() {
        if (this.state.activeGameId) return true;
        this.setContent('<div class="empty-state page-empty">Create or select a game first.</div>');
        return false;
    }

    show(screen) {
        this.state.activeScreen = screen;
        if (screen === 'abilities') {
            this.renderAbilities();
            return;
        }
        this.screens[screen].render();
    }

    async openAssetUsage(usage) {
        await this.autosave.flushAll();
        if (usage.type === 'template_layer' || usage.type === 'template_background') {
            this.state.activeScreen = 'templates';
            this.state.activeTemplateId = Number(usage.template_id);
            this.state.activeTemplateDetail = await ApiClient.getTemplate(usage.template_id);
            const layout = this.state.activeTemplateDetail?.layout || [];
            const index = usage.element_id
                ? layout.findIndex(element => Number(element.id) === Number(usage.element_id))
                : -1;
            this.templateCanvas.selectedIndex = index >= 0 ? index : -1;
            this.screens.templates.expandedFieldKeys.clear();
            await this.screens.templates.render();
            return;
        }
        if (usage.type === 'enum_option') {
            this.state.assetUsageHighlight = {
                enumId: Number(usage.enum_id),
                optionId: Number(usage.option_id)
            };
            this.show('games');
            setTimeout(() => {
                document.querySelector('.asset-usage-highlight')?.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }, 0);
            return;
        }
        if (usage.type === 'card_image') {
            this.state.activeScreen = 'cards';
            this.state.activeSetId = Number(usage.set_id);
            this.state.activeCardId = Number(usage.card_id);
            this.state.cardTagFilter = { tagId: '', mode: 'has' };
            await this.refreshCards();
            this.state.activeCardDetail = await ApiClient.getCard(usage.card_id);
            await this.screens.cards.render();
        }
    }

    toast(message, type = 'success') {
        const host = document.getElementById('toast');
        host.textContent = message;
        host.className = `toast show ${type}`;
        clearTimeout(this.toastTimer);
        this.toastTimer = setTimeout(() => { host.className = 'toast'; }, 3000);
    }

    async renderAbilities() {
        this.setContent(`
            <section class="ability-workbench">
                <aside class="pane compact">
                    <div class="pane-head"><h2>Roots</h2><button onclick="app.loadAbilityRoots()">Refresh</button></div>
                    <select id="abilityRootSelect" class="wide-select" onchange="app.selectAbilityRoot(this.value)">
                        <option value="">Select root</option>
                    </select>
                    <label class="inline-filter"><input type="checkbox" id="abilityHideImplemented" onchange="app.renderAbilityCardList()"> Unimplemented only</label>
                    <div id="abilityCardList" class="list tight"><div class="empty-state">Load roots to begin.</div></div>
                </aside>
                <main class="pane ability-editor-pane">
                    <div id="editorArea" class="editor-wrapper"><div class="empty-state page-empty">Select a root and card.</div></div>
                </main>
            </section>
        `);
        await this.loadAbilityRoots();
    }

    async loadAbilityRoots() {
        try {
            const response = await fetch('../API/GetRoots.php');
            const data = await response.json();
            if (!data.success || !data.roots) throw new Error(data.error || 'Failed to load roots');
            this.state.abilityRoots = data.roots;
            const select = document.getElementById('abilityRootSelect');
            if (!select) return;
            select.innerHTML = '<option value="">Select root</option>' + Object.keys(data.roots).sort().map(root => `<option value="${PreviewRenderer.escape(root)}">${PreviewRenderer.escape(root)}</option>`).join('');
            if (this.state.abilityRoot) select.value = this.state.abilityRoot;
            this.renderAbilityCardList();
        } catch (error) {
            this.toast(error.message, 'error');
        }
    }

    selectAbilityRoot(root) {
        this.state.abilityRoot = root;
        this.state.abilityCard = '';
        this.renderAbilityCardList();
    }

    renderAbilityCardList() {
        const host = document.getElementById('abilityCardList');
        if (!host) return;
        const root = this.state.abilityRoot || document.getElementById('abilityRootSelect')?.value || '';
        this.state.abilityRoot = root;
        if (!root) {
            host.innerHTML = '<div class="empty-state">Select a root.</div>';
            return;
        }
        const hide = document.getElementById('abilityHideImplemented')?.checked;
        const cards = this.state.abilityRoots[root] || {};
        let ids = Object.keys(cards).sort();
        if (hide) ids = ids.filter(id => !cards[id].isImplemented);
        host.innerHTML = ids.length ? ids.map(id => {
            const data = cards[id] || {};
            const clickArg = JSON.stringify(id).replace(/"/g, '&quot;');
            return `<button class="list-row ${this.state.abilityCard === id ? 'active' : ''}" onclick="app.selectAbilityCard(${clickArg})"><strong>${PreviewRenderer.escape(id)}${data.isImplemented ? ' OK' : ''}</strong><span>${data.testCount || 0} tests</span></button>`;
        }).join('') : '<div class="empty-state">No cards found.</div>';
    }

    async selectAbilityCard(cardId) {
        this.state.abilityCard = cardId;
        this.renderAbilityCardList();
        try {
            const root = this.state.abilityRoot;
            const abilitiesResponse = await fetch(`../API/LoadAbilities.php?root=${encodeURIComponent(root)}&card=${encodeURIComponent(cardId)}`);
            const abilitiesData = await abilitiesResponse.json();
            if (!abilitiesData.success) throw new Error(abilitiesData.error || 'Failed to load abilities');
            const macrosResponse = await fetch(`../API/GetMacros.php?root=${encodeURIComponent(root)}`);
            const macrosData = await macrosResponse.json();
            if (!macrosData.success) throw new Error(macrosData.error || 'Failed to load macros');
            const assetResponse = await fetch(`../API/GetAssetPath.php?root=${encodeURIComponent(root)}`);
            const assetData = await assetResponse.json();
            const editor = new AbilityEditor(root, cardId, macrosData.macros || [], abilitiesData.abilities || [], assetData.success ? assetData.assetPath : root, macrosData.zones || []);
            editor.render();
        } catch (error) {
            this.toast(error.message, 'error');
        }
    }
}

const app = new AppShell();
window.app = app;
document.addEventListener('DOMContentLoaded', () => app.init());
