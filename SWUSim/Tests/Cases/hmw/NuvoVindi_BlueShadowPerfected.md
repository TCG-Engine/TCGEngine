# WhenPlayed_MayGiveWeaknessToAUnit
#// HMW_062 Nuvo Vindi, Blue Shadow Perfected — Unit (Ground) 1/4, cost 3, [Vigilance][Villainy],
#// Separatist, unique.
#// CLAUSE A — "When Played: You may give a Weakness token to a unit."
#// Weakness (HMW_T02) is a -1/-1 Token Upgrade, so SOR_046 (3/7) reads 2/6.

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myhandCardIds:HMW_062}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6

---

# WhenPlayed_Decline_NoToken
#// Clause A is optional — the decline branch. MZMAYCHOOSE declines with `-`.

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myhandCardIds:HMW_062}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayed_FriendlyUnitIsALegalTarget
#// "a unit" is unqualified, so a friendly unit — including Vindi himself, who is in play by the time
#// his own When Played resolves — is a legal target. Vindi is 1/4; a Weakness makes him 0/3.

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myhandCardIds:HMW_062}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_062
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:3

---

# Reaction_WeakenedEnemyDefeated_MayGiveAnotherWeakness
#// CLAUSE B — "When an enemy unit with a Weakness token on it is defeated: You may give a Weakness
#// token to a unit. Use this ability only once each round."
#// The Weakness must be read at DEFEAT time: by the time the reaction resolves the dying unit's
#// subcards are already stripped, so an implementation that inspects the unit afterwards sees nothing.
#// SOR_128 Death Star Stormtrooper is 3/1 and pre-weakened, so ANY damage defeats it. Vindi attacks it
#// and survives (the 3/1 counter is 3 into Vindi's 4 HP).

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [HMW_062:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:HMW_T02

---

# Reaction_UnweakenedEnemyDefeated_NoTrigger
#// THE LOAD-BEARING GATE: the defeated enemy must have carried a Weakness. Same board as above with
#// the token removed — defeating a plain enemy must raise no offer at all.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [HMW_062:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# Reaction_FriendlyWeakenedUnitDefeated_NoTrigger
#// "an ENEMY unit" — a friendly weakened unit dying must NOT trigger it. P1's own pre-weakened 3/1
#// attacks into a 3/7 and dies to the counter; Vindi is a bystander.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [HMW_062:1:0 SOR_128:1:0]
WithP1GroundArenaUpgrade: 1:HMW_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_062
P1NODECISION

---

# Reaction_Decline_NoToken
#// Clause B is optional too.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [HMW_062:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# Reaction_EnemyDefeatedByEffect_AlsoTriggers
#// A defeat reaches the collector by TWO different code paths — combat (CombatLogic) and effect
#// (SWUDefeatUnit) — and the "had a Weakness token" capture had to be added to BOTH. Every other
#// section here kills by combat, so without this one the effect path is unexercised.
#// SOR_077 Takedown ("Defeat a unit with 5 or less remaining HP", cost 4, Vigilance — on-aspect for
#// this base) defeats the weakened SOR_095 (3/3 → 2/2, so 2 remaining HP).

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myhandCardIds:SOR_077}
P1OnlyActions: true
WithP1GroundArena: HMW_062:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:HMW_062
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02

---

# Reaction_OnlyOnceEachRound
#// "Use this ability only once each round." Two weakened enemies defeated in the same round: the first
#// defeat offers, the second must not.
#// The first token goes on VINDI (1/4 → 0/3), deliberately not on SEC_080: putting it on the second
#// attacker drops it to 2/2, so it would TRADE with the 2/2 defender and leave the board unreadable.
#// SEC_080 staying 3/3 kills the weakened SOR_095 (2/2) and survives at 2 damage.
#// SEC_080 ending with NO token is what proves the second defeat granted nothing.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [HMW_062:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArenaUpgrade: 1:HMW_T02

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_062
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# Reaction_ResetsNextRound
#// "once each ROUND" has two halves and the sibling above only tests one. If the SWU_HMW062_USED flag
#// is never cleared, the ability works once per GAME — a bug the once-per-round section cannot see.
#// Round 1: Vindi kills the weakened SOR_128 and the offer is taken (consuming the round).
#// Then both players pass to regroup and resource-pass into round 2, where units are ready again.
#// P2 holds the claimed initiative under P1OnlyActions, so it is the turn player after the regroup and
#// P1 cannot act until a P2>Pass.
#// Round 2: SEC_080 kills the weakened SOR_095 — a pending offer is the proof the flag reset.
#// Asserting the offer's exact POOL rather than a bare P1HASDECISION, so an unrelated pending decision
#// cannot satisfy it: the Weakness offer spans every unit in play, and P2's board is empty by now.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [HMW_062:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArenaUpgrade: 1:HMW_T02
WithP1Deck: [SOR_237 SOR_225 SOR_046 SEC_080]
WithP2Deck: [SOR_237 SOR_225 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:1:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
