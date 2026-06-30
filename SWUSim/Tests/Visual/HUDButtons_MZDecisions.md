# VISUAL CHECK — chamfered cyan HUD buttons across the MZ decision UIs
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it
# in the Test Schema Editor and PLAY the hand cards one at a time to eyeball the
# restyled decision-queue buttons (the SWUSim chamfered-cyan HUD sweep in
# GameLayoutShared.php). Works on desktop and ?swuLayout=mobile.
#
# P1 (me) has 20 resources (CommonSetup gbk/grw) so cost/aspect penalties are a non-issue.
#
# Play each card to exercise a button set:
#   • SOR_092  Overwhelming Barrage  → choose a friendly unit (inline MZChoose), then the
#                                       MZSplitAssign panel: .mzsplit-btn-minus / -plus
#                                       steppers + .mzsplit-submit-btn.
#   • JTL_244  There Is No Escape     → MZMultiChoose panel (choose up to 3 units):
#                                       .mzmulti-btn-primary / .mzmulti-btn-secondary.
#   • SOR_042  Search Your Feelings   → MZChoose card-picker MODAL (search deck for 1 card):
#                                       .mzmodal-submit-btn.
#
# SEPARATE view check:
#   • JTL_041  Annihilator (space Capital Ship) → deploy it, defeat an enemy Battlefield
#               Marine (SOR_095); its On-Play searches that controller's deck+hand for
#               same-named cards — P2 holds extra SOR_095 copies so the search/reveal
#               views populate. Big landscape-ish unit + opponent deck/hand reveal.
#
# Board units (both arenas, both players) exist so SOR_092 / JTL_244 have targets.
# No WHEN steps — interaction is manual.

## GIVEN
CommonSetup: gbk/grw
WithP1Resources: 20

# Targets for SOR_092 (divide damage) and JTL_244 (choose up to 3 units)
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArena: JTL_T01:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArena: JTL_T01:1:0

# My hand — one card per button set, plus Annihilator (separate view check)
WithP1Hand: SOR_092 JTL_244 SOR_042 JTL_041

# P1 deck so SOR_042's search modal has cards to pick from
WithP1Deck: SOR_095 SOR_123 SOR_092 JTL_244 SOR_042

# P2 deck + hand carry extra Battlefield Marines so Annihilator's name-search shows hits
WithP2Deck: SOR_095 SOR_095 SOR_095
WithP2Hand: SOR_095

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:2
P2GROUNDARENACOUNT:2
P2SPACEARENACOUNT:2
P1HANDCOUNT:4
P1RESCOUNT:20
