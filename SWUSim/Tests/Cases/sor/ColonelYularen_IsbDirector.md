# CommandUnitPlayed_HealsBase
#// SOR_109 Colonel Yularen — with Yularen already in play, playing ANOTHER [Command] unit (SOR_095, a
#// Command,Heroism unit) heals 1 from P1's base (3 → 2).
#// COVERAGE: offer=N/A (the heal is mandatory and untargeted — no choice ever exists) ·
#//           reqboundary=OpponentOwnedCommandUnit_PlayedByYou_Heals (attach, defeat, Bounty-collect
#//           and heal all land across separate serialized requests) · control=
#//           OpponentOwnedCommandUnit_PlayedByYou_Heals ("you play" follows the PLAYER, not the
#//           card's owner) · boundary pair=CommandUnitPlayed_HealsBase + OwnPlay_HealsBase
#//           ("including this one") vs NonCommandUnit_NoHeal + OpponentCommandUnit_NoHeal (aspect
#//           and seat gates) · decline=N/A (mandatory trigger, no "you may").
#// Intended per the pilot rules: a Command PILOT played with Piloting as an UPGRADE is not
#// "playing a unit" and must not heal — deferred pending an engine fix (see the session log).

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1Hand: SOR_095
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:2

---

# NonCommandUnit_NoHeal
#// SOR_109 Colonel Yularen — playing a NON-[Command] unit (SOR_237, Heroism only) does NOT trigger the
#// heal; P1's base stays at 3 damage.

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1Hand: SOR_237
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1SPACEARENACOUNT:1

---

# OwnPlay_HealsBase
#// SOR_109 Colonel Yularen (2/3) — "When you play a [Command] unit (including this one): Heal 1 damage
#// from your base." Yularen is itself a Command unit, so playing HIM (the "including this one" clause)
#// heals 1 from P1's base (3 → 2).

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_109
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:1

---

# OpponentCommandUnit_NoHeal
#// SOR_109 Colonel Yularen — "When YOU play a [Command] unit". The OPPONENT playing a Command
#// unit (SOR_108 Vanguard Infantry) does not heal P1's base: damage stays at 3.

## GIVEN
CommonSetup: ggw/ggk/{
  myBaseDamage:3;
  theirResources:1;
  theirhandCardIds:SOR_108
}
WithP1GroundArena: SOR_109:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:1
P1NODECISION

---

# OpponentOwnedCommandUnit_PlayedByYou_Heals
#// SOR_109 Colonel Yularen — "you play a [Command] unit" includes a unit you do NOT own. P1
#// attaches SHD_226 Unrefusable Offer to P2's Battlefield Marine (granting it a Bounty: "play
#// this unit for free under your control"), then Takedown defeats it: P1 collects the Bounty and
#// PLAYS the opponent-owned Marine under P1's control. That play is P1 playing a Command unit,
#// so Yularen heals 1 (3 -> 2) and the Marine sits on P1's side.

## GIVEN
CommonSetup: gyk/rrk/{
  myBaseDamage:3;
  myResources:8;
  myhandCardIds:SHD_226,SOR_077
}
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENACOUNT:0
P1NODECISION

---

# CommandPilot_PlayedAsUpgrade_NoHeal
#// Candidate #7 fix guard (the CR 17.c pilot-as-upgrade family sweep): a Piloting card played AS AN
#// UPGRADE is an upgrade play, not a unit play — Yularen's "When you play a [Command] unit" must NOT
#// heal. Nien Nunb (JTL_093, Command) goes onto the TIE Fighter as a pilot; base damage stays 2.

## GIVEN
CommonSetup: ggw/ggk/{myResources:2;myBaseDamage:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: JTL_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:2

---

# CommandPilotCard_PlayedAsUnit_Heals
#// Control: the SAME Piloting card played as a UNIT is a Command unit play — Yularen heals 1
#// (base 2 → 1).

## GIVEN
CommonSetup: ggw/ggk/{myResources:2;myBaseDamage:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: JTL_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit

## EXPECT
P1GROUNDARENACOUNT:2
P1BASEDMG:1
