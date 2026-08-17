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

---

# EnemyUnitsNeitherCountNorAreBuffed
#// COVERAGE: control=EnemyUnitsNeitherCountNorAreBuffed (enemy units neither feed the 7-unit threshold nor
#//           receive the override) + SevenIncludingStolenUnit_BuffsOnlyYourSide (a P2-OWNED unit that P1
#//           CONTROLS counts and is buffed) + OwnedButNotControlled_DoesNotCount (a P1-OWNED unit that P2
#//           CONTROLS does neither) — "you control" is measured by CONTROL, not ownership, on both sides ·
#//           offer=N/A (continuous board-state aura, no chooser) · decline=N/A (not optional) ·
#//           reqboundary=N/A (no decision; the aura is recomputed from board state every read).
#//
#// LAW_036 Obi-Wan Kenobi — "While YOU control 7 or more units". P1 controls only 5 (Obi-Wan + 4 Dark
#// Troopers) while P2 controls 4 Battlefield Marines: NINE units are in play, so a threshold counted over
#// the whole board rather than over the ability controller's units would switch the aura on. It must stay
#// off — P1's SEC_080 keep their printed 3/3, and P2's SOR_095 keep theirs.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_036:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:3:POWER:3
P2GROUNDARENAUNIT:3:HP:3

---

# SevenIncludingStolenUnit_BuffsOnlyYourSide
#// LAW_036 Obi-Wan Kenobi — owner ≠ controller, aura ON. P1 controls seven units: Obi-Wan, five OWNED
#// Dark Troopers, and a SOR_095 that sits in P1's arena but is OWNED by P2 (index 6). That stolen unit is
#// a unit P1 CONTROLS, so it both completes the seventh count and takes the 7/7 override itself. P2 still
#// controls two Battlefield Marines of its own — printed 3/3, identical card to the stolen one, so the
#// only thing separating them is which seat controls them. They must stay 3/3.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_036:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:7
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
P1GROUNDARENAUNIT:6:CARDID:SOR_095
P1GROUNDARENAUNIT:6:POWER:7
P1GROUNDARENAUNIT:6:HP:7
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:3

---

# OwnedButNotControlled_DoesNotCount
#// LAW_036 Obi-Wan Kenobi — the mirror: P1 OWNS seven units but CONTROLS only six. Obi-Wan plus five Dark
#// Troopers are in P1's arena; a seventh P1-OWNED Dark Trooper sits in P2's arena under P2's control. A
#// threshold counted by OWNERSHIP would read 7 and switch the aura on; counted by CONTROL it reads 6 and
#// the aura stays off. P1's Dark Troopers keep 3/3, and the away Dark Trooper — same card, other seat —
#// keeps 3/3 too.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_036:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]
WithP2GroundArenaControlled: SEC_080:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:6
P1GROUNDARENAUNIT:0:CARDID:LAW_036
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3

---

# AuraCoversSpaceArenaToo
#// COVERAGE (supersedes the ledger in EnemyUnitsNeitherCountNorAreBuffed, which is a pre-existing section
#//           and therefore not editable here):
#//           control=EnemyUnitsNeitherCountNorAreBuffed + SevenIncludingStolenUnit_BuffsOnlyYourSide +
#//           OwnedButNotControlled_DoesNotCount — "you control" is measured by CONTROL, not ownership, on
#//           both sides · threshold=SevenUnitsSevenSeven (at 7, on) + FewerThanSevenNoBuff (at 6, off,
#//           statically) + ThresholdDropsBelowSeven_AuraTurnsOffImmediately (the LIVE 7→6 crossing turns
#//           it off mid-phase) · arena=AuraCoversSpaceArenaToo (the override is not arena-scoped: space
#//           units are counted AND overridden) · layering=SizeMattersNot_InPlayFirst_ObiWanWins +
#//           SizeMattersNot_InPlaySecond_SMNWins (two printed-value overrides, most recent wins) ·
#//           offer=N/A (continuous board-state aura, no chooser) · decline=N/A (not optional) ·
#//           reqboundary=N/A (no decision; the aura is recomputed from board state every read).
#//
#// LAW_036 Obi-Wan Kenobi — "While you control 7 or more units, their printed power and printed HP are
#// considered to be 7." "Units" names no arena, so both the COUNT and the OVERRIDE span ground and space.
#// SevenUnitsSevenSeven already seats space units to reach seven but never reads one back, so a
#// ground-arena-only implementation of the override would pass it. Here P1 controls seven — Obi-Wan plus
#// SOR_098 Echo Base Defender (printed 4/3) and two SOR_095 on the ground, plus two SOR_237 Alliance
#// X-Wings (printed 2/3) and one SOR_225 TIE/ln Fighter (printed 2/1) in space — and every one of the
#// three distinct printed lines involved must read 7/7, in whichever arena it sits.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_036:1:0 SOR_098:1:0 SOR_095:1:0 SOR_095:1:0]
WithP1SpaceArena: [SOR_237:1:0 SOR_237:1:0 SOR_225:1:0]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_098
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:7
P1SPACEARENAUNIT:0:HP:7
P1SPACEARENAUNIT:2:CARDID:SOR_225
P1SPACEARENAUNIT:2:POWER:7
P1SPACEARENAUNIT:2:HP:7

---

# ThresholdDropsBelowSeven_AuraTurnsOffImmediately
#// LAW_036 Obi-Wan Kenobi — "While you control 7 or more units" is a continuous condition, so the moment
#// the seventh unit leaves play the override stops applying and every survivor snaps back to its own
#// printed line. FewerThanSevenNoBuff only ever seats six units, so it proves the aura is off from a cold
#// start; it cannot catch an override that is STAMPED onto units when the threshold is met and never
#// removed. This section crosses the threshold live, downward, mid-phase.
#//
#// The same seven-unit board as AuraCoversSpaceArenaToo. P2 plays SHD_078 Fell the Dragon, "Defeat a
#// non-leader unit with 5 or more power", and takes the SOR_098 Echo Base Defender. That choice is only
#// legal BECAUSE the aura is live: SOR_098's printed power is 4, and only the override lifts it to 7 —
#// so the section carries its own proof that the aura was on when the effect resolved. Losing SOR_098
#// leaves P1 controlling six, and every remaining unit must read its printed line again: the two SOR_095
#// back to 3/3, the SOR_237 back to 2/3, the SOR_225 back to 2/1. Obi-Wan is asserted at 7/7 as the
#// deliberate non-signal — that is his OWN printed line, so he looks identical either way, and pinning him
#// keeps a later reader from mistaking his 7/7 for surviving aura.
#// AuraCoversSpaceArenaToo is this section's positive control: same board, aura on, those same space
#// indices read 7/7 there and 2/3 & 2/1 here.

## GIVEN
CommonSetup: bgw/bbk/{theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [LAW_036:1:0 SOR_098:1:0 SOR_095:1:0 SOR_095:1:0]
WithP1SpaceArena: [SOR_237:1:0 SOR_237:1:0 SOR_225:1:0]
WithP2Hand: SHD_078
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:LAW_036
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:HP:3
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3
P1SPACEARENAUNIT:2:POWER:2
P1SPACEARENAUNIT:2:HP:1
