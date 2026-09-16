import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const layout = fs.readFileSync(new URL('../../FaBSim/Custom/GameLayout.php', import.meta.url), 'utf8');
const ui = fs.readFileSync(new URL('../../Core/UILibraries20260910.js', import.meta.url), 'utf8');
const multi = fs.readFileSync(new URL('../../Core/MZMultiChooseUI.js', import.meta.url), 'utf8');
function setup(viewer, seats = '12') {
  const context = vm.createContext({window: {SeatOrderData: seats}, document: {getElementById: () => ({value: viewer})}});
  vm.runInContext(layout.slice(layout.indexOf('window.swuTwNormalizeSelection ='), layout.indexOf('function FaBToggleWindow(')), context);
  vm.runInContext(ui.slice(ui.indexOf('function IsSelectableCard('), ui.indexOf('function IsSelectableSubcard(')), context);
  vm.runInContext(multi.slice(multi.indexOf('function parseCardJson('), multi.indexOf('function instructionText(')), context);
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

test('Cindra popup finds both Kunai in duel aliases and preserves their absolute submitted refs', () => {
  for (const viewer of ['1', '2']) {
    const context = setup(viewer);
    context.window.myGraveyardData = 'kunai_of_retribution 0 -<|>kunai_of_retribution 0 -';
    const specs = [0, 1].map(index => ({zone: 'p' + viewer + 'Graveyard', specificIndex: index, isSpecificCard: true}));
    const candidates = context.expandCandidates(specs);
    assert.deepEqual(Array.from(candidates, c => c.cardNumber), ['kunai_of_retribution', 'kunai_of_retribution']);
    assert.deepEqual(Array.from(candidates, c => c.submittedValue), specs.map(s => s.zone + '-' + s.specificIndex));
    assert.equal(context.expandCandidates([{zone: 'p' + viewer + 'Graveyard', specificIndex: 4, isSpecificCard: true}]).length, 0);
  }
});

test('multi-card popups retain relative and UPF zone lookups', () => {
  const context = setup('3', '1234');
  for (const zone of ['myGraveyard', 'p3Graveyard', 'p4Graveyard']) {
    context.window[zone + 'Data'] = 'kunai_of_retribution 0 -';
    assert.equal(context.expandCandidates([{zone, specificIndex: 0, isSpecificCard: true}])[0].submittedValue, zone + '-0');
    context.window[zone + 'Data'] = '';
    assert.equal(context.expandCandidates([{zone, specificIndex: 0, isSpecificCard: true}]).length, 0);
  }
});

test('UPF retains absolute card references', () => {
  const context = setup('3', '1234');
  const specs = [{zone: 'p3Hand', specificIndex: 0}];
  assert.equal(context.window.swuTwNormalizeSelection(specs).inlineNormalized, specs);
  assert.equal(context.window.swuTwRemapCardId('p3Hand-0'), 'p3Hand-0');
});
