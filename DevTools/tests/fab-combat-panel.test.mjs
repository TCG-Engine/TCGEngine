import {test} from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../FaBSim/Custom/CombatPanel.php', import.meta.url), 'utf8').split('<script>')[1].split('</script>')[0];
function setup() {
  const saved = new Map();
  const window = {innerWidth:1000, innerHeight:700, PriorityPlayerData:3, addEventListener() {}};
  const context = vm.createContext({window, document:{getElementById:() => ({value:'3'})}, localStorage:{getItem:key => saved.get(key) || null, setItem:(key,value) => saved.set(key,value)}});
  vm.runInContext(source, context);
  return {window, saved};
}

test('phase updates are scoped to their panel and track priority for UPF seats', () => {
  const {window} = setup();
  const nodes = ['LAYER','ATTACK','DEFEND','REACTION','DAMAGE','RESOLUTION','CLOSE'].map(fabStep => {
    const classes = new Set();
    return {dataset:{fabStep}, classes, classList:{toggle:(name,on) => on ? classes.add(name) : classes.delete(name)}};
  });
  const progress = {dataset:{}, setAttribute() {}};
  const panel = {dataset:{}, querySelectorAll:() => nodes, querySelector:() => progress};
  window.FaBUpdateCombatProgress(panel, {combatStep:'REACTION'});
  assert.equal(nodes[3].classes.has('is-active'), true);
  assert.equal(nodes[2].classes.has('is-complete'), true);
  assert.equal(nodes[4].classes.size, 0);
  assert.equal(progress.dataset.priority, 'self');
  window.PriorityPlayerData = 4;
  window.FaBUpdateCombatProgress(panel, {});
  assert.equal(progress.dataset.priority, 'opponent');
  assert.ok(nodes.every(node => node.classes.size === 0));
});

test('shared drag preserves centered position, clamps movement, saves and restores coordinates', () => {
  const {window, saved} = setup();
  const handlers = {};
  const handle = {dataset:{}, style:{}, addEventListener:(name,fn) => handlers[name] = fn, setPointerCapture() {}};
  const panel = {id:'combat', hidden:false, dataset:{}, style:{}, getBoundingClientRect:() => ({left:300,top:200,width:400,height:200})};
  window.FaBMakeDraggablePanel(panel, handle);
  handlers.pointerdown({button:0, target:{closest:() => null}, clientX:350, clientY:220, pointerId:1, preventDefault() {}});
  handlers.pointermove({clientX:400,clientY:250});
  assert.equal(panel.style.left, '350px');
  assert.equal(panel.style.top, '230px');
  assert.equal(panel.style.transform, 'none');
  handlers.pointerup();
  assert.deepEqual(JSON.parse(saved.get('combat-position')), {left:350,top:230});
  const restored = {...panel, dataset:{}, style:{}};
  window.FaBMakeDraggablePanel(restored, {...handle,dataset:{}});
  assert.equal(restored.style.left, '350px');
  window.FaBClampPanel(panel, 2000, -100);
  assert.equal(panel.style.left, '592px');
  assert.equal(panel.style.top, '8px');
});
