# AbilityPrevent_Accept
#// ASH_062 The Mandalorian — the rider also fires against NON-combat (ability) damage. P1 plays Open Fire
#// (SOR_172, "Deal 4 damage to a unit") targeting its OWN damaged SOR_095; P1 defeats The Mandalorian's
#// Shield to prevent it, so SOR_095 takes 0 and the Shield is gone.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_172}
WithActivePlayer: 1
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# CombatPrevent_Accept
#// ASH_062 The Mandalorian (Ground, 5/4, Shielded) — "If damage would be dealt to another friendly unit,
#// you may defeat a Shield token on this unit. If you do, prevent that damage." P1's SOR_046 attacks P2's
#// SOR_095; P2 defeats The Mandalorian's Shield to prevent the 3 combat damage, so SOR_095 takes 0 and the
#// Shield is gone (SOR_095 still counters 3 onto SOR_046).
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:CARDID:ASH_062
P2GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# CombatPrevent_Decline
#// ASH_062 The Mandalorian — the prevention is a "may": P2 declines (AnswerDecision:-), so the Shield is
#// kept and the 3 combat damage lands normally on SOR_095 (3/3 → defeated). The Mandalorian survives with
#// its Shield intact.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:ASH_062
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# NoShield_NoPrevent
#// ASH_062 The Mandalorian with NO Shield token cannot prevent anything — no offer is made and the combat
#// damage lands. P1's SOR_046 attacks P2's SOR_095 (3/3); SOR_095 is defeated normally and The Mandalorian
#// (which has no Shield to spend) survives untouched.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:ASH_062
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# SelfDamage_UsesOwnShield
#// ASH_062 The Mandalorian — the rider is "another friendly unit", so when The Mandalorian ITSELF is
#// attacked the rider does NOT fire (no spurious prevention offer). Its own Shielded simply absorbs the
#// damage: P1's SOR_046 attacks ASH_062, the Shield absorbs 3 (DAMAGE 0, SHIELDCOUNT 0), and ASH_062's
#// 5 power counters onto SOR_046.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: ASH_062:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:ASH_062
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:5

---

# BaseDamage_NotPrevented
#// ASH_062 The Mandalorian — his Shield-defeat only prevents damage to another friendly UNIT, not to the
#// base. An enemy attack on P1's base is not intercepted: the base takes 3 and Mando keeps his Shield.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# CombatPrevent_AttackerCase
#// ASH_062 The Mandalorian — the protected "another friendly unit" can be the ATTACKER taking counter damage,
#// not just a defender. P1's SOR_095 (Battlefield Marine, 3/3) attacks P2's SOR_164 (Wampa, 4/5). The counter
#// would deal 4 to SOR_095; P1 defeats The Mandalorian's Shield to prevent it, so SOR_095 takes 0 (Shield
#// gone) while SOR_164 still takes SOR_095's 3 power.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# MultiShield_AutoRemoveOne
#// ASH_062 The Mandalorian — when he carries MULTIPLE Shields the prevention auto-defeats exactly ONE of them.
#// P1 plays Open Fire (SOR_172, deal 4) on its own SOR_095; P1 accepts the prevention, so SOR_095 takes 0 and
#// The Mandalorian is left with exactly one Shield.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_172}
WithActivePlayer: 1
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: [0:SOR_T02 0:SOR_T02]
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# EnemyUnitDamaged_NoTrigger
#// ASH_062 The Mandalorian — the rider only protects FRIENDLY units. P1 plays Open Fire (SOR_172, deal 4) on
#// the ENEMY SOR_164 (Wampa): no prevention is offered, the Wampa takes the full 4, and The Mandalorian keeps
#// his Shield.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_172}
WithActivePlayer: 1
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# EventPrevent_Decline_UnitTakesFullDamage
#// ASH_062 The Mandalorian — declining the prevention lets the effect damage land in full and keeps the Shield.
#// P1 plays Open Fire (SOR_172, deal 4) on its own friendly SOR_095 (3/3); P1 declines (AnswerDecision:-), so
#// SOR_095 takes the full 4 and is defeated, while The Mandalorian keeps his Shield (SHIELDCOUNT:1).
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_172}
WithActivePlayer: 1
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_062
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# AnotherUnitHasShield_NoTrigger
#// ASH_062 The Mandalorian — the prevention needs a Shield on THE MANDALORIAN specifically. Here Mando has NO
#// Shield but a different friendly unit (SHD_029 Pyke Sentinel) does; that other Shield cannot be spent for the
#// rider. P1 plays Open Fire (SOR_172, deal 4) on its own SOR_164 (Wampa, 4/5): no prevention is offered and
#// the Wampa takes the full 4. The unrelated Shield on the Pyke Sentinel is untouched.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_172}
WithActivePlayer: 1
WithP1GroundArena: ASH_062:1:0
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SHD_029:1:0
WithP1GroundArenaUpgrade: 2:SOR_T02
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1GROUNDARENAUNIT:1:DAMAGE:4
P1GROUNDARENAUNIT:2:CARDID:SHD_029
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# IndirectDamage_NotPrevented
#// ASH_062 The Mandalorian — indirect damage cannot be prevented by defeating a Shield (indirect damage is
#// unpreventable and never routes through the Shield-prevention funnel). P2 plays Torpedo Barrage (JTL_234, 5
#// indirect) directed at P1; P1 assigns 3 to its own SOR_095 (3/3, defeated) and 2 to its base. No prevention
#// is offered and The Mandalorian keeps his Shield — the indirect damage lands regardless.
## GIVEN
CommonSetup: rrk/yyk/{theirResources:3;theirHandCardIds:JTL_234}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:myGroundArena-1:3,myBase-0:2
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_062
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1BASEDMG:2

---

# EventPrevent_Accept_OpponentSource
#// ASH_062 The Mandalorian — the prevention also fires when the OPPONENT deals the effect damage and its
#// controller is the NON-active player. P2 plays Open Fire (SOR_172, deal 4) on P1's friendly SOR_095 (3/3);
#// P1 accepts and defeats The Mandalorian's Shield to prevent it, so SOR_095 takes 0, the Shield is gone,
#// and the turn passes to P1 (P2 spent their action).
## GIVEN
CommonSetup: bbw/rrk/{theirResources:5;theirHandCardIds:SOR_172}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
TURNPLAYER:1

---

# Jetpack_SecondShield_Prevent
#// ASH_062 The Mandalorian — a freshly played Jetpack (SHD_225) gives The Mandalorian a SECOND Shield token
#// through its When Played, and that extra Shield is available to spend on the prevention. P1 plays Jetpack on
#// The Mandalorian (now 2 Shields); P2 plays Open Fire (SOR_172, deal 4) on P1's friendly SOR_095 (3/3); P1
#// accepts and defeats exactly ONE Shield to prevent the 4 (SOR_095 takes 0), leaving The Mandalorian with a
#// single Shield still attached.
## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SHD_225;theirResources:5;theirHandCardIds:SOR_172}
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
