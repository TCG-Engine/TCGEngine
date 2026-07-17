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
