import {execFileSync} from 'node:child_process';
import assert from 'node:assert/strict';
const {chromium, firefox, webkit} = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const markup = execFileSync('php', ['GrandArchiveSim/Custom/RevealHistory.php'], {encoding:'utf8'});
for (const [name, engine] of Object.entries({chromium, firefox, webkit})) {
    const browser = await engine.launch();
    try {
        const page = await browser.newPage({viewport:{width:390,height:844}});
        await page.route('http://localhost/**', route => route.fulfill({contentType:'text/html',body:'<!doctype html><script>window.showCardReveal=function(){};window.showCardRevealMultiple=function(){};window.nameData={abc:"Test card",def:"Other card"};</script>'+markup}));
        const url = 'http://localhost/TCGEngine/NextTurn.php?gameName=history-test&playerID=1';
        await page.goto(url);
        await page.evaluate(() => {showCardReveal('abc');showCardRevealMultiple(['abc','def']);showCardReveal('abc');});
        await page.locator('summary').click();
        assert.equal(await page.locator('#ga-reveals-list section').count(),3);
        assert.equal(await page.locator('#ga-reveals-list button').count(),4);
        await page.locator('#ga-reveals-list button').first().click();
        assert.equal(await page.locator('#ga-reveals-count').textContent(), '(3)', 'Review must not create a reveal');
        await page.reload(); await page.locator('summary').click();
        assert.equal(await page.locator('#ga-reveals-list section').count(),3,'History survives refresh');
        const bounds = await page.locator('#ga-reveals-panel').boundingBox();
        assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= 390);
        await page.goto(url.replace('playerID=1','playerID=2')); await page.locator('summary').click();
        assert.equal(await page.locator('#ga-reveals-list section').count(),0,'Seats remain separate');
        await page.goto(url.replace('history-test','other-game')); await page.locator('summary').click();
        assert.equal(await page.locator('#ga-reveals-list section').count(),0,'Games remain separate');
        console.log(name+': recording, repeats, review, reload, isolation, mobile width passed');
    } finally {await browser.close();}
}
