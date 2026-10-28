const { chromium, firefox, webkit } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
import assert from 'node:assert/strict';
for (const engine of [chromium, firefox, webkit]) {
const browser=await engine.launch({headless:true});
try {
for(const [width,height] of [[320,568],[390,844],[740,390],[1280,900]]){
 const page=await browser.newPage({viewport:{width,height}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto(process.env.GA_REACT_URL || 'http://127.0.0.1:3211');
 await page.getByRole('heading',{name:'Your action',exact:true}).waitFor();
 assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
 if(width<=740){
  const dock=page.getByRole('region',{name:'Response controls'});
  let bounds=await dock.boundingBox();
  assert.ok(Math.abs(bounds.y+bounds.height-height)<2, 'dock touches viewport bottom');
  await page.getByRole('button',{name:'Response preferences',exact:true}).click();
  await page.getByRole('checkbox').uncheck();
  await page.getByRole('checkbox').check();
  await page.getByRole('button',{name:'Response preferences',exact:true}).click();
  await page.evaluate(()=>window.scrollTo(0,document.documentElement.scrollHeight));
  bounds=await dock.boundingBox();
  const probe=await page.getByRole('button',{name:'Simulate unrelated update'}).boundingBox();
  assert.ok(probe.y+probe.height<=bounds.y, 'last content scrolls above dock');
 }
 await page.getByRole('button',{name:'Forese Your field · Inspect'}).click();
 await page.getByRole('dialog').waitFor();
 await page.keyboard.press('Escape');
 assert.equal(await page.getByRole('dialog').count(),0);
 await page.getByRole('button',{name:'Melody Hand · Inspect'}).click();
 await page.getByRole('button',{name:'Play Melody',exact:true}).click();
 await page.getByRole('heading',{name:'Use Forese before resolution?'}).waitFor();
 await page.getByRole('button',{name:'Simulate unrelated update'}).click();
 await page.getByRole('button',{name:'Activate Forese',exact:true}).click();
 await page.getByRole('heading',{name:'Opponent responding'}).waitFor();
 assert.equal(await page.getByRole('button',{name:'Pass once'}).isDisabled(),true);
 await page.getByRole('button',{name:'Reset',exact:true}).click();
 await page.getByRole('combobox').selectOption('attack');
 await page.getByRole('heading',{name:'Respond to the attack'}).waitFor();
 await page.screenshot({path:`/tmp/ga-react-${width}.png`,fullPage:true});
 await page.getByRole('button',{name:'Open live viewer',exact:true}).click();
 await page.route('**/UIState.php', async route => {
  assert.equal(route.request().method(), 'POST');
  assert.ok(!route.request().url().includes('fixture-key'));
  await route.fulfill({json: {revision: 1, viewerSeat: 1, turn: 2, turnPlayer: 1, players: [
   {seat: 1, damage: 3, zones: {Hand: {count: 1, cards: [{id:'one', cardID:'fixture',name:'Visible fixture card',printedText:'Board reference <img src=x onerror=alert(1)>'}]}}},
   {seat: 2, damage: 4, zones: {Hand: {count: 4, cards: []}}}
  ], effectStack: [{id:'EffectStack-2',cardID:'effect',name:'Public pending card',printedText:'On Enter: Draw a card. <img src=x onerror=alert(1)>',controller:2,kind:'Triggered ability',triggerLabel:'On Hit'}], choices: [{id:'one', cardID:'fixture', name:'Visible choice', owner:1, zone:'Hand'}], commands: []}});
 });
 await page.getByLabel('Game number').fill('123');
 await page.getByLabel('Match access key').fill('fixture-key');
 await page.getByRole('button',{name:'Connect',exact:true}).click();
 await page.getByText('Visible fixture card',{exact:true}).waitFor();
 await page.getByRole('button',{name:'Inspect Visible fixture card',exact:true}).click();
 await page.getByRole('dialog').getByText('Board reference <img src=x onerror=alert(1)>',{exact:true}).waitFor();
 assert.equal(await page.getByRole('dialog').locator('img').count(),0);
 await page.keyboard.press('Escape');
 assert.equal(await page.getByRole('button',{name:'Inspect Visible fixture card',exact:true}).evaluate(el => el === document.activeElement),true);
 await page.getByRole('region',{name:'Pending effects'}).getByText('Public pending card',{exact:true}).waitFor();
 await page.getByText('Triggered ability · On Hit · Player 2 · Top of stack', {exact:false}).waitFor();
 await page.getByText('Printed card text',{exact:true}).click();
 await page.getByText('On Enter: Draw a card. <img src=x onerror=alert(1)>',{exact:true}).waitFor();
 if(await page.getByRole('region',{name:'Pending effects'}).locator('img').count()) throw new Error('Printed text interpreted as HTML');
 await page.getByText('Hand · 4 cards',{exact:true}).click();
 await page.getByText('4 hidden cards',{exact:true}).waitFor();
 await page.getByRole('region',{name:'Available card choices'}).getByText('Visible choice',{exact:true}).waitFor();
 assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
 await page.getByRole('button',{name:'Disconnect',exact:true}).click();
 assert.equal(await page.getByText('Visible fixture card',{exact:true}).count(),0);
 // Exercise every new form family, including explicit confirmation and wire values.
 await page.unroute('**/UIState.php');
 let formRevision = 10, formDecision = null, submitted;
 await page.route('**/UIState.php', route => route.fulfill({json:{revision:formRevision,viewerSeat:1,turn:3,turnPlayer:1,players:[],controls:formDecision ? {decision:formDecision} : {},commands:formDecision ? [{id:'answer-decision',label:'Confirm choice',mode:'100',params:'command=answer-decision'}] : []}}));
 await page.route('**/UIAction.php', async route => {
  submitted = JSON.parse(new URLSearchParams(route.request().postData()).get('answer'));
  formRevision++; formDecision=null;
  await route.fulfill({json:{success:true}});
 });
 for (const example of [
  {type:'MZMODAL',minimum:1,maximum:2,options:[{id:'0',label:'Draw'},{id:'1',label:'Heal'}], expected:['0'],fill: async () => page.getByLabel('Draw',{exact:true}).check()},
  {type:'MZMULTICHOOSE',minimum:1,maximum:2,options:[{id:'0',label:'First card'},{id:'1',label:'Second card'}],expected:['1'],fill: async () => page.getByLabel('Second card',{exact:true}).check()},
  {type:'NUMBERCHOOSE',minimum:0,maximum:4,options:[],expected:'3',fill: async () => page.getByRole('spinbutton').fill('3')},
  {type:'NAMECARD',options:[],expected:'Fireball',fill: async () => page.getByLabel('Card name',{exact:true}).fill('Fireball')},
  {type:'MZSPLITASSIGN',amount:3,options:[{id:'0',label:'Target one'}],expected:{'0':3},fill: async () => page.getByRole('spinbutton').fill('3')},
  {type:'MZREARRANGE',piles:[{id:'0',label:'Top'},{id:'1',label:'Bottom'}],options:[{id:'0',label:'First card',pile:'0'}],expected:{'0':[],'1':['0']},fill: async () => page.getByRole('button',{name:'Move to Bottom',exact:true}).click()}
 ]) {
  formDecision = {...example,prompt:'Test choice'}; formRevision++; submitted=undefined;
  await page.getByLabel('Game number').fill('123');
  await page.getByLabel('Match access key').fill('fixture-key');
  await page.getByRole('button',{name:'Connect',exact:true}).click();
  await page.getByRole('heading',{name:'Test choice',exact:true}).waitFor();
  await example.fill();
  assert.equal(submitted,undefined,'editing never submits');
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  const response=page.waitForResponse(r=>r.url().endsWith('/UIAction.php'));
  await page.getByRole('button',{name:'Confirm choice',exact:true}).click();
  await response;
  assert.deepEqual(submitted,example.expected);
  await page.getByRole('button',{name:'Disconnect',exact:true}).click();
 }
 await page.getByRole('button',{name:'Return to simulated preview',exact:true}).click();
 assert.deepEqual(errors,[]);console.log(`${engine.name()} ${width}px: layout, inspection, reminder, activation, waiting and reset passed`);await page.close();
}
}finally{await browser.close();}

}
