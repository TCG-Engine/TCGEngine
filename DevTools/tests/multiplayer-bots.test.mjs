import {test} from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../../Core/jsInclude.js',import.meta.url),'utf8');
test('browser bot state accepts seats three and four and rejects invalid seats',()=>{
  const context={window:{}};vm.createContext(context);
  vm.runInContext(source.slice(source.indexOf('function SetBotControllerState('),source.indexOf('function ScheduleBotControllerRetry(')),context);
  for(const seat of [1,2,3,4]){
    context.SetBotControllerState({enabled:true,players:[0,1,2,3,4,4,5],pendingPlayer:seat,mode:'bot'});
    assert.deepEqual(Array.from(context.window.BotController.players),[1,2,3,4]);
    assert.equal(context.window.BotController.pendingPlayer,seat);
    assert.equal(context.window.BotController.enabled,true);
  }
  context.SetBotControllerState({enabled:true,players:[3,4],pendingPlayer:1});
  assert.equal(context.window.BotController.pendingPlayer,0);
});
