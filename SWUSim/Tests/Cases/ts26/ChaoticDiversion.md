# ReadyEnemyShieldFriendly
#// TS26_31 Chaotic Diversion (Event, cost 1) — Ready an enemy unit (it can't attack you this phase), then
#// give a Shield to a friendly unit. The exhausted enemy SEC_080 is readied; the friendly SOR_095 is
#// shielded. (The can't-attack-you restriction uses the shared CANT_ATTACK phase marker.)
## GIVEN
CommonSetup: ryk/rrk/{myResources:1;handCardIds:TS26_31}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:0:0 LAW_124:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# ReadiedEnemy_CannotAttackYouThisPhase
#// TS26_31 Chaotic Diversion — the point of readying an enemy is that it still can't come at you. The
#// exhausted Wampa (SOR_164) is readied and shielded-against; when P2 then tries to attack P1's base with
#// it, nothing happens — the base stays at 0 while the Wampa sits ready.

## GIVEN
CommonSetup: ryk/rrk/{myResources:1}
SkipPreGame: true
WithP1Hand: TS26_31
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_164:0:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:READY

---

# TargetingAnAlreadyREADYEnemy_AppliesNoRestriction
#// TS26_31 Chaotic Diversion — "Ready an enemy unit. IF YOU DO, it can't attack …". Any enemy unit may be
#// chosen, but choosing one that is ALREADY ready readies nothing, so the restriction never attaches: the
#// Wampa goes on to hit P1's base for 4. The Shield half still resolves.
#// Discriminating: the restriction used to be stamped on whoever was chosen, which silenced a unit the
#// event had not actually readied.

## GIVEN
CommonSetup: ryk/rrk/{myResources:1}
SkipPreGame: true
WithP1Hand: TS26_31
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_164:1:0 SEC_080:0:0]
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# EnemyThatCANNOTBeReadied_AppliesNoRestriction
#// TS26_31 Chaotic Diversion — the other half of the "if you do" gate. P2's exhausted Wampa carries Frozen
#// in Carbonite (SHD_193, "attached unit can't ready"), so the event readies nothing and stamps no
#// restriction; the Shield is still given. P2 then plays Dogfight (JTL_123, "attack with a unit even if
#// it's exhausted, no bases") and the Wampa attacks the shielded SOR_095: the Shield absorbs the hit
#// (shield count back to 0) and the Wampa takes 3 counter-damage.

## GIVEN
CommonSetup: ryk/rrk/{myResources:1}
SkipPreGame: true
WithP1Hand: TS26_31
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:0:0
WithP2GroundArenaUpgrade: 0:SHD_193
WithP2Hand: JTL_123
WithP2Resources: 4
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# RestrictionIsDIRECTIONAL_LiftsWhenYouTakeControl
#// TS26_31 Chaotic Diversion — the restriction is "can't attack YOUR base or units YOU control", not
#// "can't attack". P1 readies P2's Wampa (restriction on), then plays Change of Heart (SOR_224) to take
#// control of it. P2's base and units were never protected, so the Wampa attacks P2's base for 4.
#// Discriminating: a blanket can't-attack marker leaves the stolen Wampa unable to attack anything.

## GIVEN
CommonSetup: ryk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_31 SOR_224]
WithP2GroundArena: [SOR_164:0:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# NoFriendlyUnits_StillReadiesAndRestricts
#// TS26_31 Chaotic Diversion — the two halves are independent. With no friendly unit to shield, the
#// enemy SEC_080 is still readied, the event still reaches the discard, and the shield step raises no
#// dangling decision.

## GIVEN
CommonSetup: ryk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_31
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1NODECISION

---

# NoEnemyUnits_StillShields
#// TS26_31 Chaotic Diversion — the mirror case: with no enemy unit to ready, the event goes straight to
#// the Shield half and the friendly SOR_095 gets its token.

## GIVEN
CommonSetup: ryk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_31
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# NoUnitsAtAll_ResolvesCleanlyWithNoEffect
#// TS26_31 Chaotic Diversion — with an empty board both halves fizzle. The event is still played and
#// discarded, the hand empties, and no half-built decision is left pending.

## GIVEN
CommonSetup: ryk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_31
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1NODECISION
