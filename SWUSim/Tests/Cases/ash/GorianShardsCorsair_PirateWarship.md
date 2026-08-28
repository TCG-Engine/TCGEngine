# NoCorsair_ShieldHolds
#// ASH_196 — control: WITHOUT a friendly ASH_196 in play, the same Underworld attacker (SOR_247) is NOT
#// unpreventable, so the Shield absorbs the hit normally — SOR_095 takes 0 and the Shield token is consumed.
#// Proves the bypass requires ASH_196, not just an Underworld source.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# NonUnderworld_ShieldHolds
#// ASH_196 — control: a NON-Underworld attacker (SOR_046 Rebel/Trooper) is preventable even while ASH_196
#// is in play, so the Shield absorbs the hit — SOR_095 takes 0 and the Shield is consumed. Proves the
#// source must be an Underworld card.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# OtherUnderworld_BypassShield
#// ASH_196 Gorian Shard's Corsair (Underworld) — "Damage dealt by friendly Underworld cards is
#// unpreventable." With ASH_196 in play (space), a friendly Underworld GROUND unit (SOR_247, 2 power)
#// attacks a Shielded SOR_095: the Shield does NOT absorb the hit — SOR_095 takes the full 2 and the Shield
#// token remains (it was bypassed, not consumed).
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# SelfAttack_BypassShield
#// ASH_196 Gorian Shard's Corsair itself (Underworld, 6 power) attacks a Shielded space token (JTL_T02,
#// 2/2). Its own combat damage is unpreventable, so the Shield is bypassed and the token takes the full 6
#// and is defeated. (Its On Attack "deal 2" is declined.)
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2SpaceArena: JTL_T02:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-
## EXPECT
P2SPACEARENACOUNT:0

---

# WhenPlayedDealTwo
#// ASH_196 Gorian Shard's Corsair (Space, 6/5, cost 6) — When Played: you may deal 2 damage to a unit.
#// (The "friendly Underworld damage is unpreventable" passive is deferred.) P1 deals 2 to the enemy
#// SEC_080.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_196}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayedDecline
#// ASH_196 Gorian Shard's Corsair — the When Played "deal 2" is optional ("you may"). P1 plays Corsair and
#// declines, so the enemy SEC_080 takes no damage and Corsair still enters play in the space arena.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_196}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:CARDID:ASH_196

---

# OnAttackDealTwo
#// ASH_196 Gorian Shard's Corsair — On Attack: you may deal 2 damage to a unit, before combat resolves. Corsair
#// (6 power) attacks the enemy base; its On Attack deals 2 to the enemy ground SOR_164 (cross-arena), then 6
#// combat damage lands on the base.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:6

---

# OnAttackTargetFriendly
#// ASH_196 Gorian Shard's Corsair — the On Attack "deal 2" can target a FRIENDLY unit. Corsair attacks the
#// enemy base and points its 2 at friendly SOR_178 (Cartel Spacer, 2/3), which survives with 2 damage; base
#// still takes 6.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1SpaceArena: SOR_178:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-1
## EXPECT
P1SPACEARENAUNIT:1:CARDID:SOR_178
P1SPACEARENAUNIT:1:DAMAGE:2
P2BASEDMG:6

---

# EnemyUnderworldDoesNotBypass
#// ASH_196 Gorian Shard's Corsair — the passive only makes FRIENDLY Underworld damage unpreventable. With a
#// friendly Corsair in play, an ENEMY Underworld attacker (SOR_178 Cartel Spacer) attacking a friendly Shielded
#// SOR_141 is still preventable: the Shield absorbs the 2 (SOR_141 takes 0, Shield consumed).
## GIVEN
CommonSetup: yyk/yyk
WithP1SpaceArena: ASH_196:1:0
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 1:SOR_T02
WithP2SpaceArena: SOR_178:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackSpaceArena:0:1
## EXPECT
P1SPACEARENAUNIT:1:CARDID:SOR_141
P1SPACEARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:1:SHIELDCOUNT:0

---

# OwnAbilityDamage_Unpreventable_BypassesShield
#// ASH_196 — Gorian is himself an Underworld card, so his OWN "deal 2" (When Played/On Attack) is unpreventable
#// per his passive. Playing Gorian and dealing 2 to a Shielded enemy applies the full 2 and does NOT consume
#// the Shield token (the bypass covers ability damage, not just combat).
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_196}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# BypassesCloseTheShieldGate
#// ASH_196 — friendly Underworld combat damage to a base is unpreventable, so it bypasses JTL_074 Close the
#// Shield Gate's prevention. P2 plays Close the Shield Gate on its own base (prevent the next damage to it),
#// then P1's friendly Underworld SOR_178 (Cartel Spacer, 2 power) attacks that base while ASH_196 is in play.
#// The prevention is bypassed and the base takes the full 2.
## GIVEN
CommonSetup: yyk/bbw/{handCardIds:none}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 1
WithP2Hand: JTL_074
WithP1SpaceArena: ASH_196:1:0
WithP1SpaceArena: SOR_178:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myBase-0
- P1>AttackSpaceArena:1:BASE
## EXPECT
P2BASEDMG:2

---

# UnpreventableHitsCantBeDamagedUnit
#// ASH_196 — because friendly Underworld damage is unpreventable, ASH_196's own On Attack "deal 2" lands on a
#// unit that normally can't be damaged by enemy card abilities (SHD_187 Lurking TIE Phantom, 2 HP). The 2
#// damage defeats the Phantom instead of being prevented.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2SpaceArena: SHD_187:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0

---

# UnpreventableIgnoresPartialPrevention
#// ASH_196 — unpreventable damage ignores partial prevention effects such as SEC_050 Vigil ("prevent 1 of the
#// damage dealt to another friendly unit"). ASH_196's On Attack deals 2 to the enemy SOR_095 while the enemy
#// SEC_050 Vigil is in play: Vigil does not reduce it, so SOR_095 takes the full 2.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP2SpaceArena: SEC_050:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# DividedDamage_Unpreventable_BypassesShield
#// ASH_196 — "Damage dealt by friendly Underworld cards is unpreventable." CR 9.12 makes a unit the
#// dealer of damage its own ability deals, and ASH_139 Hold Them Off names its dealer explicitly
#// ("that unit deals damage equal to its power"), so a DIVIDED share dealt by a friendly Underworld
#// unit must ignore the target's Shield exactly as a single-target hit does.
#// P1 controls ASH_196 (space) and SOR_247 Underworld Thug (ground, 2 power). Hold Them Off picks the
#// Thug as the dealer and assigns its 2 to the shielded enemy SOR_095: the damage lands and the Shield
#// is NOT spent (a Shield only absorbs damage it is allowed to prevent).

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0:2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
