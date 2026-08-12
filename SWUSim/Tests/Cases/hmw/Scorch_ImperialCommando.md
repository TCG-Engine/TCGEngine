# OnAttack_DealsOneToUpgradedEnemy
#// HMW_064 Scorch, Imperial Commando — Unit (Ground) 3/5, cost 3, [Vigilance][Villainy],
#// Imperial/Clone/Trooper, unique.
#// "On Attack: You may deal 1 damage to an upgraded unit."
#// SOR_046 Consular Security Force is 3/7 and carries an upgrade, so it survives both the 3 combat
#// damage and the extra 1 — keeping the two damage sources distinguishable (4 total, not a defeat).

## GIVEN
CommonSetup: grw/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_064:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# OnAttack_Decline_NoExtraDamage
#// "You may" needs a real decline branch — MZMAYCHOOSE declines with `-`, not `NO`.
#// Only the 3 combat damage lands.

## GIVEN
CommonSetup: grw/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_064:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# OnAttack_NonUpgradedUnitIsNotSelectable
#// THE LOAD-BEARING GATE: "an upgraded unit" must exclude un-upgraded units from the OFFER.
#// THREE units so that TWO legal targets survive the filter — with only one left the pick
#// auto-resolves and there is no offer to inspect.
#// Attacking the BASE keeps every unit alive and un-damaged, so the only thing under test is the pool.
#// Scorch himself is upgraded and friendly, and "a unit" is unqualified, so he is legal too.

## GIVEN
CommonSetup: grw/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_064:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# OnAttack_FriendlyUpgradedUnitIsALegalTarget
#// "an upgraded unit" carries no friendly/enemy qualifier, so a FRIENDLY upgraded unit is legal.
#// The friendly SEC_080 is the only upgraded unit on the board, so being answerable at all is the proof
#// it was in the pool. Note a MAY-choose does NOT auto-resolve on a single target — the player must
#// still be able to decline — so the pick is explicit even though there is only one option.

## GIVEN
CommonSetup: grw/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_064:1:0 SEC_080:1:0]
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1

---

# OnAttack_TokenOnlyUpgradeCountsAsUpgraded
#// Value-CLASS variant: a Shield token (SOR_T02) IS an upgrade, so a unit carrying nothing but a token
#// is "an upgraded unit". A test that only ever uses real upgrades cannot tell the two apart.
#// The 1 damage goes to the Shield-only enemy; its shield absorbs nothing here because this is ability
#// damage to a DIFFERENT unit than the one being attacked... so assert on the shield holder directly.

## GIVEN
CommonSetup: grw/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_064:1:0
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# OnAttack_NoUpgradedUnitAnywhere_NoPromptAttackStillHappens
#// No valid target must be a clean fizzle: no dangling decision, and the attack itself still resolves.
#// The sibling clause (the attack) is NOT gated on the rider being possible.

## GIVEN
CommonSetup: grw/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_064:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
