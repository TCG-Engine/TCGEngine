#// 4P ALWAYS TWO — the partner vote (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "## 4P Always Two" → "The
#// partner vote"). Every MAIN action one teammate proposes waits for the partner: "<name> wants to play <card>. Accept?" Accept runs
#// it for the proposer; Decline puts it back in the bag. Red = boards 1+3, Blue = 2+4. In a test game the names are P<seat>.
#// SOR_095 Battlefield Marine (3/3, cost 2); SOR_098 Echo Base Defender (Sentinel).
# PlayProposed_PartnerSeesThePrompt
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P3HASDECISION
P3DECISIONTYPE:YESNO
P3DECISIONTOOLTIP:P1 wants to play Battlefield Marine. Accept?
P1NODECISION
P1HANDCOUNT:2
P1GROUNDARENACOUNT:0
TURNPLAYER:1

---

# PlayProposed_Accepted_CardPlayed
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P3>AnswerDecision:YES
## EXPECT
P3NODECISION
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
LOGCONTAINS:P3 accepted.
TURNPLAYER:2

---

# PlayProposed_Declined_NothingChanged_PartnerMayProposeNext
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP3Resources: 8
WithP1Hand: [SOR_095 SOR_095]
WithP3Hand: [SOR_095]
## WHEN
- P1>PlayHand:0
- P3>AnswerDecision:NO
- P3>PlayHand:0
## EXPECT
P1HANDCOUNT:2
P1GROUNDARENACOUNT:0
LOGCONTAINS:P3 declined.
P1HASDECISION
P1DECISIONTOOLTIP:P3 wants to play Battlefield Marine. Accept?
TURNPLAYER:1

---

# SecondProposal_Refused
#// While a proposal is pending, the proposer's next main action is refused — it does not replace the first.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: [SOR_095 SOR_098]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:1
## EXPECT
P3DECISIONTOOLTIP:P1 wants to play Battlefield Marine. Accept?
P1HANDCOUNT:2
P1GROUNDARENACOUNT:0

---

# UndoWhilePending_WithdrawsTheProposal
#// Undo while a proposal waits WITHDRAWS it (final review 2026-11-21): the partner's prompt goes, nothing else is rewound, and the
#// team can propose again — here a DIFFERENT card, so the new prompt proves the old one is gone.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: [SOR_095 SOR_098]
## WHEN
- P1>PlayHand:0
- P1>Undo
## EXPECT
P3NODECISION
P1HANDCOUNT:2
TURNPLAYER:1
LOGCONTAINS:P1 withdrew the proposal.

---

# UndoWhilePending_ThenProposeAnotherCard
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: [SOR_095 SOR_098]
## WHEN
- P1>PlayHand:0
- P1>Undo
- P1>PlayHand:1
## EXPECT
P3HASDECISION
P3DECISIONTOOLTIP:P1 wants to play Echo Base Defender. Accept?

---

# UndoWhilePending_BlueActedFirst_BluesPlayStands
#// The proposal takes no undo point, so a plain Undo would rewind the PREVIOUS action — Blue's. Withdrawing touches only the proposal.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP2Resources: 8
WithP1Hand: [SOR_095]
WithP2Hand: [SOR_095]
## WHEN
- P2>PlayHand:0
- P4>AnswerDecision:YES
- P1>PlayHand:0
- P1>Undo
## EXPECT
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0
P3NODECISION
P1HANDCOUNT:1

---

# AttackOneTarget_PromptNamesTheTarget
#// Blue board 2 fields a Sentinel: the shared-Sentinel rule leaves it board 1's ONLY legal target, so the prompt names it and
#// Accept attacks at once.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_098:1:0
## WHEN
- P1>AttackGroundArena:0:?
## EXPECT
P3DECISIONTOOLTIP:P1 wants to attack with Battlefield Marine into Echo Base Defender. Accept?

---

# AttackOneTarget_Accepted_AttacksAtOnce
#// Accept → the 3-power Marine attacks the 3-HP Echo Base Defender at once (no target choice) and defeats it.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_098:1:0
## WHEN
- P1>AttackGroundArena:0:?
- P3>AnswerDecision:YES
## EXPECT
P1NODECISION
P3NODECISION
P2GROUNDARENACOUNT:0
TURNPLAYER:2

---

# AttackSeveralTargets_PickAfterAccept
#// Several legal targets: the prompt names only the attacker; after Accept the proposer picks the target, and the partner is not
#// asked again.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:?
- P3>AnswerDecision:YES
## EXPECT
P3NODECISION
P1HASDECISION
P1DECISIONTOOLTIP:Choose an attack target

---

# TakeInitiative_Voted
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Input:InitiativeCounter-0|TakeInitiative
## EXPECT
P3DECISIONTOOLTIP:P1 wants to take the initiative. Accept?
TURNPLAYER:1

---

# TakeInitiative_Accepted_ThenBlueVotesToPass
#// Red takes the initiative (accepted) → Blue's turn: Blue may now pass, and Blue votes too (board 4 is asked).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Input:InitiativeCounter-0|TakeInitiative
- P3>AnswerDecision:YES
- P2>Input:myHealth-0|Pass
## EXPECT
LOGCONTAINS:P3 accepted.
TURNPLAYER:2
P4HASDECISION
P4DECISIONTOOLTIP:P2 wants to pass. Accept?

---

# BaseAbility_Voted
#// Tomb of Eilram's Action is a main action: proposed, then on Accept its own cost choice comes to the proposer (not voted).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBase:LOF_028}
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>Input:myBase-0|EpicAction
## EXPECT
P3DECISIONTOOLTIP:P1 wants to use Tomb of Eilram's ability. Accept?

---

# BaseAbility_Accepted_CostChoiceGoesToTheProposer
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBase:LOF_028}
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>Input:myBase-0|EpicAction
- P3>AnswerDecision:YES
## EXPECT
P3NODECISION
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# Control_TeamSuns_NotVoted
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: [SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P3NODECISION
P1GROUNDARENACOUNT:1

---

# Control_OneVsOneAlwaysTwo_NotVoted
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: [SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P3NODECISION
P1GROUNDARENACOUNT:1
