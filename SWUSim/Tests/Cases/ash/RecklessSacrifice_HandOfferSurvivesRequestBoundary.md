# HandOffer_SurvivesRequestBoundary
#// ASH_163 Reckless Sacrifice — "Discard a unit from your hand." The offered discard targets are
#// absolute hand INDICES, and the event is still sitting in the hand as a removed tombstone when they
#// are computed. WriteGamestate skips removed objects (GamestateParser's writeZone), so saving the game
#// between the prompt and the click renumbers the hand — every card after the event shifts down one and
#// the stored mzIDs go stale.
#//
#// Reported from game 3300: Reckless Sacrifice at hand index 6 could not discard Imperial Door
#// Technician (LAW_097) at index 11. Minimised to the same shape — two units so the choice stays
#// interactive (a single legal target auto-resolves in-request, before any save, and would hide the
#// bug), with the non-unit positioned so it slides into a formerly-offered slot:
#//
#//   at prompt time   myHand-0 ASH_163(removed)  1 LAW_097  2 SEC_179  3 LAW_045   offer = 1 & 3
#//   after the save   myHand-0 LAW_097           1 SEC_179  2 LAW_045             offer STILL 1 & 3
#//                             ^ unreachable       ^ an EVENT, now "legal"  ^ points at nothing
#//
#// Both live symptoms, and both assertions are load-bearing: the reported unit must still be offered,
#// and the Event that slid into its old slot must NOT have become a legal discard.

## GIVEN
CommonSetup: grw/brk/{myResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [ASH_163 LAW_097 SEC_179 LAW_045]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1SELECTABLEHAS:myHand-0
P1SELECTABLEHAS:myHand-2
P1SELECTABLENOT:myHand-1
P1SELECTABLEEXACT:myHand-0&myHand-2

---

# HandOffer_ReportedBoard_Game3300
#// The bug exactly as reported, on the real board (SWUSim/Tests/Snapshots/3300.md).
#//
#// Reckless Sacrifice is hand index 6; the four units are at 3, 7, 11, 12, so the offer is
#// myHand-3&7&11&12. Dropping the event's tombstone shifts everything after index 6 down one:
#//
#//   myHand-3   -> Karis Nemik              ok   (ahead of the event — never moved)
#//   myHand-7   -> Aggressive Negotiations  BAD  an Event, offered as a legal discard
#//   myHand-11  -> Zeb Orellios             ok   a unit, but not the one that was in that slot
#//   myHand-12  -> nothing                  BAD
#//
#// leaving Imperial Door Technician (the reported card) and Rebellious Hammerhead unreachable.
#// Post-compaction the four units sit at 3, 6, 10, 11 — that is what must be offered.

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:ASH_014:true:false:false:0;
  myBase:JTL_021;
  theirBase:SOR_024;
  theirBaseDamage:5;
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10:SOR_095:1
WithP1GroundArena: [SEC_148:1:0 LOF_070:1:0]
WithP1Hand: [LOF_077 SEC_180 JTL_078 SEC_148 LAW_044 LAW_132 ASH_163 JTL_153 SEC_179 SEC_180 LAW_133 LAW_097 LAW_045]

## WHEN
- P1>PlayHand:6
- P1>SimulateRequestBoundary

## EXPECT
P1SELECTABLEHAS:myHand-10
P1SELECTABLEHAS:myHand-6
P1SELECTABLENOT:myHand-7
P1SELECTABLEEXACT:myHand-3&myHand-6&myHand-10&myHand-11
