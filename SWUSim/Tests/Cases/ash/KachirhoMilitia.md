# ReadyOnBaseAttacked
#// ASH_160 Kachirho Militia (Ground, 4/6, Hidden) — When an enemy ground unit attacks your base: ready
#// this unit (once each round). P1's exhausted Kachirho readies when P2's SEC_080 attacks P1's base.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_160:0:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:ASH_160
P1GROUNDARENAUNIT:0:READY

---

# EnemyAttacksBase_ReadySelf
#// ASH_160 Kachirho Militia — "When an enemy ground unit attacks your base: ready this unit (once each
#// round)." An exhausted Kachirho readies when P2's SOR_046 attacks P1's base.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_160:0:0
WithP2GroundArena: SOR_046:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_160
P1GROUNDARENAUNIT:0:READY

---

# SpaceAttack_DoesNotReady
#// ASH_160 Kachirho Militia — the reaction is gated on an enemy GROUND unit. When P2's space unit SOR_237
#// attacks P1's base, the exhausted Kachirho does NOT ready.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_160:0:0
WithP2SpaceArena: SOR_237:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackSpaceArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_160
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# FriendlyAttack_DoesNotReady
#// ASH_160 Kachirho Militia — the reaction requires an ENEMY unit attacking YOUR base. A friendly ground
#// unit (SOR_095) attacking the enemy base does not ready the exhausted Kachirho.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_160:0:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_160
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# OncePerRound_SecondBaseAttackDoesNotReadyAgain
#// "Use this ability only once each round." P2's first ground attack on P1's base readies Kachirho; P1
#// then attacks with it (exhausting it), and P2's second ground attack on the base the same round leaves
#// it exhausted.
## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: ASH_160:0:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:1:BASE
## EXPECT
P1BASEDMG:6
P1GROUNDARENAUNIT:0:EXHAUSTED
