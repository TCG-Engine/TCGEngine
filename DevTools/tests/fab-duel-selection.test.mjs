import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const layout = fs.readFileSync(new URL('../../FaBSim/Custom/GameLayout.php', import.meta.url), 'utf8');
const ui = fs.readFileSync(new URL('../../Core/UILibraries20260910.js', import.meta.url), 'utf8');
function setup(viewer, seats = '12') {
  const context = vm.createContext({window: {SeatOrderData: seats}, document: {getElementById: () => ({value: viewer})}});
  vm.runInContext(layout.slice(layout.indexOf('window.swuTwNormalizeSelection ='), layout.indexOf('function FaBToggleWindow(')), context);
  vm.runInContext(ui.slice(ui.indexOf('function IsSelectableCard('), ui.indexOf('function IsSelectableSubcard(')), context);
  return context;
}

test('Art of War hand choices highlight and submit the offered seat for either duel player', () => {
  for (const viewer of ['1', '2']) {
    const context = setup(viewer);
    const spec = {zone: 'p' + viewer + 'Hand', specificIndex: 2, isSpecificCard: true, originalSpec: 'p' + viewer + 'Hand-2'};
    const normalized = context.window.swuTwNormalizeSelection([spec]).inlineNormalized;
    context.window.SelectionMode = {active: true, inlineSpecs: normalized};
    assert.equal(normalized[0].zone, 'myHand');
    assert.equal(normalized[0].originalSpec, spec.originalSpec);
    assert.equal(context.IsSelectableCard('myHand', [], 2), true);
    assert.equal(context.IsSelectableCard('myHand', [], 1), false);
    assert.equal(context.IsSelectableCard('theirHand', [], 2), false);
    assert.equal(context.window.swuTwRemapCardId('myHand-2'), spec.originalSpec);
    const other = viewer === '1' ? '2' : '1';
    assert.equal(context.window.swuTwRemapCardId('theirHero-0'), 'p' + other + 'Hero-0');
    assert.equal(context.window.swuTwRemapCardId('PASS'), 'PASS');
  }
});

test('UPF retains absolute card references', () => {
  const context = setup('3', '1234');
  const specs = [{zone: 'p3Hand', specificIndex: 0}];
  assert.equal(context.window.swuTwNormalizeSelection(specs).inlineNormalized, specs);
  assert.equal(context.window.swuTwRemapCardId('p3Hand-0'), 'p3Hand-0');
});
