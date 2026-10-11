(() => {
    const dialog = document.getElementById('deckPreview');
    const title = document.getElementById('deckPreviewTitle');
    const summary = document.getElementById('deckPreviewSummary');
    const contents = document.getElementById('deckPreviewCards');
    const copyButton = document.getElementById('deckPreviewCopy');
    const copyStatus = document.getElementById('deckPreviewCopyStatus');
    const copyText = document.getElementById('deckPreviewText');
    let deckText = '';
    let request = null;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    };

    document.querySelectorAll('[data-preview-deck]').forEach(button => {
        button.addEventListener('click', async () => {
            const select = document.getElementById(button.dataset.previewDeck);
            request?.abort();
            const controller = new AbortController();
            request = controller;
            title.textContent = select.selectedOptions[0].textContent;
            summary.textContent = 'Loading deck…';
            deckText = '';
            copyButton.disabled = true;
            copyStatus.textContent = '';
            copyText.hidden = true;
            copyText.value = '';
            contents.replaceChildren();
            dialog.showModal();
            try {
                const response = await fetch('deck-preview.php?deck=' + encodeURIComponent(select.value), {
                    cache: 'no-store', signal: controller.signal
                });
                const data = await response.json();
                if (!response.ok || !data.ok) throw Error(data.error || 'Could not load this deck.');
                if (controller.signal.aborted) return;
                title.textContent = data.name;
                deckText = data.deckText;
                copyButton.disabled = !deckText;
                summary.textContent = data.cards.reduce((sum, card) => sum + card.count, 0) +
                    ' cards · ' + data.cards.length + ' unique printings';
                for (const [type, label] of [['Pokemon', 'Pokémon'], ['Trainer', 'Trainer'], ['Energy', 'Energy']]) {
                    const cards = data.cards.filter(card => card.type === type);
                    if (!cards.length) continue;
                    const section = element('section', undefined, 'deck-preview-section');
                    section.append(element('h3', label + ' · ' + cards.reduce((sum, card) => sum + card.count, 0)));
                    const grid = element('div', undefined, 'deck-preview-grid');
                    for (const card of cards) {
                        const figure = element('figure', undefined, 'deck-preview-card');
                        const image = element('img');
                        image.src = 'WebpImages/' + encodeURIComponent(card.id) + '.webp';
                        image.alt = card.name;
                        image.loading = 'lazy';
                        image.addEventListener('error', () => image.replaceWith(element('div', card.name, 'card-fallback')), {once: true});
                        figure.append(image, element('span', '×' + card.count, 'deck-preview-count'),
                            element('figcaption', card.name), element('small', card.id));
                        grid.append(figure);
                    }
                    section.append(grid);
                    contents.append(section);
                }
            } catch (error) {
                if (error.name !== 'AbortError') summary.textContent = error.message;
            }
        });
    });
    copyButton.addEventListener('click', async () => {
        if (!deckText) return;
        const textToCopy = deckText;
        copyButton.disabled = true;
        copyStatus.textContent = '';
        try {
            await navigator.clipboard.writeText(textToCopy);
            if (deckText !== textToCopy || !dialog.open) return;
            copyStatus.textContent = 'Copied! Paste into Limitless’s deck import.';
            copyText.hidden = true;
        } catch {
            if (deckText !== textToCopy || !dialog.open) return;
            copyStatus.textContent = 'Clipboard unavailable. Copy the selected deck text below.';
            copyText.value = textToCopy;
            copyText.hidden = false;
            copyText.focus();
            copyText.select();
        } finally {
            if (deckText === textToCopy) copyButton.disabled = false;
        }
    });
    document.getElementById('deckPreviewClose').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => request?.abort());
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right ||
            event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
    });
})();
