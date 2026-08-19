# KeywordMirror
#// LOF_105 Oppo Rancisis — gains a keyword while another friendly unit has it. With LOF_044 (Sentinel) and
#// SOR_164 (Overwhelm) in play, Rancisis gains both Sentinel and Overwhelm.

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: LOF_044:1:0
WithP1GroundArena: SOR_164:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# KeywordMirror_RaidRestoreAmbushGrit
#// LOF_105 Oppo Rancisis mirrors NINE keywords; the base test covers Sentinel + Overwhelm. This covers the
#// numeric ones (Raid → Raid 2, Restore → Restore 2) plus Ambush + Grit, from friendly donors: SOR_157
#// (Raid), SOR_044 (Restore), SOR_213 (Ambush), SOR_032 (Grit). Oppo gains all four.

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: SOR_157:1:0
WithP1GroundArena: SOR_044:1:0
WithP1GroundArena: SOR_213:1:0
WithP1GroundArena: SOR_032:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# OnlyUnit_NoKeywordsMirrored
#// LOF_105 Oppo Rancisis — "ANOTHER friendly unit" excludes Oppo himself. With Oppo as the ONLY friendly
#// unit and no keyword donor in play, he mirrors NOTHING for all nine keywords. (Intended: "should do nothing
#// when he is the only unit".) GUARD for the fixed innate-keyword bug: LOF_105 was wrongly in the innate
#// $Overwhelm_/$Saboteur_/$Sentinel_/$Hidden_Cards tables (generator scraped the keyword names out of his
#// prose ability text); fixed in Data/ProcessKeywordsSWU.php (comma-segment gate) + regenerated. All four
#// now correctly OFF on a lone Oppo.

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:NOTKEYWORD:Shielded
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden

---

# Hidden_Mirrored
#// LOF_105 Oppo Rancisis gains Hidden while another friendly unit has Hidden. Donor: LOF_154 Witch of the
#// Mist (Hidden). Oppo gains Hidden; the Witch (self-donor is fine, it's "another" unit relative to Oppo)
#// keeps hers. (Intended: "should gain Hidden while a friendly unit has Hidden".)

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: LOF_154:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# Saboteur_Mirrored
#// LOF_105 Oppo Rancisis gains Saboteur while another friendly unit has Saboteur. Donor: SOR_239 Rebel
#// Pathfinder (Saboteur). (Intended: "should gain Saboteur when a friendly unit has Saboteur".)

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: SOR_239:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# Shielded_GainedOnPlay
#// LOF_105 Oppo Rancisis gains Shielded while another friendly unit has Shielded, and — because he has
#// Shielded as he ENTERS play — gets a Shield token. Donor SOR_207 Crafty Smuggler (Shielded) is already
#// in play; Oppo is played from hand and enters with a shield. (Intended: "should gain Shielded when a friendly
#// unit has Shielded" — the unit's only upgrade is a Shield token.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_105}
WithP1GroundArena: SOR_207:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_105
P1GROUNDARENAUNIT:1:HASKEYWORD:Shielded
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# NotFromEnemyUnits
#// LOF_105 Oppo Rancisis mirrors keywords only from FRIENDLY units. Enemy SOR_207 (Shielded) is in P2's
#// arena; Oppo (P1, lone friendly unit) does NOT gain Shielded. (Intended: "should not gain keywords from enemy
#// units".) Saboteur/Sentinel are also asserted OFF here — enemy SOR_239 (Saboteur) + SOR_044 (Sentinel)
#// in P2's arena do NOT grant to Oppo (guards the fixed innate-table bug on the enemy-donor path too).

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_239:1:0
WithP2GroundArena: LOF_044:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Shielded
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Raid2_Value
#// LOF_105 Oppo Rancisis gains Raid 2 (fixed value) while another friendly unit has Raid — regardless of
#// the donor's Raid amount. Donor SOR_157 Cantina Braggart (Raid 2). Oppo (3 power) attacks P2 base:
#// 3 + Raid 2 = 5 damage. (Intended: "should gain Raid 2 when a friendly unit has Raid" → base damage 5.)

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: SOR_157:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# Restore2_Value
#// LOF_105 Oppo Rancisis gains Restore 2 (fixed value) while another friendly unit has Restore. Donor
#// SOR_243 Regional Sympathizers (Restore 2). P1 base starts at 5 damage; Oppo attacks P2 base → Oppo's
#// Restore 2 heals P1 base to 3, and P2 base takes Oppo's 3 power. (Intended: "should gain Restore 2 when a
#// friendly unit has Restore".)

## GIVEN
CommonSetup: ggw/rrk/{myBaseDamage:5}
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: SOR_243:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:3

---

# RestoreDoesNotStackToFour
#// LOF_105 Oppo Rancisis mirrors Restore as a fixed Restore 2 even with TWO friendly Restore donors — it
#// does NOT become Restore 4. Donors: SOR_243 (Restore 2, ground) + SOR_044 (Restore 1, space). P1 base
#// starts at 5 damage; Oppo attacks → heals only 2 (to 3), not 4. (Intended: "should not gain Restore 4 when 2
#// friendly units have Restore".)

## GIVEN
CommonSetup: ggw/rrk/{myBaseDamage:5}
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: SOR_243:1:0
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:3

---

# LosesKeywordWhenDonorLeaves
#// LOF_105 Oppo Rancisis loses a mirrored keyword the moment the donor leaves play (continuous recompute).
#// P1 controls Oppo + SOR_243 (Restore donor); P1 base is at 5 damage. P2 plays SOR_077 Takedown to defeat
#// SOR_243. Now Oppo has no Restore donor, so when he attacks P2's base his Restore does NOT fire: P1 base
#// stays at 5, P2 base takes Oppo's 3 power. (Intended: "should lose keywords when the friendly unit leaves
#// play".)

## GIVEN
CommonSetup: ggw/rrk/{myBaseDamage:5}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: SOR_243:1:0
WithP2Hand: SOR_077
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:5
P2BASEDMG:3
