# EpicDefeatTokenCredit
#// LAW_019 Alliance Outpost (Base, Vigilance) — "Epic Action [defeat a friendly token]: Give an
#// Experience or Shield token to a unit, or create a Credit token." P1 has one TIE Fighter token
#// (JTL_T01); the epic defeats it (cost) and P1 chooses the Credit mode → 1 Credit created.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_T01:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1

---

# EpicDefeatTokenGiveExperience
#// LAW_019 Alliance Outpost — the Experience mode of the three-way choice. The TIE Fighter token
#// (JTL_T01) is defeated to pay the cost, then SOR_046 Consular Security Force (3/7) receives an
#// Experience token: one upgrade, and +1/+1 → 4/8. The stat check is what distinguishes Experience from
#// the Shield mode, which also lands exactly one upgrade-shaped token.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [JTL_T01:1:0 SOR_046:1:0]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Experience
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1CREDITCOUNT:0

---

# EpicDefeatTokenGiveShield
#// The Shield mode. Same cost (the TIE token is defeated), but SOR_046 gets a SHIELD rather than
#// Experience — so SHIELDCOUNT is 1 and its power stays at the printed 3, which is what separates this
#// from the Experience branch above.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [JTL_T01:1:0 SOR_046:1:0]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1CREDITCOUNT:0

---

# TokenRecipientListIncludesENEMYUnits
#// "Give an Experience or Shield token to A UNIT" — unqualified, so it is NOT friendly-only. The offer
#// must include the opponent's units too (giving an enemy unit a Shield is a real, if rare, play — and
#// handing one Experience is a genuine cost of taking the Credit-free mode).
#// Stops AT the target decision so the offer list itself is the assertion; the sections above cover
#// what actually happens once a target is picked.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [JTL_T01:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Experience

## EXPECT
P1DECISIONTOOLTIP:Give_the_token_to_a_unit
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# UnpayableCost_EpicSlotIsPRESERVED
#// The Epic's cost is "[defeat a friendly token]". With NO friendly token the cost is unpayable, so the
#// Action must be a clean no-op that PRESERVES the once-per-game Epic slot — the LAW_023 Great Pit
#// bug family, where the flag was set before the ability discovered it could not pay.
#// Setup deliberately puts a Shield (created by P2's SOR_019 Security Complex, whose "give a Shield
#// token to a non-leader unit" is unqualified and so can target an enemy unit) on P1's only unit.
#// ⚠ Per the printed rules that shield IS a friendly token and SHOULD be a legal cost — SWUSim does not
#// yet offer attached/abstract tokens (Shield, Experience, Credit, Force) as costs, only token UNITS.
#// That narrowing is a known deferral (see law.md); this section pins the CURRENT contract, so it will
#// fail loudly and demand updating on the day the cost is widened. What it guarantees today is the part
#// that is unambiguously right either way: an unpayable Epic never burns the slot.

## GIVEN
CommonSetup: bbw/bbw/{
  myBase:LAW_019;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P2>UseBaseAbility
- P2>AnswerDecision:theirGroundArena-0
- P1>UseBaseAbility

## EXPECT
P1BASE:EPICAVAILABLE
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1CREDITCOUNT:0
