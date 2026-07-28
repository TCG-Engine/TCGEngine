# OnAttackDealPowerIfBountyHunter
#// LAW_064 Zuckuss (3/5, Saboteur) — On Attack: if you control another Bounty Hunter unit, you may deal
#// damage equal to this unit's power to a ground unit. P1 controls LAW_124 (Bounty Hunter); Zuckuss
#// attacks the base and deals 3 to the enemy SOR_046.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# MayPassAbility
#// LAW_064 Zuckuss — the On Attack damage is a "you may", so it can be declined. Zuckuss attacks the
#// base while controlling LAW_124 (a Bounty Hunter); pass the ability -> the enemy Wampa takes no damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoOtherBountyHunter
#// LAW_064 Zuckuss — with NO other friendly Bounty Hunter, the On Attack ability does not fire. Zuckuss
#// attacks the base alone; the enemy Wampa takes no damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# UpgradesIncreaseDamage
#// LAW_064 Zuckuss — the damage equals this unit's CURRENT power. With two Experience upgrades Zuckuss is
#// 5/7, so he deals 5 to the enemy AT-ST (6/7, survives).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# OpponentBountyHunterNotCounted
#// LAW_064 Zuckuss — only a unit YOU control counts as "another Bounty Hunter". Zuckuss attacks alone while
#// the opponent controls a Bounty Hunter (Greedo); the ability does not fire and Greedo takes no damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP2GroundArena: SOR_204:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_204
P2GROUNDARENAUNIT:0:DAMAGE:0
