// Twin Suns HOME-VIEW preview tiles: subcard (upgrade / token) targeting.
//
// WHY THIS EXISTS — bug #1068 (game 1105765, 4-seat Twin Suns). LAW_078 Sabine Wren's When Played
// offers "defeat an upgrade" as SUBCARD mzIDs ("p3GroundArena-0.u0"). The full board renders those as
// a clickable sliver on the host unit, but the home view draws far seats ONLY as preview tiles, whose
// upgrades are a read-only count badge. swuHighlightPreviewTargets ignored spec.subIndex, so the HOST
// tile lit up as if the unit were the target, and swuPreviewTargetClick submitted the bare host mzID —
// which the server rejects ("Invalid selection.", Core/EngineActionRunner.php). Net: every
// subcard-targeting effect was unplayable from the view the game DEFAULTS to.
//
// The contract pinned here:
//   • a tile glows only when one of ITS OWN subcards is offered (not merely "has upgrades")
//   • clicking such a tile OPENS THE PANEL instead of submitting the host
//   • the panel's pick submits the full "<host>.u<sub>" mzID
//   • the tile's attached-card badges are SPLIT: upgrades (neutral) vs captives (goldenrod), matching
//     the base tile's existing FORTIFIED / ARRESTED chips
//   • base-hosted (Fortify) upgrades are addressable the same way
//
// Node `vm` slices, no browser — same pattern as fab-duel-selection.test.mjs.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const layout = fs.readFileSync(new URL('../../SWUSim/Custom/GameLayoutShared.php', import.meta.url), 'utf8');

function slice(from, to) {
  const a = layout.indexOf(from), b = layout.indexOf(to);
  assert.ok(a !== -1, `slice start not found: ${from}`);
  assert.ok(b > a, `slice end not found after start: ${to}`);
  return layout.slice(a, b);
}

// A fake preview tile. Carries its own badge elements so a click can find them the way the real
// tile does (cardEl.querySelector).
function tile(mz, opts = {}) {
  const classes = new Set();
  const badges = {};
  if (opts.upgradeBadge) badges['.swu-mb-upgcount'] = { __kind: 'upg', getAttribute: () => opts.upgradeBadge };
  if (opts.captiveBadge) badges['.swu-mb-capcount'] = { __kind: 'cap', getAttribute: () => opts.captiveBadge };
  return {
    __mz: mz,
    getAttribute: (k) => (k === 'data-mz' ? mz : null),
    querySelector: (sel) => badges[sel] || null,
    classList: {
      toggle(c, on) { on ? classes.add(c) : classes.delete(c); },
      add: (c) => classes.add(c),
      remove: (c) => classes.delete(c),
      contains: (c) => classes.has(c),
    },
    getClientRects: () => [{}],
  };
}

function makeCtx(cards) {
  const panelOpens = [];
  const submits = [];
  const ctx = vm.createContext({
    window: { swuView: { viewSeat: 1, oppSeat: 2, mode: 'home' }, typeData: {}, traitData: {} },
    console,
    document: {
      querySelectorAll: (sel) => (String(sel).includes('.swu-mb-card') ? cards : []),
      getElementById: () => null,
      querySelector: () => null,
      addEventListener() {}, removeEventListener() {},
    },
    setTimeout: (fn) => fn(),
    ApplyInlineMultiSelectionDomState() {}, UpdateInlineMultiChooseMessage() {},
    resolveCardImageID: (id) => id,
    __panelOpens: panelOpens, __submits: submits,
  });
  vm.runInContext(slice('function swuHighlightPreviewTargets(', '// ── Split-assign (MZSPLITASSIGN)'), ctx);
  vm.runInContext(slice('function swuMarkPreviewMultiSelection(', 'function swuInitPairSwitcher('), ctx);
  vm.runInContext(slice('function swuMbUnitUpgrades(', 'function swuMbUnitOverlays('), ctx);
  vm.runInContext(slice('function swuMbFxColumn(', 'function swuSeatLabelHtml('), ctx);
  vm.runInContext('function swuBaseArtRoot(){ return "AppCore/SWU/Images"; }', ctx);
  // The panel opener is stubbed: this suite asserts WHICH element gets opened, not the popup's pixels.
  vm.runInContext('window.swuClickPanel = function (el) { __panelOpens.push(el); };', ctx);
  ctx.window.SelectionMode = {
    active: true, callback: (zone, mz, idx) => submits.push([zone, mz, idx]),
    decisionIndex: 0, multiSelected: null, multiMax: 0,
  };
  return { ctx, panelOpens, submits };
}

// One offered SUBCARD on seat 3's first ground unit — the Sabine/shield shape from #1068.
const SUBCARD_SPEC = [{ zone: 'p3GroundArena', isSpecificCard: true, specificIndex: 0, subIndex: 0 }];
// A plain unit-level target, for the control cases.
const UNIT_SPEC = [{ zone: 'p3GroundArena', isSpecificCard: true, specificIndex: 0, subIndex: null }];

const PAYLOAD = encodeURIComponent(JSON.stringify({
  subcards: ['SOR_T02'], mzids: ['p3GroundArena-0.u0'], folder: 'x', size: 150, title: 'Attached Upgrades',
}));

test('a unit-level target still highlights and submits the host mzID (control)', () => {
  const host = tile('p3GroundArena-0');
  const { ctx, submits } = makeCtx([host]);
  ctx.window.SelectionMode._twAllSpecs = UNIT_SPEC;
  ctx.swuHighlightPreviewTargets();
  assert.equal(host.classList.contains('mini-selectable'), true);
  assert.equal(host.classList.contains('mini-subcard-host'), false, 'a unit target is not a subcard host');
  ctx.swuPreviewTargetClick(host);
  assert.deepEqual(submits, [['p3GroundArena', 'p3GroundArena-0', 0]]);
});

test('a subcard target marks its HOST tile as a subcard host, not a plain target', () => {
  const host = tile('p3GroundArena-0', { upgradeBadge: PAYLOAD });
  const { ctx } = makeCtx([host]);
  ctx.window.SelectionMode._twAllSpecs = SUBCARD_SPEC;
  ctx.swuHighlightPreviewTargets();
  assert.equal(host.classList.contains('mini-selectable'), true, 'the tile must be reachable');
  assert.equal(host.classList.contains('mini-subcard-host'), true, 'and must be flagged as a subcard host');
});

test('clicking a subcard host OPENS THE PANEL and submits nothing', () => {
  const host = tile('p3GroundArena-0', { upgradeBadge: PAYLOAD });
  const { ctx, panelOpens, submits } = makeCtx([host]);
  ctx.window.SelectionMode._twAllSpecs = SUBCARD_SPEC;
  ctx.swuHighlightPreviewTargets();
  ctx.swuPreviewTargetClick(host);
  assert.deepEqual(submits, [], 'the bare host mzID is what the server rejects — it must never be sent');
  assert.equal(panelOpens.length, 1, 'the attached-upgrades panel opens instead');
  assert.equal(panelOpens[0].__kind, 'upg');
});

test('a unit whose subcards are NOT offered does not light up', () => {
  const offered = tile('p3GroundArena-0', { upgradeBadge: PAYLOAD });
  const bystander = tile('p3GroundArena-1', { upgradeBadge: PAYLOAD });
  const { ctx } = makeCtx([offered, bystander]);
  ctx.window.SelectionMode._twAllSpecs = SUBCARD_SPEC;
  ctx.swuHighlightPreviewTargets();
  assert.equal(offered.classList.contains('mini-selectable'), true);
  assert.equal(bystander.classList.contains('mini-selectable'), false,
    'highlighting every unit that merely HAS an upgrade promises a click that leads nowhere');
});

test('the panel pick submits the full subcard mzID', () => {
  const host = tile('p3GroundArena-0', { upgradeBadge: PAYLOAD });
  const { ctx, submits } = makeCtx([host]);
  ctx.window.SelectionMode._twAllSpecs = SUBCARD_SPEC;
  assert.equal(typeof ctx.window.PanelSubcardPick, 'function', 'the panel needs a pick hook');
  ctx.window.PanelSubcardPick('p3GroundArena-0.u0');
  assert.deepEqual(submits, [['p3GroundArena', 'p3GroundArena-0.u0', 0]]);
});

test('the panel marks only OFFERED entries selectable', () => {
  const { ctx } = makeCtx([tile('p3GroundArena-0', { upgradeBadge: PAYLOAD })]);
  ctx.window.SelectionMode._twAllSpecs = SUBCARD_SPEC;
  assert.equal(typeof ctx.window.PanelSubcardSelectable, 'function', 'the panel needs a legality hook');
  assert.equal(ctx.window.PanelSubcardSelectable('p3GroundArena-0.u0'), true);
  assert.equal(ctx.window.PanelSubcardSelectable('p3GroundArena-0.u1'), false, 'a sibling token is not offered');
  assert.equal(ctx.window.PanelSubcardSelectable('p3GroundArena-1.u0'), false, 'another host is not offered');
});

test('subcard targeting is inert when no decision is active', () => {
  const host = tile('p3GroundArena-0', { upgradeBadge: PAYLOAD });
  const { ctx, panelOpens, submits } = makeCtx([host]);
  ctx.window.SelectionMode.active = false;
  ctx.window.SelectionMode._twAllSpecs = SUBCARD_SPEC;
  ctx.swuHighlightPreviewTargets();
  assert.equal(host.classList.contains('mini-selectable'), false);
  ctx.swuPreviewTargetClick(host);
  assert.deepEqual(submits, []);
  assert.deepEqual(panelOpens, []);
});

test('unit tile badges split upgrades from captives, and carry subcard mzIDs', () => {
  const { ctx } = makeCtx([]);
  const html = ctx.swuMbUnitUpgrades(
    { Subcards: [{ CardID: 'SOR_T02' }, { CardID: 'SOR_032', IsCaptive: true }] },
    'p3GroundArena-0');
  assert.ok(html.includes('swu-mb-upgcount'), 'an upgrade badge');
  assert.ok(html.includes('swu-mb-capcount'), 'a SEPARATE captive badge, like the base ARRESTED pip');
  // Counts are per-kind, not one lumped total.
  assert.ok(/swu-mb-upgcount[^>]*>1</.test(html), `upgrade count should be 1: ${html}`);
  assert.ok(/swu-mb-capcount[^>]*>1</.test(html), `captive count should be 1: ${html}`);
  const payloads = [...html.matchAll(/data-lineage-subcards='([^']+)'/g)]
    .map((m) => JSON.parse(decodeURIComponent(m[1])));
  const upg = payloads.find((p) => p.subcards.includes('SOR_T02'));
  const cap = payloads.find((p) => p.subcards.includes('SOR_032'));
  assert.deepEqual(upg.mzids, ['p3GroundArena-0.u0'], 'raw Subcards index, so the server can address it');
  assert.deepEqual(cap.mzids, ['p3GroundArena-0.u1'], 'captive keeps its own raw index');
  assert.equal(cap.title, 'Captured Units');
});

test('a captive does not inflate the upgrade count', () => {
  const { ctx } = makeCtx([]);
  const html = ctx.swuMbUnitUpgrades({ Subcards: [{ CardID: 'SOR_032', IsCaptive: true }] }, 'p3GroundArena-0');
  assert.ok(html.includes('swu-mb-capcount'));
  assert.ok(!html.includes('swu-mb-upgcount'), 'no upgrades attached → no upgrade badge at all');
});

// ── The panel's own rendering (Core/UILibraries) ────────────────────────────────────────────────
// showLineageOverflowPopup is shared with GrandArchive's champion lineage, so the selectable entry is
// strictly additive: it appears only when the payload carries `mzids` AND the host page defines the
// two hooks. These tests pin both halves of that gate.
const ui = fs.readFileSync(new URL('../../Core/UILibraries20260918.js', import.meta.url), 'utf8');

function renderPanel({ mzids, selectable }) {
  const a = ui.indexOf('function showLineageOverflowPopup(');
  const b = ui.indexOf('function hideLineageOverflowPopup(', a);
  assert.ok(a !== -1 && b > a, 'popup slice');
  const popup = { innerHTML: '', style: {}, classList: { add() {}, remove() {}, contains: () => false },
                  getBoundingClientRect: () => ({}), offsetWidth: 0, offsetHeight: 0 };
  const payload = encodeURIComponent(JSON.stringify({
    subcards: ['SOR_T02', 'SOR_T01'], mzids, folder: 'x', size: 150, title: 'Attached Upgrades',
  }));
  const ctx = vm.createContext({
    window: { innerWidth: 1000, innerHeight: 800 }, console,
    document: { getElementById: () => null, createElement: () => popup, body: { appendChild() {} } },
    requestAnimationFrame: () => {}, setTimeout: () => {}, clearTimeout: () => {},
    resolveCardImageID: (id) => id,
    getOrCreateLineageOverflowPopup: () => popup,
    lineageOverflowPopupTimeout: null,
  });
  if (selectable) {
    ctx.window.PanelSubcardSelectable = (mz) => selectable.includes(mz);
    ctx.window.PanelSubcardPick = () => {};
  }
  vm.runInContext(ui.slice(a, b), ctx);
  ctx.showLineageOverflowPopup({ getAttribute: () => payload, getBoundingClientRect: () => ({}) });
  return popup.innerHTML;
}

test('the panel renders an offered entry as pickable and leaves the rest inert', () => {
  const html = renderPanel({
    mzids: ['p3GroundArena-0.u0', 'p3GroundArena-0.u1'],
    selectable: ['p3GroundArena-0.u0'],
  });
  const entries = html.split('ga-lineage-popup-card').slice(1);
  assert.equal(entries.length, 2);
  assert.ok(entries[0].includes('ga-lineage-popup-pickable'), 'the offered Shield is pickable');
  assert.ok(entries[0].includes('PanelSubcardPick("p3GroundArena-0.u0")'), 'and submits its own mzID');
  assert.ok(!entries[1].includes('ga-lineage-popup-pickable'), 'the unoffered Experience token is not');
  assert.ok(!entries[1].includes('PanelSubcardPick('), 'and has no click handler at all');
});

test('the panel is unchanged for callers that pass no mzids (GrandArchive lineage)', () => {
  const html = renderPanel({ mzids: undefined, selectable: ['p3GroundArena-0.u0'] });
  assert.ok(!html.includes('ga-lineage-popup-pickable'));
  assert.ok(!html.includes('data-mzid'));
  assert.ok(!html.includes('PanelSubcardPick('));
});

test('the panel is inert when the host page defines no pick hooks', () => {
  const html = renderPanel({ mzids: ['p3GroundArena-0.u0', 'p3GroundArena-0.u1'], selectable: null });
  assert.ok(!html.includes('ga-lineage-popup-pickable'), 'no hooks → nothing is clickable');
  assert.ok(html.includes("data-mzid='p3GroundArena-0.u0'"), 'the address is still emitted');
});

test('base-hosted Fortify upgrades carry their subcard mzIDs too', () => {
  const { ctx } = makeCtx([]);
  const html = ctx.swuMbFxColumn(
    { UpgradeCount: 1, UpgradeCardIDs: 'HMW_081', CaptiveCount: 1, CaptiveCardIDs: 'SOR_032',
      Subcards: [{ CardID: 'HMW_081' }, { CardID: 'SOR_032', IsCaptive: true }] },
    'p3Base-0');
  const payloads = [...html.matchAll(/data-lineage-subcards='([^']+)'/g)]
    .map((m) => JSON.parse(decodeURIComponent(m[1])));
  const fort = payloads.find((p) => p.subcards.includes('HMW_081'));
  assert.deepEqual(fort.mzids, ['p3Base-0.u0'], 'a Fortify upgrade must be addressable from the home view');
});
