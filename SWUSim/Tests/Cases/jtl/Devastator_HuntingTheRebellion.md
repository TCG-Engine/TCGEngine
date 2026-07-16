# OverridesSeparateSource
#// JTL_143 Devastator's passive override is GLOBAL, not card-specific: while P1 controls Devastator,
#// P1's SEPARATE indirect source (JTL_234 Torpedo Barrage → Opponent) is also assigned by P1, not P2.
#// Devastator sits in P1's space arena; P1 plays Torpedo Barrage, chooses Opponent, and P1 (not P2)
#// assigns the 5 to P2's units/base: 3 to P2's 3/3 (defeats) + 2 to P2 base.
#// P1 = ryk (leader Cunning+Villainy) covers JTL_234's Cunning pip → printed cost 3.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP1SpaceArena: JTL_143:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:theirGroundArena-0:3,theirBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# WhenPlayed_ControllerAssignsToOpponent
#// JTL_143 Devastator (Unit, 9/6, Space, Aggression+Villainy, cost 8) — passive: "You assign all
#// indirect damage you deal to opponents." + "When Played: Deal 4 indirect damage to each opponent."
#// Devastator's passive is active the moment it enters, so the CONTROLLER (P1) assigns the 4 to P2's
#// units/base — P2 makes NO decision (the override flips the assigner and the perspective to "their").
#// P1 puts 3 on P2's 3/3 SEC_080 (defeats it) and 1 on P2's base.
#// P1 = rrk (base Aggression, leader Aggression+Villainy) covers both pips → printed cost 8.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:JTL_143}
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3,theirBase-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_143
