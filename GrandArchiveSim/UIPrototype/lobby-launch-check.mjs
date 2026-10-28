import assert from 'node:assert/strict';
const { chromium, firefox, webkit } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
for (const engine of [chromium, firefox, webkit]) {
 const browser = await engine.launch({headless:true});
 try {
  const page = await browser.newPage();
  let revision = 1, actions = 0;
  const credentials = {gameName:'123', playerID:'1', authKey:'fixture-key'};
  const projection = () => ({revision, viewerSeat:1, turn:1, turnPlayer:1, players:[], commands:[{id:'pass',label:'Pass once',mode:'100',params:'command=pass'}]});
  await page.route('**/UIState.php', async route => {
   assert.deepEqual(Object.fromEntries(new URLSearchParams(route.request().postData())), credentials);
   await route.fulfill({json:projection()});
  });
  await page.route('**/UIAction.php', async route => {
   const body = new URLSearchParams(route.request().postData());
   for (const [key,value] of Object.entries(credentials)) assert.equal(body.get(key), value);
   actions++; revision++;
   await route.fulfill({json:{success:true}});
  });
  await page.goto((process.env.GA_REACT_URL || 'http://127.0.0.1:3200/TCGEngine/GrandArchiveSim/UIPrototype/') + '#' + new URLSearchParams(credentials));
  for (let attempt=0; attempt<2; attempt++) {
   if (attempt) await page.reload();
   await page.getByRole('button',{name:'Pass once',exact:true}).click();
   await page.getByText(`Revision ${attempt + 2}`,{exact:false}).waitFor();
   await page.getByRole('button',{name:'Pass once',exact:true}).waitFor();
   assert.equal(await page.getByRole('alert').count(),0);
  }
  assert.equal(actions,2);
  console.log(`${engine.name()}: lobby launch and reload preserve action credentials`);
 } finally { await browser.close(); }
}
