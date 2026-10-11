# EachPlayerDefeatsAUnit
#// TWI_238 Merciless Contest (Event, cost 3, Villainy, Tactic) — "Each player chooses a non-leader unit
#// they control. Defeat those units." Each player has one unit (SOR_095), so both auto-resolve and both are
#// defeated.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_238}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# OpponentChoosesBeforeAnythingIsDefeated
#// CR v9.0 7.1.a: "If an ability involves a choice made about open information (such as each player defeating a
#// friendly unit), make each choice sequentially, then resolve the ability simultaneously." P1's only unit is
#// SHD_164 Rhokai Gunship (its pick auto-resolves); P2 has two units, so P2 must choose. While P2 is choosing,
#// NOTHING has been defeated yet: Rhokai is still in play. (The old chain defeated the caster's pick at once, so
#// Rhokai's When Defeated could resolve before P2 had even chosen.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_238}
P1OnlyActions: true
WithP1SpaceArena: SHD_164:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:2
P2HASDECISION
P1NODECISION

---

# BothPicksDefeatedTogether_ThenWhenDefeatedResolves
#// Same board, finished: P2 picks SEC_080; both chosen units are defeated together, and only then does Rhokai's
#// "When Defeated: Deal 1 damage to a unit or base" resolve — onto P2's surviving SOR_095. (P1>Drain: the trigger
#// sits in P1's queue after P2 answered, as a live client picks it up on its next poll.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_238}
P1OnlyActions: true
WithP1SpaceArena: SHD_164:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
