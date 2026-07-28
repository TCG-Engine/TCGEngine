# SevenUnitsSevenSeven
#// LAW_036 Obi-Wan Kenobi (7/7, Sentinel) — While you control 7 or more units, their printed power is
#// considered 7 and printed HP 7. With Obi-Wan + 6 SEC_080 (7 units), each SEC_080 (printed 3/3) becomes
#// 7/7.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_036:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7

---

# FewerThanSevenNoBuff
#// LAW_036 — the "printed power/HP is 7" effect is active only while you control 7 or more units. With
#// only 6 units (Obi-Wan + 3 Dark Troopers + 2 TIE Fighters) the allies keep their printed stats.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_036:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_036
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:1

---

# SizeMattersNot_InPlayFirst_ObiWanWins
#// LAW_036 Obi-Wan — his "printed power/HP is considered to be 7" and LOF_056 Size Matters Not's
#// "considered to be 5" are the same kind of printed-value override; when both apply the MORE RECENTLY
#// applied wins. Here Size Matters Not is on the Echo Base Defender (SOR_098) FIRST (→5/5); playing Obi-Wan
#// (making 7 units) applies his override LATER, so the Echo becomes 7/7.

## GIVEN
CommonSetup: bgw/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: [SOR_098:1:0 SOR_095:1:0 SOR_095:1:0 SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:LOF_056
WithP1Hand: LAW_036

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# SizeMattersNot_InPlaySecond_SMNWins
#// LAW_036 Obi-Wan — the reverse order. Obi-Wan is already active (7 units → Echo is 7/7); attaching Size
#// Matters Not LATER applies its "considered to be 5" override most recently, so the Echo becomes 5/5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [LAW_036:1:0 SOR_098:1:0 SOR_095:1:0 SOR_095:1:0 SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]
WithP1Hand: LOF_056

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
