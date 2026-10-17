import assert from 'node:assert/strict';
import {createServer} from 'node:http';
import {readFile} from 'node:fs/promises';
import {createRequire} from 'node:module';
const require=createRequire(import.meta.url),{chromium,firefox,webkit}=require('../ui-harness/node_modules/playwright');
const server=createServer(async(req,res)=>{
    try{
        const path=new URL(req.url,'http://localhost').pathname;
        if(path.includes('..')||!/^\/[\w./-]+$/.test(path))throw Error('Invalid path');
        const data=await readFile(new URL('.'+path,import.meta.url));
        res.setHeader('Content-Type',path.endsWith('.mjs')?'text/javascript':path.endsWith('.json')?'application/json':'text/html');res.end(data);
    }catch{res.statusCode=404;res.end('Not found');}
});
await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
// Optional real-host base catches web-server configuration issues that the
// temporary server's explicit MIME mappings cannot reproduce.
const base=process.argv[3]??`http://127.0.0.1:${server.address().port}`;
try {
    for(const [name,engine] of Object.entries({chromium,firefox,webkit}).filter(([name])=>!process.argv[2]||process.argv[2].split(',').includes(name))) {
        const browser=await engine.launch({headless:true});
        try {
            for(const width of [1440,390]) {
                const page=await browser.newPage({viewport:{width,height:1000}}),errors=[];
                page.on('pageerror',e=>errors.push(e.message));
                await page.goto(base+'/results/bloodrush-v2/explorer.html');
                await page.locator('#rows tr').last().waitFor();
                assert.equal(await page.locator('#rows tr').count(),37);
                await page.locator('#selected').selectOption('28');
                assert.match(await page.locator('#stats').innerText(),/17.63/);
                assert.match(await page.locator('#stats').innerText(),/Mean full-cycle value/);
                await page.locator('#metric').selectOption('block');
                assert.match(await page.locator('#chart').textContent(),/Pre-turn block value/);
                await page.locator('#metric').selectOption('damage');
                assert.match(await page.locator('#chart').textContent(),/Unblocked damage/);
                await page.locator('#metric').selectOption('cycle');
                await page.locator('#group').selectOption('3');
                assert.match(await page.locator('#detailNote').innerText(),/turns play 0 \/ 1 \/ 2 \/ 3/);
                assert.ok(await page.locator('#histogram rect').count()>0);
                await page.locator('#bellows').fill('1');
                await page.locator('#size').fill('30');
                await page.locator('#run').click();
                await page.waitForFunction(()=>document.getElementById('status').textContent.startsWith('Exact sweep complete'));
                assert.equal(await page.locator('#rows tr').count(),9);
                assert.equal(await page.locator('#chart circle').count(),0);
                assert.match(await page.locator('#stats').innerText(),/—/);
                await page.locator('#group').selectOption('all');
                assert.equal(await page.locator('#chart circle').count(),9);
                await page.locator('#arsenal').selectOption('1');
                await page.locator('#run').click();
                await page.waitForFunction(()=>document.getElementById('status').textContent.startsWith('Exact sweep complete'));
                assert.match(await page.locator('#detailNote').innerText(),/Bellow starts in arsenal/);
                assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),false);
                assert.deepEqual(errors,[]);
                if(name==='chromium'&&width===1440){await page.locator('#size').fill('60');await page.locator('#bellows').fill('3');await page.locator('#arsenal').selectOption('0');await page.locator('#run').click();await page.waitForFunction(()=>document.getElementById('status').textContent.startsWith('Exact sweep complete · 37 compositions'));assert.equal(await page.locator('#rows tr').count(),37);await page.locator('#selected').selectOption('23');await page.screenshot({path:new URL('./results/bloodrush-v2/preview.png',import.meta.url).pathname.replace(/^\/(\w:)/,'$1'),fullPage:true});}
                await page.close();
            }
            console.log(`PASS ${name}: cycle/damage/block metrics, arsenal mode, worker sweep, filters, desktop/mobile layout`);
        } finally {await browser.close();}
    }
} finally {await new Promise(resolve=>server.close(resolve));}
