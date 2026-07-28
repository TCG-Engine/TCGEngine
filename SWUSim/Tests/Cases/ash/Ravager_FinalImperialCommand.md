# PlayedUnitDealsPower
#// ASH_102 Ravager (Space, 8/10, Restore 2) — When you play a unit: you may have it deal damage equal to
#// its power to a unit in the same arena. With Ravager in play, P1 plays SOR_095 (3 power); it deals 3 to
#// the enemy SEC_080 (3/3) in the ground arena, defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_102:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:0

---

# PlayedUnit_Pass
#// ASH_102 Ravager — the deal is optional. P1 plays SOR_095 with an enemy present but passes; no damage.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_102:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095

---

# PlayedUnit_TargetFriendly
#// ASH_102 Ravager — "a unit in the same arena" may be a FRIENDLY one. Played SOR_095 (3 power) deals 3 to
#// the friendly SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_102:1:0
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095

---

# PlaySpaceUnit_DealsInSpace
#// ASH_102 Ravager — the "same arena" is the played unit's arena. A played SPACE unit SOR_237 (2 power)
#// deals 2 to the enemy space token JTL_T02 (2/2), defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_237}
WithP1SpaceArena: ASH_102:1:0
WithP2SpaceArena: JTL_T02:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0

---

# UpgradePlay_NoTrigger
#// ASH_102 Ravager — the trigger is "when you play a UNIT." Playing an upgrade (SOR_120 on SOR_095) is not a
#// unit play, so Ravager does not fire and the enemy takes no damage.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_120}
WithP1SpaceArena: ASH_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# RavagerSelfPlay_Deals8
#// ASH_102 Ravager — the "when you play a unit" trigger also fires when RAVAGER ITSELF is played: the
#// played unit is Ravager, so it deals its own 8 power to a unit in the space arena. P1 plays Ravager
#// (cost 9) and blasts the enemy JTL_243 Quasar TIE Carrier (5/7) in space, defeating it.
## GIVEN
CommonSetup: ggk/ggk/{myResources:9;handCardIds:ASH_102}
WithP2SpaceArena: JTL_243:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_102

---

# PlayedUnit_FriendlySpace
#// ASH_102 Ravager — a played SPACE unit may deal its power to a FRIENDLY space unit. Ravager in play,
#// P1 plays SOR_237 (2 power); it deals 2 to the friendly SOR_178 Cartel Spacer (2/3) in space.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_237}
WithP1SpaceArena: ASH_102:1:0
WithP1SpaceArena: SOR_178:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-1
## EXPECT
P1SPACEARENAUNIT:1:CARDID:SOR_178
P1SPACEARENAUNIT:1:DAMAGE:2

---

# LeaderDeploy_NoTrigger
#// ASH_102 Ravager — deploying a LEADER is not "playing a unit," so Ravager does not fire. P1 deploys its
#// leader; the enemy space unit takes no damage and no decision dangles.
## GIVEN
CommonSetup: yyw/yyk/{myResources:9;myLeader:SOR_011:1:0}
WithP1SpaceArena: ASH_102:1:0
WithP2SpaceArena: SOR_178:1:0
P1OnlyActions: true
## WHEN
- P1>DeployLeader:0
## EXPECT
P1LEADER:DEPLOYED
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# TokenCreated_NoTrigger
#// ASH_102 Ravager — creating token UNITS (via an event) is not "playing a unit," so Ravager does not
#// fire. P1 plays TWI_251 Drop In (create 2 Clone Trooper tokens); the enemy takes no damage.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:TWI_251}
WithP1SpaceArena: ASH_102:1:0
WithP2SpaceArena: SOR_178:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# ZeroPowerUnit_NoTrigger
#// ASH_102 Ravager — a played unit with 0 power would "deal 0", which has no valid effect, so Ravager does not
#// trigger (no prompt). Playing SOR_157 Cantina Braggart (0/3) with Ravager and an enemy present yields no
#// decision.
## GIVEN
CommonSetup: ggk/brk/{myResources:1;handCardIds:SOR_157}
WithP1GroundArena: ASH_102:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# PlayBlueLeader_DealsInSpaceBeforeMove
#// ASH_102 Ravager — a played unit deals its power in the arena it OCCUPIES when Ravager resolves. Blue Leader
#// (JTL_096, 3 power) is played into the space arena; its play stacks two triggers with Ravager's. Resolving
#// Ravager FIRST (while Blue Leader is still a 3-power SPACE unit) deals 3 to a space unit — here Ravager
#// itself → 3 damage on Ravager. Only THEN does Blue Leader's When Played resolve (pay 2 → move to the ground
#// arena as a 5/5), confirming Ravager used the space arena / 3 power at resolution time.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: JTL_096
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_102
P1SPACEARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:POWER:5

---

# PlayBlueLeader_DealsInGroundAfterMove
#// ASH_102 Ravager — when Blue Leader's When Played (move to the ground arena + 2 Experience) is resolved
#// FIRST, Blue Leader is a 5-power GROUND unit by the time Ravager resolves, so Ravager deals 5 in the ground
#// arena. Blue Leader (now 5/5) blasts the enemy SEC_080 (3/3) for 5, defeating it.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: JTL_096
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENACOUNT:0
