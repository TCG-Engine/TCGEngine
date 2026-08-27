# ThreeExhausted_Shield
#// JTL_199 Blade Squadron B-Wing — When Played: If another player controls 3+ exhausted units, give a
#// Shield token to a unit. P2 has 3 exhausted units, so P1 shields the newly-played JTL_199.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_199
WithP1Resources: 3
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_199
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# TwoExhausted_NoShield
#// JTL_199 Blade Squadron B-Wing — with only 2 exhausted enemy units the condition is not met, so no
#// Shield is granted (no decision pending).

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_199
WithP1Resources: 3
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_199
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# Offer_AnyUnitEitherSide
#// JTL_199 Blade Squadron B-Wing — "give a Shield token to A UNIT". The printed text carries no
#// "friendly" qualifier and no arena limit, so once the 3-exhausted-units condition is met the pool must
#// be EVERY unit in play: the newly-played B-Wing itself, another friendly unit, and all three exhausted
#// enemy units across both arenas.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_199
WithP1Resources: 3
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SPACEARENAUNIT:0:CARDID:JTL_199
P1SELECTABLEEXACT:mySpaceArena-0&myGroundArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# TwinSuns_AnotherPLAYERAtAnySeatCounts
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — batch 4, an EXISTENTIAL condition.
#// "If ANOTHER PLAYER controls 3 or more exhausted units" — any other seat satisfies it, and note the
#// text says another PLAYER, not another opponent, so in a team game a TEAMMATE counts too. This read
#// GetOpponent(), which checks ONE seat and returns null above seat 2 — so a far-seat caster counted
#// zero and the ability silently did nothing.
#// Here the 3 exhausted units are on SEAT 4 and nobody else has any, so the condition can only be met by
#// looking past seat 2. The Shield offer appearing at all is the assertion.
#// ⚠ The count is per PLAYER, never a sum across players — "a player controls 3+", not "3+ exist".
## GIVEN
CommonSetup: byw/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: JTL_199
WithP1Resources: 3
WithP4GroundArena: SEC_080:0:0
WithP4GroundArena: SOR_095:0:0
WithP4SpaceArena: SOR_237:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
SEATCOUNT:4
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
