# BaseDamagedGivesAdvantage
#// ASH_204 Blade Three (Space, 2/4) — When your base is dealt damage: give an Advantage token to this
#// unit. P2 attacks P1's base with SEC_080 (3 power); P1's base takes 3, so P1's ASH_204 gains 1
#// Advantage token (the reaction fires inline during the damage event, even on the opponent's turn).
## GIVEN
CommonSetup: yyw/grk
WithP1SpaceArena: ASH_204:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1SPACEARENAUNIT:0:CARDID:ASH_204
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# OverwhelmCombatDamageToBase
#// ASH_204 Blade Three — Overwhelm excess is still combat damage to the base, so the reaction fires. P2's
#// SOR_232 (AT-ST 6/7 Overwhelm) attacks P1's SOR_095 (Battlefield Marine 3/3): the Marine is defeated and 3
#// excess combat damage overflows to P1's base, giving Blade Three an Advantage token.
## GIVEN
CommonSetup: yyw/grk
WithP1SpaceArena: ASH_204:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_232:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1BASEDMG:3
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# AbilityDamageToBase
#// ASH_204 Blade Three — the reaction fires on ABILITY damage to P1's base, not only combat damage. P2's
#// LAW_181 (Cloud-Rider Veteran) attacks P1's SOR_095, and its On Attack deals 2 ability damage to P1's base.
#// The base takes 2 non-combat damage, giving Blade Three an Advantage token.
## GIVEN
CommonSetup: yyw/grk
WithP1SpaceArena: ASH_204:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_181:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:theirBase-0
## EXPECT
P1BASEDMG:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# EventDamageToBase
#// ASH_204 Blade Three — the reaction fires on EVENT damage to P1's base. P2 plays SHD_178 (Daring Raid,
#// "Deal 2 damage to a unit or base") targeting P1's base, giving Blade Three an Advantage token.
## GIVEN
CommonSetup: yyw/grk/{theirResources:3;theirhandCardIds:SHD_178}
WithP1SpaceArena: ASH_204:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirBase-0
## EXPECT
P1BASEDMG:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# ZeroPowerAttack_NoTrigger
#// ASH_204 Blade Three — a 0-power unit deals no damage, so no reaction. P2's LOF_057 (Owen Lars 0/3) attacks
#// P1's base for 0; the base takes nothing and Blade Three gains no Advantage token.
## GIVEN
CommonSetup: yyw/grk
WithP1SpaceArena: ASH_204:1:0
WithP2GroundArena: LOF_057:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# EnemyBaseDamaged_NoTrigger
#// ASH_204 Blade Three — the reaction is "When YOUR base is dealt damage"; damage to the OPPONENT's base does
#// not trigger it. P1's SOR_095 attacks P2's base for 3; P1's Blade Three gains no Advantage token.
## GIVEN
CommonSetup: yyw/grk
WithP1SpaceArena: ASH_204:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# MultipleDamageInstances_MultipleTokens
#// ASH_204 Blade Three — the reaction fires once per damage event, so multiple hits in a phase stack multiple
#// Advantage tokens. P2's SEC_080 then SOR_046 each attack P1's base (P1 attacks P2's base in between to keep
#// the round going), giving Blade Three two Advantage tokens for 6 total base damage.
## GIVEN
CommonSetup: yyw/grk
WithP1SpaceArena: ASH_204:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:1:BASE
## EXPECT
P1BASEDMG:6
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:2

---

# IndirectDamageToBase
#// ASH_204 Blade Three — the reaction fires on INDIRECT damage to P1's base, not only combat/ability/event
#// damage. P2 plays Torpedo Barrage (JTL_234, 5 indirect) directed at P1; with ASH_204 as P1's only unit,
#// P1 assigns all 5 to its own base. The base takes indirect damage, giving Blade Three an Advantage token.
## GIVEN
CommonSetup: yyw/grk/{theirResources:6;theirhandCardIds:JTL_234}
WithP1SpaceArena: ASH_204:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:myBase-0:5
## EXPECT
P1BASEDMG:5
P1SPACEARENAUNIT:0:CARDID:ASH_204
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
