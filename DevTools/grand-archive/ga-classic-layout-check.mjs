import { execFileSync } from 'node:child_process';
const { chromium, firefox, webkit } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const html = execFileSync('php', ['GrandArchiveSim/Custom/GameLayoutMobile.php'], { encoding: 'utf8' });
for(const [name,engine] of Object.entries({chromium,firefox,webkit})) {
 const browser=await engine.launch();
 for(const width of [320,390,740]) {
  const page=await browser.newPage({viewport:{width,height:width===740?390:844}});
  await page.setContent(html);
  await page.evaluate(()=>{
   for(const zone of ['myField','theirField','myHand']) {
    const slot=document.getElementById(zone+'Slot');
    slot.innerHTML=`<div id="${zone}Wrapper"><div id="${zone}">${Array.from({length:18},(_,i)=>`<span id="${zone}-${i}"><a><img style="height:80px;width:57px" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='57' height='80'%3E%3Crect width='57' height='80' fill='teal'/%3E%3C/svg%3E"></a></span>`).join('')}</div></div>`;
   }
  });
  await page.waitForTimeout(100);
  const result=await page.evaluate(()=>{
   const wrapper=document.getElementById('myFieldWrapper');
   const hand=document.getElementById('myHandWrapper');
   const size=document.getElementById('myField-0').getBoundingClientRect().width;
   wrapper.scrollLeft=10000;hand.scrollLeft=10000;
   window.mutations=0;new MutationObserver(m=>window.mutations+=m.length).observe(document.getElementById('myField'),{attributes:true,subtree:true});
   return {size,fieldScroll:wrapper.scrollLeft,handScroll:hand.scrollLeft};
  });
  await page.waitForTimeout(1250);
  const idle=await page.evaluate(()=>window.mutations);
  if(result.size<88 || result.fieldScroll<=0 || result.handScroll<=0 || idle!==0) throw new Error(JSON.stringify({name,width,result,idle}));
  console.log(name,width,JSON.stringify(result),'idle mutations',idle);
  await page.close();
 }
 await browser.close();
}
