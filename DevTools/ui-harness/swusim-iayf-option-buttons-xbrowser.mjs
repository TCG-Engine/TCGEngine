// I Am Your Father (SOR_233 / IC27_185): the controller's choice as two labelled buttons, the first with a card link —
// Chromium + Firefox + WebKit, desktop + ?swuLayout=mobile.
//
// Owner request 2026-11-09: prompt "Choose an option"; buttons "<card> Takes 7 Damage" / "Opponent Draws 3 Cards" (was a
// YESNO, "Say no to the 7 damage? (opponent draws 3)"). The first option travels as "[[LAW_124]]_Takes_7_Damage" and
// Core/OptionChooseUI.js draws the token as the card's NAME in a span.swu-card-link with the game log's hover preview.
//
// The schema suite never renders the page, so this is the only evidence. Spec + fixture:
// SWUSim/Tests/Visual/IAmYourFather_OptionButtons.md
//
// Usage: node swusim-iayf-option-buttons-xbrowser.mjs [BASE] [SHOTS_DIR]
//        ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS  = process.argv[3] || '/tmp';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/IAmYourFather_OptionButtons.md'), 'utf8');
// The state AFTER clicking "Takes 7 Damage": the 4/7 is defeated, nothing drawn. Only the EXPECT section is evaluated.
const AFTER  = SCHEMA.replace(/## EXPECT[\s\S]*$/, '## EXPECT\nP2NODECISION\nP2GROUNDARENACOUNT:0\nP1HANDCOUNT:0\n');
const TAKE = '[[LAW_124]]_Takes_7_Damage', DRAW = 'Opponent_Draws_3_Cards';

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

async function buildGame(page) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps) {
    const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
      { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
    if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
  }
  return setup.gameName;
}

async function probe(page) {
  return page.evaluate(async () => {
    const el = document.querySelector('.optchoose-banner');
    if (!el) return { missing: '.optchoose-banner' };
    // The page-load flash overlay (z-index 10000, ~750ms) sits above every banner — wait it out before hit tests.
    for (let i = 0; i < 40; i++) {
      if (!Array.from(document.body.children).some((e) => getComputedStyle(e).zIndex === '10000' && e !== el)) break;
      await new Promise((r) => setTimeout(r, 100));
    }
    const vw = window.innerWidth, vh = window.innerHeight;
    const inView = (b) => b.left >= -0.5 && b.right <= vw + 0.5 && b.top >= -0.5 && b.bottom <= vh + 0.5;
    const buttons = Array.from(el.querySelectorAll('button')).map((b) => {
      const r = b.getBoundingClientRect();
      const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
      const link = b.querySelector('.swu-card-link');
      const lr = link ? link.getBoundingClientRect() : null;
      return { option: b.getAttribute('data-option'), text: (b.textContent || '').replace(/\s+/g, ' ').trim(),
               link: link ? link.textContent : null, linkBox: lr ? { w: lr.width, h: lr.height } : null,
               inView: inView(r), hittable: !!hit && (hit === b || b.contains(hit)) };
    });
    const label = el.querySelector('.optchoose-label');
    const r = el.getBoundingClientRect();
    return { label: label ? label.textContent : null, buttons, bannerInView: inView(r) };
  });
}

async function controls(page) {
  return page.evaluate(() => {
    const shape = (param) => {
      window.ShowOptionChooseUI(param, 'Choose', 7, function () {});
      const el = document.querySelector('.optchoose-banner');
      const out = { links: el.querySelectorAll('.swu-card-link').length, art: el.querySelectorAll('.optchoose-card').length,
                    text: Array.from(el.querySelectorAll('button')).map((b) => b.textContent.trim()) };
      window.HideOptionChooseUI();
      return out;
    };
    return { arena: shape('Ground&Space'), art: shape('@SOR_046&Play&Discard&Leave'), words: shape('Replace_Raid_With_Restore&Keep') };
  });
}

for (const [engineName, engine] of ENGINES) {
  let browser;
  try { browser = await engine.launch(); }
  catch (e) { ok(`${engineName}: browser launched`, false, String(e.message).split('\n')[0]); continue; }
  try {
    for (const layout of ['desktop', 'mobile']) {
      const tag = `${engineName}/${layout}`;
      const ctx = await browser.newContext(layout === 'mobile' ? { viewport: { width: 390, height: 844 } } : { viewport: { width: 1600, height: 900 } });
      const page = await ctx.newPage();
      await login(page);
      let gameName;
      try { gameName = await buildGame(page); }
      catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
      // P2's seat: the target's CONTROLLER answers.
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=2&authKey=testschema`
        + `&viewerPerspective=2&opponentID=1${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
      await page.waitForSelector('.optchoose-banner button', { timeout: 10000 }).catch(() => {});

      const m = await probe(page);
      await page.screenshot({ path: `${SHOTS}/swusim-iayf-${engineName}-${layout}.png` }).catch(() => {});
      if (m.missing) { ok(`${tag}: banner rendered`, false, 'missing ' + m.missing); await ctx.close(); continue; }
      ok(`${tag}: prompt reads "Choose an option"`, (m.label || '').trim() === 'Choose an option', m.label);
      ok(`${tag}: two buttons, raw values in order`, m.buttons.map((b) => b.option).join(',') === `${TAKE},${DRAW}`, m.buttons.map((b) => b.option).join(','));
      const [take, draw] = m.buttons;
      ok(`${tag}: first button reads "Industrious Team Takes 7 Damage"`, take && /^Industrious Team Takes 7 Damage$/i.test(take.text), take && take.text);
      ok(`${tag}: the unit's name is a card link`, take && take.link === 'Industrious Team' && take.linkBox && take.linkBox.w > 10, take && JSON.stringify([take.link, take.linkBox]));
      ok(`${tag}: no raw token or CardID shown`, take && !/\[\[|LAW[ _]124/.test(take.text), take && take.text);
      ok(`${tag}: second button reads "Opponent Draws 3 Cards"`, draw && /^Opponent Draws 3 Cards$/i.test(draw.text), draw && draw.text);
      ok(`${tag}: buttons on screen and clickable`, m.buttons.every((b) => b.inView && b.hittable), JSON.stringify(m.buttons.map((b) => [b.inView, b.hittable])));
      ok(`${tag}: banner within viewport`, m.bannerInView);

      // Hover the card link -> the card preview (#cardDetail) shows; mouse away hides it. Pointer devices only, so desktop.
      if (layout === 'desktop') {
        await page.hover('.optchoose-banner .swu-card-link');
        await page.waitForTimeout(400);
        const shown = await page.evaluate(() => { const d = document.getElementById('cardDetail'); return !!d && getComputedStyle(d).display !== 'none'; });
        ok(`${tag}: hovering the card link shows the card preview`, shown);
        await page.screenshot({ path: `${SHOTS}/swusim-iayf-${engineName}-${layout}-hover.png` }).catch(() => {});
        await page.mouse.move(2, 2);
        await page.waitForTimeout(300);
      }

      const c = await controls(page);
      ok(`${tag}: control "Ground&Space" unchanged`, c.arena.links === 0 && c.arena.text.join(',') === 'Ground,Space', JSON.stringify(c.arena));
      ok(`${tag}: control "@CardID&Play&…" unchanged`, c.art.art === 1 && c.art.text.join(',') === 'Play,Discard,Leave', JSON.stringify(c.art));
      ok(`${tag}: control underscored words unchanged`, c.words.links === 0 && /^Replace Raid With Restore$/i.test(c.words.text[0] || ''), JSON.stringify(c.words));
      // controls() hid the banner — reload to answer the real decision.
      await page.reload({ waitUntil: 'load' });
      await page.waitForSelector('.optchoose-banner button', { timeout: 10000 }).catch(() => {});
      await probe(page);   // waits out the load flash

      const clicked = await page.click(`.optchoose-banner button[data-option="${TAKE}"]`, { timeout: 5000 }).then(() => true, (e) => String(e.message).split('\n')[0]);
      ok(`${tag}: clicked "Takes 7 Damage"`, clicked === true, clicked === true ? '' : clicked);
      await page.waitForTimeout(1500);
      ok(`${tag}: banner closes on the click`, await page.evaluate(() => !document.querySelector('.optchoose-banner button')));
      const exp = await (await page.request.post(BASE + 'SWUSim/TestSchemaExpect.php', { multipart: { gameName: String(gameName), schema: AFTER } })).json();
      ok(`${tag}: server: the 7 damage defeated the unit, nothing drawn`, exp.passed === true, exp.error || (exp.failures || []).join(' | ') || `${exp.total} checks`);
      await ctx.close();
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
