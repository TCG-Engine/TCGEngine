import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../FaBSim/Custom/GameLayout.php', import.meta.url), 'utf8');
function setup() {
  const elements = Object.fromEntries([
    'fabCombatWindow', 'fabLayersWindow', 'fabCombatToggle', 'fabLayersToggle',
    'fabCombatCount', 'fabLayersCount', 'fabLayers',
  ].map(id => [id, {
    hidden: true, dataset: {}, attributes: {}, cards: [],
    setAttribute(key, value) { this.attributes[key] = value; },
    querySelectorAll() { return this.cards; },
  }]));
  const context = vm.createContext({
    window: {}, document: { getElementById: id => elements[id] },
    FaBRefreshCombatProgress() {},
  });
  vm.runInContext(source.slice(source.indexOf('function FaBToggleWindow('), source.indexOf('function FaBReadCombatState(')), context);
  vm.runInContext(source.slice(source.indexOf('window.RenderFaBLayers ='), source.indexOf("document.addEventListener('DOMContentLoaded'")), context);
  return { context, elements, render: context.window.RenderFaBLayers };
}

test('playing and resolving layers preserves an open populated combat chain', () => {
  const { context, elements, render } = setup();
  const combat = elements.fabCombatWindow;
  combat.cards = [{ classList: { add() {}, remove() {} } }];
  context.FaBRefreshSharedWindows();
  assert.equal(combat.hidden, false);
  for (const count of [1, 2, 1, 0]) {
    render('layer content', count);
    context.FaBRefreshSharedWindows();
    assert.equal(combat.hidden, false);
    assert.equal(elements.fabLayersWindow.hidden, true);
    assert.equal(elements.fabCombatToggle.attributes['aria-expanded'], 'true');
    assert.equal(elements.fabLayersCount.textContent, String(count));
  }
  context.FaBToggleWindow('fabLayersWindow');
  assert.equal(elements.fabLayersWindow.hidden, false);
  assert.equal(combat.hidden, true);
});

test('a new stack opens automatically but refreshes respect manual dismissal', () => {
  const { context, elements, render } = setup();
  render('card', 1);
  assert.equal(elements.fabLayersWindow.hidden, false);
  context.FaBToggleWindow('fabLayersWindow', false);
  render('card and trigger', 2);
  assert.equal(elements.fabLayersWindow.hidden, true);
  render('', 0);
  render('next card', 1);
  assert.equal(elements.fabLayersWindow.hidden, false);
  render('', 0);
  assert.equal(elements.fabLayersWindow.hidden, true);
});
