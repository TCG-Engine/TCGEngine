# OnAttackOpponentDeals1
#// TS26_66 Wartime Pirate (Unit 4/4 space, cost 3) — On Attack: an opponent deals 1 damage to a unit.
#// Wartime Pirate attacks JTL_069; the opponent (P2) chooses to deal 1 to P1's SEC_080.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: TS26_66:1:0
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: JTL_069:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# TheOpponentCanKillTheATTACKERBeforeCombatDamage
#// TS26_66 Wartime Pirate — "On Attack: AN OPPONENT deals 1 damage to a unit", and the opponent picks the
#// target. With the Pirate already on 3 damage of its 4 HP, P2 aims that 1 at the Pirate itself: it is
#// defeated inside the On Attack window, so combat damage is never dealt and the defender is untouched.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: TS26_66:1:3
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# TwinSuns_TheCHOSENOpponentDealsTheDamage
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, PROMPT). "AN opponent deals 1 damage to a unit" —
#// the Pirate's controller chooses WHO deals it; OtherPlayer() picked one silently.
#// ⚠ NO $eligible filter: the target pool is "a unit", unqualified, so it spans every seat's board and is
#// IDENTICAL whichever opponent is chosen — no opponent can be pre-filtered as unable to act. The only
#// guard is board-level (no unit anywhere ⇒ nobody can act). Same shape as TS26_54.
#// P1's Pirate attacks; P1 hands the trigger to SEAT 3, and SEAT 3 owns the target decision — seat 2, whom
#// the old code always picked, must have NO decision at all.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer() and this reds (the decision lands on seat 2).

## GIVEN
CommonSetup: rrk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1SpaceArena: TS26_66:1:0
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: JTL_069:1:0
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackSpaceArena:0:P2S0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HASDECISION
P2NODECISION
