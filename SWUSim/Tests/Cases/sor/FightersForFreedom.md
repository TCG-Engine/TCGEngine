# Decline
#// SOR_143 Fighters for Freedom — decline the optional "deal 1 to a base" reaction.
#// Playing another Aggression card triggers FFF, but the player passes → no base damage.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# DeployAggressionLeader_DoesNotDeal
#// SOR_143 Fighters for Freedom — "When you play another [Aggression] card: you may deal 1 to a base."
#// FFF#1 in play; P1 deploys Sabine (Red Hero leader); not "played" so no trigger

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:0
P1NODECISION

---

# NonAggression_NoTrigger
#// SOR_143 Fighters for Freedom — a NON-Aggression card does NOT trigger the reaction.
#// Absence guard: Confiscate is a neutral event (no Aggression aspect), so FFF stays silent.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# PlayAggression_DealsBase
#// SOR_143 Fighters for Freedom — "When you play another [Aggression] card: you may deal 1 to a base."
#// FFF#1 in play; P1 plays a SECOND FFF (an Aggression card). FFF#1 reacts → deal 1 to a base.
#// Also proves the "another" self-exclusion: only FFF#1 triggers (the just-played FFF#2 is excluded),
#// so after one base-deal there is NO second pending decision.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:1
P1NODECISION
