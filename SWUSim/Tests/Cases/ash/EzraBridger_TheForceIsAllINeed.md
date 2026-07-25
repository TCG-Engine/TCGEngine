# UpgradedDebuff_TargetsFriendlyUnit
#// ASH_209 Ezra Bridger (Ground, 6/6, Support) — On Attack while upgraded: "you may give a unit -3/-0 for
#// this phase." Any unit is a legal target, including a FRIENDLY one. Ezra (carrying SOR_120) attacks the
#// enemy base and gives the friendly SOR_095 (3/3) -3/-0 → power 0.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: [ASH_209:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:0

---

# UpgradedDebuff_TargetsSelf
#// ASH_209 Ezra Bridger — the -3/-0 may target Ezra HIMSELF (any unit is legal). Upgraded Ezra (base 6
#// power, +2 from SOR_120 = 8) attacks the enemy base and takes -3/-0 on itself → power 5.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_209:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_209
P1GROUNDARENAUNIT:0:POWER:5

---

# UpgradedDebuff_ExpiresNextPhase
#// ASH_209 Ezra Bridger — the -3/-0 is "for this phase." Upgraded Ezra debuffs the enemy SEC_080 (3/3 →
#// power 0); after both players pass to end the action phase, the effect expires and SEC_080 returns to
#// its printed 3 power. (Decks seeded so the round-end resolves cleanly.)
## GIVEN
CommonSetup: bbw/bbk
WithActivePlayer: 1
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_046 SOR_046]
WithP1GroundArena: ASH_209:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>Pass
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:3
