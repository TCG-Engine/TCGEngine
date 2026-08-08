# FriendlyLeftPlay_ForceAndShield
#// LOF_216 Disturbance in the Force — "If a friendly unit left play this phase, the Force is with you and
#// you may give a Shield token to a unit." P1's 3/1 attacker dies to counter-damage (a friendly unit left
#// play), so playing the event creates the Force and lets P1 shield its surviving SOR_095.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:LOF_216}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# NoFriendlyLeftPlay_NoForce
#// LOF_216 Disturbance in the Force — negative: no friendly unit left play this phase, so the event's
#// condition fails — no Force token and no Shield.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:LOF_216}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# TriggersAfterNGORStealsAndDefeatsEnemy
#// LOF_216 Disturbance — "if a friendly unit left play this phase." The left-play attribution uses the
#// controller AT DEFEAT TIME. P1 plays No Glory, Only Results (JTL_043: take control of an enemy unit, then
#// defeat it) on SOR_095 — it leaves play as a FRIENDLY unit (P1 controlled it when defeated) — so
#// Disturbance then creates P1's Force and shields SOR_046 (1 upgrade = the shield token).
## GIVEN
CommonSetup: rrk/ggw/{myResources:10;theirBase:SOR_021}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1Hand: LOF_216
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HASFORCE
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NoFriendlyUnitLeft_NoEffect
#// LOF_216 — with no friendly unit having left play this phase, Disturbance does nothing (no Force).
## GIVEN
CommonSetup: yyk/ggw/{myResources:5;theirBase:SOR_021}
P1OnlyActions: true
WithP1Hand: LOF_216
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NOFORCE

---

# DeclineShield_KeepsForce
#// LOF_216 Disturbance — the Shield half is a "may". P1 ALREADY holds the Force and a friendly unit left play
#// this phase (its Death Star Stormtrooper, SOR_128 3/1, dies to counter-damage). Playing Disturbance keeps
#// the (idempotent) Force but P1 declines the Shield (choose nothing) → the surviving Marine gets no Shield.
#// Intended: "should allow to not giving a Shield token when a friendly unit left play this phase (player has
#// already the Force)".

## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:LOF_216}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# TokenUnitLeft_ForceAndShield
#// LOF_216 Disturbance — a friendly TOKEN unit leaving play also satisfies the condition. P1's Battle Droid
#// token (TWI_T01, 1/1) attacks Consular Security Force (SOR_046, 3/7) and dies to the 3 counter-damage — the
#// token leaves play — so Disturbance creates P1's Force and shields the surviving Marine. Intended: "should create
#// a Force token and allow giving a Shield token when a friendly token unit left play this phase".

## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:LOF_216}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# EnemyNGORTakesMyUnit_NoForce
#// LOF_216 Disturbance — the "friendly unit left play" attribution uses the controller AT DEFEAT TIME. P2
#// plays No Glory, Only Results (JTL_043) on P1's lone Marine (SOR_095): P2 takes control, then defeats it —
#// so from P1's side the unit left play as an ENEMY unit (P2 controlled it when defeated). Disturbance then
#// finds no FRIENDLY unit left play → no Force for P1. (Mirror of the friendly-NGOR positive case.)

## GIVEN
CommonSetup: yyk/bbk/{theirBase:SOR_021}
WithActivePlayer: 2
WithP1Resources: 3
WithP2Resources: 8
WithP1Hand: LOF_216
WithP2Hand: JTL_043
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:0
