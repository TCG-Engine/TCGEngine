// Shared card-motion helpers. Semantic events are emitted by the server and consumed by
// NextTurn's ordered render queue, so motion never schedules an independent board render.
(function() {
  if (typeof window === 'undefined' || window.TCGCardMotion) return;

  function normalizeAbsoluteMzID(mzID, perspectivePlayerID) {
    var value = String(mzID || '').trim();
    if (!value) return '';
    var match = value.match(/^p([1-4])(.*)$/);
    if (!match) return value;
    return (parseInt(match[1], 10) === parseInt(perspectivePlayerID, 10) ? 'my' : 'their') + match[2];
  }

  function safeUniqueSelector(uniqueID) {
    var value = String(uniqueID == null ? '' : uniqueID).trim();
    if (!value) return null;
    var escaped = value.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    return document.querySelector("[data-uniqueid='" + escaped + "']");
  }

  function resolveElement(mzID, uniqueID, perspectivePlayerID) {
    var byUniqueID = safeUniqueSelector(uniqueID);
    if (byUniqueID) return byUniqueID;

    var normalized = normalizeAbsoluteMzID(mzID, perspectivePlayerID);
    if (!normalized) return null;
    if (normalized === 'P1BASE' || normalized === 'P2BASE') {
      return document.getElementById(normalized);
    }
    var escapedMz = normalized.replace(/'/g, "\\'");
    return document.getElementById(normalized)
      || document.querySelector("[data-mzid='" + escapedMz + "']")
      // Explicit motion anchor: lets a layout nominate a VISIBLE element to fly to when the real zone
      // element is hidden or has no box. Deliberately NOT data-mzid — UILibraries treats that as a card
      // identifier, so reusing it on a HUD badge would make the badge behave like a card.
      || document.querySelector("[data-motion-anchor='" + escapedMz + "']");
  }

  function getRootName(explicitRootName) {
    if (explicitRootName) return String(explicitRootName);
    if (window.TCGSettings && typeof window.TCGSettings.getCurrentRootName === 'function') {
      return window.TCGSettings.getCurrentRootName();
    }
    return '';
  }

  function defaultMotionEnabled() {
    try {
      return !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
      return true;
    }
  }

  function isEnabled(rootName) {
    var root = getRootName(rootName);
    if (root === 'AzukiSim') return true;
    // AzukiDeck's phone layout uses a horizontally paged workspace. Flying fixed-position
    // clones across those translated pages is distracting and can cross the viewport seam.
    if (root === 'AzukiDeck' && document.getElementById('swuDeckMobileRoot')) return false;
    if (!window.TCGSettings || typeof window.TCGSettings.get !== 'function') {
      return defaultMotionEnabled();
    }
    return window.TCGSettings.get('EnableCardMotion', {
      rootName: root,
      type: 'boolean',
      defaultValue: defaultMotionEnabled()
    }) !== false;
  }

  function updateToggleButton(buttonOrID, rootName) {
    var button = typeof buttonOrID === 'string' ? document.getElementById(buttonOrID) : buttonOrID;
    if (!button) return;
    var enabled = isEnabled(rootName);
    button.textContent = enabled ? 'Motion: On' : 'Motion: Off';
    button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    button.title = enabled ? 'Disable card movement animations' : 'Enable card movement animations';
  }

  function toggle(rootName, buttonOrID) {
    var root = getRootName(rootName);
    var enabled = !isEnabled(root);
    if (window.TCGSettings && typeof window.TCGSettings.set === 'function') {
      window.TCGSettings.set('EnableCardMotion', enabled, {
        rootName: root,
        type: 'boolean'
      });
    }
    updateToggleButton(buttonOrID, root);
    return enabled;
  }

  function playLunge(animation, perspectivePlayerID) {
    if (!animation) return 0;
    var source = resolveElement(
      animation.source || animation.target,
      animation.sourceUniqueID || animation.uniqueID,
      perspectivePlayerID
    );
    var destination = resolveElement(
      animation.destination,
      animation.destinationUniqueID,
      perspectivePlayerID
    );
    if (!source || !destination) return 0;

    var sourceRect = source.getBoundingClientRect();
    var destinationRect = destination.getBoundingClientRect();
    if (!sourceRect.width || !sourceRect.height) return 0;
    var ratio = Number(animation.distanceRatio);
    if (!Number.isFinite(ratio)) ratio = 0.7;
    ratio = Math.max(0.1, Math.min(1, ratio));
    var rawDx = (destinationRect.left + destinationRect.width / 2 - sourceRect.left - sourceRect.width / 2) * ratio;
    var rawDy = (destinationRect.top + destinationRect.height / 2 - sourceRect.top - sourceRect.height / 2) * ratio;
    // Clamp the travel so a lunge always reads as "leans toward" rather than "flies at". Without this,
    // a multi-seat board — where the attacker/target vector spans the whole table rather than one
    // opposed pair of lanes — would sweep the card most of the way across the screen. Computed here
    // from live rects rather than passed from the server, because the SAME animation payload is
    // replayed by every viewer and one may be zoomed in while another sees the wide board; only the
    // client knows the real on-screen distance.
    // 1.5x the source card's larger dimension is a no-op at ordinary two-player distances.
    var maxTravel = Math.max(sourceRect.width, sourceRect.height) * 1.5;
    var rawDistance = Math.sqrt(rawDx * rawDx + rawDy * rawDy);
    var clampScale = (rawDistance > maxTravel && rawDistance > 0) ? (maxTravel / rawDistance) : 1;
    var dx = rawDx * clampScale;
    var dy = rawDy * clampScale;
    var durationMs = Math.max(120, parseInt(animation.durationMs || 360, 10));
    var delayMs = Math.max(0, parseInt(animation.delayMs || 0, 10));

    // Scrollable lanes clip their descendants regardless of z-index. Animate a fixed clone
    // on the document body so lunges can cross Garden/field boundaries, and include attached
    // subcards in the motion without changing the source lane's layout or scroll position.
    var sourceStyle = window.getComputedStyle ? window.getComputedStyle(source) : null;
    // Take the card's INTENDED pose, not its current one.
    //
    // Attacking exhausts the attacker, and that is a COST — it has to be visible before the motion it
    // paid for. The EXHAUST frame animation runs first (same batch, NextTurn's animation loop) and
    // tilts this very element by setting an inline `transform: rotate(Ndeg)` behind a CSS transition.
    // A transition reports its START value until it advances, so reading the COMPUTED transform here
    // yields the upright matrix: the clone then lunged bolt upright and the card only tipped over once
    // the clone was removed. The inline declaration is the end state, so prefer it and let the clone
    // travel already-exhausted. Falls back to computed when there is no inline transform (an upright
    // attacker, or an app that tilts via a stylesheet rule).
    var inlineTransform = source.style && source.style.transform ? String(source.style.transform) : '';
    var sourceTransform = inlineTransform
      || (sourceStyle && sourceStyle.transform ? sourceStyle.transform : 'none');
    var sourceTransformOrigin = (source.style && source.style.transformOrigin)
      || (sourceStyle && sourceStyle.transformOrigin ? sourceStyle.transformOrigin : 'center center');
    var sourceWidth = source.offsetWidth || sourceRect.width;
    var sourceHeight = source.offsetHeight || sourceRect.height;
    var cloneLeft = sourceRect.left + (sourceRect.width - sourceWidth) / 2;
    var cloneTop = sourceRect.top + (sourceRect.height - sourceHeight) / 2;
    var clone = source.cloneNode(true);
    stripCloneIdentity(clone);
    clone.classList.add('tcg-card-lunge-clone');
    clone.style.cssText += ';position:fixed!important;left:' + cloneLeft + 'px!important;top:'
      + cloneTop + 'px!important;width:' + sourceWidth + 'px!important;height:'
      + sourceHeight + 'px!important;margin:0!important;z-index:20000!important;'
      + 'pointer-events:none!important;transform:' + sourceTransform + '!important;transform-origin:'
      + sourceTransformOrigin + '!important;overflow:visible!important;will-change:transform,translate!important;'
      // cloneNode copies the source's inline styles, including the CSS transition the EXHAUST frame
      // just installed. The clone is born in its final pose, so that transition has nothing to run —
      // but leaving it armed would make any later style touch ease instead of apply.
      + 'transition:none!important;';
    document.body.appendChild(clone);
    if (typeof clone.animate !== 'function') {
      if (clone.parentNode) clone.parentNode.removeChild(clone);
      return 0;
    }

    source.style.visibility = 'hidden';
    // Animate the independent translate property rather than composing into transform.
    // Opponent cards are commonly rotated by the board layout; composing translation into
    // that matrix mirrors the travel vector for the defending viewer.
    var motion = clone.animate([
      { translate: '0px 0px', offset: 0 },
      { translate: dx + 'px ' + dy + 'px', offset: 0.46 },
      { translate: dx + 'px ' + dy + 'px', offset: 0.58 },
      { translate: '0px 0px', offset: 1 }
    ], {
      duration: durationMs,
      delay: delayMs,
      easing: 'cubic-bezier(0.22, 0.78, 0.2, 1)',
      iterations: 1
    });
    var cleaned = false;
    var cleanup = function() {
      if (cleaned) return;
      cleaned = true;
      if (source.isConnected) source.style.visibility = '';
      if (clone.parentNode) clone.parentNode.removeChild(clone);
    };
    try {
      motion.addEventListener('finish', cleanup, { once: true });
      motion.addEventListener('cancel', cleanup, { once: true });
    } catch (e) {}
    window.setTimeout(cleanup, durationMs + delayMs + 80);
    return durationMs + delayMs;
  }

  function stripCloneIdentity(clone) {
    if (!clone || clone.nodeType !== 1) return;
    clone.removeAttribute('id');
    clone.removeAttribute('data-mzid');
    clone.removeAttribute('data-uniqueid');
    clone.removeAttribute('onclick');
    clone.removeAttribute('draggable');
    var descendants = clone.querySelectorAll('[id], [data-mzid], [data-uniqueid], [onclick], [draggable]');
    for (var i = 0; i < descendants.length; ++i) {
      descendants[i].removeAttribute('id');
      descendants[i].removeAttribute('data-mzid');
      descendants[i].removeAttribute('data-uniqueid');
      descendants[i].removeAttribute('onclick');
      descendants[i].removeAttribute('draggable');
    }
  }

  // Zone/card CSS is often scoped through its live container (for example
  // #myMainDeck > span > a). A motion clone is appended directly to document.body, so those
  // selectors no longer match and its inner anchor/image can fall back to their smaller
  // generated dimensions. Freeze the rendered visual boxes before detaching the clone.
  function preserveDetachedCardGeometry(source, clone) {
    if (!source || !clone || typeof source.querySelector !== 'function') return;
    var selectors = [':scope > a', ':scope > a > img:first-child'];
    for (var i = 0; i < selectors.length; ++i) {
      var sourcePart = source.querySelector(selectors[i]);
      var clonePart = clone.querySelector(selectors[i]);
      if (!sourcePart || !clonePart) continue;
      var partRect = sourcePart.getBoundingClientRect();
      if (!partRect.width || !partRect.height) continue;
      var partStyle = window.getComputedStyle ? window.getComputedStyle(sourcePart) : null;
      clonePart.style.setProperty('display', 'block', 'important');
      clonePart.style.setProperty('width', partRect.width + 'px', 'important');
      clonePart.style.setProperty('height', partRect.height + 'px', 'important');
      clonePart.style.setProperty('max-width', 'none', 'important');
      clonePart.style.setProperty('max-height', 'none', 'important');
      clonePart.style.setProperty('margin', '0', 'important');
      clonePart.style.setProperty('box-sizing', partStyle && partStyle.boxSizing ? partStyle.boxSizing : 'border-box', 'important');
      if (i === 0) clonePart.style.setProperty('position', 'relative', 'important');
    }
  }

  function prepareZoneMoves(animations, perspectivePlayerID) {
    if (!Array.isArray(animations)) return [];
    var prepared = [];
    for (var i = 0; i < animations.length; ++i) {
      var event = animations[i];
      if (!event || String(event.type || '').toUpperCase() !== 'ZONE_MOVE') continue;
      // Optional owner scoping: a move into a self-visible zone (SWU resources are
      // Display: Visibility=Self) should play only for the seat it belongs to. Absent the field,
      // every viewer plays it as before — so this is inert for existing callers.
      if (event.onlySeat !== undefined && event.onlySeat !== null
          && Number(event.onlySeat) !== Number(perspectivePlayerID)) continue;
      var source = resolveElement(
        event.source || event.target,
        event.sourceUniqueID || event.uniqueID,
        perspectivePlayerID
      );
      if (!source) continue;
      var rect = source.getBoundingClientRect();
      if (!rect.width || !rect.height) continue;

      var clone = source.cloneNode(true);
      stripCloneIdentity(clone);
      // Quantity badges describe the stack/zone, not the individual card being moved.
      // Leaving one on the detached clone makes it carry the source stack's old count.
      var stackMetadata = clone.querySelectorAll('.counter-bubble');
      for (var metadataIndex = 0; metadataIndex < stackMetadata.length; ++metadataIndex) {
        if (stackMetadata[metadataIndex].parentNode) {
          stackMetadata[metadataIndex].parentNode.removeChild(stackMetadata[metadataIndex]);
        }
      }
      var existingDestination = resolveElement(
        event.destination,
        event.destinationUniqueID,
        perspectivePlayerID
      );
      clone.classList.add('tcg-zone-move-clone');
      preserveDetachedCardGeometry(source, clone);
      clone.style.cssText += ';position:fixed!important;left:' + rect.left + 'px!important;top:' + rect.top
        + 'px!important;width:' + rect.width + 'px!important;height:' + rect.height
        + 'px!important;margin:0!important;z-index:20000!important;pointer-events:none!important;'
        // Centre origin: the flight can carry the destination's rotation (see playPreparedZoneMoves),
        // and rotating about a corner would swing the card wide of its slot. For an unrotated landing
        // this is equivalent to the corner origin it replaced.
        + 'visibility:visible!important;transform-origin:center center!important;will-change:transform,opacity!important;';
      document.body.appendChild(clone);
      source.style.visibility = 'hidden';
      prepared.push({
        event: event,
        clone: clone,
        source: source,
        sourceRect: rect,
        destinationExisted: !!existingDestination,
        order: prepared.length
      });
    }
    return prepared;
  }

  // How far the destination card is rotated by its own transform, in degrees.
  //
  // A card that lands EXHAUSTED is rendered rotated (UILibraries applies RotationRules as an inline
  // `transform: rotate(Ndeg)` — SWUSim tilts an exhausted unit 9°). The flying clone used to stay
  // upright the whole way, so the moment it was removed the real card popped into its tilt. Reading
  // the angle here lets the clone travel already-tilted and land on the exact final pose.
  //
  // Prefer the INLINE transform: RotationRules writes it directly, and it cannot be perturbed by the
  // WAAPI exhaust-enter tween that UILibraries starts on the same element during this render.
  function elementRotationDegrees(element) {
    if (!element || element.nodeType !== 1) return 0;
    var inline = element.style && element.style.transform ? String(element.style.transform) : '';
    var rotateMatch = inline.match(/rotate\(\s*(-?[\d.]+)deg\s*\)/i);
    if (rotateMatch) return parseFloat(rotateMatch[1]) || 0;
    if (!window.getComputedStyle) return 0;
    var computed = window.getComputedStyle(element).transform;
    if (!computed || computed === 'none') return 0;
    var numbers = computed.match(/matrix\(\s*([-\d.eE, ]+)\)/);
    if (!numbers) return 0;                       // matrix3d and friends: not worth decomposing
    var parts = numbers[1].split(',');
    if (parts.length < 2) return 0;
    return Math.atan2(parseFloat(parts[1]) || 0, parseFloat(parts[0]) || 0) * 180 / Math.PI;
  }

  // getBoundingClientRect on a rotated element reports its axis-aligned BOUNDING box, which is bigger
  // than the card. Scaling the clone to that box would overshoot, so undo the rotation:
  //   boxW = w*|cos| + h*|sin|,  boxH = w*|sin| + h*|cos|
  // is a 2x2 system in (w, h) whose determinant is cos(2*angle). Near 45° that determinant vanishes and
  // the size is not recoverable — fall back to the bounding box (no SWU rotation is anywhere near 45°).
  function unrotatedSize(rect, degrees) {
    var size = { width: rect.width, height: rect.height };
    if (!degrees) return size;
    var radians = degrees * Math.PI / 180;
    var c = Math.abs(Math.cos(radians));
    var s = Math.abs(Math.sin(radians));
    var determinant = c * c - s * s;
    if (Math.abs(determinant) < 0.05) return size;
    var width = (rect.width * c - rect.height * s) / determinant;
    var height = (rect.height * c - rect.width * s) / determinant;
    if (width > 0 && height > 0) { size.width = width; size.height = height; }
    return size;
  }

  // Match the destination's exhausted dimming so the clone does not brighten-pop on landing. The real
  // card dims via a 50%-black overlay child (UILibraries' .exhausted-status-overlay-layer), faded in by
  // the exhaust-enter tween while the card is still hidden behind the clone. The clone comes from the
  // hand, where no such layer exists, so add an equivalent one inset to match the rendered overlay box.
  function applyExhaustedTint(clone, destination) {
    if (!clone || !destination || typeof destination.querySelector !== 'function') return;
    var sourceLayer = destination.querySelector('.exhausted-status-overlay-layer');
    if (!sourceLayer) return;
    if (clone.querySelector('.exhausted-status-overlay-layer')) return;   // already dim, nothing to do
    var tint = document.createElement('div');
    tint.className = 'tcg-zone-move-exhaust-tint';
    tint.style.cssText = 'position:absolute;top:2px;left:2px;width:calc(100% - 4px);height:calc(100% - 4px);'
      + 'border-radius:10px;background:rgba(0, 0, 0, 0.5);pointer-events:none;z-index:1;';
    clone.appendChild(tint);
  }

  function destinationZoneFallback(event, perspectivePlayerID) {
    var normalized = normalizeAbsoluteMzID(event.destination, perspectivePlayerID);
    var zoneName = normalized.replace(/-\d+$/, '');
    return document.getElementById(zoneName)
      || document.getElementById(zoneName + 'Wrapper')
      || document.getElementById(zoneName + 'Slot');
  }

  function cleanupPrepared(item) {
    if (!item) return;
    if (item.source && item.source.isConnected) item.source.style.visibility = '';
    if (item.destination && item.destination.isConnected) item.destination.style.visibility = '';
    if (item.clone && item.clone.parentNode) item.clone.parentNode.removeChild(item.clone);
  }

  function discardPrepared(prepared) {
    if (!Array.isArray(prepared)) return;
    for (var i = 0; i < prepared.length; ++i) cleanupPrepared(prepared[i]);
  }

  function playPreparedZoneMoves(prepared, perspectivePlayerID) {
    if (!Array.isArray(prepared) || prepared.length === 0) return 0;
    var blockingMs = 0;
    for (var i = 0; i < prepared.length; ++i) {
      var item = prepared[i];
      var event = item.event;
      var destination = resolveElement(
        event.destination,
        event.destinationUniqueID,
        perspectivePlayerID
      ) || destinationZoneFallback(event, perspectivePlayerID);
      if (!destination || !item.clone || typeof item.clone.animate !== 'function') {
        cleanupPrepared(item);
        continue;
      }

      var destinationRect = destination.getBoundingClientRect();
      // A display:none / unrendered destination reports 0x0 at (0,0), which would send the card flying
      // to the top-left corner of the viewport. Skip instead — a missing slide beats a wrong one.
      // (SWU resources are exactly this: the resource PANEL is a click-to-open flyout that is hidden by
      // default, so it has no box until opened.)
      if (destinationRect.width <= 0 && destinationRect.height <= 0) {
        cleanupPrepared(item);
        continue;
      }
      item.destination = destination;
      // Land on the destination's FINAL pose — including its rotation, so an exhausted arrival (a unit
      // played into the arena) travels already-tilted instead of snapping upright-to-tilted on landing.
      //
      // The clone is animated about its CENTER rather than its top-left corner: rotating about a corner
      // would swing the card wide of its slot. Centre-origin is equivalent to the old corner-origin math
      // for the unrotated case (both end with the card exactly covering the destination box), so draw /
      // defeat / mill / bounce are unchanged. A rotation about the centre also leaves the bounding box
      // centred on the card, which is why the centres can be compared directly here.
      var rotationDeg = elementRotationDegrees(destination);
      var destinationSize = unrotatedSize(destinationRect, rotationDeg);
      var dx = (destinationRect.left + destinationRect.width / 2)
             - (item.sourceRect.left + item.sourceRect.width / 2);
      var dy = (destinationRect.top + destinationRect.height / 2)
             - (item.sourceRect.top + item.sourceRect.height / 2);
      var scaleX = item.sourceRect.width > 0 ? destinationSize.width / item.sourceRect.width : 1;
      var scaleY = item.sourceRect.height > 0 ? destinationSize.height / item.sourceRect.height : 1;
      var rotate = rotationDeg ? ' rotate(' + rotationDeg + 'deg)' : '';
      if (rotationDeg) applyExhaustedTint(item.clone, destination);
      var durationMs = Math.max(120, parseInt(event.durationMs || 420, 10));
      var delayMs = Math.max(0, parseInt(event.delayMs || 0, 10)) + item.order * 60;
      // A destination that already existed is a collapsed stack. Keep its newly rendered
      // quantity visible while the single-card clone flies in. New destinations stay hidden
      // until landing so the arriving card is not shown twice.
      if (!item.destinationExisted) destination.style.visibility = 'hidden';

      var motion = item.clone.animate([
        { transform: 'translate(0px, 0px)' + rotate + ' scale(1, 1)', opacity: 1 },
        { transform: 'translate(' + dx + 'px, ' + dy + 'px)' + rotate
            + ' scale(' + scaleX + ', ' + scaleY + ')', opacity: 1 }
      ], {
        duration: durationMs,
        delay: delayMs,
        easing: event.easing || 'cubic-bezier(0.22, 0.78, 0.2, 1)',
        // 'both', not 'forwards': staggered clones wait out `delayMs` before their first keyframe, and
        // with a forwards-only fill they would sit UPRIGHT during that wait and then snap into the tilt
        // as the animation starts — the very pop this rotation is here to remove.
        fill: 'both',
        iterations: 1
      });
      (function(preparedItem, animationHandle, fallbackMs) {
        var cleaned = false;
        var finish = function() {
          if (cleaned) return;
          cleaned = true;
          cleanupPrepared(preparedItem);
        };
        try {
          animationHandle.addEventListener('finish', finish, { once: true });
          animationHandle.addEventListener('cancel', finish, { once: true });
        } catch (e) {}
        window.setTimeout(finish, fallbackMs + 80);
      })(item, motion, durationMs + delayMs);

      if (event.blocking !== false && durationMs + delayMs > blockingMs) {
        blockingMs = durationMs + delayMs;
      }
    }
    return blockingMs;
  }

  window.TCGCardMotion = {
    isEnabled: isEnabled,
    toggle: toggle,
    updateToggleButton: updateToggleButton,
    normalizeAbsoluteMzID: normalizeAbsoluteMzID,
    playLunge: playLunge,
    prepareZoneMoves: prepareZoneMoves,
    playPreparedZoneMoves: playPreparedZoneMoves,
    discardPrepared: discardPrepared
  };
})();
