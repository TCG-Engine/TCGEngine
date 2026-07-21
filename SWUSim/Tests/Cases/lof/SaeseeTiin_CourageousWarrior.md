# Initiative_Deal1ToThree
#// LOF_167 Saesee Tiin — When Played: if you have the initiative, deal 1 damage to each of up to 3 units.
#// P1 holds the initiative and deals 1 to each of three enemy 3/7 units.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_167}
WithInitiativePlayer: 1
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:1

---

# NoInitiative_NoDamage
#// LOF_167 Saesee Tiin — the When Played damage only fires "if you have the initiative." Here P2 holds the
#// initiative, so playing Saesee deals no damage to the enemy unit.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_167}
WithInitiativePlayer: 2
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Initiative_ChooseTwo
#// LOF_167 Saesee Tiin — "up to 3" lets P1 hit fewer than 3. With initiative and three enemy units available,
#// P1 chooses only two; those two take 1 damage each and the third is untouched.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_167}
WithInitiativePlayer: 1
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:0

---

# Initiative_SelectableAllUnitsInclSelf
#// LOF_167 Saesee Tiin — with initiative the damage may target ANY unit in either arena, friendly or enemy,
#// including Saesee herself. Selectable set = exactly the friendly Marine, Saesee (self), and the enemy unit.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_167}
WithInitiativePlayer: 1
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0
