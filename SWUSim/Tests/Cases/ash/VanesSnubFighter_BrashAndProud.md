# BaseHitGivesAdvantage
#// ASH_144 Vane's Snub Fighter (Space, 2/4) — When a friendly unit's attack ends: if it dealt combat
#// damage to a base, give an Advantage token to this unit. A friendly Dark Trooper attacks P2's base →
#// ASH_144 gains an Advantage token.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# AttackUnit_NoBaseHit_NoAdvantage
#// ASH_144 Vane's Snub Fighter — the Advantage needs combat damage to a BASE. When the friendly SEC_080
#// attacks the enemy unit SOR_046 (no base damage), ASH_144 gains nothing.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# OverwhelmExcessToBase_Advantage
#// ASH_144 Vane's Snub Fighter — combat damage a friendly attacker deals to a base via Overwhelm counts. The
#// friendly SOR_232 AT-ST (6 power, Overwhelm) attacks SOR_095 Battlefield Marine (3 HP); 3 excess combat
#// damage hits the base, so ASH_144 gains an Advantage token.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# AbilityDamageToBase_NoAdvantage
#// ASH_144 Vane's Snub Fighter — only COMBAT damage to a base counts, not ability damage. LAW_181 Cloud-Rider
#// Veteran attacks the enemy SOR_095 Battlefield Marine and its On-Attack ability deals 2 damage to the base;
#// since that base damage came from an ability, ASH_144 gains no Advantage token.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1GroundArena: LAW_181:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# AttackBaseZeroPower_NoAdvantage
#// ASH_144 Vane's Snub Fighter — attacking a base with no combat damage doesn't count. LOF_057 Owen Lars
#// (0 power) attacks the enemy base dealing 0 combat damage, so ASH_144 gains no Advantage token.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1GroundArena: LOF_057:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# EnemyAttacksBase_NoAdvantage
#// ASH_144 Vane's Snub Fighter — the trigger is a FRIENDLY unit's attack. When an enemy unit (SOR_095
#// Battlefield Marine) attacks P1's base, ASH_144 gains no Advantage token.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP2GroundArena: SOR_095:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# SelfAttack_BaseHit_TokenArrivesAfterCombatDamage
#// Bug #1103 (game 1438045). "A friendly unit" includes the Snub Fighter ITSELF, and the token is a
#// When-Attack-Ends reward: it arrives AFTER combat damage, so it cannot pump the hit that earned it.
#// ASH_144 (2 power, no tokens) attacks P2's base → 2 damage, then it holds one Advantage token.
#// (Every other section uses a DIFFERENT friendly attacker, where an early token changes no damage.)
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# SelfAttack_WithAdvantage_OldTokenSpentNewTokenKept
#// Bug #1103 — the reported board. ASH_144 already carries an Advantage token (+1 → 3 power) and attacks
#// P2's base: 3 damage, not 4. Then both When-Attack-Ends effects resolve: the OLD token defeats itself
#// ("When attached unit's attack … ends: Defeat this upgrade") and the Snub Fighter gives itself a NEW
#// one — ending the attack with exactly one Advantage token. Live it dealt 4 and ended with NONE: the
#// new token was attached before damage and then swept by the token's own end-of-attack self-defeat.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_144:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
