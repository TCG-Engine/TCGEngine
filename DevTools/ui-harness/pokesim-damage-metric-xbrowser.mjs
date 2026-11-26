import {chromium,firefox,webkit} from 'playwright';
import {readFileSync,mkdirSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import assert from 'node:assert/strict';

const repo=fileURLToPath(new URL('../../',import.meta.url));
const output=path.resolve(repo,'tmp/pokesim-damage-metric');mkdirSync(output,{recursive:true});
const read=file=>readFileSync(path.join(repo,'PokeSim',file),'utf8');
const games=[0,0,300].map((damage,i)=>({status:'complete',damageTurns:[
    {deck:'ceruledge-lantern',turn:1,order:'first',damage,complete:true},
    {deck:'ceruledge-lantern',turn:1,order:'second',damage:[10,20,300][i],complete:true},
    {deck:'brisbane-lopunny',turn:1,order:'first',damage:[20,100,400][i],complete:true},
]}));
for(const [name,engine] of Object.entries({chromium,firefox,webkit})){
    if(process.argv.length>2 && !process.argv.slice(2).includes(name))continue;
    const browser=await engine.launch();
    try{
        const page=await browser.newPage({viewport:{width:1280,height:900}}),errors=[];
        page.on('pageerror',error=>errors.push(error.message));
        await page.setContent('<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body class="menu-page"><section id="chart" class="damage-stats"></section></body></html>');
        for(const file of ['local.css','menu.css','damage-stats.css'])await page.addStyleTag({content:read(file)});
        await page.addScriptTag({content:read('damage-stats.js')});
        await page.evaluate(games=>{window.testGames=games;PokeDamageStats.averages(document.querySelector('#chart'),games);},games);
        const deck=page.getByLabel('Damage statistics deck'),metric=page.getByLabel('Damage statistic',{exact:true});
        assert.equal(await metric.inputValue(),'average');
        await deck.selectOption('ceruledge-lantern');
        assert.match(await page.locator('circle').first().getAttribute('aria-label'),/Average damage: 100\.0/);
        await metric.selectOption('median');
        assert.equal(await deck.inputValue(),'ceruledge-lantern');
        assert.equal(await page.locator('h3').textContent(),'Median damage by deck and starting order');
        assert.match(await page.locator('svg').getAttribute('aria-label'),/^Median damage/);
        const dots=page.locator('circle');
        assert.match(await dots.first().getAttribute('aria-label'),/Median damage: 0\.0\nSamples: 3/);
        assert.match(await dots.nth(1).getAttribute('aria-label'),/Median damage: 20\.0/);
        await dots.first().focus();
        assert.equal(await page.locator('.damage-tooltip').isVisible(),true);
        assert.match(await page.locator('.damage-tooltip').textContent(),/Median damage: 0\.0/);
        await page.keyboard.press('Escape');assert.equal(await page.locator('.damage-tooltip').isVisible(),false);
        await deck.selectOption('all');assert.equal(await metric.inputValue(),'median');
        await page.evaluate(()=>PokeDamageStats.averages(document.querySelector('#chart'),window.testGames));
        assert.equal(await metric.inputValue(),'median');assert.equal(await deck.inputValue(),'all');
        await page.locator('#chart').screenshot({path:path.join(output,name+'-median.png')});
        await metric.selectOption('average');assert.equal(await deck.inputValue(),'all');
        assert.equal(await page.locator('h3').textContent(),'Average damage by deck and starting order');
        await page.setViewportSize({width:390,height:844});await metric.selectOption('median');
        assert.equal(await metric.isVisible(),true);assert.equal(await deck.isVisible(),true);
        assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),true);
        await page.locator('circle').first().click();assert.equal(await page.locator('.damage-tooltip').isVisible(),true);
        await page.locator('#chart').screenshot({path:path.join(output,name+'-mobile-median.png')});
        await page.evaluate(()=>PokeDamageStats.averages(document.querySelector('#chart'),[]));
        assert.equal(await metric.inputValue(),'median');assert.equal(await deck.inputValue(),'all');
        assert.equal(await page.locator('svg').count(),0);assert.deepEqual(errors,[]);
        console.log('PASS '+name+': switching, filters, rerenders, median tooltip, mobile layout, empty data');
    }finally{await browser.close();}
}
