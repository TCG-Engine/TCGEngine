# DefenderShrunkToZero_DefeatedBeforeCombat
#// ASH_046 Scion Shuttle — "While this unit is attacking, the defending unit gets -1/-1." A defender at 1 HP
#// is dropped to 0 HP by the -1 and defeated by SBA BEFORE combat damage, so it deals no counter. Scion
#// attacks SOR_225 (2/1) → SOR_225 defeated, Scion takes 0.
## GIVEN
CommonSetup: bbk/bbk
WithP1SpaceArena: ASH_046:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_046
P1SPACEARENAUNIT:0:DAMAGE:0

---

# SupportLent_DefenderShrunkToZero_DefeatedBeforeCombat
#// ASH_046 Scion Shuttle (Support) — the lent -1/-1 also defeats a 1-HP defender before combat. Scion is
#// played; SOR_237 supports and "attacks" SOR_225 (2/1), which the lent -1 HP defeats before any combat →
#// SOR_237 takes 0.
## GIVEN
CommonSetup: bbk/bbk/{myResources:2;handCardIds:ASH_046}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0

---

# DefenderMinusOneMinusOne_WhileAttacking
#// ASH_046 Scion Shuttle — "While this unit is attacking, the defending unit gets -1/-1." Scion (1/3)
#// attacks SOR_141 Green Squadron A-Wing (1/3, already 1 damage). The -1/-1 makes the defender 0/2 with
#// 1 damage; Scion's 1 combat damage brings it to 2 on 2 HP → defeated. The -1 power leaves the defender
#// dealing no counter, so Scion takes 0.
## GIVEN
CommonSetup: bbk/bbk
WithP1SpaceArena: ASH_046:1:0
WithP2SpaceArena: SOR_141:1:1
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_046
P1SPACEARENAUNIT:0:DAMAGE:0

---

# SupportLent_DefenderMinusOneMinusOne
#// ASH_046 Scion Shuttle (Support) — the lent -1/-1 applies during a Support-initiated attack. Scion is
#// played; the friendly SEC_213 A-Wing (1/2, Raid) "attacks" SOR_141 Green Squadron A-Wing (1/3). While
#// attacking the A-Wing's power is 2 (Raid); the defender gets -1/-1 → 0/2, taking 2 combat damage on 2 HP
#// → defeated. The defender's power is reduced to 0, so the A-Wing takes 0.
## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:ASH_046}
WithP1SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_141:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SEC_213
P1SPACEARENAUNIT:0:DAMAGE:0
