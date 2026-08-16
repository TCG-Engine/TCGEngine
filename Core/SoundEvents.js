// Shared, non-blocking sound-effect runtime for semantic frame events.
// Apps register cue manifests; authoritative gameplay code sends cue names, never asset paths.
(function() {
  if (typeof window === 'undefined' || window.TCGSound) return;

  var manifests = {};
  var audioContext = null;
  var unlocked = false;
  var buffers = {};
  var loading = {};
  var activeSources = [];
  var lastCueAt = {};
  var initializedRoots = {};
  var maxGlobalVoices = 8;

  function normalizeRoot(rootName) {
    return String(rootName || '').replace(/^\.\//, '').replace(/^\//, '').trim();
  }

  function clamp(value, min, max, fallback) {
    var parsed = Number(value);
    if (!Number.isFinite(parsed)) return fallback;
    return Math.max(min, Math.min(max, parsed));
  }

  function getAudioContext() {
    if (audioContext) return audioContext;
    var AudioContextCtor = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextCtor) return null;
    try {
      audioContext = new AudioContextCtor();
    } catch (e) {
      audioContext = null;
    }
    return audioContext;
  }

  function isEnabled(rootName) {
    var root = normalizeRoot(rootName);
    if (!window.TCGSettings) return true;
    return window.TCGSettings.get('EnableSoundEffects', {
      rootName: root,
      type: 'boolean',
      defaultValue: true
    }) !== false;
  }

  function getVolume(rootName) {
    var root = normalizeRoot(rootName);
    if (!window.TCGSettings) return 0.5;
    return clamp(window.TCGSettings.get('SoundEffectsVolume', {
      rootName: root,
      type: 'number',
      defaultValue: 0.5
    }), 0, 1, 0.5);
  }

  function notifySettings(rootName) {
    try {
      window.dispatchEvent(new CustomEvent('tcg-sound-settings-changed', {
        detail: {
          rootName: normalizeRoot(rootName),
          enabled: isEnabled(rootName),
          volume: getVolume(rootName)
        }
      }));
    } catch (e) {}
  }

  function setEnabled(rootName, enabled) {
    var root = normalizeRoot(rootName);
    if (window.TCGSettings) {
      window.TCGSettings.set('EnableSoundEffects', !!enabled, {
        rootName: root,
        type: 'boolean'
      });
    }
    if (enabled) unlock();
    notifySettings(root);
    return !!enabled;
  }

  function toggle(rootName) {
    return setEnabled(rootName, !isEnabled(rootName));
  }

  function setVolume(rootName, volume) {
    var root = normalizeRoot(rootName);
    var normalized = clamp(volume, 0, 1, 0.5);
    if (window.TCGSettings) {
      window.TCGSettings.set('SoundEffectsVolume', normalized, {
        rootName: root,
        type: 'number'
      });
    }
    notifySettings(root);
    return normalized;
  }

  function hashString(value) {
    var hash = 2166136261;
    var text = String(value || '');
    for (var i = 0; i < text.length; ++i) {
      hash ^= text.charCodeAt(i);
      hash = Math.imul(hash, 16777619);
    }
    return hash >>> 0;
  }

  function cueDefinition(rootName, cue) {
    var manifest = manifests[normalizeRoot(rootName)] || {};
    var cues = manifest.cues || manifest;
    return cues && cues[cue] ? cues[cue] : null;
  }

  function selectFile(definition, seed) {
    var files = Array.isArray(definition && definition.files) ? definition.files : [];
    files = files.filter(function(file) { return typeof file === 'string' && file !== ''; });
    if (!files.length) return '';
    return files[hashString(seed) % files.length];
  }

  function loadBuffer(url) {
    if (!url) return Promise.resolve(null);
    if (buffers[url]) return Promise.resolve(buffers[url]);
    if (loading[url]) return loading[url];
    var context = getAudioContext();
    if (!context || typeof window.fetch !== 'function') return Promise.resolve(null);

    loading[url] = window.fetch(url, { credentials: 'same-origin' })
      .then(function(response) {
        if (!response.ok) throw new Error('Sound request failed: ' + response.status);
        return response.arrayBuffer();
      })
      .then(function(data) { return context.decodeAudioData(data); })
      .then(function(buffer) {
        buffers[url] = buffer;
        delete loading[url];
        return buffer;
      })
      .catch(function() {
        delete loading[url];
        return null;
      });
    return loading[url];
  }

  function cleanupSources() {
    activeSources = activeSources.filter(function(entry) { return !entry.ended; });
  }

  function playBuffer(buffer, gainValue, delayMs, playbackRate) {
    var context = getAudioContext();
    if (!context || !buffer || !unlocked) return;
    cleanupSources();
    while (activeSources.length >= maxGlobalVoices) {
      var oldest = activeSources.shift();
      try { oldest.source.stop(); } catch (e) {}
    }

    var source = context.createBufferSource();
    var gain = context.createGain();
    source.buffer = buffer;
    source.playbackRate.value = clamp(playbackRate, 0.5, 2, 1);
    gain.gain.value = clamp(gainValue, 0, 2, 0.5);
    source.connect(gain);
    gain.connect(context.destination);
    var entry = { source: source, ended: false };
    source.onended = function() { entry.ended = true; };
    activeSources.push(entry);
    try {
      source.start(context.currentTime + Math.max(0, delayMs) / 1000);
    } catch (e) {
      entry.ended = true;
    }
  }

  function viewerCanHear(event, viewerSeat) {
    var onlySeat = parseInt(event && event.onlySeat || 0, 10);
    if (!onlySeat) return true;
    return parseInt(viewerSeat, 10) === onlySeat;
  }

  function resolvePerspectiveCue(event, context) {
    var cue = String(event && event.cue || '');
    var actorSeat = parseInt(event && event.actorSeat || 0, 10);
    if (!actorSeat || !event.perspectiveCues || typeof event.perspectiveCues !== 'object') return cue;
    var viewerSeat = parseInt(context.viewerSeat || 0, 10);
    if (viewerSeat === actorSeat && event.perspectiveCues.self) return String(event.perspectiveCues.self);
    if (viewerSeat > 0 && event.perspectiveCues.other) return String(event.perspectiveCues.other);
    return event.perspectiveCues.spectator ? String(event.perspectiveCues.spectator) : cue;
  }

  function playEvent(event, frameContext, eventIndex) {
    if (!event || String(event.type || '').toUpperCase() !== 'SOUND') return;
    var root = normalizeRoot(frameContext.rootName);
    if (!isEnabled(root) || !unlocked || !viewerCanHear(event, frameContext.viewerSeat)) return;

    var cue = resolvePerspectiveCue(event, frameContext);
    var definition = cueDefinition(root, cue);
    if (!definition) return;
    var now = Date.now();
    var cooldownMs = Math.max(0, parseInt(definition.cooldownMs || 0, 10));
    var cooldownKey = root + ':' + cue;
    if (cooldownMs && now - (lastCueAt[cooldownKey] || 0) < cooldownMs) return;
    lastCueAt[cooldownKey] = now;

    var seed = event.variantSeed || [frameContext.gameName, frameContext.update, eventIndex, cue].join(':');
    var file = selectFile(definition, seed);
    if (!file) return;
    var delayMs = Math.max(0, parseInt(event.delayMs || 0, 10));
    var master = getVolume(root);
    var eventVolume = clamp(event.volume, 0, 2, 1);
    var cueGain = clamp(definition.gain, 0, 2, 1);
    var intensity = clamp(event.intensity, 0, 1, 0.5);
    var intensityGain = 0.85 + intensity * 0.3;
    var intendedAt = Date.now() + delayMs;

    loadBuffer(file).then(function(buffer) {
      if (!buffer || !unlocked || !isEnabled(root)) return;
      var remainingDelay = intendedAt - Date.now();
      // A late network/decode should not make an old event fire conspicuously after the board changed.
      if (remainingDelay < -250) return;
      playBuffer(buffer, master * eventVolume * cueGain * intensityGain, Math.max(0, remainingDelay), definition.playbackRate || 1);
    });
  }

  function playFrameEvents(events, frameContext) {
    if (!Array.isArray(events) || !frameContext) return 0;
    for (var i = 0; i < events.length; ++i) playEvent(events[i], frameContext, i);
    return 0;
  }

  function preload(rootName) {
    var root = normalizeRoot(rootName);
    var manifest = manifests[root] || {};
    var cues = manifest.cues || manifest;
    Object.keys(cues || {}).forEach(function(cue) {
      var definition = cues[cue];
      if (!definition || definition.preload === false) return;
      (definition.files || []).forEach(loadBuffer);
    });
  }

  function unlock() {
    var context = getAudioContext();
    if (!context) return Promise.resolve(false);
    var resume = context.state === 'suspended' ? context.resume() : Promise.resolve();
    return resume.then(function() {
      unlocked = context.state === 'running';
      if (unlocked) Object.keys(initializedRoots).forEach(preload);
      return unlocked;
    }).catch(function() { return false; });
  }

  function registerManifest(rootName, manifest) {
    var root = normalizeRoot(rootName);
    if (!root || !manifest || typeof manifest !== 'object') return;
    manifests[root] = manifest;
    if (unlocked) preload(root);
  }

  function init(rootName) {
    var root = normalizeRoot(rootName);
    if (!root || initializedRoots[root]) return;
    initializedRoots[root] = true;
    if (window.TCGSettings) {
      window.TCGSettings.registerSchema(root, {
        EnableSoundEffects: { type: 'boolean', defaultValue: true },
        SoundEffectsVolume: { type: 'number', defaultValue: 0.5 }
      });
    }
    document.addEventListener('pointerdown', unlock, { capture: true, once: true });
    document.addEventListener('keydown', unlock, { capture: true, once: true });
    // Decoding is allowed while an AudioContext is suspended, so warm the small local pack before
    // the first gameplay click. The gesture only resumes playback; it does not start a download burst.
    preload(root);
  }

  window.TCGSound = {
    init: init,
    unlock: unlock,
    registerManifest: registerManifest,
    playFrameEvents: playFrameEvents,
    isEnabled: isEnabled,
    setEnabled: setEnabled,
    toggle: toggle,
    getVolume: getVolume,
    setVolume: setVolume
  };
})();
