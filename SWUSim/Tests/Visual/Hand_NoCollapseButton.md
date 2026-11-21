# VISUAL CHECK — the hand has no collapse / expand button (desktop and phone)
#
# Owner, 2026-11-20: "i like how we did away with the hand tray open and close button. that always felt unnecessary. i'd like to get
# rid of that in our regular 1v1 game layout as well". Harness: DevTools/ui-harness/swusim-hand-no-collapse-xbrowser.mjs.
# Check Chromium, Firefox AND Safari/WebKit. PHONE: &swuLayout=mobile.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • DESKTOP: no small ▼ / ▲ pill on the top edge of your hand (or the opponent's). Your five hand cards sit fully in view.
#   • A player who had collapsed their hand before this change (localStorage swu-hand-collapsed-v1 = 1) still sees it OPEN — the
#     old setting no longer hides anything.
#   • PHONE: unchanged (it never showed the button).

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Hand: [SOR_046 SOR_095 SEC_111 ASH_110 HMW_254]
WithP2Hand: [SOR_095 SOR_095 SOR_095]

## WHEN

## EXPECT
TURNPLAYER:1
