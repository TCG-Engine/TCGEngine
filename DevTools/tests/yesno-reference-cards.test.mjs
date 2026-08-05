import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const coreDir = new URL('../../Core/', import.meta.url);
const uiBundle = fs.readdirSync(coreDir).find((name) => /^UILibraries20\d{6}\.js$/.test(name));
assert.ok(uiBundle, 'browser UI bundle is present');
const uiSource = fs.readFileSync(new URL(uiBundle, coreDir), 'utf8');
const parserStart = uiSource.indexOf('function ParseYesNoDecisionPresentation');
const parserEnd = uiSource.indexOf('// Show a YES/NO popup', parserStart);
assert.ok(parserStart >= 0 && parserEnd > parserStart, 'YESNO presentation parser is present');

const context = {};
vm.runInNewContext(uiSource.slice(parserStart, parserEnd), context);

test('YESNO presentation keeps the NAMECARD-style double-pipe reference value intact', () => {
  const result = context.ParseYesNoDecisionPresentation(
    'review:myHand|refs:Opening_hand||myHand|yes:Mulligan|no:Keep'
  );
  assert.deepEqual({ ...result }, {
    reviewZone: 'myHand',
    referenceParam: 'Opening_hand||myHand',
    yesLabel: 'Mulligan',
    noLabel: 'Keep'
  });
});

test('legacy YESNO prompts retain their default presentation', () => {
  const result = context.ParseYesNoDecisionPresentation('-');
  assert.deepEqual({ ...result }, {
    reviewZone: '',
    referenceParam: '',
    yesLabel: 'Yes',
    noLabel: 'No'
  });
});

test('reference-card preview data can expand a whole visible zone', () => {
  const nameCardSource = fs.readFileSync(new URL('../../Core/NameCardUI.js', import.meta.url), 'utf8');
  const nameCardContext = {
    window: {
      myHandData: 'CARD_A 0 -<|>CARD_B 0 -'
    }
  };
  vm.runInNewContext(nameCardSource, nameCardContext);
  const preview = nameCardContext.window.NameCardLookup.buildPreviewCards('Opening_hand||myHand');
  assert.equal(preview.label, 'Opening hand');
  assert.equal(preview.cards.length, 2);
  assert.equal(preview.cards[0].spec, 'myHand-0');
  assert.equal(preview.cards[1].spec, 'myHand-1');
});

test('Azuki opening mulligan requests the visible hand as reference cards', () => {
  const gameLogic = fs.readFileSync(new URL('../../AzukiSim/Custom/GameLogic.php', import.meta.url), 'utf8');
  assert.match(gameLogic, /refs:Opening_hand\|\|myHand/);
});
