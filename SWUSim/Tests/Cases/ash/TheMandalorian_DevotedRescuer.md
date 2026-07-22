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
