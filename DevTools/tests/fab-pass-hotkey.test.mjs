import {test} from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const ui = fs.readFileSync(new URL('../../Core/UILibraries20260918.js', import.meta.url), 'utf8');
function setup() {
  const calls = [];
  const buttons = [];
  const document = {activeElement: null, getElementById: () => null, querySelectorAll(selector) {
    assert.equal(selector, '#myActionPointsSlot .widget-button-pass, #fab-upf-own .widget-button-pass');
    return buttons;
  }};
  const window = {rootPath: './FaBSim', getComputedStyle: button => ({visibility: button.visibility || 'visible'})};
  const context = vm.createContext({window, document, SubmitInput: (...args) => calls.push(args), ClearSelectionMode() {window.SelectionMode = null;}});
  vm.runInContext(ui.slice(ui.indexOf('function Hotkeys('), ui.indexOf('function ProcessInputLink(')), context);
  const button = (visible = true) => ({getAttribute: () => null, getClientRects: () => visible ? [{}] : [], click: () => calls.push('pass')});
  const press = (extra = {}) => context.Hotkeys({key: ' ', preventDefault() {}, ...extra});
  return {window, document, buttons, button, press, calls};
}

test('Space uses the visible Pass action in duel and skips the hidden duel layout in UPF', () => {
  for (const upf of [false, true]) {
    const s = setup();
    if (upf) s.buttons.push(s.button(false));
    s.buttons.push(s.button());
    s.press();
    assert.deepEqual(s.calls, ['pass']);
  }
});

test('Space preserves optional decision passing and cannot bypass a required selection', () => {
  const s = setup();
  s.buttons.push(s.button());
  s.window.SelectionMode = {active: true, mode: '100', mayPass: false, decisionIndex: 4};
  s.press();
  assert.deepEqual(s.calls, []);
  s.window.SelectionMode.mayPass = true;
  s.press();
  assert.deepEqual(s.calls, [['DECISION', '&decisionIndex=4&cardID=PASS']]);
});

test('Space ignores typing, repeats, disabled and invisible buttons, and other games', () => {
  const s = setup();
  const button = s.button();
  s.buttons.push(button);
  s.document.activeElement = {tagName: 'TEXTAREA'};
  s.press();
  s.document.activeElement = null;
  s.press({repeat: true});
  button.disabled = true;
  s.press();
  button.disabled = false;
  button.visibility = 'hidden';
  s.press();
  button.visibility = 'visible';
  s.window.rootPath = './AzukiSim';
  s.press();
  assert.deepEqual(s.calls, []);
});
