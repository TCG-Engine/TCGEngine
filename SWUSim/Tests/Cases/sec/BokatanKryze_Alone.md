# EnemyDefeated_GiveExp
#// SEC_051 Bo-Katan Kryze — "When an enemy unit is defeated: give an Experience token to a friendly unit."
#//   SOR_095 kills SOR_128; Bo-Katan's reaction gives an Experience token to SOR_095.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_051:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_051
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# WhenPlayed_EnemyMinus33
#// SEC_051 Bo-Katan Kryze (Ground, 8/8, cost 9) — When Played: give each enemy unit -3/-3 for this phase.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_051

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:4
P1NODECISION

---

# WhenPlayed_DefeatedUnitFirst_LaterUnitStillDebuffed
#// SEC_051 Bo-Katan Kryze — the -3/-3 must hit EVERY enemy unit even when an EARLIER-indexed unit is
#//   defeated by the debuff mid-resolution. IBH_076 Rampaging Wampa (6/3) at index 0 drops to 3/0 and is
#//   defeated; the later SOR_164 Wampa (4/5) at index 1 must STILL become 1/2 — regression: the defeat
#//   removed index 0 and shifted the remaining captured mzIDs, so the later unit was skipped and stayed 4/5.
#//   Fix: all debuffs apply simultaneously, then state-based defeats resolve once.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: LOF_254:1:0
WithP1Hand: SEC_051
WithP2GroundArena: IBH_076:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:2
P1NODECISION

---

# WhenPlayed_Minus33_KillsAndFriendlyUnaffected
#// SEC_051 Bo-Katan Kryze — the When Played -3/-3 hits every enemy unit: SOR_164 Wampa (4/5) → 1/2, and
#//   IBH_076 Rampaging Wampa (6/3) → 3/0 which is defeated. Friendly LOF_254 Porg is untouched (1/1). The
#//   Rampaging Wampa defeat triggers "when an enemy unit is defeated: give a friendly unit an Experience
#//   token"; here the token goes to Bo-Katan.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: LOF_254:1:0
WithP1Hand: SEC_051
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: IBH_076:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# WhenPlayed_Minus33_ExpiresNextPhase
#// SEC_051 Bo-Katan Kryze — the -3/-3 lasts "for this phase" only. After passing to the next action phase,
#//   the surviving SOR_164 Wampa is back to its printed 4/5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SEC_051
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:5

---

# OnPlayDefeat_ExpSelectableExactly
#// SEC_051 Bo-Katan Kryze — when the -3/-3 defeats IBH_076 Rampaging Wampa on play, the Experience
#//   token may go to any friendly unit: exactly Bo-Katan or the Porg.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: LOF_254:1:0
WithP1Hand: SEC_051
WithP2GroundArena: IBH_076:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# EnemyDefeat_ExpToPorg
#// SEC_051 Bo-Katan Kryze — routing the on-play-defeat Experience token to the Porg makes it a 2/2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: LOF_254:1:0
WithP1Hand: SEC_051
WithP2GroundArena: IBH_076:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1NODECISION

---

# FriendlyDefeat_NoExp
#// SEC_051 Bo-Katan Kryze — the reaction is "when an ENEMY unit is defeated". A FRIENDLY unit dying does
#//   not trigger it: P2's Wampa attacks and defeats the friendly Porg, and Bo-Katan gains no Experience.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_051:1:0
WithP1GroundArena: LOF_254:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_051
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# ControllerNGOR_NoTrigger
#// SEC_051 Bo-Katan Kryze — Bo-Katan's controller (P1) uses No Glory, Only Results (JTL_043) on the enemy
#//   Wampa. No Glory takes control FIRST, so the defeat is FRIENDLY and Bo-Katan's enemy-defeat reaction
#//   does not fire — no Experience token.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP1GroundArena: SEC_051:1:0
WithP1GroundArena: LOF_254:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# OpponentNGOR_CountsAsAnEnemyDefeat_GivesExperience
#// SEC_051 Bo-Katan Kryze — the mirror of ControllerNGOR_NoTrigger. Here the OPPONENT (P2) plays
#// JTL_043 No Glory, Only Results on P1's LOF_254: control moves to P2 first, so at the moment of the
#// defeat the unit is an ENEMY unit from Bo-Katan's side. Her reaction fires and P1 gives an Experience
#// token to a friendly unit — Bo-Katan herself, the only friendly left.

## GIVEN
CommonSetup: bbw/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SEC_051:1:0
WithP1GroundArena: LOF_254:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_051
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:0
