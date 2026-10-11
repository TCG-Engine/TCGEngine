// Profile → Game Settings → "Turn off health bars" (owner request 2026-10-08): the ACCOUNT layer of the setting.
// Logged in as claudebot1: tick the Profile toggle, then open a game in a FRESH browser (no local choice), so
// only the account value can hide the bars. Untick, and they come back. The Twin Suns HP badge always stays.
// The browser layer (gear menu, logged out) is covered by swusim-base-health-bar-xbrowser.mjs's `setting` section.
//
//   node DevTools/ui-harness/swusim-profile-hide-health-bars-xbrowser.mjs
//
// ⚠ Touches claudebot1's real account row (setting 3). The original value is read first and put back in a
// finally block — deleted again if it was never set — so the account ends exactly as it started.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SITE = BASE + 'SharedUI/Sites/SWUSim/';
const CONTAINER = 'otmtcge-swusim-web-server-1';
const SETTING = 3;   // SWUSIM_SET_HIDE_HEALTH_BARS

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok = () => { checks++; };

const php = (code) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, 'php', '-d', 'xdebug.mode=off', '-r',
  'require "Database/ConnectionManager.php"; $c = GetLocalMySQLConnection(); ' + code], { encoding: 'utf8' }).trim();
const UID = Number(php('$r = $c->query("SELECT usersId FROM users WHERE usersUid = \'claudebot1\'")->fetch_row(); echo $r[0];'));
const readRow = () => { const v = php(`$r = $c->query("SELECT settingValue FROM savedsettings WHERE playerId = ${UID} AND settingNumber = ${SETTING}")->fetch_row(); echo $r === null ? "NULL" : $r[0];`); return v === 'NULL' ? null : v; };
const restoreRow = (v) => php(v === null
  ? `$c->query("DELETE FROM savedsettings WHERE playerId = ${UID} AND settingNumber = ${SETTING}");`
  : `$c->query("INSERT INTO savedsettings (playerId, settingNumber, settingValue) VALUES (${UID}, ${SETTING}, '${v}') ON DUPLICATE KEY UPDATE settingValue = VALUES(settingValue)");`);

// A Twin Suns board: tiles (thin bars + HP badges) plus your own full-size bar.
const SCHEMA = [
  '## GIVEN',
  'CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myBase:SOR_029; myBaseDamage:9; theirBase:SOR_019}',
  'SkipPreGame: true', 'WithSeatOrder: 1234', 'WithLiveSeats: 1234', 'WithGamePhase: ActionPhase', 'WithActivePlayer: 1',
  'WithP3Base: JTL_031:30', 'WithP4Base: LOF_024:12',
  '## WHEN', '## EXPECT', 'SEATCOUNT:4',
].join('\n');
const seed = async () => {
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ schema: SCHEMA }).toString() });
  return String((await r.json()).gameName);
};

async function login(ctx) {
  const page = await ctx.newPage();
  await page.goto(SITE + 'LoginPage.php');
  await page.fill('input[name="userID"]', 'claudebot1'); await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation().catch(() => {}), page.click('button[type="submit"]')]);
  return page;
}
async function boardState(ctx, game) {
  const p = await ctx.newPage();
  await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema`, { waitUntil: 'domcontentloaded' });
  await p.waitForSelector('#myBaseSlot img', { timeout: 20000 }); await p.waitForTimeout(2000);
  const st = await p.evaluate(() => {
    const vis = e => !!(e && e.getClientRects().length);
    return { bars: [...document.querySelectorAll('.swu-hb')].filter(vis).length,
             badges: [...document.querySelectorAll('#swuHomeStrips .swu-mb-basehp')].filter(vis).length,
             account: window.SWU_ACCOUNT_HIDE_HEALTH_BARS };
  });
  await p.close();
  return st;
}
async function profileToggle(page, want) {
  await page.goto(SITE + 'Profile.php', { waitUntil: 'domcontentloaded' });
  const box = await page.$('#profileHideHealthBars');
  if (!box) return { missing: true };
  const label = await page.evaluate(() => {
    const b = document.getElementById('profileHideHealthBars'), row = b.closest('label');
    const hint = row && row.nextElementSibling ? row.nextElementSibling.textContent : '';
    return { text: row ? row.textContent.trim() : '', hint };
  });
  if ((await box.isChecked()) !== want) await box.click();
  await page.waitForFunction(() => /Saved\.|Could not save/.test((document.getElementById('profileHideHealthBarsStatus') || {}).textContent || ''),
                             null, { timeout: 10000 }).catch(() => {});
  const status = await page.evaluate(() => (document.getElementById('profileHideHealthBarsStatus') || {}).textContent || '');
  return { label, status };
}

const original = readRow();
console.log(`claudebot1 = user ${UID}; setting ${SETTING} was ${original === null ? 'never set' : original}`);
const game = await seed();
try {
  for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
    const name = engine;
    const b = await launcher.launch();
    try {
      restoreRow(null);   // every engine starts from "never set", so the Profile box starts unticked
      const ctx = await b.newContext(); const page = await login(ctx);
      const on = await profileToggle(page, true);
      if (on.missing) { bad(name, 'no "Turn off health bars" toggle (#profileHideHealthBars) on Profile'); continue; }
      /Turn off health bars/.test(on.label.text) ? ok() : bad(name, `label reads "${on.label.text}"`);
      /badge/i.test(on.label.hint) ? ok() : bad(name, `the hint should say the HP badge stays: "${on.label.hint}"`);
      /Saved\./.test(on.status) ? ok() : bad(name, `ticking didn't save (status "${on.status}")`);
      readRow() === '1' ? ok() : bad(name, `account row after ticking is ${readRow()}, want 1`);
      // A FRESH browser, logged in, with no local choice: only the account value is in play.
      const fresh = await b.newContext(); await (await login(fresh)).close();
      const off = await boardState(fresh, game);
      off.account === true ? ok() : bad(name, `the game page got SWU_ACCOUNT_HIDE_HEALTH_BARS = ${off.account}`);
      off.bars === 0 ? ok() : bad(name, `account says off, but ${off.bars} bar(s) show`);
      off.badges === 3 ? ok() : bad(name, `the Twin Suns HP badges must stay (found ${off.badges})`);
      await fresh.close();
      // Logging in from the fresh browser ended the first browser's session: sign in again before Profile.
      const page2 = await login(ctx);
      const offAgain = await profileToggle(page2, false);
      if (offAgain.missing) { bad(name, 'Profile toggle missing on the second visit'); continue; }
      /Saved\./.test(offAgain.status) ? ok() : bad(name, `unticking didn't save (status "${offAgain.status}")`);
      readRow() === '0' ? ok() : bad(name, `account row after unticking is ${readRow()}, want 0`);
      const fresh2 = await b.newContext(); await (await login(fresh2)).close();
      const back = await boardState(fresh2, game);
      back.bars > 0 ? ok() : bad(name, 'unticked on Profile, but the bars stay hidden');
      await fresh2.close(); await ctx.close();
    } finally { await b.close(); }
  }
} finally {
  restoreRow(original);
  const after = readRow();
  after === original ? console.log('restored claudebot1 setting 3 to', original === null ? 'never set' : original)
                     : bad('cleanup', `could not restore claudebot1 setting 3 (now ${after}, was ${original})`);
}
console.log(fails === 0 ? `PASS (${checks} checks, 3 engines)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
