# Front_AdvantageTokenIsAFriendlyToken
#// LAW_017 Han Solo (leader front) — "Action [Exhaust, defeat a friendly token]: Deal 1 damage to a
#// unit." An Advantage token (ASH_T02) is a token upgrade its host's controller controls, so it is a
#// "friendly token" and pays the cost exactly as an Experience or Shield token does (CR 3.50.f).
#// Reported from a live game: P1's only token was an Advantage on a SPACE unit and the Action could
#// not be used at all, because the cost pool was a whitelist of token kinds that did not list it.
#// It is P1's only token, so the cost auto-pays and the prompt goes straight to the damage target.
#// The leader is the discriminator for the gate: an unpayable cost makes the Action unavailable and
#// Han stays READY (Front_NoTokens_AbilityUnavailable in HanSolo_IGotAReallyGoodFeeling.md).

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_167:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1SPACEARENAUNIT:0:CARDID:ASH_167
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_AdvantageTokenIsOfferedAlongsideOtherTokens
#// LAW_017 Han Solo (leader front) — the same cost with TWO tokens in the pool, so nothing auto-pays
#// and the choice is real: an Advantage on P1's space unit and an Experience on P1's ground unit.
#// P1 picks the Advantage. Answering with its mzID is the assertion: the harness refuses an answer
#// outside the offered pool, so a pool that still excluded Advantage tokens could not accept it.
#// The Experience survives untouched — exactly one token is spent, and it is the one chosen.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_167:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0.u0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Deployed_OnAttack_AdvantageTokenIsInTheCostPool
#// LAW_017 Han Solo (deployed) — On Attack: "Defeat any number of friendly tokens. Deal damage to a
#// unit equal to the number of tokens defeated this way." The deployed side collects the same pool, so
#// an Advantage token on a friendly SPACE unit must be offered there too. Deployed Han (4/5) attacks
#// P2's base for 4; on attack he defeats the Advantage and deals the resulting 1 damage to P2's
#// SOR_046 (3/7, survives). No decline is needed: with the Advantage spent the pool is empty, so the
#// repeating offer ends on its own rather than asking again.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_167:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0.u0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1
