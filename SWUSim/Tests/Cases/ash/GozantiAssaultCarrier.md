# Gozanti099_SupportLendsSentinel
#// ASH_099 Gozanti Assault Carrier (Space, 4/6, Support) — On Attack: this unit gains Sentinel for this
#// phase. Support: when played, another friendly unit may attack and gains Gozanti's other ability for
#// that attack. Gozanti (Space) is played and lends its On-Attack to SOR_164 Wampa (Ground), which
#// attacks the enemy base (dealing 4); the lent On-Attack fires, so Wampa gains Sentinel for the phase.
## GIVEN
CommonSetup: grk/grk/{myResources:6;handCardIds:ASH_099}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Gozanti099_SentinelForcesEnemyAttack
#// ASH_099 Gozanti Assault Carrier — the Sentinel it gains on attack forces the enemy to attack it. Gozanti
#// attacks the enemy base (dealing 4) and gains Sentinel. When the opponent's SEC_213 A-Wing then attacks,
#// Sentinel makes Gozanti the only legal target: the A-Wing's attempt to hit the base is redirected onto
#// Gozanti (P1 base takes 0). The A-Wing deals 2 (its 1 power + Raid 1 while attacking) to Gozanti and is
#// defeated by Gozanti's 4 counter.
## GIVEN
CommonSetup: grk/yrw
WithP1SpaceArena: ASH_099:1:0
WithP2SpaceArena: SEC_213:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:4
P1BASEDMG:0
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_099
P1SPACEARENAUNIT:0:DAMAGE:2
