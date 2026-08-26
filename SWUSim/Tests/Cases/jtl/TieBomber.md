# OnAttack_3Indirect
#// JTL_237 TIE Bomber — On Attack: 3 indirect damage to the defending player. Power 0, so its base attack
#// deals no combat damage; the 3 indirect lands on P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# OnAttack_IndirectSplitUnitAndBase
#// JTL_237 TIE Bomber — On Attack: 3 indirect to the defending player. The Bomber has power 0, so its
#// base attack deals NO combat damage — making the base total a clean read of the indirect assignment.
#// With an enemy unit in play, P2 ASSIGNS the 3 indirect across a unit AND the base: 1 to their 1-HP
#// SOR_128 (defeats it) + 2 to their base. P2 base = 0 combat + 2 indirect = 2; SOR_128 is defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1NODECISION

---

# TwinSuns_IndirectGoesToTheACTUALDefendingPlayer
#// ⚠ REPORTED BUG (2026-08-25): the indirect damage "defaulted to the same player regardless of who was
#// attacked". JTL_237's text names "the DEFENDING player" — a seat the board has already DETERMINED, not
#// a choice — but the handler resolved it with OtherPlayer($player), which is literally
#// `$player === 1 ? 2 : 1`. At four seats that always answers seat 2 for seat 1, so a TIE Bomber that
#// attacks seat 4's base sends its 3 indirect to seat 2 — a player not even in the combat.
#//
#// The fix is SWUCurrentDefendingSeat($player), reading SWU_CURRENT_DEFENDING_SEAT, which CombatLogic
#// already publishes from the target mzID before On-Attack triggers are collected.
#//
#// The Bomber has power 0, so its base attack deals NO combat damage and every point on any base is
#// indirect. Seats 2 and 3 are asserted at ZERO — without those the section would pass for a build that
#// simply sprayed all opponents. No units anywhere, so the assignment auto-resolves onto the lone base.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001; theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: JTL_237:1:0

## WHEN
- P1>AttackSpaceArena:0:P4B

## EXPECT
SEATCOUNT:4
P4BASEDMG:3
P2BASEDMG:0
P3BASEDMG:0
