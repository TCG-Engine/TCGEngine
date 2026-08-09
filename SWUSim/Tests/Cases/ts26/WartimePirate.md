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
