# PlayUnitDamageSelfShield
#// ASH_060 Cobb Vanth (Ground, 2/6, Grit) — When you play another unit: you may deal 2 damage to this
#// unit; if you do, give a Shield token to that unit. With Cobb in play, P1 plays SOR_095; answering YES
#// deals 2 to Cobb and Shields SOR_095.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1GroundArena: ASH_060:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# PlayUnit_Decline_NoShield
#// ASH_060 Cobb Vanth — the self-damage/shield is optional. Declining leaves Cobb undamaged and the played
#// unit unshielded.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1GroundArena: ASH_060:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# PilotPlayedAsUpgrade_NoTrigger
#// ASH_060 Cobb Vanth — the trigger is "when you play another UNIT". Playing a Pilot as an upgrade
#// (JTL_108 Clone Pilot piloted onto the ASH_261 vehicle) is NOT playing a unit, so Cobb does not trigger:
#// no self-damage, no shield, the pilot just attaches.
## GIVEN
CommonSetup: yyw/yyk/{myResources:8;handCardIds:JTL_108}
WithP1GroundArena: ASH_060:1:0
WithP1GroundArena: ASH_261:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:ASH_261
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# PilotPlayedAsUnit_Triggers
#// ASH_060 Cobb Vanth — the same Pilot (JTL_108) played AS A UNIT does count as playing a unit, so Cobb
#// triggers: accepting deals 2 to Cobb and Shields the newly played unit.
## GIVEN
CommonSetup: yyw/yyk/{myResources:8;handCardIds:JTL_108}
WithP1GroundArena: ASH_060:1:0
WithP1GroundArena: ASH_261:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:2:CARDID:JTL_108
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# SelfDamageAbsorbedByShield
#// ASH_060 Cobb Vanth — if Cobb has a Shield token, the 2 self-damage is absorbed by the Shield (removing it)
#// and Cobb takes 0 damage, but the played unit is still Shielded.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1GroundArena: ASH_060:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# LeaderDeploy_NoTrigger
#// ASH_060 Cobb Vanth — deploying a leader is not "playing a unit", so Cobb does not trigger when the
#// friendly leader is deployed to the ground.
## GIVEN
CommonSetup: yyw/yyk/{myResources:8}
WithP1GroundArena: ASH_060:1:0
P1OnlyActions: true
## WHEN
- P1>DeployLeader:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# OpponentPlaysUnit_NoTrigger
#// ASH_060 Cobb Vanth — the trigger only fires for units YOU play. When the opponent plays a unit, Cobb
#// (a friendly unit) does not trigger.
## GIVEN
CommonSetup: yyw/yyk/{theirResources:8;theirhandCardIds:SOR_095}
WithP1GroundArena: ASH_060:1:0
## WHEN
- P1>Pass
- P2>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# PlayCobbHimself_NoTrigger
#// ASH_060 Cobb Vanth — the trigger is "when you play ANOTHER unit". Playing Cobb himself does not count,
#// so there is no self-damage prompt.
## GIVEN
CommonSetup: yyw/yyk/{myResources:8;handCardIds:ASH_060}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# SelfDamageDefeatsCobb_PlayedUnitStillShielded
#// ASH_060 Cobb Vanth — the "if you do" gate is PAYING the 2 self-damage, not surviving it. Cobb pre-damaged
#// to 4 (2/6): playing SOR_095 and accepting deals 2 (defeating Cobb, 4+2=6), but the played unit is STILL
#// shielded (the Shield resolves as part of the same effect, before Cobb leaves).
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1GroundArena: ASH_060:1:4
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
