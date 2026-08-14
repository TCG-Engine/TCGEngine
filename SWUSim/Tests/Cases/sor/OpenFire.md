# Deals4ToUnit
#// SOR_172 Open Fire (Event, cost 3) — "Deal 4 damage to a unit." P1 plays it and
#// targets the enemy Consular Security Force (SOR_046, 3/7), which takes 4 and
#// survives at 4 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:1

---

# TokenUnitIsALegalTarget
#// BUG REPORT (2026-08-13): Open Fire could not hit TOKEN units — "deal 4 damage to a UNIT" is
#// unqualified, and a hand-built ["Unit","Leader Unit"] pool silently dropped the "Token Unit" type
#// (six files shared the miss; all now use AnyUnitFilter). The offer must hold the real unit AND the
#// TIE Fighter token, and picking the token defeats it (1 HP vs 4).

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# TokenUnitTakesTheFourAndDies
#// The branch partner: actually shooting the token. JTL_T01 (1/1) is defeated — tokens are set aside,
#// never discarded, so P2's discard stays empty and only the event is in P1's.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
