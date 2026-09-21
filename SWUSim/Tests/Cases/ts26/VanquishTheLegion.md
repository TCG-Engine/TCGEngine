# EachEnemyGroundMinus2
#// TS26_48 Vanquish the Legion (Event, cost 4, Vigilance) — Give each enemy GROUND unit -2/-2 for this
#// phase. The two enemy ground units drop to 1/1; the enemy SPACE unit is untouched.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:TS26_48}
WithP2GroundArena: [SEC_080:1:0 LAW_124:1:0]
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:1
P2GROUNDARENAUNIT:1:POWER:2
P2SPACEARENAUNIT:0:POWER:2

---

# DefeatedUnitFirst_LaterUnitStillDebuffed
#// BUG #1055 (game 850132). ⚠ THE SECTION ABOVE CANNOT SEE THIS: nothing dies there, so the loop never
#// shifts and a broken loop passes it. Here the FIRST unit is killed by the debuff itself.
#// SOR_128 Death Star Stormtrooper is 3/1 — -2/-2 takes it to no remaining HP and it is defeated, which
#// COMPACTS the arena and slides SEC_080 from index 1 down into index 0. A loop that walks the mzIDs it
#// captured up front then applies its second debuff to a slot that no longer holds that unit, and the
#// survivor is never debuffed at all.
#// Family: multi-unit "give each enemy -X/-X" loops (SEC_051 Bo-Katan, LAW_101 Lawbringer were fixed
#// 2026-07-28 with the defer pattern; this card was written later and reintroduced it).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:TS26_48}
WithP2GroundArena: [SOR_128:1:0 SEC_080:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:1

---

# DefeatedUnitInTheMIDDLE_UnitBehindItStillDebuffed
#// BUG #1055, in the shape the live game actually had. Game 850132's P1 ground was
#// [0] Jabba (survived) [1] Qi'ra (KILLED by the -2/-2) [2] Boba Fett — and only Boba went undebuffed,
#// because the defeat at index 1 shifted him from 2 into 1, past the loop's cursor. So the survivor
#// AHEAD of the victim is debuffed and the one BEHIND it is not: a victim at index 0 and a victim in
#// the middle are different cells, and only this one matches the report.
#// SOR_095 (3/3) and SEC_080 (3/3) both survive at 1/1; SOR_128 (3/1) dies.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:TS26_48}
WithP2GroundArena: [SOR_095:1:0 SOR_128:1:0 SEC_080:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENAUNIT:1:HP:1
