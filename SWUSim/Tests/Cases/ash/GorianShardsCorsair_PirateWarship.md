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
