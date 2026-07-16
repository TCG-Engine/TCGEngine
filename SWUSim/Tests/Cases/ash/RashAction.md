# BaseHit_OppDiscards
#// ASH_162 Rash Action (Event, cost 2) — Attack with a unit; for this attack it gets +1/+0 and gains "When
#// Attack Ends: if this unit dealt combat damage to an opponent's base, that opponent discards a card."
#// SOR_095 (3/3) is the only ready friendly (auto-chosen) and the only target is P2's base (no enemy units):
#// it hits for 3+1 = 4, then P2 discards one of its two hand cards.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_162;theirHandCardIds:SOR_095}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:4
P2HANDCOUNT:0

---

# UnitHit_NoDiscard
#// ASH_162 Rash Action (Event, cost 2) — the discard rider only fires on a BASE hit. Here SOR_095 (3/3,
#// +1/+0 → 4/3) attacks the enemy unit SOR_046 (3/7) instead of the base, so no combat damage reaches P2's
#// base and P2 discards nothing (hand stays at 1). Confirms the dealt-to-base condition gates the discard.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_162;theirHandCardIds:SOR_095}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:1
