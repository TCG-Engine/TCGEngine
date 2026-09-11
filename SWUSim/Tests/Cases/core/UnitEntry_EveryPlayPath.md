# OwnDiscardUnit_IsAPlayedUnit
#// SSOT #1 follow-up (gamelog-updates, 2026-09-11). Three paths put a PLAYED unit into play: ActivateCard's
#// unit branch (the canonical one), the own-discard play (_SWUOwnDiscardPlayAsUnit) and the Smuggle play
#// (_SWUSmugglePlaceUnit). The two inline ones skipped ActivateCard's per-play entry bookkeeping:
#//   · "the next unit you play" charges — LOF_180 Deceptive Shade (gains Ambush), LOF_010 Third Sister
#//     (gains Hidden) — a unit played from the discard or via Smuggle IS the next unit you play;
#//   · the "played this phase / round" flags (SWU_PLAYED_UNIT_<uid>, SWU_UNITS_PLAYED_ROUND for HMW_145,
#//     SWU_PLAYED_VILLAINY / _FO / _FORCE_CARD / _BOUNTYHUNTER / _PILOT);
#//   · the paid-resources stamp, and (own-discard) the continuous-shrink check.
#// They now share _SWUPlayedUnitEntry. SHD_053 Second Chance lets P1 replay ASH_259 LEP Ratcatcher from the
#// discard for free; the LOF_180 charge is seeded. The replayed unit counts as a unit played this round,
#// spends the charge and gains Ambush. (Fixture from shd/SecondChance.md FreeReplay.)

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: ASH_259:1:0
WithP1GroundArenaUpgrade: 0:SHD_053
WithP2GroundArena: SOR_095:1:0
WithP1GlobalEffect: SWU_LOF180_NEXT_AMBUSH

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_259
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1NOGLOBALEFFECT:SWU_LOF180_NEXT_AMBUSH
P1GLOBALEFFECT:SWU_UNITS_PLAYED_ROUND

---

# OwnDiscardUnit_EntersUnderSnoke_IsDefeated
#// The own-discard path also skipped the continuous-shrink check a unit entering play runs. SHD_037 Supreme
#// Leader Snoke: "Each enemy non-leader unit gets -2/-2." P2 plays Snoke AFTER Ratcatcher (1/1) has died;
#// P1 then replays Ratcatcher from the discard — it enters at -1/-1 and is defeated at once.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: ASH_259:1:0
WithP1GroundArenaUpgrade: 0:SHD_053
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 14
WithP2Hand: SHD_037

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Pass
- P2>PlayHand:0
- P1>PlayFromDiscard:1

## EXPECT
P2GROUNDARENACOUNT:2
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# SmuggledUnit_IsAPlayedUnit
#// The Smuggle path: SHD_065 smuggled from the resource zone counts as a unit played this round and spends
#// the seeded LOF_180 "next unit you play gains Ambush" charge. (Fixture from
#// sec/BailOrgana_DoingEverythingHeCan.md.)

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SHD_065:1,8:SOR_095:1
WithP1Deck: [SOR_128]
WithP1GlobalEffect: SWU_LOF180_NEXT_AMBUSH

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:HASKEYWORD:Ambush
P1NOGLOBALEFFECT:SWU_LOF180_NEXT_AMBUSH
P1GLOBALEFFECT:SWU_UNITS_PLAYED_ROUND
