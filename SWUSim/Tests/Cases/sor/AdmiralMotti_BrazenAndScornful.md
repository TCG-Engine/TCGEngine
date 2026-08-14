# DeclineReady
#// SOR_226 Admiral Motti WhenDefeated — player declines; Villainy unit stays exhausted.
#// P1 attacks with Motti (1/1) into P2's Battlefield Marine (3/3). Motti is defeated.
#// P1 says NO; Cell Block Guard (SOR_229) remains exhausted.
#// COVERAGE: offer=Offer_VillainyBothSidesAnyState_NonVillainyExcluded (pending SELECTABLEEXACT:
#//           "[Villainy] unit" spans both sides and both ready states) · decline=DeclineReady ·
#//           control=NGOR_OpponentDefeatsMotti_OpponentGetsTheReady (defeat under the opponent's
#//           control → THEY resolve the When Defeated) · boundary=ReadiesVillainyUnit (the
#//           When Defeated resolves during the attack that kills Motti) · reqboundary=covered by
#//           the pending-decision offer section (the pick survives to the end-state read)

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_226:1:0
WithP1GroundArena: SOR_229:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ReadiesVillainyUnit
#// SOR_226 Admiral Motti WhenDefeated — readies the only Villainy unit (auto-pick).
#// P1 attacks with Motti (1/1) into P2's Battlefield Marine (3/3). Motti is defeated.
#// P1 says YES; Cell Block Guard (SOR_229, Villainy, exhausted) is the only eligible target.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_226:1:0
WithP1GroundArena: SOR_229:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:READY

---

# Offer_VillainyBothSidesAnyState_NonVillainyExcluded
#// SOR_226 Admiral Motti — Intended: the may-ready pool is every [Villainy]-aspect unit — friendly
#// or ENEMY, ground or space, ready or exhausted — and nothing else. Motti (1/1) attacks P2's
#// Marine (3/3) and dies; the When Defeated pick is left PENDING. Candidates must be exactly:
#// P1's TIE/ln (space, Villainy, ready) and P2's Death Star Stormtrooper (ground, Villainy,
#// exhausted). Excluded: P1's Consular Security Force and P2's Marine (both non-Villainy).
#// (P1's ground compacts after Motti's death — the Security Force sits at index 0.)

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_226:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-1
P1GROUNDARENAUNIT:0:CARDID:SOR_046

---

# NGOR_OpponentDefeatsMotti_OpponentGetsTheReady
#// SOR_226 Admiral Motti — Intended: when the OPPONENT takes control of Motti and defeats him
#// (No Glory, Only Results, JTL_043: take control of a non-leader unit, then defeat it), the
#// When Defeated resolves for the defeat-time controller — P2 — who readies their own exhausted
#// Villainy unit (Cell Block Guard). Motti still goes to his OWNER's (P1) discard.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1GroundArena: SOR_226:1:0
WithP2GroundArena: SOR_229:0:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:0
