#// ACTION-CLOSE OWNERSHIP — the group-A gap fixes (plan: SWUSim/docs/action-close-ownership.md).
#//
#// Both cards called ActivateCard from a whenPlayedAbilities continuation with NO guard at all, so the
#// nested play finalised the action on top of the outer effect's own finalisation and the caster acted
#// again. Both were MEASURED broken (reverting SWUNestedPlay to ActivateCard reds them).
#//
#// ⚠⚠ A "PLAY UP TO N" CARD MASKS THIS AT EVEN COUNTS, and it cost a false all-clear here.
#// The swaps simply accumulate: unguarded, playing TWO units is 2 nested after-actions + the outer 1 =
#// THREE swaps, which lands on the same seat as the correct single swap. The section passed, and the
#// mutation came back GREEN, and ASH_104 looked innocent.
#// Playing ONE unit (odd) is what discriminates. Any test for a multi-play card must use an ODD count.

# P_TS26_57_Mechanize
#// Event: "Play a non-Vehicle unit from your discard pile (paying its cost) and give an Experience
#// token to it." SEC_080 is a 2-cost non-Vehicle. One P2 action => the turn must be P1's.
## GIVEN
CommonSetup: bbw/ggk/{theirResources:8}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Discard: SEC_080
WithP2Hand: TS26_57
## WHEN
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
NOEXTRAACTION
TURNPLAYER:1

---

# P_ASH_104_DathomiriMagicks
#// Event: "Play up to 3 non-Vehicle units that each cost 2 or less from your discard pile for free."
## GIVEN
CommonSetup: bbw/ggk/{theirResources:10}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Discard: [SEC_080 SOR_095]
WithP2Hand: ASH_104
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myDiscard-0
## EXPECT
P2GROUNDARENACOUNT:1
P2DISCARDCOUNT:2
NOEXTRAACTION
TURNPLAYER:1
