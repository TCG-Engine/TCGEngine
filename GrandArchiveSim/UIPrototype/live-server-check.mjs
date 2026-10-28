// Invoked by DevTools/test-ga-ui-revision-http.php --browser with disposable credentials on stdin.
import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';
const { chromium, firefox, webkit } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
let input = '';
for await (const chunk of process.stdin) input += chunk;
const config = JSON.parse(input);
const original = await readFile(config.statePath, 'utf8');
const revision = Number(original.split('\n')[1]);
for (const engine of [chromium, firefox, webkit]) {
 const browser = await engine.launch({ headless: true });
 try {
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('request', request => {
   if (request.url().includes('UIState.php') || request.url().includes('UIAction.php')) {
    assert.equal(request.method(), 'POST');
    assert.ok(!request.url().includes(config.key));
   }
  });
  await page.goto(config.url);
  await page.getByRole('button', { name: 'Open live viewer', exact: true }).click();
  const connect = async (seat, key) => {
   await page.getByLabel('Game number').fill(config.game);
   await page.getByLabel('View as').selectOption(seat);
   await page.getByLabel('Match access key').fill(key);
   const response = page.waitForResponse(r => r.url().endsWith('/UIState.php'));
   await page.getByRole('button', { name: 'Connect', exact: true }).click();
   return await response;
  };
  if (config.action) {
   await connect('1', config.key);
   if (config.playLabel) await page.getByRole('button', {name:`Inspect ${config.playLabel.slice(5)}`,exact:true}).first().click();
   const pass = page.getByRole('button', { name: config.playLabel || (config.selectionLabel ? 'Confirm choice' : 'Pass priority'), exact: true });
   if (config.selectionLabel) await page.locator('.live-decision input[type=checkbox]').first().check();
   await pass.waitFor();
   const response = page.waitForResponse(r => r.url().endsWith('/UIAction.php'));
   await pass.click();
   assert.equal((await (await response).json()).success, true);
   await page.waitForFunction(rev => {
    const text = document.querySelector('.live-view')?.textContent || '';
    const match = text.match(/Revision (\d+)/);
    return match && Number(match[1]) > rev;
   }, revision);
   assert.deepEqual(errors, []);
   await page.getByRole('button', { name: 'Disconnect', exact: true }).click();
   console.log(`PASS ${engine.name()}: real projected action and updated board`);
   continue;
  }
  for (const seat of ['1', '2']) {
   const response = await connect(seat, config.key);
   assert.equal(response.status(), 200);
   const match = await response.json();
   assert.equal(match.viewerSeat, Number(seat));
   assert.ok(match.attention.message);
   await page.getByLabel('Match attention', { exact: true }).getByText(match.attention.message, { exact: true }).waitFor();
   assert.equal(match.players[2 - Number(seat)].zones.Hand.cards.length, 0);
   await page.getByText(`Player ${seat} · You`, { exact: true }).waitFor();
   const ownCards = match.players[Number(seat) - 1].zones.Hand.cards;
   assert.ok(ownCards.length > 0, 'fixture exercises a visible private hand');
   const board = page.getByRole('region', { name: `Player ${seat} board`, exact: true });
   for (const card of ownCards) await board.getByRole('button', { name: `Inspect ${card.name || card.cardID}`, exact: true }).first().waitFor();
   assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
   if (seat === '1') await page.screenshot({path:`/tmp/ga-live-${engine.name()}.png`,fullPage:true});
   await page.getByRole('button', { name: 'Disconnect', exact: true }).click();
   assert.equal(await page.getByRole('region', { name: `Player ${seat} board`, exact: true }).count(), 0);
  }
  await connect('1', config.key);
  await page.getByText(`Revision ${revision}`, { exact: false }).waitFor();
  const updated = original.split('\n');
  updated[1] = String(revision + 1);
  await writeFile(config.statePath, updated.join('\n'));
  await page.getByText(`Revision ${revision + 1}`, { exact: false }).waitFor();
  await page.getByRole('button', { name: 'Disconnect', exact: true }).click();
  await writeFile(config.statePath, original);
  assert.equal((await connect('1', 'invalid-test-key')).status(), 403);
  await page.getByRole('status').filter({ hasText: 'Connection lost or access denied' }).waitFor();
  assert.equal(await page.locator('.live-players').count(), 0);
  assert.deepEqual(await page.evaluate(() => [localStorage.length, sessionStorage.length]), [0, 0]);
  assert.deepEqual(errors, []);
  console.log(`PASS ${engine.name()}: real PHP auth, both seats, private hands, polling, disconnect, denied access`);
 } finally {
  await writeFile(config.statePath, original);
  await browser.close();
 }
}
