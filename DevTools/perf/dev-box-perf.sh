#!/usr/bin/env bash
# dev-box-perf.sh — reduce the background load competing with the SWUSim regression suite.
#
# WHY THIS EXISTS
#   Suite wall-clock on this box swung 51s -> 253s for an UNCHANGED set of tests. It was not the
#   engine and not a container leak: the host was saturated (load ~12 on 12 cores) by Firefox and
#   WindowServer, while the Docker VM got what was left. Two other constant taxes were measured:
#   Xdebug's step-debug mode costs ~7.5x on real PHP work, and the harness leaks a /tmp scratch dir
#   per Apache worker PID forever.
#
#   Everything here is reversible and prints what it did. Nothing runs unless you name it.
#
# USAGE
#   ./dev-box-perf.sh status              # report only, changes nothing
#   ./dev-box-perf.sh firefox-tune        # write a managed user.js to ACTIVE profiles
#   ./dev-box-perf.sh firefox-tune --all  # ...to every profile
#   ./dev-box-perf.sh firefox-revert      # remove it (restores any pre-existing backup)
#   ./dev-box-perf.sh xdebug off|on       # toggle step-debug in DevTools/xdebug/xdebug.ini
#   ./dev-box-perf.sh tmp-clean           # sweep stale boundary scratch dirs in the containers
#   ./dev-box-perf.sh all                 # firefox-tune + xdebug off + tmp-clean
set -uo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
FF_PROFILES="$HOME/Library/Application Support/Firefox/Profiles"
XDEBUG_INI="$REPO/DevTools/xdebug/xdebug.ini"
MARK_BEGIN="// >>> dev-box-perf.sh managed block — edits below are overwritten"
MARK_END="// <<< dev-box-perf.sh managed block"
# Containers that mount the shared xdebug ini / run the harness.
CONTAINERS="$(docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'web-server' || true)"

c_grn(){ printf "\033[32m%s\033[0m\n" "$*"; }
c_ylw(){ printf "\033[33m%s\033[0m\n" "$*"; }
c_hdr(){ printf "\n\033[1m== %s ==\033[0m\n" "$*"; }

# Profiles whose places.sqlite changed in the last 7 days = ones you actually use.
active_profiles() {
  [ -d "$FF_PROFILES" ] || return 0
  find "$FF_PROFILES" -maxdepth 2 -name places.sqlite -mtime -7 2>/dev/null \
    | while read -r f; do dirname "$f"; done
}
all_profiles() {
  [ -d "$FF_PROFILES" ] || return 0
  find "$FF_PROFILES" -maxdepth 1 -mindepth 1 -type d 2>/dev/null
}

write_user_js() {
  # ⚠ Two statements, not one. `local a="$1" b="$a/x"` declares BOTH names before expanding, so $a is
  # still unset when b is evaluated — which under `set -u` aborts with "unbound variable".
  local prof="$1"
  local f="$prof/user.js"
  # Preserve anything that was there before we ever ran (once — never clobber our own backup).
  if [ -f "$f" ] && ! grep -qF "$MARK_BEGIN" "$f" && [ ! -f "$f.pre-dev-box-perf.bak" ]; then
    cp "$f" "$f.pre-dev-box-perf.bak"
    c_ylw "    kept your existing user.js as user.js.pre-dev-box-perf.bak"
  fi
  cat > "$f" <<'JSEOF'
// >>> dev-box-perf.sh managed block — edits below are overwritten
// Goal: cut Firefox's BACKGROUND cost while a long test suite runs. Nothing here changes how a
// page you are actively looking at renders.
//
// user.js is re-applied at EVERY startup and overrides the same prefs set in about:preferences —
// so if a setting below fights something you change in the UI, the UI change will not stick.
// Remove this file (or run `dev-box-perf.sh firefox-revert`) to get normal behaviour back.

// ── Background tabs: the single biggest win ──────────────────────────────────────────────────
// A background tab's timers are throttled to once a minute instead of once a second. This is what
// stops an idle dashboard/chat tab from burning a core while you compile.
user_pref("dom.min_background_timeout_value", 60000);
user_pref("dom.ipc.processPriorityManager.enabled", true);

// ── Fewer content processes: less RAM, less scheduler pressure ───────────────────────────────
// Default is 8. Four is comfortable for normal browsing; raise it if tabs start feeling coupled.
user_pref("dom.ipc.processCount", 4);
user_pref("browser.tabs.remote.warmup.enabled", false);

// ── Animation and autoplay: pure CPU/GPU burn you rarely want ────────────────────────────────
user_pref("image.animation_mode", "none");        // animated GIFs stop looping ("once"/"normal" to restore)
user_pref("media.autoplay.default", 5);           // block audio AND video autoplay
user_pref("toolkit.cosmeticAnimations.enabled", false);
user_pref("browser.download.animateNotifications", false);

// ── Session store: it writes your whole tab tree to disk on a timer ──────────────────────────
user_pref("browser.sessionstore.interval", 60000);   // default 15000ms
user_pref("browser.sessionstore.max_tabs_undo", 10);  // default 25

// ── Speculative network work ─────────────────────────────────────────────────────────────────
user_pref("network.prefetch-next", false);
user_pref("network.dns.disablePrefetch", true);
user_pref("network.predictor.enabled", false);

// ── New tab page: sponsored/Pocket content is a live network + render job on every new tab ────
user_pref("browser.newtabpage.activity-stream.showSponsored", false);
user_pref("browser.newtabpage.activity-stream.showSponsoredTopSites", false);
user_pref("browser.newtabpage.activity-stream.feeds.section.topstories", false);

// ── Background telemetry / studies ───────────────────────────────────────────────────────────
user_pref("toolkit.telemetry.enabled", false);
user_pref("datareporting.healthreport.uploadEnabled", false);
user_pref("app.shield.optoutstudies.enabled", false);
user_pref("browser.ping-centre.telemetry", false);
user_pref("browser.discovery.enabled", false);

// ── DELIBERATELY NOT SET ─────────────────────────────────────────────────────────────────────
// gfx.webrender.all=false / layers.acceleration.disabled=true — disabling hardware acceleration
//   on macOS usually RAISES CPU, because compositing falls back to the CPU. A busy "Firefox GPU
//   Helper" is normally a symptom of a heavy page, not of acceleration being wrong. Only try this
//   if about:processes shows the GPU process hot with no tab explaining it.
// accessibility.force_disabled=1 — cheap, but breaks VoiceOver and some automation. Add it
//   yourself if you never use either.
// <<< dev-box-perf.sh managed block
JSEOF
  c_grn "    wrote $(basename "$prof")/user.js"
}

cmd_status() {
  c_hdr "host"
  uptime | sed 's/^/  /'
  echo "  cores: $(sysctl -n hw.ncpu 2>/dev/null)"
  echo "  top consumers:"
  ps -Ao %cpu,comm -r 2>/dev/null | head -6 | sed 's/^/    /'

  c_hdr "firefox"
  local n; n=$(pgrep -f "Firefox.app/Contents/MacOS" 2>/dev/null | wc -l | tr -d ' ')
  echo "  processes: $n"
  echo "  instances:"
  ps -Ao pid,etime,command 2>/dev/null | grep "MacOS/firefox" | grep -v grep \
    | sed 's/^/    /' | cut -c1-140
  echo "  profiles (managed = user.js from this script):"
  while IFS= read -r p; do
    [ -n "$p" ] || continue
    local tag="unmanaged"
    [ -f "$p/user.js" ] && grep -qF "$MARK_BEGIN" "$p/user.js" && tag="MANAGED"
    printf "    %-34s %s\n" "$(basename "$p")" "$tag"
  done < <(all_profiles)

  c_hdr "xdebug (DevTools/xdebug/xdebug.ini — mounted into every sim container)"
  if [ -f "$XDEBUG_INI" ]; then
    grep -E "^xdebug\.(mode|start_with_request)" "$XDEBUG_INI" | sed 's/^/  /'
    grep -q "^xdebug.mode=.*debug" "$XDEBUG_INI" \
      && c_ylw "  step-debug is ON — measured ~7.5x slower on real PHP work" \
      || c_grn "  step-debug is off"
  else
    echo "  (not found at $XDEBUG_INI)"
  fi

  c_hdr "harness /tmp scratch dirs"
  for c in $CONTAINERS; do
    docker exec "$c" sh -c '
      t=$(ls -d /tmp/swusim_request_boundary_* 2>/dev/null | wc -l)
      s=0; for d in /tmp/swusim_request_boundary_*; do [ -d "$d" ] || continue; p=${d##*_}; [ -d "/proc/$p" ] || s=$((s+1)); done
      echo "  '"$c"': $t dirs ($s stale), /tmp $(du -sh /tmp 2>/dev/null | cut -f1)"' 2>/dev/null
  done
}

cmd_firefox_tune() {
  local list
  if [ "${1:-}" = "--all" ]; then list=$(all_profiles); else list=$(active_profiles); fi
  [ -n "$list" ] || { c_ylw "  no Firefox profiles found"; return; }
  c_hdr "writing managed user.js"
  while IFS= read -r p; do [ -n "$p" ] && write_user_js "$p"; done <<< "$list"
  c_ylw "  RESTART FIREFOX for these to take effect (user.js is read at startup)."
}

cmd_firefox_revert() {
  c_hdr "reverting"
  while IFS= read -r p; do
    [ -n "$p" ] || continue
    local f="$p/user.js"
    if [ -f "$f" ] && grep -qF "$MARK_BEGIN" "$f"; then
      rm -f "$f"
      [ -f "$f.pre-dev-box-perf.bak" ] && mv "$f.pre-dev-box-perf.bak" "$f" \
        && c_grn "    $(basename "$p"): restored your original user.js" \
        || c_grn "    $(basename "$p"): removed managed user.js"
    fi
  done < <(all_profiles)
  c_ylw "  RESTART FIREFOX."
}

cmd_xdebug() {
  local want="${1:-}"
  [ -f "$XDEBUG_INI" ] || { c_ylw "  $XDEBUG_INI not found"; return 1; }
  case "$want" in
    off) sed -i '' 's/^xdebug\.mode=.*/xdebug.mode=develop/' "$XDEBUG_INI" ;;
    on)  sed -i '' 's/^xdebug\.mode=.*/xdebug.mode=develop,debug/' "$XDEBUG_INI" ;;
    *)   echo "  usage: xdebug on|off"; return 1 ;;
  esac
  c_grn "  set $(grep '^xdebug.mode' "$XDEBUG_INI")"
  # The ini is bind-mounted, but PHP reads it at process start — Apache needs a reload.
  for c in $CONTAINERS; do
    docker exec "$c" sh -c 'apachectl -k graceful 2>/dev/null || apache2ctl -k graceful 2>/dev/null' >/dev/null 2>&1 \
      && c_grn "  reloaded apache in $c" || c_ylw "  could not reload $c — restart it to apply"
  done
  c_ylw "  NOTE: this ini is shared by every sim container, not just SWUSim."
}

cmd_tmp_clean() {
  c_hdr "sweeping stale boundary scratch dirs"
  for c in $CONTAINERS; do
    docker exec "$c" sh -c '
      r=0; k=0
      for d in /tmp/swusim_request_boundary_*; do
        [ -d "$d" ] || continue
        p=${d##*_}
        if [ -d "/proc/$p" ]; then k=$((k+1)); else rm -rf "$d" && r=$((r+1)); fi
      done
      echo "  '"$c"': removed $r stale, kept $k live, /tmp now $(du -sh /tmp 2>/dev/null | cut -f1)"' 2>/dev/null
  done
}

case "${1:-status}" in
  status)         cmd_status ;;
  firefox-tune)   cmd_firefox_tune "${2:-}" ;;
  firefox-revert) cmd_firefox_revert ;;
  xdebug)         cmd_xdebug "${2:-}" ;;
  tmp-clean)      cmd_tmp_clean ;;
  all)            cmd_firefox_tune "${2:-}"; cmd_xdebug off; cmd_tmp_clean ;;
  *)              sed -n '2,26p' "$0" | sed 's/^# \{0,1\}//' ;;
esac
