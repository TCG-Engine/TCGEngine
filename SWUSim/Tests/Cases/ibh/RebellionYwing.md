# OnAttack_DealOneToBase
#// IBH_006 Rebellion Y-Wing (Space, 2/3, Cunning/Heroism) — On Attack: deal 1 damage to a base. The
#//   Y-Wing attacks an enemy 2/1 space unit (combat → the unit, which dies); the On Attack separately
#//   deals 1 to the enemy base. Isolates the On Attack base damage from combat.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: IBH_006:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# Reprints024_032
#// IBH_024 / IBH_032 Rebellion Y-Wing (reprints of IBH_006) — On Attack: deal 1 to a base. Two reprints,
#//   each attacks the enemy base directly; base takes combat (2) + On Attack (1) = 3.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: IBH_024:1:0

## WHEN
- P1>AttackSpaceArena:0:theirBase-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P1NODECISION

---

# OfferPool_BaseClauseOffersEitherBase
#// IBH_006 — "Deal 1 damage to a base" names no controller, so the pick is a genuine two-candidate
#// choose. Guards the regression where this was hardcoded to the enemy base behind a stale comment
#// claiming a base choose could not survive an On Attack.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:theirBase-0

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# OnAttack_CanChooseYourOwnBase
#// IBH_006 — picking your OWN base sends the 1 there while combat (power 2) still lands on the enemy
#// base, so the two totals separate: own base 1, enemy base 2. Also proves the choose survives an
#// attack made DIRECTLY on the base, which is the exact case the stale workaround claimed was broken.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:theirBase-0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:2

---

# TeamSuns_OfferPoolIncludesTheTEAMMATESBase
#// IBH_006 "deal 1 damage to A BASE" — unqualified, so the pool is EVERY live seat's base: your own, your
#// PARTNER's, and both opponents'. SWUAllBaseMzIDs(…,'any') used to be 'my' + 'their', and "their" is
#// ZoneSearch's opponent fan-out, so the teammate's base was silently missing from ~35 unqualified
#// "a base" offers. Answering p3Base-0 is the assertion: it is only a candidate once the pool is
#// team + their. (SWUAllUnits() already documents this exact hole for units; bases were left behind.)

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
WithTeams: true
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1SpaceArena: IBH_006:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:P2S0
- P1>AnswerDecision:p3Base-0

## EXPECT
SEATCOUNT:4
P3BASEDMG:1
P2BASEDMG:0
P4BASEDMG:0
P1BASEDMG:0
