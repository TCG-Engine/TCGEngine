import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import { spawnSync } from 'node:child_process';

const source = fs.readFileSync(new URL('../../SharedUI/Sites/GrandArchiveSim/MainMenu.php', import.meta.url), 'utf8');
const official = JSON.parse(source.match(/var GA_OFFICIAL_DECKS = (\[[\s\S]*?\]);/)[1]);
const samples = vm.runInNewContext(source.match(/var GA_BOT_SAMPLE_DECKS = (\[[\s\S]*?\]);/)[1]);

function menu() {
  const nodes = new Map();
  function element(id) {
    if (!nodes.has(id)) {
      let value = '';
      nodes.set(id, {
        get value() { return value; },
        // Match HTML input[type=text] sanitization, which caused this regression.
        set value(next) { value = id === 'ga-deck2-input' ? next.replace(/[\r\n]/g, '') : next; },
      });
    }
    return nodes.get(id);
  }
  element('deck-link').value = 'https://example.test/player-one';
  element('ga-format-select').value = 'bot';
  const callbacks = {};
  const context = vm.createContext({
    window: {}, document: { getElementById: element },
    StyledAlert(message) { throw new Error(message); },
    setDeckSelectionSummary() {},
    renderBotSampleDecks(id, callback) { callbacks[id] = callback; },
    renderOfficialDeckSelect(id, callback) { callbacks[id] = callback; },
  });
  vm.runInContext(source.match(/function getDeckSubmission\(\) \{[\s\S]*?\n      \}/)[0], context);
  for (const id of ['ga-bot-sample-decks-list-2', 'ga-official-deck-select-2']) {
    const start = source.indexOf(id.startsWith('ga-bot') ? `renderBotSampleDecks('${id}',` : `renderOfficialDeckSelect('${id}',`);
    vm.runInContext(source.slice(start, source.indexOf('\n      });', start) + '\n      });'.length), context);
  }
  return { context, callbacks, element };
}

for (const [tab, id, decks] of [
  ['official', 'ga-official-deck-select-2', official],
  ['archetype', 'ga-bot-sample-decks-list-2', samples],
]) {
  test(`${tab} bot prefills preserve every line through submission`, () => {
    const { context, callbacks, element } = menu();
    context.window._gaDeck2Tab = tab;
    element('ga-deck2-input').value = 'https://example.test/stale-link';
    for (const deck of decks) {
      callbacks[id](deck);
      assert.equal(context.getDeckSubmission().deckLink2, deck.text.trim(), deck.label);
      context.window._gaDeck2Tab = 'text';
      assert.equal(context.getDeckSubmission().deckLink2, deck.text.trim());
      context.window._gaDeck2Tab = tab;
    }
    context.window._gaDeck2Tab = 'link';
    assert.equal(context.getDeckSubmission().deckLink2, 'https://example.test/stale-link');
  });
}

test('submitted Fire Diana starter resolves to its real cards, not the wolf fallback', () => {
  const { context, callbacks } = menu();
  const deck = official.find(deck => deck.label === 'Diana Starter Deck');
  context.window._gaDeck2Tab = 'official';
  callbacks['ga-official-deck-select-2'](deck);
  const result = spawnSync('php', ['-r', `require 'GrandArchiveSim/Custom/DeckImport.php'; echo json_encode(GrandArchiveResolveDeckInput(stream_get_contents(STDIN)));`], {
    cwd: new URL('../../', import.meta.url), input: context.getDeckSubmission().deckLink2, encoding: 'utf8',
  });
  assert.equal(result.status, 0, result.stderr);
  const resolved = JSON.parse(result.stdout);
  assert.equal(resolved.success, true);
  assert.equal(resolved.mainDeck.length, 60);
  assert.equal(resolved.material.length, 12);
  assert.deepEqual(resolved.unresolved, []);
  assert.ok(resolved.material.includes('e3z4pyx8bd')); // Diana, Keen Huntress
  assert.ok(!resolved.mainDeck.includes('5n874ubgai')); // fallback Direwolf Alpha
});
