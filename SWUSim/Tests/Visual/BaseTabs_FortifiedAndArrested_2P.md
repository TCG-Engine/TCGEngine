# VISUAL CHECK — 2P: the FORTIFIED / ARRESTED tabs under the base
#
#   curl -s -X POST .../SWUSim/TestSchemaSetup.php --data-urlencode "schema@<this file>"
#   then drive the WHEN lines through TestSchemaStep.php (the arrests must actually be PLAYED — there
#   is no directive that seeds a base captive), and open
#   http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=1
#
# ⚠ THE TABS ARE TUCKED UNDER THE BASE, like subcards — not floating pills beside it. Check the joint:
#   base bottom and the first tab must BUTT (measured -1px, i.e. the tab slides behind the card), the
#   two tabs butt each other, each spans the base card's FULL width (161px — identical, not merely
#   close), and only the OUTER corners are CHAMFERED.
#   ⚠ They follow the board BUTTON idiom (.swu-init-pass-btn): coloured rim, inset translucent flat
#   fill via ::before, chamfered clip-path, same label font at 0.16em tracking — but deliberately more
#   TRANSPARENT than a real button, because these are STATUS, not controls, and must not out-shout the
#   PASS cluster. The edge meeting the card stays square; only the outer corners chamfer, so the tuck
#   still reads as one piece. Their half chamfers the TOP corners instead (mirrored).
#   ⚠ THEIR half MIRRORS it: their tabs sit between leader and base, so they tuck UPWARD into the base
#   — column-reverse (so the nearest tab is still the first one), radius on TOP, shadow cast upward.
#   Verified: their fort tab radius "5px 5px 0px 0px", gap fort→base -1px. Check BOTH halves; a change
#   that only fixes the one you are looking at is the easy mistake here.
#
# WHAT TO LOOK AT — P1's centre column reads, top to bottom:
#   [Base] / [FORTIFIED 3] / [ARRESTED 2] / [Leader]
#   • FORTIFIED is neutral grey, ARRESTED is goldenrod. Both carry a count, and the count is the point:
#     the extreme case here is 3 Fortify upgrades AND 2 arrests at once.
#   • CLICK the FORTIFIED tab → the "Attached Upgrades" panel opens with THREE card images that must
#     RENDER (HMW_081, HMW_171, HMW_205), not broken-image icons. Click anywhere to dismiss.
#   • CLICK the ARRESTED tab → a "Captured Units" panel naming the captured units.
#     ⚠ That is CORRECT, not a leak: captured cards are OPEN INFORMATION to every player
#     (CR 1077.1 "still open information to all players"; CR 207.1 lists "the attributes of ... any
#     captured cards"). Facedown under the base is a placement, not secrecy. An earlier version of
#     this file claimed the opposite — do not restore it.
#   • The base's old bottom-left corner badge is GONE — the tab replaced it. If you see both, the
#     Counters line got re-added to the Base zone in GameSchema.txt.
#   • P2's base has neither, so #theirBaseTabs is present but ZERO height — an empty tab strip must not
#     push their leader and base apart.
#
# ⚠ THE TRAP, and it cost a cycle: swuParseZoneCard runs .replace(/_/g,' ') over the whole JSON string
# (underscores are the transport's stand-in for spaces), so UpgradeCardIDs arrives as "HMW 081" and
# every popup image 404s — the panel still opens, with three broken images. Assert naturalWidth > 0,
# not just that the popup appeared. The main card path (createCardHTML) parses WITHOUT that replace,
# which is why the old corner badge never hit this.
#
# ⚠ Base captives are NOT subcards. They are "SWU_BASECAPTIVE|CardID|owner" flags in the CAPTURING
# player's GlobalEffects, drained at RegroupPhaseStart — so ARRESTED resets each round, and a fixture
# that passes the regroup phase will show 0. Fortify upgrades ARE subcards and persist.
#
# VERIFIED 2026-08-19, Chromium + Firefox at 1700x1100: base y=563, FORTIFIED y=683, ARRESTED y=706,
# leader y=740; corner badge count 0; popup 3 images, all 3 loaded; zero page errors.
# WebKit NOT covered: it does not launch on this machine.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP1Hand: SEC_195
WithP1BaseUpgrade: HMW_081
WithP1BaseUpgrade: HMW_171
WithP1BaseUpgrade: HMW_205
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
#// Two Arrests. The SECOND auto-resolves — after the first capture only one legal target remains, so
#// a single-target choose resolves itself and needs no answer line. Adding one would be a spare answer
#// that lands on whatever prompt comes next.
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1BASEUPGRADECOUNT:3
P1BASECAPTIVECOUNT:2
P2GROUNDARENACOUNT:0
