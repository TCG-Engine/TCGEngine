(function() {
  if (typeof window === 'undefined' || !window.TCGSound) return;

  var rootName = 'AzukiSim';
  var soundBase = './AzukiSim/Assets/Sounds/';
  var soundVersion = encodeURIComponent(String(window.AzukiSoundAssetVersion || '1'));
  function soundFile(fileName) {
    return soundBase + fileName + '?v=' + soundVersion;
  }
  window.TCGSound.registerManifest(rootName, {
    cues: {
      'azuki.card_draw': {
        files: [soundFile('card-draw-1.mp3'), soundFile('card-draw-2.mp3')],
        gain: 0.72,
        cooldownMs: 70
      },
      'azuki.entity_play': {
        files: [soundFile('entity-play-1.mp3'), soundFile('entity-play-2.mp3')],
        gain: 0.82,
        cooldownMs: 90
      },
      'azuki.weapon_equip': {
        files: [soundFile('weapon-equip.mp3')],
        gain: 0.62,
        cooldownMs: 100
      },
      'azuki.portal': {
        files: [soundFile('portal.mp3')],
        gain: 0.82,
        cooldownMs: 120
      },
      'azuki.attack_whoosh': {
        files: [soundFile('attack-whoosh-1.mp3'), soundFile('attack-whoosh-2.mp3')],
        gain: 0.78,
        cooldownMs: 70
      },
      'azuki.damage_light': {
        files: [soundFile('damage-light-1.mp3'), soundFile('damage-light-2.mp3')],
        gain: 0.82,
        cooldownMs: 55
      },
      'azuki.damage_heavy': {
        files: [soundFile('damage-heavy.mp3')],
        gain: 0.9,
        cooldownMs: 75
      },
      'azuki.leader_hit': {
        files: [soundFile('leader-hit.mp3')],
        gain: 0.94,
        cooldownMs: 80
      },
      'azuki.heal': {
        files: [soundFile('heal.mp3')],
        gain: 0.72,
        cooldownMs: 100
      },
      'azuki.destroy': {
        files: [soundFile('damage-heavy.mp3')],
        gain: 0.72,
        cooldownMs: 90
      },
      'azuki.sacrifice': {
        files: [soundFile('sacrifice.mp3')],
        gain: 0.72,
        cooldownMs: 90
      },
      'azuki.bounce': {
        files: [soundFile('bounce.mp3')],
        gain: 0.68,
        cooldownMs: 90
      },
      'azuki.ikz_gain': {
        files: [soundFile('ikz-gain.mp3')],
        gain: 0.66,
        cooldownMs: 90
      },
      'azuki.ikz_spend': {
        files: [soundFile('ikz-spend.mp3')],
        gain: 0.68,
        cooldownMs: 75
      },
      'azuki.turn_start': {
        files: [soundFile('turn-start.mp3')],
        gain: 0.7,
        cooldownMs: 250
      },
      'azuki.victory': {
        files: [soundFile('victory.mp3')],
        gain: 0.82,
        cooldownMs: 1000
      },
      'azuki.defeat': {
        files: [soundFile('defeat.mp3')],
        gain: 0.78,
        cooldownMs: 1000
      },
      'azuki.game_end': {
        files: [soundFile('game-end.mp3')],
        gain: 0.78,
        cooldownMs: 1000
      }
    }
  });
  window.TCGSound.init(rootName);

  function injectStyles() {
    if (document.getElementById('azuki-sound-control-style')) return;
    var style = document.createElement('style');
    style.id = 'azuki-sound-control-style';
    style.textContent = ''
      + '#azukiSoundControls{position:fixed;left:8px;bottom:8px;z-index:12010;display:flex;align-items:center;gap:7px;padding:6px 8px;border:1px solid rgba(226,216,198,.22);border-radius:9px;background:rgba(14,14,16,.94);box-shadow:0 8px 20px rgba(0,0,0,.34);font:700 11px/1 "Segoe UI",sans-serif;color:#f4ede0;}'
      + '#azukiSoundToggle{border:0;background:transparent;color:inherit;padding:2px 3px;cursor:pointer;font:inherit;white-space:nowrap;}'
      + '#azukiSoundVolume{width:82px;accent-color:#b53741;cursor:pointer;}'
      + '.azuki-sound-mobile-row{display:flex!important;align-items:center;gap:7px;padding:7px 9px;border:1px solid rgba(212,175,55,.28);border-radius:7px;background:rgba(18,26,50,.92);color:rgba(255,244,207,.96);}'
      + '.azuki-sound-mobile-row button{width:auto!important;flex:1;border:0!important;padding:1px!important;background:transparent!important;box-shadow:none!important;}'
      + '.azuki-sound-mobile-row input{width:62px;accent-color:#b53741;}'
      + '@media(max-width:1000px){#azukiSoundControls{display:none!important;}}';
    document.head.appendChild(style);
  }

  function bindControls(toggleButton, volumeInput) {
    if (!toggleButton || !volumeInput) return;
    function render() {
      var enabled = window.TCGSound.isEnabled(rootName);
      toggleButton.textContent = enabled ? '\uD83D\uDD0A Sound: On' : '\uD83D\uDD07 Sound: Off';
      toggleButton.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      toggleButton.title = enabled ? 'Mute sound effects' : 'Enable sound effects';
      volumeInput.value = String(Math.round(window.TCGSound.getVolume(rootName) * 100));
      volumeInput.disabled = !enabled;
    }
    toggleButton.addEventListener('click', function(event) {
      event.preventDefault();
      event.stopPropagation();
      window.TCGSound.toggle(rootName);
      render();
    });
    volumeInput.addEventListener('input', function() {
      window.TCGSound.setVolume(rootName, Number(volumeInput.value) / 100);
    });
    window.addEventListener('tcg-sound-settings-changed', function(event) {
      if (!event.detail || event.detail.rootName === rootName) render();
    });
    render();
  }

  function createDesktopControls() {
    if (document.getElementById('azukiSoundControls')) return;
    var controls = document.createElement('div');
    controls.id = 'azukiSoundControls';
    controls.setAttribute('aria-label', 'Sound effect settings');
    var toggle = document.createElement('button');
    toggle.id = 'azukiSoundToggle';
    toggle.type = 'button';
    var volume = document.createElement('input');
    volume.id = 'azukiSoundVolume';
    volume.type = 'range';
    volume.min = '0';
    volume.max = '100';
    volume.step = '5';
    volume.setAttribute('aria-label', 'Sound effect volume');
    controls.appendChild(toggle);
    controls.appendChild(volume);
    document.body.appendChild(controls);
    bindControls(toggle, volume);
  }

  function createMobileControls() {
    var panel = document.getElementById('azukiAdminMenuPanel');
    if (!panel || document.getElementById('azukiMobileSoundToggle')) return;
    var row = document.createElement('div');
    row.className = 'azuki-sound-mobile-row';
    var toggle = document.createElement('button');
    toggle.id = 'azukiMobileSoundToggle';
    toggle.type = 'button';
    var volume = document.createElement('input');
    volume.id = 'azukiMobileSoundVolume';
    volume.type = 'range';
    volume.min = '0';
    volume.max = '100';
    volume.step = '5';
    volume.setAttribute('aria-label', 'Sound effect volume');
    row.appendChild(toggle);
    row.appendChild(volume);
    panel.insertBefore(row, panel.firstChild);
    bindControls(toggle, volume);
  }

  function installControls() {
    injectStyles();
    createDesktopControls();
    createMobileControls();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', installControls);
  else installControls();
})();
