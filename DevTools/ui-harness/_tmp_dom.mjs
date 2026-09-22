import { chromium } from 'playwright';
const B = 'http://localhost:3400/TCGEngine/';
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1440, height: 900 } }); const p = await ctx.newPage();
const dump = async (label) => console.log('=== ' + label + '\n' + await p.evaluate(() => {
  // visible "panel-like" boxes: have a background or border and are reasonably large, outside the header/nav
  const out = [];
  document.querySelectorAll('body *').forEach(e => {
    if (e.closest('.home-header, .nav-bar, .burger-menu, .menu-overlay, script, style')) return;
    const c = getComputedStyle(e), r = e.getBoundingClientRect();
    const painted = (c.backgroundColor !== 'rgba(0, 0, 0, 0)' || c.backdropFilter !== 'none' || (c.borderTopWidth !== '0px' && c.borderTopStyle !== 'none'));
    if (painted && r.width > 250 && r.height > 60 && c.display !== 'none') out.push(`${e.tagName.toLowerCase()}${e.id ? '#' + e.id : ''}.${[...e.classList].join('.')}  ${Math.round(r.width)}x${Math.round(r.height)} bg=${c.backgroundColor} blur=${c.backdropFilter} radius=${c.borderTopLeftRadius}`);
  });
  return out.slice(0, 25).join('\n');
}));
await p.goto(B + 'SharedUI/Sites/SWUSim/LoginPage.php', { waitUntil: 'load' }); await dump('login');
await p.goto(B + 'SharedUI/Sites/SWUSim/Signup.php', { waitUntil: 'load' }); await dump('signup');
await p.goto(B + 'SharedUI/Sites/SWUSim/Previews.php', { waitUntil: 'load' }); await p.waitForTimeout(800); await dump('previews');
await p.goto(B + 'SharedUI/Sites/SWUSim/LoginPage.php'); await p.fill('input[name="userID"]', 'claudebot1'); await p.fill('input[name="password"]', 'pass');
await Promise.all([p.waitForNavigation().catch(()=>{}), p.click('button[type="submit"]')]);
await p.goto(B + 'SharedUI/Sites/SWUSim/Profile.php', { waitUntil: 'load' }); await p.waitForTimeout(800); await dump('profile');
await b.close();
