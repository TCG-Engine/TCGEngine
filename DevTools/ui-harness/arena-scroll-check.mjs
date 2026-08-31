// Does a 15-unit arena column expose every unit? Measures the real board, both engines.
import { chromium, firefox } from 'playwright';
const BASE='http://localhost:3400/TCGEngine/';
const SCHEMA = process.argv[2];
const results=[]; let ok=true;
const t=(e,n,c,x)=>{ if(!c) ok=false; results.push([e,n,!!c,x]); };

for (const [name, engine] of Object.entries({chromium, firefox})) {
  const b = await engine.launch();
  try {
    const p = await (await b.newContext({viewport:{width:1600,height:900}})).newPage();
    await p.goto(BASE+'SharedUI/LoginPage.php',{waitUntil:'domcontentloaded'});
    await p.fill('input[name="userID"]','claudebot1'); await p.fill('input[name="password"]','pass');
    await Promise.all([p.waitForNavigation({waitUntil:'load'}).catch(()=>{}),p.click('button[type="submit"]')]);
    await p.goto(BASE+`NextTurn.php?gameName=${SCHEMA}&playerID=1&folderPath=SWUSim`,{waitUntil:'load'});
    await p.waitForTimeout(3500);
    const m = await p.evaluate(() => {
      const col = document.querySelector('.swu-arena-col-bot.swu-arena-col-ground')
               || document.querySelector('.swu-arena-col-ground');
      const zone = document.querySelector('#myGroundArena');
      if (!col || !zone) return null;
      const cs = getComputedStyle(col);
      const units = Array.from(zone.children).filter(e=>e.id && e.id.indexOf('myGroundArena-')===0);
      const cr = col.getBoundingClientRect();
      // ⚠ DO NOT judge reachability from scrollHeight — it reports the FULL content height even when
      // the box is overflow:hidden, so a clipped column looks identical to a scrollable one and the
      // assertion cannot fail. (Caught by mutation: reverting to overflow:hidden left this green.)
      // Actually TRY to scroll, then count units whose box never enters the client area.
      const before = col.scrollTop;
      col.scrollTop = 99999;
      const canScroll = col.scrollTop > before;
      const maxScroll = col.scrollTop;
      col.scrollTop = before;
      const unreachable = units.filter(u=>{
        const r = u.getBoundingClientRect();
        const topInContent = (r.top - cr.top) + col.scrollTop;   // position within the scrollable content
        // Reachable if some scroll offset in [0, maxScroll] brings it inside the client box.
        return topInContent > col.clientHeight + maxScroll + 1;
      }).length;
      return { overflowY: cs.overflowY, overflowX: cs.overflowX,
               scrollH: col.scrollHeight, clientH: col.clientHeight,
               scrollable: col.scrollHeight > col.clientHeight + 1, canScroll, maxScroll,
               units: units.length, unreachable };
    });
    if (!m) { t(name,'found the ground arena column',false); continue; }
    t(name,'overflow-y is auto (scrollable axis)', m.overflowY==='auto', m.overflowY);
    t(name,'overflow-x stayed hidden (no forced auto)', m.overflowX==='hidden', m.overflowX);
    t(name,'all 15 units present', m.units===15, m.units);
    t(name,'content genuinely overflows the column', m.scrollable, `${m.scrollH} > ${m.clientH}`);
    // ⚠ The ONLY behavioural difference between overflow:hidden and :auto is USER scrolling — a
    // hidden box is still programmatically scrollable (setting scrollTop works and reports a non-zero
    // max), so a JS-driven scroll check passes under both and cannot fail. Drive the real gesture.
    const wheeled = await p.evaluate(() => {
      const c = document.querySelector('.swu-arena-col-bot.swu-arena-col-ground')
             || document.querySelector('.swu-arena-col-ground');
      // ⚠ Aim at a UNIT, not the column's corner. The column carries ~22px of padding plus an 8px
      // rotation bleed, so left+40/top+40 lands on non-hit-testable padding and the synthetic wheel
      // goes nowhere — which reads as "the fix doesn't work" when it does.
      if (!c) return null;
      c.scrollTop = 0;
      // Aim at a MID-column unit: the first one sits at the very top edge, where the synthetic wheel
      // was landing outside the scrollable area (verified separately — wheeling over unit 5 scrolls
      // the column to its 178px maximum).
      const us = c.querySelectorAll('[id^="myGroundArena-"]');
      const u  = us[Math.min(5, Math.max(0, us.length - 1))];
      const r = (u || c).getBoundingClientRect();
      return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
    });
    if (wheeled) {
      // ⚠ Firefox needs the pointer to settle and more than one tick before it routes a synthetic
      // wheel to a nested scroller; a single 400px wheel lands as 0 there while Chromium takes it.
      await p.mouse.move(wheeled.x, wheeled.y);
      await p.waitForTimeout(250);
      await p.mouse.move(wheeled.x + 1, wheeled.y + 1);
      for (let w = 0; w < 4; w++) { await p.mouse.wheel(0, 200); await p.waitForTimeout(200); }
      await p.waitForTimeout(400);
      const after = await p.evaluate(() => {
        const c = document.querySelector('.swu-arena-col-bot.swu-arena-col-ground')
               || document.querySelector('.swu-arena-col-ground');
        return c ? c.scrollTop : -1;
      });
      t(name,'the user can scroll it with the wheel', after > 0, 'scrollTop after wheel = '+after);
    }
    t(name,'every unit is reachable by scrolling', m.unreachable===0, m.unreachable+' unreachable');
  } catch(e){ t(name,'ran without throwing',false,String(e).slice(0,90)); }
  finally { await b.close(); }
}
for (const [e,n,pass,x] of results) console.log(`${pass?'ok  ':'FAIL'}  [${e}] ${n}${x!==undefined?'  ['+x+']':''}`);
console.log(ok?'PASS':'FAIL'); process.exit(ok?0:1);
