import {mkdirSync,readFileSync,writeFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {analyze,defaults,VERSION} from './bloodrush-model.mjs';
const dir=new URL(`./results/bloodrush-v${VERSION}/`,import.meta.url);
mkdirSync(dir,{recursive:true});
const rows=[];
for(let red=0;red<=defaults.size-defaults.bellows-defaults.blue6Block-defaults.blue6Nonblock;red++) {
    rows.push(analyze(red));console.log(`${red} red / ${rows.at(-1).blue5} blue five: ${rows.at(-1).all.mean.toFixed(3)} cycle value`);
}
const data={version:VERSION,generatedAt:new Date().toISOString(),config:defaults,rows};
writeFileSync(new URL('data.json',dir),JSON.stringify(data));
const csv=['reds,blue5,bellowsPlayed,probability,cycleMean,cycleMin,cycleMax,cyclePopulationSD,damageMean,blockMean,failedDiscardRate'];
for(const row of rows)for(const [group,s] of [['all',row.all],...row.groups.map((s,n)=>[n,s])])csv.push([row.reds,row.blue5,group,s.probability,s.mean,s.min,s.max,s.sd,s.damage.mean,s.block.mean,s.failedRate].join(','));
writeFileSync(new URL('summary.csv',dir),csv.join('\n')+'\n');
const html=readFileSync(new URL('./bloodrush.html',import.meta.url),'utf8').replace('src="./bloodrush-app.mjs','src="../../bloodrush-app.mjs').replace('<!-- RESULTS -->',`<script id="savedResults" type="application/json">${JSON.stringify(data)}</script>`);
writeFileSync(new URL('explorer.html',dir),html);
// Keep existing bookmarked explorer URLs functional when the model advances.
const previous=new URL('./results/bloodrush-v1/explorer.html',import.meta.url);
mkdirSync(new URL('./results/bloodrush-v1/',import.meta.url),{recursive:true});
writeFileSync(previous,html);
writeFileSync(new URL('data.json',previous),JSON.stringify(data));
writeFileSync(new URL('summary.csv',previous),csv.join('\n')+'\n');
console.log(`Saved ${fileURLToPath(dir)}`);
