# VISUAL CHECK — active turn effects on home-tile units (purple badge + what the effect DID)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then run the WHEN steps in order. Desktop layout.
#
# WHY THIS FILE EXISTS. The home tiles rendered no turn effects at all — DisplayEffects never reached
# the mini-board renderer. That was worse than an omission, because the effects' CONSEQUENCES were
# already showing: a debuff moves the live power/HP counters and a granted keyword adds a keyword
# badge. So a Battlefield Marine under Make an Opening read a flat 1/1 — indistinguishable from a
# printed 1/1 unit that will still be 1/1 next phase. The purple badge is what says "this is
# temporary, and here is what caused it".
#
# The two cards are chosen to hit the tile's two NEW features from opposite directions:
#   SOR_076 Make an Opening   "Give a unit -2/-2 for this phase."  -> moves the LIVE STAT counters
#   SOR_156 Benthic "Two Tubes"  On Attack: another friendly Aggression unit "gains Raid 2 for this
#                                phase"                            -> adds a KEYWORD BADGE
# One effect you can only see in the numbers, one you can only see in the badges — and both must also
# raise the purple effect badge.
#
# After the WHEN steps, seat 2 reads:
#   ground 0  SOR_156 Benthic         NO effect badge   2/2   (the source, not a target — control)
#   ground 1  SOR_157 Cantina Braggart   "1"            0/3   + a RAID keyword badge
#   ground 2  SOR_095 Battlefield Marine "1"            1/1   <- printed 3/3; the debuff landed
#   seat 3    SOR_046 Consular Sec Force NO effect badge 3/7  (untouched seat — control)
#
# What to look at:
#   • The purple badge sits TOP-LEFT, the colour and corner the full board uses for DisplayEffects.
#   • ⚠ THE CORNER BUDGET IS NOW FULL. top-left = effects, top-right = attached upgrades, centre =
#     damage, bottom corners = live power/HP, bottom edge = keyword badges. Confirm none of them
#     overlaps another on the two affected units. Anything added after this has to SHARE a corner —
#     there is no sixth free spot.
#   • Battlefield Marine reads 1/1 while its card art still prints 3/3 underneath. That pair — wrong
#     printed number, right live number, badge explaining it — is the whole point of the check.
#   • Cantina Braggart shows BOTH a purple badge and a Raid keyword badge from the same effect. Its
#     power stays 0 because Raid is a while-attacking bonus, so do NOT expect the stat counters to
#     move for this one; that is the discriminator against "any effect must change the numbers".
#   • The count is DISTINCT SOURCE CARDS, not raw effect tokens — one card firing two effects is one
#     source, matching the zoomed board.
#   • CLICK a purple badge -> the panel lists the source cards with art.
#   • Benthic itself and seat 3's unit carry NO badge. Without those controls, a badge that rendered
#     on every unit would look correct here.

## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 9
WithP1Hand: [SOR_076]
WithP2GroundArena: SOR_156:1:0
WithP2GroundArena: SOR_157:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-2
- P2>AttackGroundArena:0:p1Base-0

## EXPECT
SEATCOUNT:3
P2GROUNDARENACOUNT:3
P3GROUNDARENACOUNT:1
