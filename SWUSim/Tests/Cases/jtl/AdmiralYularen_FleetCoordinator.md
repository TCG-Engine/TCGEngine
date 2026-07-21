# GritGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; while in play, friendly Vehicles gain it.
#// Choosing Grit, the friendly Vehicle SOR_237 (Alliance X-Wing) gains the Grit keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Grit

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Grit

---

# RestoreGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; friendly Vehicles gain it. Choosing Restore 1,
#// the friendly Vehicle SOR_237 attacks the base (for 2) and heals P1's base by 1 (3 → 2).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Restore_1
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:2

---

# SentinelGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; while in play, friendly Vehicles gain it.
#// Choosing Sentinel, the friendly Vehicle SOR_237 (Alliance X-Wing) gains the Sentinel keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Sentinel

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# ShieldedGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; while in play, friendly Vehicles gain it.
#// Choosing Shielded, the friendly Vehicle SOR_237 (Alliance X-Wing) gains the Shielded keyword.
#// SOR_237 was already in play BEFORE the grant, so it gains the keyword but receives no Shield token
#// (Shielded only shields a unit as it enters play, not retroactively) — SHIELDCOUNT stays 0.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shielded

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Shielded
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# ShieldedGrant_JTL130TokensEnterShielded
#// JTL_047 Admiral Yularen grants Shielded to friendly Vehicles, THEN JTL_130 Timely Reinforcements
#// creates X-Wing tokens (JTL_T02, Vehicles). The opponent controls 8 resources → 4 X-Wings. Because
#// Yularen grants Shielded to Vehicles, each token gains Shielded and — since it's entering play — must
#// enter WITH a Shield token (Shielded applies on creation, not just when "played").
#// gbw aspects cover JTL_047 (Vigilance/Heroism, cost 3) and JTL_130 (Command, cost 5) with no penalty.

## GIVEN
CommonSetup: gbw/grw/{myResources:8;theirResources:8}
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Hand: JTL_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shielded
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:HASKEYWORD:Shielded
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:1:SHIELDCOUNT:1
P1SPACEARENAUNIT:2:SHIELDCOUNT:1
P1SPACEARENAUNIT:3:SHIELDCOUNT:1

---

# NonVehicle_DoesNotGainKeyword
#// JTL_047 Admiral Yularen — the grant is only to friendly VEHICLE units. Choosing Grit, a non-Vehicle
#// friendly (SOR_046 Trooper) does NOT gain Grit.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Grit

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# ControlChange_GrantFollowsThePlayerWhoPlayedHim
#// JTL_047 Admiral Yularen — official ruling: "Until Yularen leaves play, each friendly Vehicle unit
#// gains the chosen keyword. This effect is NOT changed if an opponent takes control of Yularen"
#// (Oct-2025 errata wording: "each Vehicle unit you control or play"). P1 plays Yularen and chooses
#// Sentinel, so P1's Vehicle (SOR_237 Alliance X-Wing) gains Sentinel. P2 then plays Traitorous
#// (SOR_122, cost 5 Command — P2 base ggk covers it) onto Yularen (cost 3 ≤ 3), taking control of him:
#// Yularen moves to P2's ground arena. The grant stays with whoever PLAYED him — P1's Vehicle KEEPS
#// Sentinel and P2's own Vehicle does NOT gain it, even though P2 now controls Yularen. Regression guard:
#// the grant was previously keyed to Yularen's CURRENT controller, so a control change dropped it entirely.

## GIVEN
CommonSetup: bbk/ggk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:7;
  theirResources:7
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: JTL_047
WithP1SpaceArena: SOR_237:1:0
WithP2Hand: SOR_122
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Sentinel
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:JTL_047
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# EnemyVehicle_DoesNotGainKeyword
#// JTL_047 Admiral Yularen — the grant is to each FRIENDLY Vehicle unit only. P1 plays Yularen and chooses
#// Sentinel: P1's own Vehicle (SOR_237 Alliance X-Wing) gains Sentinel, but an ENEMY Vehicle (P2's SOR_237)
#// does NOT gain it. Same-card Vehicle on both sides isolates the friendly-only clause.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Sentinel

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Clone_CopyOfYularenAlsoGrants
#// JTL_047 Admiral Yularen — a Clone (TWI_116) copy of Yularen carries his When Played grant. P1 first plays
#// the real Yularen choosing Sentinel (friendly Vehicle SOR_237 gains Sentinel), then plays Clone copying
#// Yularen (myGroundArena-0) and choosing Grit. The Clone enters play AS Yularen (its own When Played fires),
#// so the friendly Vehicle now has BOTH Sentinel (from the original) and Grit (from the Clone copy). The Clone
#// sits in the ground arena as a JTL_047 with the Clone trait.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Hand: TWI_116
WithP1Resources: 16
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Sentinel
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Grit

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_047
P1GROUNDARENAUNIT:1:HASTRAIT:Clone
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:HASKEYWORD:Grit

---

# PlayedViaObiWan_BuffsThePlayerWhoPlaysHim
#// JTL_047 Admiral Yularen played from an OPPONENT's discard via SEC_205 Obi-Wan buffs the Vehicles of the
#// player who PLAYS him, not the owner. P1's Obi-Wan attacks P2's base (4 dmg) → mills a Yularen from P2's
#// deck into P2's discard, and (this phase) P1 may play it from there ignoring aspect penalties. P1 plays
#// Yularen and chooses Sentinel: P1's Vehicle (SOR_237) gains Sentinel because P1 PLAYED him — even though
#// P2 OWNS Yularen (it was P2's deck). P2's own Vehicle (SOR_237) does NOT gain Sentinel.
#// The play-from-opponent-discard flow surfaces a play-confirmation decision first (answered "-"), THEN
#// Yularen's When Played keyword choice (answered Sentinel).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 6
WithP2SpaceArena: SOR_237:1:0
WithP2Deck: [JTL_047 JTL_047 JTL_047]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:-
- P1>AnswerDecision:Sentinel

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
