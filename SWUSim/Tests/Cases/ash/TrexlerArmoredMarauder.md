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
