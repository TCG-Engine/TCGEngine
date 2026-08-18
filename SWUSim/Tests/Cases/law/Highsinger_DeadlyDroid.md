# WhenDefeatedExpAggression
#// LAW_059 Highsinger (4/2) — When Defeated: give an Experience token to a friendly Aggression unit.
#// Highsinger attacks SOR_046 (3/7) and dies (takes 3 vs 2 HP); SOR_128 (Aggression) gets the Experience.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_059:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenPlayedExpCommand
#// LAW_059 Highsinger (4/2) — When Played: give an Experience token to another friendly Command unit.
#// SOR_095 (Command,Heroism) is the only one -> auto.

## GIVEN
CommonSetup: grk/bgw/{myResources:3}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_059

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenDefeatedGivesExpToNewControllersAggression
#// LAW_059 Highsinger — "When Defeated: give an Experience token to a friendly Aggression unit." If an
#// opponent steals & defeats Highsinger (via No Glory, Only Results, JTL_043), the token goes to the new
#// controller's Aggression unit. P2 steals & defeats P1's Highsinger; P2's SOR_128 gets the Experience.

## GIVEN
CommonSetup: grk/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_059:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenPlayedOffer_AnotherFriendlyCommandOnly
#// LAW_059 Highsinger — OFFER assertion for "Give an Experience token to ANOTHER FRIENDLY COMMAND unit."
#// Discriminating board: two friendly Command units (SOR_095 Command/Heroism idx 0, SEC_080 Command/Villainy
#// idx 1) are IN; the friendly SOR_128 (Aggression/Villainy, idx 2) is OUT on aspect; an ENEMY Battlefield
#// Marine (Command) is OUT on controller scope; and Highsinger itself — which lands at idx 3 and IS a
#// Command unit — is OUT on "another". Two legal targets keep the mandatory choose genuinely pending
#// (WhenPlayedExpCommand's single Command unit auto-resolves, which is why it proved nothing about the pool).
#// COVERAGE: offer=this section (aspect filter + friendly scope + self-exclusion) · decline=N/A (both
#//           clauses are mandatory "Give", no "you may") · control=WhenDefeatedGivesExpToNewControllersAggression
#//           (the When Defeated token follows the NEW controller) · boundary pair=WhenPlayedExpCommand
#//           (Command target exists) vs this section's Aggression/enemy/self exclusions ·
#//           reqboundary=N/A (the token is applied inside the same request that answers the pick)

## GIVEN
CommonSetup: grk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0 SOR_128:1:0]
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_059

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENAUNIT:2:CARDID:SOR_128
P1GROUNDARENAUNIT:3:CARDID:LAW_059

---

# WhenDefeatedOffer_FriendlyAggressionOnly
#// LAW_059 Highsinger — the When Defeated reads "Give an Experience token to a FRIENDLY AGGRESSION unit",
#// which applies a controller filter and an aspect filter and — unlike the When Played half — has no
#// "another". Discriminating board: two friendly Aggression units (SOR_128 and SOR_164) are IN; the
#// friendly SOR_095 (Command/Heroism) is OUT on aspect; the ENEMY SOR_128 — an Aggression unit — is OUT on
#// controller. WhenDefeatedExpAggression has a single Aggression unit and auto-resolves, so it could not
#// have seen an aspect-only or both-sides pool.
#// Highsinger (4/2) attacks the 3/7 SOR_046 and dies to the counter damage.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_059:1:0 SOR_128:1:0 SOR_164:1:0 SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# WhenPlayed_NoOtherFriendlyCommandUnit_FizzlesSilently
#// LAW_059 Highsinger — the When Played needs ANOTHER friendly COMMAND unit. Played onto a board whose
#// only other friendly unit is Aggression (SOR_128) and whose only Command unit is the ENEMY SOR_095, the
#// clause has no legal target: no decision is raised and no Experience is created. Highsinger is himself a
#// Command unit, so this is also the section that proves "another" excludes him.

## GIVEN
CommonSetup: grk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_059

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:LAW_059
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenDefeated_NoFriendlyAggressionUnit_FizzlesSilently
#// LAW_059 Highsinger — the mirror negative on the other clause. When he dies with no friendly Aggression
#// unit surviving — the only Aggression unit on the board belongs to the opponent — nothing is offered and
#// no token is created for either player.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_059:1:0 SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
