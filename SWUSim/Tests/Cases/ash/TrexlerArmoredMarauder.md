# ShieldCheapUnit
#// ASH_082 Trexler Armored Marauder (Ground, 5/6, Grit, cost 6) — When Played: you may give a Shield token
#// to a unit that costs 3 or less. The only eligible unit is SOR_095 (cost 3); Trexler itself (cost 6) is
#// not. Playing Trexler shields SOR_095.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_082}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayed_Decline
#// ASH_082 Trexler — the Shield grant is optional; declining shields nobody.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_082}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# WhenPlayed_ShieldEnemy
#// ASH_082 Trexler — "a unit that costs 3 or less" may be an ENEMY unit. P1 shields the enemy SOR_095
#// (cost 2).
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_082}
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayed_ShieldFriendlySpace
#// ASH_082 Trexler — the "unit that costs 3 or less" target may be a SPACE unit. P1 shields its own
#// friendly SOR_141 Green Squadron A-Wing (cost 2) in the space arena.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_082}
WithP1SpaceArena: SOR_141:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_141
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayed_ShieldEnemySpace
#// ASH_082 Trexler — the cheap target may be an ENEMY space unit. P1 shields the enemy SHD_187 Lurking
#// TIE Phantom (cost 3) in the space arena.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_082}
WithP2SpaceArena: SHD_187:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
