# WhenDamagedInCombat_DealsToUnique
#// SEC_143 The Elite Squad — "When damage is dealt to this unit: you may deal 2 damage to another unique
#//   unit." P1's SEC_143 (6/8) attacks P2's SEC_080 (3/3, non-unique), defeats it, and takes 3 counter
#//   (survives). That counter damage triggers the reaction → P1 deals 2 to another unique unit (LOF_093,
#//   at index 0 after cleanup — the offer builds POST-cleanup via the queued builder, so its pool is
#//   live). Proves the post-damage combat reaction.

## GIVEN
CommonSetup: rrk/grk
P1OnlyActions: true
WithP1GroundArena: SEC_143:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_093
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayed_DealsToUniqueUnit
#// SEC_143 The Elite Squad — When Played: you may deal 2 damage to another unique unit. P1 plays SEC_143
#//   (unique); the only OTHER unique unit is P2's LOF_093 (2/5), which takes 2. (Non-unique units would not
#//   be offered; SEC_143 itself is excluded as "another".)

## GIVEN
CommonSetup: rrk/grk/{myResources:8;handCardIds:SEC_143}
P1OnlyActions: true
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_Decline_NoDamage
#// SEC_143 The Elite Squad — the deal-2 is optional ("you may"). P1 plays SEC_143 and declines → LOF_093
#//   is untouched.

## GIVEN
CommonSetup: rrk/grk/{myResources:8;handCardIds:SEC_143}
P1OnlyActions: true
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_TargetFriendlyUnique
#// SEC_143 The Elite Squad — When Played: "another unique unit" includes friendly ones. P1 plays SEC_143 with
#//   the friendly unique SOR_045 Yoda already in play → deal 2 to Yoda.

## GIVEN
CommonSetup: rrk/grk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP1Hand: SEC_143

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_045
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SEC_143
P1NODECISION

---

# WhenPlayed_TargetSpaceUnique
#// SEC_143 The Elite Squad — the target may be in any arena. P1 plays SEC_143 (ground) and deals 2 to the enemy
#//   unique SPACE unit SOR_099 Bright Hope.

## GIVEN
CommonSetup: rrk/grk/{myResources:8}
P1OnlyActions: true
WithP2SpaceArena: SOR_099:1:0
WithP1Hand: SEC_143

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# WhenPlayed_NoUniqueTarget_NoTrigger
#// SEC_143 The Elite Squad — the ability needs "another unique unit"; with only non-unique units around (SEC_080
#//   Dark Trooper, SOR_164 Wampa) and itself excluded, nothing is dealt and no prompt appears.

## GIVEN
CommonSetup: rrk/grk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SEC_143

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_143
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION

---

# WhenDamagedByEvent_DealsToUnique
#// SEC_143 The Elite Squad — the reaction fires on ANY damage, including a card ability. P2 plays SHD_178 Daring
#//   Raid (deal 2) on P1's Elite Squad → it takes 2 and the reaction lets P1 deal 2 to the friendly unique Yoda.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 3
WithP1GroundArena: SEC_143:1:0
WithP1GroundArena: SOR_045:1:0
WithP2Hand: SHD_178

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SOR_045
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# WhenDamagedWhileDefending_DealsToUnique
#// SEC_143 The Elite Squad — the reaction fires when it takes combat damage as the DEFENDER. P2's SOR_164 Wampa
#//   (4 power) attacks Elite Squad → it takes 4 and survives (8 HP), then P1 deals 2 to the friendly unique Yoda.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_143:1:0
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:CARDID:SOR_045
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# WhenDefeatedByCombat_StillDealsToUnique
#// SEC_143 The Elite Squad — "When damage is dealt to this unit" has no "and survives" clause, so it fires
#// even when the incoming damage DEFEATS Elite Squad. P1's SEC_143 (6/8, pre-damaged to 2 remaining HP) is
#// attacked by P2's SEC_080 (3 power) and defeated; the reaction still fires → P1 deals 2 to another unique
#// (LOF_093, at index 0 after cleanup — the queued builder makes the offer post-cleanup).

## GIVEN
CommonSetup: rrk/grk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_143:1:6
WithP1GroundArena: LOF_093:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_093
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_TargetsAUniqueLEADERUnit
#// SEC_143 The Elite Squad — "another UNIQUE unit" includes a deployed leader, which is always unique.
#// With P2's leader deployed as the only other unique unit on the board, the When Played damage lands
#// on it for 2.

## GIVEN
CommonSetup: rrk/grk/{theirLeaderDeployed:true;myResources:8}
P1OnlyActions: true
WithP1Hand: SEC_143

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenDamagedByIndirectDamage_AlsoTriggers
#// SEC_143 The Elite Squad — "when damage is dealt to this unit" does not care about the damage's kind,
#// so INDIRECT damage arms the reaction too. P2 plays JTL_234 Torpedo Barrage at P1, and P1 (the damaged
#// player, who assigns indirect damage themselves) puts 3 on the Elite Squad and 2 on their own base.
#// The reaction then deals 2 to the other unique unit, P2's LOF_093.

## GIVEN
CommonSetup: rrk/yyk
WithActivePlayer: 2
WithP2Resources: 3
WithP1GroundArena: SEC_143:1:0
WithP2GroundArena: LOF_093:1:0
WithP2Hand: JTL_234

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:myGroundArena-0:3,myBase-0:2
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
