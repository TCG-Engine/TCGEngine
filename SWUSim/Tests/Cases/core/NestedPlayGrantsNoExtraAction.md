#// NESTED PLAY MUST NOT GRANT AN EXTRA ACTION — the family sweep (worklist:
#// SWUSim/docs/action-close-ownership.md).
#//
#// A card whose resolution PLAYS another card calls ActivateCard, which finalises the action itself —
#// but the OUTER effect finalises too (an event's FINISH_PLAY_CARD, a unit's entry-trigger flush). Two
#// finalisations swap the turn twice and the acting player acts again. All three cards below were
#// MEASURED broken before SWUNestedPlay() was introduced.
#//
#// ⚠ NONE of this is visible under P1OnlyActions — it claims initiative and auto-passes the opponent,
#// so the turn returns either way. Every section here deliberately uses an ALTERNATING turn and asserts
#// TURNPLAYER, which is the only thing that can see a double swap.
#//
#// The three cover the two distinct OUTER contexts:
#//   • JTL_121 Salvage and SHD_094 Palpatine's Return are EVENTS  -> FINISH_PLAY_CARD is the finaliser.
#//   • SOR_102 Home One is a UNIT's When Played -> the entry-trigger flush is, and it is itself
#//     dispatched from a resume. That difference is load-bearing: the first cut of the helper flagged on
#//     "is a resume pending" and wrongly claimed Home One's OWN outer resume, stranding the turn at 2.
#//     The helper counts resumes before/after instead, so only one the nested play ADDED is claimed.

# P_JTL121_Salvage
## GIVEN
CommonSetup: bbw/ggw/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Discard: SOR_237
WithP2Hand: JTL_121
## WHEN
- P2>PlayHand:0
## EXPECT
P2SPACEARENACOUNT:1
NOEXTRAACTION
TURNPLAYER:1

---

# P_SHD094_PalpatinesReturn
## GIVEN
CommonSetup: bbw/ggk/{theirResources:8}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Discard: SEC_080
WithP2Hand: SHD_094
## WHEN
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
NOEXTRAACTION
TURNPLAYER:1

---

# P_SOR102_HomeOne
## GIVEN
CommonSetup: bbw/ggw/{theirResources:10}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Discard: SOR_095
WithP2Hand: SOR_102
## WHEN
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
NOEXTRAACTION
TURNPLAYER:1
