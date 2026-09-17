import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../Core/UILibraries20260917.js', import.meta.url), 'utf8');
function setup(active = true) {
  const context = {
    window: {}, console, HighlightRules: {Hand:'SelectionMetadata', Arsenal:'SelectionMetadata'},
    IsViewerActivePlayer: () => active,
    GetZoneData: () => ({Sort:{}}), IsDragDropEnabled: () => false,
    ShouldUseCollapsedStacks: () => false, ShouldSkipZoneCardRendering: () => false,
    ShouldShowLatestInSingleZone: () => false, ShouldUseVisualSingleZoneStack: () => false,
  };
  vm.createContext(context);
  vm.runInContext(source.slice(source.indexOf('function GetHighlightMetadataForCard'), source.indexOf('function ParseSharedCardData')), context);
  vm.runInContext(source.slice(source.indexOf('function PopulateZone('), source.indexOf('function OnSelectableCardClick(')), context);
  // Keep the real zone renderer and highlight lookup; replace only card art construction.
  context.createCardHTML = (zone, schema, folder, size, record, index, heatmap, colors, forced) => {
    const metadata = forced || context.GetHighlightMetadataForCard(schema, JSON.parse(record[2]));
    return `<card id="${zone}-${index}" highlighted="${!!metadata}"></card>`;
  };
  return context;
}
const legal = 'card 0 {"SelectionMetadata":{"color":"green"}}';
const illegal = 'card 0 {"SelectionMetadata":{"highlight":false}}';

test('playability follows schema rules for all multiplayer seats and legacy aliases', () => {
  const context = setup();
  for (const prefix of ['p1','p2','p3','p4','my','their']) {
    const html = context.PopulateZone(prefix+'Hand', legal+'<|>'+illegal);
    assert.ok(html.includes(`id="${prefix}Hand-0" highlighted="true"`));
    assert.ok(html.includes(`id="${prefix}Hand-1" highlighted="false"`));
  }
});
test('single-card piles show a legal buried card without changing their absolute reference', () => {
  const html = setup().PopulateZone('p4Arsenal', illegal+'<|>'+legal, 90, 'concat', 0, 'Single');
  assert.ok(html.includes('id="p4Arsenal-0" highlighted="true"'));
});
test('inactive viewers receive no action highlights', () => {
  const html = setup(false).PopulateZone('p3Hand', legal);
  assert.ok(html.includes('highlighted="false"'));
});

test('target selection stays scoped to the offered seat and card index', () => {
  const context = setup();
  vm.runInContext(source.slice(source.indexOf('function IsSelectableCard('), source.indexOf('function IsSelectableSubcard(')), context);
  context.window.SelectionMode = {active:true, inlineSpecs:[{zone:'p4Hero',isSpecificCard:true,specificIndex:0}]};
  assert.equal(context.IsSelectableCard('p4Hero', ['hero','0','-'], 0), true);
  assert.equal(context.IsSelectableCard('p3Hero', ['hero','0','-'], 0), false);
  assert.equal(context.IsSelectableCard('p4Hero', ['hero','0','-'], 1), false);
  context.window.SelectionMode.active = false;
  assert.equal(context.IsSelectableCard('p4Hero', ['hero','0','-'], 0), false);
});
