(() => {
  const dialog = document.getElementById('fab-playmats');
  const tiles = [...dialog.querySelectorAll('[data-fab-playmat]')];
  const catalog = new Map(tiles.map(tile => [tile.dataset.fabPlaymat, tile.dataset.image]));
  const sideButtons = [...dialog.querySelectorAll('[data-fab-playmat-side]')];
  const settings = { rootName: 'FaBSim', type: 'json' };
  const defaults = { local: 'The-Pits', opponent: 'Demonastery' };
  const saved = window.TCGSettings?.get('Playmats', { ...settings, defaultValue: defaults });
  const selection = {};
  let side = 'local';
  let opener;

  function apply(field) {
    const image = catalog.get(selection[field]);
    const root = document.documentElement;
    const property = `--fab-${field}-playmat`;
    if (image) root.style.setProperty(property, `url(${JSON.stringify(image)})`);
    else root.style.removeProperty(property);
    root.toggleAttribute(`data-fab-${field}-playmat`, Boolean(image));
  }

  function updatePicker() {
    for (const button of sideButtons) button.setAttribute('aria-pressed', String(button.dataset.fabPlaymatSide === side));
    for (const tile of tiles) tile.setAttribute('aria-pressed', String(tile.dataset.fabPlaymat === selection[side]));
  }

  for (const field of Object.keys(defaults)) {
    selection[field] = catalog.has(saved?.[field]) ? saved[field] : defaults[field];
    apply(field);
  }

  for (const button of sideButtons) {
    button.addEventListener('click', () => {
      side = button.dataset.fabPlaymatSide;
      updatePicker();
    });
  }

  for (const tile of tiles) {
    tile.addEventListener('click', () => {
      selection[side] = tile.dataset.fabPlaymat;
      apply(side);
      updatePicker();
      window.TCGSettings?.set('Playmats', selection, settings);
    });
  }

  for (const trigger of document.querySelectorAll('[data-fab-playmat-open]')) {
    trigger.addEventListener('click', () => {
      opener = trigger;
      updatePicker();
      dialog.showModal();
    });
  }
  dialog.addEventListener('close', () => opener?.focus());
})();
