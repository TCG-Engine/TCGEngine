# OnAttack_OpponentAutoDefeatsNonLeader
#// SOR_040 Avenger (8/8 Space) — "When Played/On Attack: An opponent chooses a non-leader unit they
#// control. Defeat that unit." Here the On Attack window: Avenger attacks the base; the opponent has a
#// single non-leader unit (SEC_080), so the forced choice defeats it directly (no decision), then the
#// 8 combat damage lands on the base.

## GIVEN
CommonSetup: bbk/brw/{
  theirLeader:SOR_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_040:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:8
P1SPACEARENAUNIT:0:EXHAUSTED

---

# WhenPlayed_IdenInteraction
#// SOR_040 Avenger (8/8 Space, cost 9) — the When Played window with a real choice. P1 plays Avenger;
#// the opponent controls TWO non-leader units (SEC_080, SOR_128) and chooses which to defeat. Here the
#// opponent picks myGroundArena-1 (SOR_128), leaving SEC_080 (reindexed to 0). SOR_002/SOR_021 cover
#// Vigilance+Villainy so Avenger plays at its printed cost 9.
#// Iden should be allowed to heal 2 at the end

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P2>AttackGroundArena:0
- P1>UseLeaderAbility
- P2>Claim
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:1

---

# WhenPlayed_OpponentChoosesNonLeader
#// SOR_040 Avenger (8/8 Space, cost 9) — the When Played window with a real choice. P1 plays Avenger;
#// the opponent controls TWO non-leader units (SEC_080, SOR_128) and chooses which to defeat. Here the
#// opponent picks myGroundArena-1 (SOR_128), leaving SEC_080 (reindexed to 0). SOR_002/SOR_021 cover
#// Vigilance+Villainy so Avenger plays at its printed cost 9.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
