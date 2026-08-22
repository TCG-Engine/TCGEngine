# LetTheWookieeWin_Mode1_ReadyResources
#// SHD_205 Let the Wookiee Win — "An opponent chooses one: [You ready up to 6 resources] OR [ready a
#// friendly unit...]." P1 plays it (cost 2, leaving 4 ready of 6); the opponent picks the ready-resources
#// mode, so P1's 2 spent resources are readied back → all 6 ready.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_205

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Ready6Resources

## EXPECT
P1RESAVAILABLE:6

---

# LetTheWookieeWin_Mode2_ReadyWookieeAttack
#// SHD_205 Let the Wookiee Win — the second mode: "You ready a friendly unit. If it's a Wookiee unit,
#// attack with it. It gets +2/+0 for this attack." The opponent picks this mode; P1 readies the exhausted
#// SHD_249 (Wookiee, 2 power), which attacks the base for 2 + 2 = 4.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_205
WithP1GroundArena: SHD_249:0:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:ReadyUnit
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4

---

# TwinSuns_TheCHOSENOpponentMakesTheModeChoice
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, PROMPT). "An opponent chooses one" — the caster picks
#// WHICH opponent does the choosing; OtherPlayer() picked one silently.
#// ⚠ NO $eligible filter: BOTH modes read and mutate only the CASTER's own resources and units, so no
#// property of a candidate opponent can make them unable to choose. Taxonomy shape 3 — the pool the chosen
#// player acts on is not theirs at all. Same shape as LOF_065 Watto.
#// P1 hands the choice to SEAT 3, who must own the OPTIONCHOOSE. Seat 2 — whom the old code always asked —
#// must have no decision at all.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// ⚠ FIXTURE: keep the existing section's yyw/yyw aspects and 6 resources — SHD_205 is Cunning/Heroism,
#//   so an off-aspect deck adds a penalty that pushes the cost above a small resource pool and the card
#//   simply never gets played (the first attempt at this section failed for exactly that reason).
#// Mutation check: revert to OtherPlayer() and this reds (the choice lands on seat 2).

## GIVEN
CommonSetup: yyw/yyw/{myResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SHD_205
WithP1GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HASDECISION
P2NODECISION
P4NODECISION
