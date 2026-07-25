# Morgan050_WhenDefeatedByEnemyEvent_OwnerChooses
#// ASH_050 Morgan Elsbeth (Ground, 5/6, Support) — When Defeated: you may give a unit -2/-2 for this phase.
#// When an OPPONENT defeats her while she is still under her owner's control, her controller (P2) resolves
#// the When Defeated. P1's SHD_079 Rival's Fall defeats P2's Morgan; P2 then gives -2/-2 to P1's SOR_095
#// Battlefield Marine (3/3 -> 1/1).
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SHD_079}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SEC_213:1:0
WithP2GroundArena: [ASH_050:1:0 SOR_164:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1

---

# Morgan050_WhenDefeatedAfterControlTaken_OpponentChooses
#// ASH_050 Morgan Elsbeth — the When Defeated is resolved by whoever CONTROLS her when she is defeated.
#// P1's JTL_043 No Glory, Only Results takes control of Morgan then defeats her, so P1 (not her owner)
#// resolves the ability and gives -2/-2 to P2's SOR_164 Wampa (4/5 -> 2/3).
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:JTL_043}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SEC_213:1:0
WithP2GroundArena: [ASH_050:1:0 SOR_164:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:3
