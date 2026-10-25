# VISUAL CHECK — face-down cards must not blink when the board updates (bug report P-4, 2026-10-23)
#
# Visual-only schema. Automated by DevTools/ui-harness/swusim-cardback-flicker-xbrowser.mjs (desktop AND
# &swuLayout=mobile, Chromium + Firefox + WebKit). By hand: open P1's view, then play anything.
#
# ── WHAT CHANGED ────────────────────────────────────────────────────────────────────────────────
#   Every board update rebuilds the card <img>s. Face-down cards used to be born as the generic
#   concat/CardBack.webp, and ApplyCosmeticCardBacks swapped each src to the seat's cosmetic back a frame
#   later. WebKit (Safari, every iPhone browser) painted each of those backs BLANK for a frame on EVERY
#   update, so the opponent's hand and both decks flickered whenever anything happened. RenderCardHTML now
#   paints the cosmetic back from the start. Chromium and Firefox never showed the blank frame.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • Play a card / pass / let the opponent act: the opponent's 4 hand cards and both deck piles stay
#     SOLID through the update. A one-frame flash to the background on Safari is the bug.
#   • Each face-down card shows its OWNER's cosmetic back (yours on your deck, theirs on their hand and
#     deck) — never the generic back.
#   • PHONE: the opponent's hand pile (one card + a count of 4) shows their back and stays solid too.
#
# ── BOARD SHAPE ─────────────────────────────────────────────────────────────────────────────────
#   The opponent holds 4 cards so several backs render side by side (a one-card hand is easy to miss),
#   and both decks are non-empty so a back renders on BOTH sides (owner resolution is per side).

## GIVEN
CommonSetup: ngw/ngw/{myLeader:JTL_006; theirLeader:JTL_006; myBase:TWI_026; theirBase:TWI_026}
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Resources: 4:SOR_095:1
WithP2Resources: 4:SOR_095:1
WithP1Hand: [SOR_088 JTL_081]
WithP1Deck: [SOR_126 SHD_084 SOR_092 JTL_119]
WithP2Hand: [SHD_084 SOR_088 JTL_084 JTL_081]
WithP2Deck: [JTL_142 SOR_132 JTL_081 SOR_128]

## WHEN

## EXPECT
TURNPLAYER:1
P2HANDCOUNT:4
