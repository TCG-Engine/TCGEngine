class AutoSaveManager {
    constructor(app) {
        this.app = app;
        this.timers = new Map();
        this.running = new Map();
    }

    bindForm(form, key, collect, save, options = {}) {
        if (!form) return;
        const delay = options.delay ?? 700;
        form.addEventListener('submit', event => {
            event.preventDefault();
            this.saveNow(key, collect, save, options);
        });
        form.addEventListener('keydown', event => {
            const tag = event.target.tagName.toLowerCase();
            if (event.key === 'Enter' && tag !== 'textarea') {
                event.preventDefault();
                this.saveNow(key, collect, save, options);
            }
        });
        form.addEventListener('input', event => {
            const tag = event.target.tagName.toLowerCase();
            if (tag === 'select' || event.target.type === 'checkbox' || event.target.type === 'file') return;
            this.schedule(key, collect, save, delay, options);
        });
        form.addEventListener('change', () => this.saveNow(key, collect, save, options));
        form.addEventListener('focusout', () => this.schedule(key, collect, save, 150, options));
    }

    schedule(key, collect, save, delay = 700, options = {}) {
        if (this.timers.has(key)) clearTimeout(this.timers.get(key));
        this.timers.set(key, setTimeout(() => {
            this.timers.delete(key);
            this.saveNow(key, collect, save, options);
        }, delay));
    }

    async saveNow(key, collect, save, options = {}) {
        if (this.timers.has(key)) {
            clearTimeout(this.timers.get(key));
            this.timers.delete(key);
        }
        if (this.running.has(key)) return this.running.get(key);
        const payload = collect();
        if (payload === null || payload === false) return null;
        const task = (async () => {
            try {
                this.app.setSaveState('Saving');
                const result = await save(payload);
                this.app.setSaveState(options.savedText || 'Saved');
                return result;
            } catch (error) {
                this.app.setSaveState('Error');
                this.app.toast(error.message, 'error');
                throw error;
            } finally {
                this.running.delete(key);
            }
        })();
        this.running.set(key, task);
        return task;
    }

    async flushAll() {
        for (const [key, timer] of this.timers.entries()) {
            clearTimeout(timer);
            this.timers.delete(key);
        }
        await Promise.allSettled([...this.running.values()]);
    }
}
