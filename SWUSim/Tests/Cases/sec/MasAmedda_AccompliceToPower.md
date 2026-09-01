# WhenPlayed_ExpTwoOfficials
#// SEC_084 Mas Amedda (Ground, 3/4, Command/Villainy) — When Played: give an Experience token to each of
#//   up to 2 OTHER Official units. (Plot auto.) Two friendly Official units (SEC_041) each get +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_084

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# WhenPlayed_ChooseNothing_NoTokens
#// SEC_084 Mas Amedda — the When Played grant is "up to 2", so P1 may choose NONE. With one other
#// friendly Official (SEC_041) in play, P1 declines → no Experience token is attached.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_084

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# PlayedViaPlot_GrantsTokens
#// SEC_084 Mas Amedda — has Plot ("When you deploy a leader, you may play this card from your resources").
#// P1 holds Mas as a resource (myResources-0) plus 5 Command/Villainy resources. Deploying P1's leader opens
#// the Plot window; P1 plays Mas from resources and its When Played still grants Experience to up to 2 other
#// Officials — here both friendly SEC_041 units.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_084:1,5:SEC_080:1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# Offer_UnqualifiedOtherOfficialUnits_SPANSBOTHSIDES
#// SEC_084 Mas Amedda — "When Played: Give an Experience token to each of up to 2 **other Official
#// units**." There is no "friendly" in that sentence, and SWU templating states "friendly" whenever it
#// means it (SOR_094 "another FRIENDLY unit", SOR_036 "a FRIENDLY unit"). So an unqualified "other
#// Official units" reaches the OPPONENT's Officials too — the same reading already applied to SOR_007
#// Grand Moff Tarkin's front side and documented on SOR_019's "a non-leader unit".
#//
#// Giving the enemy Experience is a real if unusual choice ("up to 2" lets you take fewer), which is why
#// the pool is what has to be asserted rather than the outcome.
#//
#// ⚠ THE OFFER IS THE ONLY OBSERVABLE. Answering a target proves the branch, never the pool — and with a
#// friendly-only pool the MZMULTICHOOSE would still appear and still work, just missing a candidate.
#// Board: P1 fields Mas Amedda plus a friendly Official (SEC_237); P2 fields an Official (SEC_237) and a
#// NON-Official (SOR_095). Four bodies, three exclusions tested at once — Mas Amedda himself ("other"),
#// the enemy non-Official (the trait gate), and neither leader.

## GIVEN
CommonSetup: ggk/ggk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SEC_084]
WithP1GroundArena: SEC_237:1:0
WithP2GroundArena: [SEC_237:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
#// The pick is left PENDING so the pool itself can be read.
P1HASDECISION
#// myGroundArena-0 is the friendly Official; theirGroundArena-0 is the ENEMY Official.
#// Mas Amedda (myGroundArena-1, "other") and the enemy non-Official are both absent.
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
