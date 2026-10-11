// The Sideboard: the screen every Bo3 player sees between games.
//
// Owner, 2026-09-26: "let's update the sideboard experience. the chat also looks kinda bad in that
// it doesn't load the cardlinks. i do like that the game log is preserved though."
//
// Two things are pinned here:
//   1. CARD LINKS in the log. GetGameLog.php returns lines carrying [[SET_NNN]] tokens plus a
//      `names` map. The page used to swap each token for PLAIN TEXT, so a log full of cards had
//      nothing to hover. The shared ChatPanel now renders them as elements — built as DOM NODES,
//      never innerHTML, because every other row in that panel is user-authored chat.
//   2. THE SKIN. This page carried its own self-contained cyan-on-navy CSS (--swu-cyan,
//      Aptos/Bahnschrift) straight through a site-wide redesign, because it 404s without a live
//      match and so was never opened. SWUSim/DevTools/make-sideboard-fixture.php is what makes it
//      openable; this gate needs that fixture.
import { chromium, firefox, webkit } from 'playwright';
const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const URL  = BASE + 'SWUSim/Sideboard.php?matchId=zzsideboardfixture&playerID=1&authKey=fixture';
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 1000 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));

  // ⚠ SERVE THE LOG, do not call appendLog() by hand. The fixture match has no gamestate, so the
  // real GetGameLog.php returns nothing -- and a gate that reaches past that into the panel's API
  // tests the PANEL while leaving the page's own wiring (sbLogLineText and the call it makes)
  // completely unexercised. Flattening the tokens back to plain text -- the reported bug -- then
  // survives the gate untouched. Measured: that mutant passed 39/39 before this route existed.
  await p.route('**/GetGameLog.php*', route => route.fulfill({
    status: 200, contentType: 'application/json',
    body: JSON.stringify({
      gameNumber: 1,
      lines: ['1|PLAY|P1 played [[SOR_001]] and defeated [[SOR_023]]'],
      names: { SOR_001: 'Director Krennic - Aspiring to Authority', SOR_023: 'Echo Base' },
    }),
  }));

  await p.goto(URL, { waitUntil: 'networkidle' });
  await p.waitForTimeout(800);

  if (/No such match/i.test(await p.evaluate(() => document.body.innerText))) {
    console.log(`SKIP ${name} :: fixture missing — run php SWUSim/DevTools/make-sideboard-fixture.php`);
    await b.close();
    if (name === 'webkit' && checks === 0) process.exit(2);
    continue;
  }

  // ── 0. THE REAL PATH: what the page renders from a real log response ──────
  const live = await p.evaluate(() => {
    const rows = [...document.querySelectorAll('.tcgc-log')];
    const row = rows.find(r => /Krennic|SOR_001/.test(r.textContent));
    if (!row) return { none: true, seen: rows.map(r => r.textContent).join(' // ') };
    const links = [...row.querySelectorAll('[data-card-id]')];
    return { none: false, text: row.textContent, links: links.length,
             ids: links.map(e => e.getAttribute('data-card-id')) };
  });
  if (live.none) bad(name, `the served game log did not render (rows: ${live.seen})`);
  else {
    if (/\[\[/.test(live.text)) bad(name, `raw token on screen: "${live.text}"`); else ok();
    if (live.links !== 2) bad(name, `the PAGE rendered ${live.links} card link(s) from a real log response, expected 2`); else ok();
    if (!/Echo Base/.test(live.text)) bad(name, 'the opponent-side card name from d.names was lost'); else ok();
    if (/PLAY\|/.test(live.text)) bad(name, 'the log source prefix was not stripped'); else ok();
  }

  // ── 1. the log's card tokens become hoverable elements ────────────────────
  // Driven through the panel's own public API with a synthetic line, because the fixture match has
  // no real gamestate to produce a log from. The unit under test is the rendering, not the fetch.
  const log = await p.evaluate(() => {
    window.TCGChatPanel.appendLog('GAME 1 LOG',
      ['P1 played [[SOR_001]] and defeated [[SOR_023]]'],
      { cards: { SOR_001: 'Director Krennic - Aspiring to Authority', SOR_023: 'Echo Base' } });
    const rows = [...document.querySelectorAll('.tcgc-log')];
    const row = rows[rows.length - 1];
    if (!row) return { none: true };
    const links = [...row.querySelectorAll('[data-card-id]')];
    return {
      none: false,
      text: row.textContent,
      rawToken: /\[\[/.test(row.textContent),
      linkCount: links.length,
      ids: links.map(e => e.getAttribute('data-card-id')),
      labels: links.map(e => e.textContent),
      // the panel must never build these by injecting caller HTML
      html: row.innerHTML,
    };
  });

  if (log.none) bad(name, 'appendLog rendered no row');
  else {
    if (log.rawToken) bad(name, `the raw [[token]] is still on screen: "${log.text}"`); else ok();
    if (log.linkCount !== 2) bad(name, `${log.linkCount} card link(s), expected 2`); else ok();
    if (log.ids.join() !== 'SOR_001,SOR_023') bad(name, `card ids ${log.ids.join()}`); else ok();
    if (!/Director Krennic/.test(log.labels.join())) bad(name, `card not NAMED (labels: ${log.labels.join(' | ')})`); else ok();
    if (!/defeated/.test(log.text)) bad(name, 'the surrounding sentence was lost'); else ok();
  }

  // ⚠ TWO TOKEN FORMS, and real logs contain both. GameLogCardRef() writes
  // [[SEC_111|Jar Jar Binks]] with the NAME EMBEDDED -- that is the common one, and matching only
  // the bare [[SOR_001]] left it on screen verbatim (owner, 2026-09-26).
  const forms = await p.evaluate(() => {
    window.TCGChatPanel.appendLog('', ['played [[SEC_111|Jar Jar Binks]] then [[SOR_777]]'],
      { cards: { SOR_777: 'Some Named Card' } });
    const rows = [...document.querySelectorAll('.tcgc-log')];
    const row = rows[rows.length - 1];
    const links = [...row.querySelectorAll('[data-card-id]')];
    return { raw: /\[\[/.test(row.textContent), n: links.length,
             ids: links.map(e => e.getAttribute('data-card-id')),
             labels: links.map(e => e.textContent) };
  });
  if (forms.raw) bad(name, 'a raw [[token]] is on screen'); else ok();
  if (forms.n !== 2) bad(name, `${forms.n} card link(s) from the two token forms, expected 2`); else ok();
  if (forms.labels[0] !== 'Jar Jar Binks') bad(name, `piped token rendered as "${forms.labels[0]}"`); else ok();
  if (forms.labels[1] !== 'Some Named Card') bad(name, `bare token rendered as "${forms.labels[1]}"`); else ok();

  // ⚠ The panel carries user-authored chat. A card link must not become a way to inject markup:
  // a name containing a tag has to come out as TEXT.
  const xss = await p.evaluate(() => {
    window.TCGChatPanel.appendLog('', ['danger [[SOR_999]]'],
      { cards: { SOR_999: '<img src=x onerror="window.__pwned=1">' } });
    const rows = [...document.querySelectorAll('.tcgc-log')];
    const row = rows[rows.length - 1];
    return { pwned: !!window.__pwned, hasImg: !!row.querySelector('img'), text: row.textContent };
  });
  if (xss.pwned || xss.hasImg) bad(name, 'a card NAME was injected as markup'); else ok();
  if (!/<img/.test(xss.text)) bad(name, 'the name was not rendered as text'); else ok();

  // ── 2. the skin ───────────────────────────────────────────────────────────
  const skin = await p.evaluate(() => {
    const cs = getComputedStyle(document.body);
    const btn = document.getElementById('submit');
    const h2 = document.querySelector('h2');
    const g = e => e ? {
      font: getComputedStyle(e).fontFamily.split(',')[0].replace(/"/g, ''),
      radius: getComputedStyle(e).borderRadius,
      h: Math.round(e.getBoundingClientRect().height),
    } : null;
    return {
      bodyFont: cs.fontFamily.split(',')[0].replace(/"/g, ''),
      // the old page's palette lived in these; their presence means it never got re-skinned
      legacyVar: getComputedStyle(document.documentElement).getPropertyValue('--swu-cyan').trim(),
      btn: g(btn), h2: g(h2),
      hasChat: !!document.getElementById('tcg-chat-panel'),
      hScroll: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };
  });
  if (skin.legacyVar) bad(name, `the legacy cyan palette is still defined (--swu-cyan: ${skin.legacyVar})`); else ok();
  if (skin.bodyFont !== 'Archivo') bad(name, `body renders in ${skin.bodyFont}, not Archivo`); else ok();
  if (!skin.btn) bad(name, 'no submit button');
  else {
    if (skin.btn.radius !== '0px') bad(name, `Submit has a ${skin.btn.radius} radius — off-pattern`); else ok();
    if (skin.btn.h < 40 || skin.btn.h > 56) bad(name, `Submit is ${skin.btn.h}px tall`); else ok();
  }
  if (!skin.hasChat) bad(name, 'the chat + game log panel is gone'); else ok();

  // Owner, 2026-09-26: the "Leader" / "Base" captions are gone -- a leader card and a base card
  // look nothing alike and neither can be moved, so the label restated the picture. The ROLE stays
  // in the alt text, because a screen reader cannot see that distinction.
  const fixed = await p.evaluate(() => {
    const slots = [...document.querySelectorAll('.fixed .slot')];
    return {
      count: slots.length,
      captions: slots.map(s => (s.textContent || '').trim()).filter(Boolean),
      alts: slots.map(s => (s.querySelector('img') || {}).alt || ''),
    };
  });
  if (fixed.count !== 2) bad(name, `${fixed.count} fixed slots, expected leader + base`); else ok();
  if (fixed.captions.length) bad(name, `the fixed slots still carry captions: ${fixed.captions.join(', ')}`); else ok();
  if (!/^Leader: .+/.test(fixed.alts[0] || '')) bad(name, `leader alt lost its role ("${fixed.alts[0]}")`); else ok();
  if (!/^Base: .+/.test(fixed.alts[1] || '')) bad(name, `base alt lost its role ("${fixed.alts[1]}")`); else ok();

  // Owner, 2026-09-26: the leader and base show their FULL HORIZONTAL art, uncropped.
  // ⚠ 'tile' (concat/) is a 450x450 SQUARE crop of a 628x450 landscape card; 'card' (WebpImages/)
  // is the whole thing. Asserting the rendered aspect equals the NATURAL aspect is what actually
  // proves nothing is cut off — checking the directory alone would miss a CSS object-fit: cover.
  const art = await p.evaluate(() => [...document.querySelectorAll('.fixed .slot img')].map(e => {
    const r = e.getBoundingClientRect();
    return { src: e.currentSrc, nat: e.naturalWidth / e.naturalHeight,
             ren: r.width / r.height, fit: getComputedStyle(e).objectFit,
             w: Math.round(r.width) };
  }));
  if (art.length !== 2) bad(name, `${art.length} fixed images, expected 2`);
  else art.forEach((a, i) => {
    const who = i === 0 ? 'leader' : 'base';
    if (/\/concat\//.test(a.src)) bad(name, `${who} still uses the square 'tile' crop`); else ok();
    if (!a.nat) bad(name, `${who} art did not load`); else ok();
    if (Math.abs(a.nat - a.ren) > 0.02)
      bad(name, `${who} is cropped: natural ${a.nat.toFixed(3)} vs rendered ${a.ren.toFixed(3)}`);
    else ok();
    if (a.fit === 'cover') bad(name, `${who} uses object-fit: cover — that crops`); else ok();
    if (a.w < 140) bad(name, `${who} renders only ${a.w}px wide`); else ok();
  });

  // ⚠ The log panel must START LEVEL WITH THE CONTENT beside it, and must still be STICKY.
  // The interior-panel port sets `position: relative` so its glass pseudo-layers have a containing
  // block; on #tcg-chat-panel that clobbered its own `position: sticky`, and ChatPanel's
  // `top: calc(var(--tcgc-top) + gutter)` -- written as a sticky offset -- became a plain downward
  // nudge. Measured: content top 69px, panel top 186px. Owner: "it's currently starting too low".
  const panel = await p.evaluate(async () => {
    const e = document.getElementById('tcg-chat-panel');
    const main = document.querySelector('.sb-main');
    if (!e || !main) return null;
    const cs = getComputedStyle(e);
    const contentTop = main.getBoundingClientRect().top + parseFloat(cs.getPropertyValue('padding-top') || 0);
    const before = Math.round(e.getBoundingClientRect().top);
    window.scrollTo(0, 400);
    await new Promise(r => setTimeout(r, 200));
    const after = Math.round(e.getBoundingClientRect().top);
    window.scrollTo(0, 0);
    return { position: cs.position, top: before, stuck: after,
             mainTop: Math.round(main.getBoundingClientRect().top),
             mainPad: Math.round(parseFloat(getComputedStyle(main).paddingTop)) };
  });
  if (!panel) bad(name, 'could not measure the log panel');
  else {
    if (panel.position !== 'sticky') bad(name, `the log panel is position:${panel.position}, not sticky`); else ok();
    const contentTop = panel.mainTop + panel.mainPad;
    if (Math.abs(panel.top - contentTop) > 8)
      bad(name, `the log panel starts at ${panel.top}px but the content beside it starts at ${contentTop}px`);
    else ok();
    if (Math.abs(panel.stuck - panel.top) > 8)
      bad(name, `the panel is not sticky on scroll (${panel.top}px -> ${panel.stuck}px)`);
    else ok();
  }
  if (skin.hScroll > 1) bad(name, `page scrolls horizontally by ${skin.hScroll}px`); else ok();

  // ── 3. the action row sits at the FOOT of its column ──────────────────────
  // Owner, 2026-09-26: "align the bottom of the submit/ready button to the bottom of the gamelog
  // pane". Done by layout (`margin-block-start: auto` in a flex column), not a fixed offset, so it
  // holds at any viewport. A long log makes the PANEL the taller column, which is the case the
  // owner was looking at; with a short log the deck grids are taller and the button correctly sits
  // at their foot instead (the panel hugs its content by owner ruling, 2026-09-22).
  await p.route('**/GetGameLog.php*', route => route.fulfill({
    status: 200, contentType: 'application/json',
    body: JSON.stringify({ gameNumber: 1, names: {},
      lines: Array.from({ length: 40 }, (_, i) => `1|PLAY|log line number ${i + 1}`) }),
  }));
  await p.reload({ waitUntil: 'networkidle' });
  await p.waitForTimeout(900);
  const feet = await p.evaluate(() => {
    const panel = document.getElementById('tcg-chat-panel');
    const btn = document.getElementById('submit');
    if (!panel || !btn) return null;
    const pb = panel.getBoundingClientRect(), bb = btn.getBoundingClientRect();
    return { panelBottom: Math.round(pb.bottom), btnBottom: Math.round(bb.bottom),
             panelTaller: pb.height > 400 };
  });
  if (!feet) bad(name, 'could not measure the action row');
  else if (!feet.panelTaller) bad(name, 'the long log did not make the panel the taller column');
  else if (Math.abs(feet.btnBottom - feet.panelBottom) > 4)
    bad(name, `Submit bottom ${feet.btnBottom}px vs log panel bottom ${feet.panelBottom}px`);
  else ok();

  // ── 4. a LEGACY log still renders ─────────────────────────────────────────
  // Entries saved before 2026-09-26 are `type|visibility|text` with no timestamp, and every live
  // gamestate from before then holds them. They must still render -- text intact, prefix stripped,
  // no crash -- and simply keep arrival order, since there is nothing to sort them by.
  //
  // ⚠ This section used to assert the opposite of section 5: that ALL log rows sat above ALL chat
  // rows. That was the interim grouping, and interleaving is what the owner actually asked for --
  // so the check was removed rather than the feature bent to satisfy it.
  await p.unroute('**/GetGameLog.php*');
  await p.route('**/GetGameLog.php*', route => route.fulfill({
    status: 200, contentType: 'application/json',
    body: JSON.stringify({ gameNumber: 1, names: {},
      lines: ['PLAY|ALL|P1 played an old-format line', 'PLAY|ALL|with [[SOR_001]] in it'] }),
  }));
  await p.reload({ waitUntil: 'networkidle' });
  await p.waitForTimeout(1600);
  const legacy = await p.evaluate(() => {
    const rows = [...document.querySelectorAll('.tcgc-log')];
    const hit = rows.find(r => /old-format line/.test(r.textContent));
    const card = rows.find(r => r.querySelector('[data-card-id]'));
    return { found: !!hit, text: hit ? hit.textContent.trim() : '',
             prefixShown: rows.some(r => /^PLAY\|/.test(r.textContent.trim())),
             cardLink: !!card };
  });
  if (!legacy.found) bad(name, 'a legacy unstamped log line did not render'); else ok();
  if (legacy.prefixShown) bad(name, 'a legacy line still shows its "PLAY|ALL|" prefix'); else ok();
  if (!legacy.cardLink) bad(name, 'a legacy line lost its card link'); else ok();

  // ⚠ THE MIXED CASE, which is every game already in progress: the LOG predates timestamps but a
  // message sent today HAS one. The unstamped log must still sit ABOVE that message.
  // Owner, 2026-09-26: "i send a chat, then refreshed and it moved to the top." The first version
  // of this gate stubbed STAMPED log lines only, so it passed while this was broken.
  await p.unroute('**/GetChat.php*');
  await p.unroute('**/GetGameLog.php*');
  const NOW = Math.floor(Date.now() / 1000);
  await p.route('**/GetChat.php*', route => {
    const m = /lastChatID=(\d+)/.exec(route.request().url());
    const last = m ? parseInt(m[1], 10) : 0;
    const all = [{ id: 1, playerID: 1, playerLabel: 'P1', text: 'sent just now', ts: NOW }];
    route.fulfill({ status: 200, contentType: 'application/json',
                    body: JSON.stringify(all.filter(x => x.id > last)) });
  });
  await p.route('**/GetGameLog.php*', async route => {
    await new Promise(r => setTimeout(r, 500));   // the log loses the race, as it does live
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      gameNumber: 1, names: {},
      lines: ['PLAY|ALL|P1 mulligans', 'PLAY|ALL|P2 keeps', 'PLAY|ALL|P1 attacked'] }) });
  });
  await p.reload({ waitUntil: 'networkidle' });
  await p.waitForTimeout(2200);
  const mixed = await p.evaluate(() => [...document.querySelectorAll('#tcgc-stream > *')]
    .filter(e => !/loghead/.test(e.className))
    .map(e => (/tcgc-log/.test(e.className) ? 'LOG' : 'CHAT')));
  if (!mixed.length) bad(name, 'nothing rendered in the mixed case');
  else if (mixed[mixed.length - 1] !== 'CHAT')
    bad(name, `the message you just sent is not last: ${mixed.join(',')}`);
  else if (mixed.indexOf('CHAT') !== mixed.length - 1)
    bad(name, `an unstamped log line sits below the new message: ${mixed.join(',')}`);
  else ok();

  // ── 5. log and chat INTERLEAVE by timestamp ───────────────────────────────
  // Owner, 2026-09-26, the scenario verbatim: opening game logs, "gl hf!", "glhf", more game logs
  // for resourcing and actions, then "gg!" -- and a refresh must reproduce that same order.
  //
  // Ordering is by each row's own wall-clock microtime: chat carries `ts` (SubmitChat.php), a log
  // line carries '@<microtime>' in field 2 (AddGameLogEntry). ⚠ The log response is delayed here
  // ON PURPOSE -- it resolves AFTER the chat, which is the real timing and was what used to decide
  // the order.
  const T = 1790368000;
  await p.unroute('**/GetChat.php*');
  await p.unroute('**/GetGameLog.php*');
  await p.route('**/GetChat.php*', route => {
    const m = /lastChatID=(\d+)/.exec(route.request().url());
    const last = m ? parseInt(m[1], 10) : 0;
    const all = [{ id: 1, playerID: 1, playerLabel: 'P1', text: 'gl hf!', ts: T + 10 },
                 { id: 2, playerID: 2, playerLabel: 'P2', text: 'glhf',   ts: T + 12 },
                 { id: 3, playerID: 1, playerLabel: 'P1', text: 'gg!',    ts: T + 40 }];
    route.fulfill({ status: 200, contentType: 'application/json',
                    body: JSON.stringify(all.filter(x => x.id > last)) });
  });
  await p.route('**/GetGameLog.php*', async route => {
    await new Promise(r => setTimeout(r, 700));
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      gameNumber: 1, names: {}, lines: [
        `PLAY|ALL|@${(T + 1).toFixed(4)}|P1 mulligans`,
        `PLAY|ALL|@${(T + 2).toFixed(4)}|P2 keeps`,
        `PLAY|ALL|@${(T + 20).toFixed(4)}|P1 resourced a card`,
        `PLAY|ALL|@${(T + 30).toFixed(4)}|P2 attacked`,
      ] }) });
  });
  await p.reload({ waitUntil: 'networkidle' });
  await p.waitForTimeout(2600);

  const woven = await p.evaluate(() => [...document.querySelectorAll('#tcgc-stream > *')]
    .filter(e => !/loghead/.test(e.className))
    .map(e => ({ kind: /tcgc-log/.test(e.className) ? 'LOG' : 'CHAT',
                 ts: parseFloat(e.getAttribute('data-ts')),
                 text: e.textContent.trim() })));
  const want = ['LOG', 'LOG', 'CHAT', 'CHAT', 'LOG', 'LOG', 'CHAT'];
  if (woven.length !== want.length)
    bad(name, `${woven.length} rows, expected ${want.length}: ${woven.map(r => r.kind).join(',')}`);
  else if (woven.map(r => r.kind).join(',') !== want.join(','))
    bad(name, `interleave is ${woven.map(r => r.kind).join(',')}, expected ${want.join(',')}`);
  else ok();
  // every row strictly later than the one above it -- that IS the ordering claim
  const ooo = woven.findIndex((r, i) => i > 0 && !(r.ts >= woven[i - 1].ts));
  if (ooo > 0) bad(name, `row ${ooo} ("${woven[ooo].text.slice(0, 24)}") is out of time order`); else ok();
  // and the greeting really does sit between the opening logs and the later ones
  const greet = woven.findIndex(r => /gl hf/.test(r.text));
  const later = woven.findIndex(r => /resourced/.test(r.text));
  if (!(greet > 1 && later > greet)) bad(name, `"gl hf!" at ${greet}, "resourced" at ${later}`); else ok();

  // ── 6. MOBILE ────────────────────────────────────────────────────────────
  // Owner, 2026-09-26: "sideboarding looks terrible on mobile."
  // ⚠ ChatPanel turns the log panel into a FIXED off-canvas drawer below 900px. Two rules of mine
  // overrode that at id specificity (the interior-panel port's `position: relative`, then my
  // desktop `position: sticky`), so the drawer stayed IN FLOW on a phone and kept its ~335px of
  // flex width -- squashing the main column to 64px and pushing 149px off-screen. Measured.
  for (const [vw, vh] of [[390, 844], [360, 740]]) {
    const m = await b.newPage({ viewport: { width: vw, height: vh }, isMobile: true, hasTouch: true });
    await m.goto(URL, { waitUntil: 'networkidle' });
    await m.waitForTimeout(700);
    const d = await m.evaluate(() => {
      const de = document.documentElement;
      const main = document.querySelector('.sb-main');
      const chat = document.getElementById('tcg-chat-panel');
      const wide = [...document.querySelectorAll('body *')]
        .filter(e => e.getBoundingClientRect().right > de.clientWidth + 1)
        .slice(0, 3).map(e => e.tagName.toLowerCase() + '.' + (e.className || '').toString().split(' ')[0]);
      return {
        vw: de.clientWidth,
        overflow: de.scrollWidth - de.clientWidth,
        wide,
        mainW: Math.round(main.getBoundingClientRect().width),
        chatPos: getComputedStyle(chat).position,
        submitW: Math.round((document.getElementById('submit') || {}).getBoundingClientRect?.().width || 0),
      };
    });
    const tag = `${name} @${vw}`;
    if (d.overflow > 1) bad(tag, `page scrolls sideways by ${d.overflow}px (${d.wide.join(', ')})`); else ok();
    // ⚠ This used to assert `position: fixed` -- that the panel stayed an off-canvas drawer. The
    // owner then asked for it inline at the bottom instead, so that check was removed rather than
    // the layout bent to keep it green. What still matters is below: the panel must not take width
    // from the page, whichever way it is positioned.
    // the main column should own essentially the whole screen
    if (d.mainW < d.vw * 0.9) bad(tag, `the main column is only ${d.mainW}px of ${d.vw}px`); else ok();
    if (d.submitW < 100) bad(tag, `Submit is only ${d.submitW}px wide`); else ok();

    // Owner, 2026-09-26: "i would prefer it go to the bottom. below the submit/ready button."
    // ChatPanel's mobile default is an off-canvas drawer with a floating launcher -- right for a
    // game board that must own the viewport, wrong for a scrolling page. Here the panel is simply
    // the last block on the page.
    const bottom = await m.evaluate(() => {
      const e = document.getElementById('tcg-chat-panel');
      const t = document.getElementById('tcg-chat-toggle');
      const sub = document.getElementById('submit');
      const r = e.getBoundingClientRect(), sr = sub.getBoundingClientRect();
      const de = document.documentElement;
      return { pos: getComputedStyle(e).position, top: Math.round(r.top), w: Math.round(r.width),
               submitBottom: Math.round(sr.bottom), vw: de.clientWidth,
               onScreen: r.right > 0 && r.left < de.clientWidth,
               toggle: !!(t && t.getClientRects().length) };
    });
    if (bottom.pos === 'fixed') bad(tag, 'the log panel is still an off-canvas drawer'); else ok();
    if (!bottom.onScreen) bad(tag, 'the log panel is off-screen'); else ok();
    if (bottom.top < bottom.submitBottom - 1)
      bad(tag, `the log panel (y=${bottom.top}) is not below Submit (y=${bottom.submitBottom})`);
    else ok();
    if (bottom.w < bottom.vw * 0.8) bad(tag, `the log panel is only ${bottom.w}px of ${bottom.vw}px`); else ok();
    if (bottom.toggle) bad(tag, 'the floating chat launcher is still shown'); else ok();
    await m.close();
  }

  if (errs.length) bad(name, `pageerror ${errs[0]}`);
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} sideboard checks failed` : `\nSIDEBOARD REWORKED — ${checks} checks`);
process.exit(fails ? 1 : 0);
