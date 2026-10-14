'use strict';
(() => {
  const {cards, colors} = JSON.parse(document.getElementById('galleryData').textContent);
  const grid = document.getElementById('galleryCards'), filters = document.getElementById('galleryColors');
  const search = document.getElementById('cardSearch');
  let selected = 'All';
  for (const color of ['All', ...Object.keys(colors)]) {
    const button = document.createElement('button');
    button.type = 'button'; button.textContent = color;
    button.className = color === 'All' ? 'hand-tab' : `hand-tab ${color.toLowerCase()}`;
    button.dataset.color = color;
    button.addEventListener('click', () => { selected = color; render(); });
    filters.append(button);
  }
  function render() {
    const query = search.value.trim().toLocaleLowerCase();
    const visible = cards.filter(c => (selected === 'All' || c.color === selected) &&
      [c.name, c.color, colors[c.color], c.ability, c.bonus, c.reaction, ...c.switches].join(' ').toLocaleLowerCase().includes(query));
    grid.replaceChildren(...visible.map(c => {
      const article = document.createElement('article');
      article.className = `card ${c.color.toLowerCase()}`;
      article.dataset.cardId = c.id;
      article.setAttribute('aria-label', c.name);
      article.innerHTML = DFGCardRenderer.details(c, colors);
      return article;
    }));
    for (const button of filters.children) button.setAttribute('aria-pressed', String(button.dataset.color === selected));
    document.getElementById('galleryCount').textContent = `${visible.length} of ${cards.length} cards${selected === 'All' ? '' : ` · ${selected} / ${colors[selected]}`}`;
    document.getElementById('galleryEmpty').hidden = visible.length !== 0;
  }
  search.addEventListener('input', render);
  render();
})();
