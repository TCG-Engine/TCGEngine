# PreventsEnemyAbilityDamage
#// SEC_042 (Ground, 2/2) — If an enemy card ability would deal damage to this unit, prevent 2. SEC_042
#//   is on P2's side; P1 plays SEC_152 (When Played: deal 2 to a ready unit) targeting it → the 2 is
#//   prevented down to 0 damage.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SEC_042:1:0
WithP1Hand: SEC_152

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# WhileDefending_AttackerMinus2
#// SEC_042 (Ground, 2/2) — While defending, the attacker gets -2/-0. P2's SOR_046 (3/3) attacks
#//   SEC_042; attacker power 3-2 = 1 → SEC_042 takes 1 (survives, 2 HP). SEC_042 deals 2 back → SOR_046
#//   takes 2 (survives, 3 HP).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:2
