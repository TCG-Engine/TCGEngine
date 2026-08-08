# DeclineDisclose_NoDebuff
#// SEC_052 Diplomatic Immunity — the granted On Defense disclose is OPTIONAL. P2 declines
#//   (AnswerDecision:-), so the attacker keeps its full power 3. Host (5/9) takes 3 (DAMAGE:3) and
#//   counters 5 onto the attacker. Proves the decline path no-ops cleanly and the upgrade seam still
#//   pauses combat correctly even when the reaction is declined.

## GIVEN
CommonSetup: ggw/ggw/{theirHandCardIds:SOR_046,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:3

---

# OnDefense_Disclose_AttackerDebuff
#// SEC_052 Diplomatic Immunity (Upgrade +2/+2, Vigilance/Heroism) — grants the host:
#//   "When this unit is attacked: you may disclose VigilanceVigilanceHeroismHeroism → the attacker gets
#//   -2/-0 for this attack." (Granted On Defense reaction via the onDefenseFromUpgrade seam.)
#// P2's host SOR_046 (3/7) + SEC_052 = 5/9. P1's SOR_046 (3/7, power 3) attacks it; before damage P2
#// discloses 2x SOR_046 (Vigilance,Heroism → covers VVHH) → attacker becomes power 1 for this attack.
#// Host takes only 1 (3-2); host counters 5 onto attacker. After the attack the debuff expires, so the
#// attacker's POWER is back to 3 (proves SWU_DUR_ATTACK duration, not a lingering phase debuff).

## GIVEN
CommonSetup: ggw/ggw/{theirHandCardIds:SOR_046,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:3
P2HANDCOUNT:2

---

# SpaceUnitDefending_Disclose
#// SEC_052 Diplomatic Immunity — the granted On Defense disclose works for a SPACE host too. P2's host
#//   SOR_178 (2/3) + SEC_052 = 4/5. P1's LOF_119 (4/10, power 4) attacks it; P2 discloses 2x SOR_046
#//   (Vigilance,Heroism each → covers VVHH) → attacker gets -2/-0 → power 2 for this attack. Host takes
#//   2, counters 4 onto the attacker. After the attack the debuff expires (attacker POWER back to 4).

## GIVEN
CommonSetup: ggw/ggw/{theirHandCardIds:SOR_046,SOR_046}
P1OnlyActions: true
WithP1SpaceArena: LOF_119:1:0
WithP2SpaceArena: SOR_178:1:0
WithP2SpaceArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:4
P1SPACEARENAUNIT:0:POWER:4

---

# AttachedUnitAttacking_NoDebuff
#// SEC_052 Diplomatic Immunity — the disclose reaction is On DEFENSE only ("When this unit is attacked").
#//   When the attached unit is the ATTACKER, there is no disclose and no -2/-0. P1's host SOR_046 (3/7)
#//   + SEC_052 = 5/9 attacks P2's base for its full power 5, with no decision offered.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1NODECISION

---

# NoDiscloseCards_AutoSkip
#// SEC_052 Diplomatic Immunity — the disclose is auto-skipped when the defending player cannot cover
#//   VigilanceVigilanceHeroismHeroism (CR 38.3). P2's host SOR_046 (3/7) + SEC_052 = 5/9 is attacked by
#//   P1's SOR_046 (power 3); P2 has an empty hand → no prompt, attacker keeps full power 3. Host takes 3,
#//   counters 5.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:5
P2NODECISION

---

# AttachedToENEMYUnit_ItsControllerResolvesTheDisclose
#// SEC_052 Diplomatic Immunity — CR 2.e: a player may play an upgrade onto an ENEMY unit and still
#// controls it, but "if that upgrade gives abilities to the attached unit, THE UNIT'S CONTROLLER resolves
#// those abilities". So P1 attaching Diplomatic Immunity to P2's unit hands P2 the granted On Defense
#// disclose — and it works against P1's own attacker. P1 attaches it to P2's SOR_046 (now 5/9), then
#// attacks with its own SOR_046; P2 discloses and P1's attacker drops to power 1 for the attack.
## GIVEN
CommonSetup: ggw/ggw/{theirHandCardIds:SOR_046,SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_052
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myHand-0&myHand-1
## EXPECT
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:3

---

# AttachedToADeployedLEADERUnit_DiscloseWorks
#// SEC_052 Diplomatic Immunity — a deployed leader IS a unit, so it can host the upgrade and use the
#// granted On Defense disclose. P2's deployed leader (TWI_003 Obi-Wan, 5/7) wears Diplomatic Immunity
#// (7/9); P1's SOR_046 attacks it and P2 discloses, dropping the attacker to power 1 for the attack.
## GIVEN
CommonSetup: ggw/ggw/{theirLeader:TWI_003;theirLeaderDeployed:true;theirHandCardIds:SOR_046,SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SEC_052
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myHand-0&myHand-1
## EXPECT
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:POWER:3

---

# AttachedToAnEnemySPACEUnit_ItsControllerResolvesTheDisclose
#// SEC_052 Diplomatic Immunity — the granted On Defense reaction belongs to the HOST's controller in
#// every arena, not just on the ground. P1 plays the upgrade onto P2's SPACE unit (CR 2.e/3.5: P1 keeps
#// owning the upgrade, but the unit's controller resolves the ability it grants). P1's JTL_069 attacks
#// at 4 - 2 = 2, so the defending SOR_237 (2/3 wearing the +2/+2 upgrade = 4/5) takes only 2 and lives.

## GIVEN
CommonSetup: ggw/ggw/{theirHandCardIds:SOR_046,SOR_046}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# AttachedToAnEnemyDEPLOYEDLEADERUnit_DiscloseWorks
#// SEC_052 Diplomatic Immunity — a deployed leader is a unit, so it can host the upgrade and resolve the
#// granted On Defense just like any other unit. P1 attaches it to P2's DEPLOYED LEADER and attacks:
#// P2 discloses and P1's attacker is debuffed for the attack.

## GIVEN
CommonSetup: ggw/ggw/{theirLeaderDeployed:true;theirHandCardIds:SOR_046,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SEC_052

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
