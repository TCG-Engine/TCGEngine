# SWUSim Action abilities: inventory (2026-11-02)

Planning input for named Action abilities. Built from the generated card dictionaries (`GeneratedCardDictionaries_20261030225104.js`) and checked against the engine registries in `SWUSim/Custom/`. Reprints with identical text are collapsed into one row.

## Where a player can click to use an Action

| Surface | Sources of Actions | Several Actions at once today? |
|---|---|---|
| **Unit** (own or enemy) | its printed Action(s); a deployed leader unit's Action; Actions granted by attached upgrades or a pilot; Satine's field-wide grant; "any player may use" Actions on enemy units | **No.** `_SWUUnitActionProviderPick` returns ONE provider: the unit's own Action, else the first attached upgrade with a use left, else Satine. Everything else is silently hidden. Crosshair works around it with its own menu. |
| **Base** | the base's own Action or Epic Action; Actions on Fortify upgrades attached to it | **Yes.** `_SWUBaseActionProviders` lists them all; with 2+ the player gets an OPTIONCHOOSE with one-word labels (`EpicAction`, or the upgrade's title). |
| **Leader** (not deployed) | front-side Action(s); Epic Action deploy | **Yes**, via the client's leader menu (`Activate:N`). |
| **Discard pile** | "play this from your discard pile" Actions | One per card; not a unit-click surface. |

## Counts

- Units with a printed Action: **48** (Crosshair SHD_087 has two)
- Deployed leader units with an Action: **14** (the text scan finds 16; TWI_017 is a flip and JTL_013's is a pilot Action, see 1b)
- Upgrades that grant an Action to the unit they're attached to: **4**, plus Poe Dameron (JTL_013) attached as a pilot
- Units that grant Actions: **2**: Satine TWI_047 (every unit on the board) and Leia LOF_098 (herself, while in space)
- Undeployed leaders with a front-side Action: **138** (two of them, Trench JTL_014 and Bail SEC_008, have two)
- Bases: **2** with an Action, **20** with an Epic Action, **3** Fortify upgrades with Actions
- Discard-pile Actions: **3**

**Text vs engine:** every Action in the card text is registered, with two expected exceptions. SHD_135 Kylo's TIE Silencer is a discard-pile Action, handled by the play-from-discard path. TWI_017 Palpatine's "deployed" side is a flip, not a unit.

## 1. Unit surface

### 1a. Units with a printed Action

"Could also receive" lists the granted Actions that are legal on this unit today, i.e. what would currently be hidden behind its own Action.

| ID | Card | Arena | Action(s) | Could also receive |
|---|---|---|---|---|
| SOR_129 | Admiral Ozzel — Overconfident | Ground | Action [exhaust]: Play an Imperial unit from your hand (paying its cost). It enters play ready. Each opponent may ready a unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SOR_184 | Fett's Firespray — Pursuing the Bounty | Space | Action [2 resources]: Exhaust a non-unique unit. | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| SOR_093 | Alliance Dispatcher | Ground | Action [exhaust]: Play a unit from your hand. It costs [1 resource] less. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SOR_110 | Frontline Shuttle | Space | Action [defeat this unit]: Attack with a unit, even if it's exhausted. It can't attack bases for this attack. | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| SOR_094 | Bail Organa — Rebel Councilor | Ground | Action [Exhaust]: Give an Experience token to another friendly unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SOR_177 | Bib Fortuna — Jabba's Majordomo | Ground | Action [Exhaust]: Play an event from your hand. It costs [1 resource] less. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_196 | Grogu — Irresistible | Ground | Action [exhaust]: Exhaust an enemy unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_087 | Crosshair — Following Orders **(2 Actions)** | Ground | Action [2 resources]: This unit gets +1/+0 for this phase. / Action [Exhaust]: This unit deals damage equal to his power to an enemy ground unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_256 | Mercenary Gunship **(any player may use)** | Space | Action [4 resources]: Take control of this unit. Any player may use this ability. | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| SHD_028 | Doctor Pershing — Experimenting With Life | Ground | Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_031 | The Client — Dictated by Discretion | Ground | Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - Heal 5 damage from a base." (When that unit is defeated or captured, its opponent collects its bounty.) | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_080 | Salacious Crumb — Obnoxious Pet | Ground | Action [Exhaust, return this unit to his owner's hand]: Deal 1 damage to a ground unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| TWI_056 | Compassionate Senator | Ground | Action [2 resources, Exhaust]: Heal 2 damage from a unit or base. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| TWI_082 | MagnaGuard Wing Leader | Space | Action: Attack with a Droid unit. Then, attack with another Droid unit. Use this ability only once each round. | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| TWI_105 | Steadfast Senator | Ground | Action [2 resources, Exhaust]: Attack with a unit. It gets +2/+0 for this attack. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| TWI_157 | Disaffected Senator | Ground | Action [2 resources, Exhaust]: Deal 2 damage to a base. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| TWI_194 | Ahsoka Tano — Always Ready For Trouble | Ground | Action [2 resources]: Return this unit and each upgrade on her to their owners' hands. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| TWI_206 | Independent Senator | Ground | Action [2 resources, Exhaust]: Exhaust a unit with 4 or less power. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| JTL_050 | Phantom II — Modified to Dock | Space | Action [1 resource]: If this card is a unit, attach it as an upgrade to The Ghost. (It's no longer a unit. Defeat all upgrades on it and remove all damage from it.) | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| JTL_134 | General Hux — No Terms, No Surrender | Ground | Action [Exhaust]: If you played a First Order card this phase, draw a card. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| JTL_146 | Massassi Tactical Officer | Ground | Action [Exhaust]: Attack with a Fighter unit. It gets +2/+0 for this attack. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LOF_094 | Jedi Consular | Ground | Action [Exhaust, use the Force (lose your Force token)]: Play a unit from your hand. It costs 2 resources less. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LOF_134 | Heavy Missile Gunship | Space | Action [Exhaust]: Deal 2 damage to a ground unit. | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| LOF_178 | Adept of Anger | Ground | Action [Exhaust, use the Force (lose your Force token)]: Exhaust a unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LOF_206 | Babu Frik — Heyyy! | Ground | Action [Exhaust]: You may attack with a friendly Droid unit. For this attack, it deals damage equal to its remaining HP instead of its power. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LOF_243 | Caretaker Matron | Ground | Action [Exhaust]: If you played a Force card this phase, draw a card. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LOF_246 | Grogu — Mysterious Child | Ground | Action [Exhaust]: Heal up to 2 damage from a unit. If you do, deal that much damage to a unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| IBH_016,IBH_027 | Ion Cannon | Ground | Action [Exhaust]: Deal 3 damage to a space unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| IBH_023,IBH_036 | General Rieekan — Stalwart Tactician | Ground | Action [Exhaust]: Attack with another Heroism unit. It gets +2/+0 for this attack.  (You can only attack with a ready friendly unit.) | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| IBH_062,IBH_100 | Imperial Deck Officer | Ground | Action [Exhaust]: Heal 2 damage from a Villainy unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SEC_093 | C-3PO — Anything I Might Do? | Ground | Action [Exhaust, return this unit to its owner's hand]: Give a unit +2/+2 for this phase. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SEC_216 | Regulations Bureaucrat | Ground | Action [Exhaust]: Exhaust a resource. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LAW_084 | Krrsantan — Hit and Run | Ground | Action [discard 2 cards from your hand]: Return this unit to your hand (from play). | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LAW_094 | Hondo Ohnaka — Plays By His Own Rules | Ground | Action: Play the top card of your deck (paying its cost). Use this ability only once each round. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LAW_156 | Hunter For Hire **(any player may use)** | Ground | Action [defeat a friendly Credit token]: Take control of this unit. Any player may use this ability. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LAW_235 | Lady Proxima — Where's the Money? | Ground | Action [Exhaust]: Create a Credit token. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| TS26_15 | C-3P0 — Die, Jedi Dogs! **(any player may use)** | Ground | Action [Exhaust]: Deal damage equal to this unit's power to another ground unit. Only opponents may use this ability. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| ASH_109 | T-6 Shuttle 1974 — With a Mentor's Dedication | Space | Action [Exhaust]: Give another unit +2/+2 for this phase. You may attack with that unit. | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| ASH_118 | 8D8 — Daimyo's Majordomo | Ground | Action [Exhaust]: Deal 1 damage to another friendly unit. If you do, search the top 5 cards of your deck for a unit, reveal it, and draw it. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| ASH_119 | Greef Karga — Introductions are in Order | Ground | Action [1 resource, Exhaust]: If your base was attacked this phase, create a Mandalorian token. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| ASH_123 | Lang — Arrogant Mercenary | Ground | Action [Exhaust]: This unit deals damage equal to his power to a ground unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| ASH_142 | Mortar Trooper | Ground | Action [Exhaust]: Deal 1 damage to each of up to 3 ground units. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| ASH_217 | Mayor's Majordomo — No Problem Groveling | Ground | Action [Exhaust, discard a card from your hand]: Exhaust a unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| ASH_245 | Eye of Sion — Delivered from Exile | Space | Action [Exhaust]: Search the top 8 cards of your deck for a unit that costs the same as or less than this unit's power. Play it for free. It enters play ready. | Heroic Resolve, Strategic Acumen, Satine, Poe (pilot) |
| HMW_170 | Han Solo — My Team's Ready | Ground | Action [Exhaust]: Ready another unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| IC27_070 | C-3PO — Impulse Buy | Ground | Action [Exhaust]: Give an Experience token to another unit. Then, if it has 6 or more power, draw a card. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| IC27_107 | Aurra Sing — Getting Even | Ground | Action [Exhaust]: Deal 4 damage to a unit. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| IC27_120 | Rey — Embracing Destiny | Ground | Action [1 resource, discard a card from your hand]: Attack with a unit. It gets +2/+0 for this attack. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |

### 1b. Deployed leader units with an Action

| ID | Card | Arena | Action | Could also receive |
|---|---|---|---|---|
| SHD_006 | Jabba the Hutt — His High Exaltedness | Ground | Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - The next unit you play this phase costs 2 resources less." | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_013 | Han Solo — Worth the Risk | Ground | Action: Play a unit from your hand. It costs 1 resource less. Deal 2 damage to it. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_016 | Fennec Shand — Honoring the Deal | Ground | Action: Play a unit that costs 4 or less from your hand (paying its cost). Give it Ambush for this phase. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SHD_017 | Lando Calrissian — With Impeccable Taste | Ground | Action: Play a card using Smuggle. It costs 2 resources less. Defeat a resource you own and control. Use this ability only once each round. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| TWI_017 | Chancellor Palpatine — Playing Both Sides |  | Action [Exhaust]: If you played a Villainy card this phase, create a Clone Trooper token, deal 2 damage to each enemy base, then flip this leader. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle |
| JTL_013 | Poe Dameron — I Can Fly Anything | Ground | Action [1 resource]: Attach this upgrade to a friendly Vehicle unit without a Pilot on it. Use this ability only once each round. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LOF_013 | Barriss Offee — We Have Become Villains | Ground | Action [use the Force]: Play an event from your hand. It costs 1 resource less. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LOF_018 | Anakin Skywalker — Tempted by the Dark Side | Ground | Action [use the Force]: Play a Villainy non-unit card from your hand, ignoring its aspect penalties. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| SEC_007 | Dryden Vos — I Never Ask Twice | Ground | Action [discard a card from your hand]: Play a unit from your hand (paying its cost). It gains Ambush for this phase. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LAW_003 | Agent Kallus — Reconsider Your Allegiance | Ground | Action [1 resource]: Play a card from your hand, ignoring its aspect penalties. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| LAW_015 | Jabba the Hutt — Crime Boss | Ground | Action: Play an Underworld unit from your hand. If you defeated a Credit while paying its cost, that unit gains Ambush for this phase. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| ASH_002 | Fennec Shand — Ready for War | Ground | Action [1 resource, exhaust a friendly unit]: Play a unit from your hand. It enters play ready. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| HMW_001 | Asajj Ventress — No Time For Regret | Ground | Action: Attack with a unit. For this attack, replace any Raid it has or gains with Restore, or vice versa. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| HMW_002 | Maz Kanata — Eclectic Pirate Queen | Ground | Action: Play a Fringe or Underworld unit from your hand. It costs 1 resource less. Give a Weakness token to it. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| HMW_009 | Chewbacca — Relentless Rebel | Ground | Action: Attack with a unit, even if it's exhausted. It can't attack bases for this attack. Use this ability only once each round. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |
| HMW_017 | Osha — Haunted By Her Past | Ground | Action: Play a Villainy unit from your resources, ignoring its Villainy aspect penalties. If you do, you may resource a card from your hand. | Heroic Resolve, Strategic Acumen, Satine, Sniper Rifle, Improvised Identity |

TWI_017 Chancellor Palpatine appears here in the text scan but is a two-sided flip leader, not a deployed unit. JTL_013 Poe Dameron's deployed side is an Action he has while attached as a pilot (1c).

### 1c. Granted to the unit they are attached to

| ID | Card | Attach restriction | Unique? | Granted Action |
|---|---|---|---|---|
| SHD_155 | Heroic Resolve | any unit | no | Action [2 resources, defeat a Heroic Resolve on this unit]: Attack with this unit. It gets +4/+0 and gains Overwhelm for this attack. |
| TWI_120 | Strategic Acumen | any unit | no | Action [Exhaust]: Play a unit from your hand. It costs 1 resource less. |
| LAW_126 | Adventurer Sniper Rifle | non-Vehicle | no | Action [Exhaust]: Choose an undamaged non-leader ground unit. Its printed HP is considered to be 1 for this phase. |
| ASH_230 | Improvised Identity | ground unit | no | Action: Search the top 3 cards of your deck for a ground unit and discard it. Then, you may attack with this unit. For this attack, this unit gains the discarded unit's abilities. Use this ability only once each round. |
| JTL_013 | Poe Dameron (as a pilot upgrade) | Vehicle without a Pilot | yes | Action [1 resource]: Attach this upgrade to a friendly Vehicle unit without a Pilot on it. Use this ability only once each round. |

### 1d. Granted by another unit

| ID | Card | Who gains it | Granted Action |
|---|---|---|---|
| TWI_047 | Satine Kryze — Committed to Peace | every unit, both sides | Action [Exhaust]: Discard cards from an opponent's deck equal to half this unit's remaining HP, rounded up. |
| LOF_098 | Leia Organa — Extraordinary | herself, while in the space arena | Action [use the Force]: Move this unit to the ground arena and give each friendly Heroism unit +2/+2 for this phase. |

### 1e. "Any player may use" (clicked on a unit you don't control)

SHD_256 Mercenary Gunship, LAW_156 Hunter For Hire, TS26_15 C-3P0 (opponents only). These are the only Actions offered when you click an enemy unit.

## 1f. How many Actions one unit can have

A unit's Actions come from these slots:

| Slot | How many | Limit |
|---|---|---|
| Printed on the unit | 0–2 | only Crosshair SHD_087 has 2 |
| Deployed leader side | 0–1 | leader units only (1b) |
| Attached upgrade grants | 0 or more | one per attached copy. None of the four granting upgrades is unique, so copies stack |
| Pilot | 0–1 | Poe JTL_013, Vehicles only |
| Satine TWI_047 | 0 or more | one per Satine in play. She is unique per *player*, so a Twin Suns table can have up to four |
| Self-grant | 0–1 | Leia LOF_098, herself, in space only |

**Examples**

| Board | Actions on the unit | Shown today |
|---|---|---|
| HMW_009 Chewbacca (deployed) + Improvised Identity (**the 2026-11-02 report**) | 2: Chewbacca's attack · Improvised Identity | Chewbacca's only |
| Fett's Firespray SOR_184 + Poe as pilot | 2: Exhaust a non-unique unit · Poe's re-attach | Firespray's only |
| Battlefield Marine + Strategic Acumen + Improvised Identity | 2: play a unit −1 · Improvised Identity | the first attached upgrade only |
| Any unit with its own Action + Satine in play | 2 | its own only (already noted in a code comment) |
| Battlefield Marine + 2× Improvised Identity | 2 identical grants, each with its own once-per-round use | 1 entry. The first unspent copy is used, which is correct today |
| Crosshair + Heroic Resolve + Strategic Acumen + Satine | 5: Buff · Deal · Heroic Resolve attack · play a unit −1 · Satine discard | Crosshair's own 2-option menu only |
| Ground non-Vehicle unit with an Action + Heroic Resolve + Strategic Acumen + Sniper Rifle + Improvised Identity + Satine | 6 | its own only |
| **Enemy** unit you click | only "any player may use" Actions (1e). A Satine grant on an enemy unit belongs to that unit's controller | 1 |

**What's hidden today.** If the unit has its own Action, every granted Action is hidden. Otherwise only the first attached upgrade with a use left is offered; any other upgrades and Satine are hidden. Only Crosshair offers a real choice, through its own ad-hoc OPTIONCHOOSE (`Buff` / `Deal`).

**Already solved elsewhere.** Bases use `_SWUBaseActionProviders`: list every *available* Action, ask with an OPTIONCHOOSE only when there are 2+, use one-word labels (`EpicAction`, or the Fortify upgrade's title, with a number added on a collision). That's the closest existing model for units.

**Owner decisions (2026-11-02)**
1. **Identical copies → one entry.** Two Heroic Resolves on a unit show one "Heroic Resolve" Action. Using it defeats one copy; if the unit is readied again, the entry is still there for the other copy. It must also be usable while the unit is **exhausted**, as a soft pass: pay 2 resources and defeat the upgrade, with no attack.
2. **Two Satines → one entry.**
3. **Unaffordable → shown greyed out.** Not hidden: the player should see the Action exists but can't be paid for right now.
4. **Scope → every surface:** units, bases, leaders and the discard pile. Two discard-pile Actions on one card will be rare; details later.
5. **SWU-PGN is in scope.**
6. **Labels: derived, with overrides.** By default, an Action's button label is its source card's title (`Heroic Resolve`, `Satine Kryze`). When one source has two or more Actions on the same surface, the cost is added (`Crosshair [2 resources]` / `Crosshair [Exhaust]`). A small hand-written override table covers the awkward cases. Each ability also gets a stable id, `<sourceCardID>.<n>` (n = order on the card side), which is what the menu answers with and what SWU-PGN writes as `ability`.

**Findings that follow from the decisions (not yet verified by a test)**
- **Heroic Resolve on an exhausted unit probably attacks anyway.** Its handler calls `BeginSWUAttack` without checking the unit is ready (Improvised Identity checks `Status` first), and `BeginSWUAttack` itself has no ready requirement (see the HMW_009 comment). Decision 1 says the attack should simply not happen.
- **Greying out is new UI.** Today an unaffordable Action is filtered out of `unitActions` before the client ever sees it (`SWUComputeActionsData`), and the base OPTIONCHOOSE only lists available entries.

## 1g. SWU-PGN already has slots for named abilities

Spec copy: `.superpowers/ref/SWU-PGN-1.0-SPEC-2026-10-28.md`, §10.1 `ABILITY_ACTIVATE`.

| Field | Spec meaning | What our writer does today (`SWULogBeginAction`) |
|---|---|---|
| `card` | the card whose ability it is | the **provider** CardID, so for an upgrade-granted Action it's the *upgrade* (ASH_230), not the unit using it |
| `grantedBy` | the card whose text gave `card` this ability [W-31, SHOULD] | written only for Fortify base grants (HMW_172) |
| `ability` | writer-chosen id, stable within our engine; readers must not branch on it | never written |
| `title` | the ability's full printed text | never written |
| `limit` `{max, per}` | **required** on every use of a once-each-phase/round/game ability, together with `ability` [W-49]; the fold counts uses per `card` + `ability` | never written (the 10-30 hotfix deliberately omitted `abilityUses`) |
| `ABILITY_RESET` | uses reset when a card changes zone | never written |

Named abilities give us the `ability` id the spec needs. With it, `card` becomes the unit, `grantedBy` the upgrade or Satine, and `limit` can be written for Improvised Identity, HMW_009, Hondo, MagnaGuard, Lando, Poe, Mystic Monastery and the rest. Any change here is reader-visible, so it means bumping `SWUPGN_WRITER_REVISION`. SWU Forge consumes these files.

## 2. Base surface

| ID | Card | Kind | Action |
|---|---|---|---|
| LOF_022 | Mystic Monastery | own Action | Action: The Force is with you (create your Force token). Use this ability no more than 3 times each game. |
| LOF_028 | Tomb of Eilram | own Action | Action [exhaust a friendly unit]: The Force is with you (create your Force token). |
| SOR_019 | Security Complex | Epic Action | Epic Action: Give a Shield token to a non-leader unit. |
| SOR_022 | Energy Conversion Lab | Epic Action | Epic Action: Play a unit that costs 6 resources or less from your hand. Give it AMBUSH for this phase. |
| SOR_025 | Tarkintown | Epic Action | Epic Action: Deal 3 damage to a damaged non-leader unit. |
| SOR_028 | Jedha City | Epic Action | Epic Action: Give a non-leader unit -4/-0 for this phase. |
| LAW_019 | Alliance Outpost | Epic Action | Epic Action [defeat a friendly token]: Give an Experience or Shield token to a unit, or create a Credit token. |
| LAW_020 | Daimyo's Palace | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| LAW_021 | Coaxium Mine | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| LAW_022 | Aldhani Garrison | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| LAW_023 | Great Pit of Carkoon | Epic Action | Epic Action [discard a unit from your hand]: Search your deck for a card named The Sarlacc of Carkoon, reveal it, and draw it. |
| LAW_024 | Imperial Command Complex | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| LAW_025 | Contested Caverns | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| LAW_026 | Shipbreaking Yard | Epic Action | Epic Action: Discard 3 cards from your deck. You may return a card discarded this way to the top of your deck. |
| LAW_027 | Stygeon Spire | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| LAW_028 | Canto Bight | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| LAW_029 | Citadel Research Center | Epic Action | Epic Action [1 resource]: Return a friendly resource to its owner's hand. If you do, resource the top card of your deck. |
| LAW_030 | Partisan Hideout | Epic Action | Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties. |
| TS26_09 | First Battle Memorial | Epic Action | Epic Action: For each friendly leader unit, give an Experience token to a unit. |
| TS26_10 | Dooku's Palace | Epic Action | Epic Action: Play a unit from your hand. It costs 1 resource less for each friendly leader unit. |
| TS26_11 | Executioner's Arena | Epic Action | Epic Action: For each friendly leader unit, you may deal 2 damage to a unit. |
| TS26_12 | Sundari Palace | Epic Action | Epic Action: For each friendly leader unit, you may resource a card from your hand and ready it. If you do, defeat that many friendly resources at the start of the regroup phase. |
| HMW_037 | Bacta Tank | Fortify upgrade | Action [defeat this upgrade]: Put a non-Vehicle unit from your discard pile on top of your deck. |
| HMW_095 | Carbonite Chamber | Fortify upgrade | Action [defeat this upgrade]: Choose a non-Vehicle unit. It doesn't ready during the next regroup phase. |
| HMW_172 | Heavy Ion Cannon | Fortify upgrade | Attached base gains: "Action [discard a card from your hand]: Deal 2 damage to a unit. Use this ability only once each phase." |

## 3. Discard-pile Actions

| ID | Card | Action |
|---|---|---|
| SHD_038 | Brutal Traditions | Action: If an enemy unit was defeated this phase, play this upgrade from your discard pile (paying its cost). |
| SHD_135 | Kylo's TIE Silencer — Ruthlessly Efficient | Action: If this unit was discarded from your hand or deck this phase, play it from your discard pile (paying its cost). |
| LAW_200 | Salvaged Blaster | Action: If this upgrade was discarded from your hand or deck this phase, play it from your discard pile (paying its cost). |

## 4. Undeployed leaders: front-side Actions

Already chosen through the leader menu, so they are listed for completeness. Epic Action deploys are left out.

| ID | Card | Action(s) |
|---|---|---|
| SOR_005 | Luke Skywalker — Faithful Friend | Action [1 resource, exhaust]: Give a Shield token to a [Heroism] unit you played this phase.  |
| SOR_010 | Darth Vader — Dark Lord of the Sith | Action [1 resource, exhaust]: If you played a [Villainy] card this phase, deal 1 damage to a unit and 1 damage to a base. |
| SOR_003 | Chewbacca — Walking Carpet | Action [exhaust]: Play a unit that costs 3 or less from your hand (paying its cost). It gains Sentinel for this phase. |
| SOR_007 | Grand Moff Tarkin — Oversector Governor | Action [1 resource, exhaust]: Give an Experience token to an Imperial unit. |
| SOR_009 | Leia Organa — Alliance General | Action [exhaust]: Attack with a Rebel unit. Then, you may attack with another Rebel unit. |
| SOR_011 | Grand Inquisitor — Hunting the Jedi | Action [exhaust]: Deal 2 damage to a friendly unit with 3 or less power and ready it. |
| SOR_014 | Sabine Wren — Galvanized Revolutionary | Action [exhaust]: Deal 1 damage to each base. |
| SOR_017 | Han Solo — Audacious Smuggler | Action [exhaust]: Put a card from your hand into play as a resource and ready it. At the start of the next action phase, defeat a resource you control. |
| SOR_016 | Grand Admiral Thrawn — Patient and Insightful | Action [1 resource, exhaust]: Reveal the top card of any player's deck. Exhaust a unit that costs the same as or less than the revealed card. |
| SOR_002 | Iden Versio — Inferno Squad Commander | Action [Exhaust]: If an enemy unit was defeated this phase, heal 1 damage from your base. |
| SOR_004 | Chirrut Îmwe — One With The Force | Action [Exhaust]: Give a unit +0/+2 for this phase. |
| SOR_006 | Emperor Palpatine — Galactic Ruler | Action [1 resource, exhaust, defeat a friendly unit]: Deal 1 damage to a unit and draw a card. |
| SOR_012 | IG-88 — Ruthless Bounty Hunter | Action [Exhaust]: Attack with a unit. If you control more units than the defending player, the attacker gets +1/+0 for this attack. |
| SOR_013 | Cassian Andor — Dedicated to the Rebellion | Action [1 resource, Exhaust]: If you've dealt 3 or more damage to an enemy base this phase, draw a card. |
| SOR_018 | Jyn Erso — Resisting Oppression | Action [Exhaust]: Attack with a unit. The defender gets -1/-0 for this attack. |
| SHD_007 | Moff Gideon — Formidable Commander | Action [exhaust]: Attack with a unit that costs 3 or less. If it's attacking a unit, it gets +1/+0 for this attack. |
| SHD_006 | Jabba the Hutt — His High Exaltedness | Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - The next unit you play this phase costs 1 resource less." |
| SHD_009 | Hunter — Outcast Sergeant | Action [1 resource, Exhaust]: Reveal a resource you control. If it shares a name with a friendly unique unit, return the resource to its owner's hand and put the top card of your deck into play as a resource. |
| SHD_012 | Bo-Katan Kryze — Princess in Exile | Action [Exhaust]: If you attacked with a Mandalorian unit this phase, deal 1 damage to a unit. |
| SHD_002 | Qi'ra — I Alone Survived | Action [1 resource, Exhaust]: Deal 2 damage to a friendly unit. Then, give a Shield token to it. |
| SHD_003 | Finn — This is a Rescue | Action [Exhaust]: Defeat a friendly upgrade on a unit. If you do, give a Shield token to that unit. |
| SHD_004 | Rey — More Than a Scavenger | Action [1 resource, Exhaust]: Give an Experience token to a unit with 2 or less power. |
| SHD_010 | Bossk — Hunting His Prey | Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may give it +1/+0 for this phase. |
| SHD_011 | Kylo Ren — Rash and Deadly | Action [Exhaust, discard a card from your hand]: Give a unit +2/+0 for this phase. |
| SHD_013 | Han Solo — Worth the Risk | Action [Exhaust]: Play a unit from your hand. It costs 1 resource less. Deal 2 damage to it. |
| SHD_016 | Fennec Shand — Honoring the Deal | Action [1 resource, Exhaust]: Play a unit that costs 4 or less from your hand (paying its cost). Give it Ambush for this phase. (After you play the unit, it may ready and attack an enemy unit.) |
| SHD_017 | Lando Calrissian — With Impeccable Taste | Action [Exhaust]: Play a card using Smuggle. It costs 2 resources less. Defeat a resource you own and control. |
| TWI_015 | General Grievous — General of the Droid Armies | Action [Exhaust]: Give a Droid unit Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.) |
| TWI_002 | Nute Gunray — Vindictive Viceroy | Action [Exhaust]: If 2 or more friendly units were defeated this phase, create a Battle Droid token. |
| TWI_003 | Obi-Wan Kenobi — Patient Mentor | Action [Exhaust]: Heal 1 damage from a unit. |
| TWI_004 | Yoda — Sensing Darkness | Action [Exhaust]: If a unit left play this phase, draw a card, then put a card from your hand on the top or bottom of your deck. |
| TWI_005 | Count Dooku — Face of the Confederacy | Action [Exhaust]: Play a Separatist card from your hand. It gains Exploit 1. (You may defeat 1 unit you control. If you do, that card costs 2 resources less.) |
| TWI_006 | Wat Tambor — Techno Union Foreman | Action [Exhaust]: If a friendly unit was defeated this phase, give a unit +2/+2 for this phase. |
| TWI_007 | Captain Rex — Fighting For His Brothers | Action [2 resources, Exhaust]: If a friendly unit attacked this phase, create a Clone Trooper token. |
| TWI_009 | Maul — A Rival in Darkness | Action [Exhaust]: Attack with a unit. It gains Overwhelm for this attack. (When attacking an enemy unit, deal excess damage to the opponent's base.) |
| TWI_010 | Pre Vizsla — Pursuing the Throne | Action [1 resource, Exhaust]: Deal damage to a unit equal to the number of cards you've drawn this phase. (This doesn't include cards drawn in the regroup phase.) |
| TWI_012 | Anakin Skywalker — What it Takes to Win | Action [Exhaust, deal 2 damage to your base]: Attack with a unit. If it's attacking a unit, it gets +2/+0 for this attack. |
| TWI_013 | Mace Windu — Vaapad Form Master | Action [1 resource, Exhaust]: Deal 1 damage to a damaged enemy unit. Then, if it has 5 or more damage on it, deal 1 damage to it. |
| TWI_014 | Asajj Ventress — Unparalleled Adversary | Action [Exhaust]: Attack with a unit. If you played an event this phase, it gets +1/+0 for this attack. |
| TWI_017 | Chancellor Palpatine — Playing Both Sides | Action [Exhaust]: If a friendly Heroism unit was defeated this phase, draw a card, heal 2 damage from your base, then flip this leader. |
| JTL_001 | Asajj Ventress — I Work Alone | Action [Exhaust]: Deal 1 damage to a friendly unit. If you do, deal 1 damage to an enemy unit in the same arena. |
| JTL_003 | Lando Calrissian — Buying Time | Action [1 resource, Exhaust]: Play a unit from your hand (paying its cost). If you do and you control a ground unit and a space unit, give a Shield token to a unit. |
| JTL_004 | Rose Tico — Saving What We Love | Action [Exhaust]: Heal 2 damage from a Vehicle unit that attacked this phase. |
| JTL_005 | Admiral Piett — Commanding the Armada | Action [Exhaust]: Play a Capital Ship unit from your hand. It costs 1 resource less. |
| JTL_006 | Darth Vader — Victor Squadron Leader | Action [Exhaust]: If you attacked with a non-token Vehicle unit this phase, create a TIE Fighter token. |
| JTL_007 | Admiral Holdo — We're Not Alone | Action [1 resource, Exhaust]: Give a Resistance unit or a unit with a Resistance upgrade on it +2/+2 for this phase. |
| JTL_008 | Wedge Antilles — Leader of Red Squadron | Action [Exhaust]: Play a card from your hand using Piloting. It costs 1 resource less. |
| JTL_010 | Captain Phasma — Chrome Dome | Action [Exhaust]: If you played a First Order card this phase, deal 1 damage to a base. |
| JTL_011 | Major Vonreg — Red Baron | Action [Exhaust]: Play a Vehicle unit from your hand (paying its cost). If you do, give another unit +1/+0 for this phase. |
| JTL_012 | Luke Skywalker — Hero of Yavin | Action [Exhaust]: If you attacked with a Fighter unit this phase, deal 1 damage to a unit. |
| JTL_013 | Poe Dameron — I Can Fly Anything | Action [1 resource, Exhaust]: Flip this leader and attach him as an upgrade to a friendly Vehicle unit without a Pilot on it. |
| JTL_014 | Admiral Trench — Chk-chk-chk-chk | Action [Exhaust]: Discard a card that costs 3 or more from your hand. If you do, draw a card. / Action [3 resources, Exhaust]: If you control 6 or more resources, deploy this leader. |
| JTL_015 | Rio Durant — Wisecracking Wheelman | Action [1 resource, Exhaust]: Attack with a space unit. It gets +1/+0 and gains Saboteur for this attack. (Ignore Sentinel and defeat the defender's Shields.) |
| JTL_016 | Admiral Ackbar — It's A Trap! | Action [1 resource, Exhaust]: Exhaust a non-leader unit. If you do, its controller creates an X-Wing token. |
| JTL_017 | Han Solo — Never Tell Me the Odds | Action [Exhaust]: Reveal the top card of your deck, then attack with a unit. If the revealed card and that unit have different odd costs, that unit gets +1/+0 for this attack. |
| JTL_018 | Kazuda Xiono — Best Pilot in the Galaxy | Action [Exhaust]: A friendly unit loses all abilities for this round. Take an extra action after this one. |
| LOF_001 | Kylo Ren — We're Not Done Yet | Action [Exhaust]: Discard a card from your hand. If you discarded an upgrade this way, draw a card. |
| LOF_002 | Mother Talzin — Power Through Magick | Action [Exhaust, use the Force (lose your Force token)]: Give a unit -1/-1 for this phase. |
| LOF_003 | Ahsoka Tano — Fighting For Peace | Action [Exhaust, use the Force (lose your Force token)]: Give a friendly unit Sentinel for this phase. (Enemy units in its arena must attack a sentinel when they attack you.) |
| LOF_004 | Kanan Jarrus — Help Us Survive | Action [1 resource, Exhaust]: Give a Shield token to a Creature or Spectre unit. |
| LOF_005 | Morgan Elsbeth — Following the Call | Action [Exhaust]: Choose a friendly unit that attacked this phase. Play a unit from your hand that shares a keyword with the chosen unit. It costs 1 resource less. |
| LOF_006 | Supreme Leader Snoke — In the Seat of Power | Action [1 resource, Exhaust]: Give an Experience token to the unit with the most power among friendly Villainy units. (If multiple units are tied, choose one.) |
| LOF_007 | Avar Kriss — Marshal of Starlight | Action [Exhaust]: The Force is with you (create your Force token). |
| LOF_008 | Obi-Wan Kenobi — Courage Makes Heroes | Action [Exhaust, use the Force (lose your Force token)]: Give an Experience token to a unit without an Experience token on it. |
| LOF_009 | Darth Maul — Sith Revealed | Action [Exhaust, use the Force (lose your Force token)]: Deal 1 damage to a unit and 1 damage to a different unit. |
| LOF_010 | Third Sister — Seething With Ambition | Action [Exhaust]: Play a unit from your hand. It gains Hidden for this phase. (It can't be attacked for this phase unless it has Sentinel.) |
| LOF_011 | Kit Fisto — Focused Jedi Master | Action [1 resource, Exhaust]: If you attacked with a Jedi unit this phase, deal 2 damage to a unit. |
| LOF_012 | Rey — Nobody | Action [Exhaust]: If you played a non-unit Force card this phase, deal 1 damage to a unit. |
| LOF_013 | Barriss Offee — We Have Become Villains | Action [Exhaust, use the Force (lose your Force token)]: Play an event from your hand. It costs 1 resource less. |
| LOF_014 | Grand Inquisitor — Stories Travel Quickly | Action [Exhaust, use the Force (lose your Force token)]: Attack with a friendly unit. The defender gets -2/-0 for this attack. |
| LOF_015 | Cal Kestis — I Can't Keep Hiding | Action [Exhaust, use the Force (lose your Force token)]: An opponent chooses a ready unit they control. Exhaust that unit. |
| LOF_016 | Qui-Gon Jinn — Student of the Living Force | Action [Exhaust, use the Force (lose your Force token)]: Return a friendly non-leader unit to its owner's hand. Play a non-Villainy unit that costs less than the returned unit from your hand for free. |
| LOF_018 | Anakin Skywalker — Tempted by the Dark Side | Action [Exhaust, use the Force (lose your Force token)]: Play a Villainy non-unit card from your hand, ignoring its aspect penalties. |
| IBH_001 | Leia Organa — Get to Your Transports! | Action [1 resource, Exhaust]: Heal 1 damage from a friendly unit. |
| IBH_053 | Darth Vader — Don't Fail Me Again | Action [1 resource, Exhaust]: Deal 1 damage to a base. |
| SEC_001 | Chancellor Palpatine — How Liberty Dies | Action [1 resource, Exhaust]: Search the top 5 cards of your deck for a card with Plot, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.) |
| SEC_002 | Jabba the Hutt — Wonderful Human Being | Action [1 resource, Exhaust]: A friendly damaged unit deals 1 damage to an enemy unit. If the friendly unit has 3 or more damage on it, it deals 2 damage instead. |
| SEC_003 | Lama Su — We Modified Their Genetics | Action [Exhaust]: Play an upgrade from your hand on a friendly non-Vehicle unit. It costs 1 resource less. If you do, deal 1 damage to that unit. |
| SEC_004 | Leia Organa — Of A Secret Bloodline | Action [1 resource, Exhaust]: Disclose Vigilance, Command, Aggression, Cunning, or Heroism (reveal a card from your hand with this aspect icon). If you do, give an Experience token to a unit that doesn't share an aspect with the disclosed card. |
| SEC_005 | Satine Kryze — Standing on Principles | Action [Exhaust]: Heal up to 2 damage from a unit. If you do, deal that much damage to your base. |
| SEC_006 | Colonel Yularen — This Is Why We Plan | Action [Exhaust]: Attack with a unit. Then, you may attack with another unit that costs less than it. |
| SEC_007 | Dryden Vos — I Never Ask Twice | Action [Exhaust, discard a card that costs 6 or more from your hand]: Play a unit that costs 5 or less from your hand (paying its cost). It gains Ambush for this phase. |
| SEC_008 | Bail Organa — Doing Everything He Can | Action [1 resource, Exhaust]: If a friendly unit was defeated this phase, return a friendly resource to its owner's hand. If you do, put the top card of your deck into play as a resource. / Action [Exhaust, discard 2 cards from your hand]: If you control 4 or more resources, deploy this leader. |
| SEC_010 | Dedra Meero — Not Wasting Time | Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may deal 2 damage to it. If they don't, draw a card. |
| SEC_011 | Governor Pryce — Tyrant of Lothal | Action [1 resource, Exhaust]: Ready a token unit. |
| SEC_014 | Sly Moore — Cipher in the Dark | Action [1 resource, Exhaust]: If there are 4 or more exhausted units in play, create a Spy token. |
| SEC_015 | C-3PO — Human-Cyborg Relations | Action [1 resource, Exhaust]: If you control an exhausted unit, exhaust a unit. |
| SEC_018 | DJ — Need a Lift? | Action [Exhaust]: Choose a friendly unit. If you do, play a unit from your hand. It costs 1 resource less. The chosen unit captures it. (When Played abilities resolve after the unit is captured.) |
| LAW_001 | Saw Gerrera — Bring Down the Empire | Action [Exhaust]: Attack with a unit. It gets +2/+0 and gains Overwhelm for this attack. After completing this attack, defeat it. |
| LAW_002 | Tobias Beckett — People are Predictable | Action [Exhaust]: Choose a friendly unit. An opponent takes control of it. If they do, create a Credit token. |
| LAW_003 | Agent Kallus — Reconsider Your Allegiance | Action [1 resource, Exhaust]: Play a card from your hand, ignoring its aspect penalties. |
| LAW_004 | Aurra Sing — Assassin | Action [Exhaust]: Defeat a non-leader unit with 1 or less remaining HP. |
| LAW_005 | Jyn Erso — Time to Fight | Action [1 resource, Exhaust]: If a friendly Rebel unit was defeated this phase, search the top 3 cards of your deck for a card and draw it. (Put the other cards on the bottom of your deck in a random order.) |
| LAW_006 | Vel Sartha — Aldhani Insurgent | Action [Exhaust]: Give an Experience token to a unit. An opponent creates a Credit token. |
| LAW_008 | Director Krennic — Amidst My Achievement | Action [Exhaust, defeat a friendly unit]: Create a Credit token. |
| LAW_010 | Leia Organa — Someone Who Loves You | Action [2 resources, Exhaust]: For this phase, give a unit +1/+1 for each different aspect it has. |
| LAW_011 | Darth Vader — Unstoppable | Action [Exhaust, discard a card from your hand]: Deal 1 damage to a unit or base. |
| LAW_012 | Sebulba — Especially Dangerous Dug | Action [Exhaust, discard a card from your deck]: A friendly unit gains Raid 1 for this phase. |
| LAW_013 | Chewbacca — Hero of Kessel | Action [1 resource, Exhaust, defeat a friendly resource]: Deal 2 damage to a unit and create a Credit token. |
| LAW_015 | Jabba the Hutt — Crime Boss | Action [1 resource, Exhaust, return a friendly Underworld unit to its owner's hand]: Create a Credit token. |
| LAW_016 | The Client — Please Lower Your Blaster | Action [Exhaust]: If you created a token this phase, exhaust an enemy unit. |
| LAW_017 | Han Solo — I Got a Really Good Feeling | Action [Exhaust, defeat a friendly token]: Deal 1 damage to a unit. |
| LAW_018 | Lando Calrissian — Full Sabacc | Action [1 resource, Exhaust]: Choose an aspect, then discard a card from a deck. If it has the chosen aspect, create a Credit token. |
| TS26_01 | Count Dooku — Offering Aid | Action [Exhaust]: Choose 2 players. They each heal 1 damage from their base and create a Battle Droid token. |
| TS26_02 | Anakin Skywalker — Protect Her At All Costs | Action [Exhaust]: If 2 or more friendly units entered play this phase (including tokens and leaders), give a Shield token to 1 of them. |
| TS26_03 | Maul — Collective Ambition | Action [Exhaust]: Choose a unit. If it has more different keywords than it has Experience tokens on it, give an Experience token to it and deal 1 damage to it. |
| TS26_04 | Padmé Amidala — Follow My Lead | Action [Exhaust]: If 2 or more friendly units entered play this phase (including tokens and leaders) , attack with 1 of them, even if it's exhausted. It can't attack bases for this attack. |
| TS26_06 | Rex — No Other Option | Action [Exhaust, ready an exhausted enemy unit]: The next event you play this phase costs 1 resource less. |
| TS26_07 | Asajj Ventress — Ambitious Apprentice | Action [Exhaust]: Attack with a token unit. It gets +1/+0 for this attack. |
| ASH_001 | The Armorer — Steel Shapes Us | Action [Exhaust]: Play an upgrade from your resources on a unit that entered play this phase (paying its cost). If you do, resource the top card of your deck. |
| ASH_002 | Fennec Shand — Ready for War | Action [1 resource, Exhaust, exhaust a friendly unit]: Play a unit from your hand (paying its cost). It enters play ready. |
| ASH_003 | Baylan Skoll — Power Beyond Dream | Action [1 resource, Exhaust]: Give a friendly unit +2/+2 for this phase if it's the only unit you control in its arena. |
| ASH_004 | Grand Admiral Thrawn — Victory is Mine | Action [Exhaust]: Attack with a unit. It gains Restore 2 for this attack if you control the same number of units as the defending player. |
| ASH_006 | Sabine Wren — Bargaining on Belief | Action [Exhaust]: An opponent gives 2 Advantage tokens to a unit they control. If they do, the next unit you play this phase gains Shielded for this phase. (When you play that unit, give a Shield token to it.) |
| ASH_007 | Grand Admiral Sloane — Holding the Empire Together | Action [Exhaust]: Choose one: |
| ASH_008 | Moff Gideon — Indomitable Warlord | Action [Exhaust]: If a friendly Imperial unit was defeated this phase, play a unit from your hand. It costs 1 resource less. |
| ASH_009 | Ahsoka Tano — Trust in the Force | Action [Exhaust]: Choose a unit with less power than a friendly unit. It gets +2/+0 for this phase. |
| ASH_010 | Bo-Katan Kryze — Reclaiming Mandalore | Action [2 resources, Exhaust]: If you control a unit in each arena, create a Mandalorian token. |
| ASH_011 | Cad Bane — Still Faster than You | Action [Exhaust]: Deal 1 damage to a unit with 2 or more remaining HP. |
| ASH_012 | Vane — Quarrelsome Pirate | Action [Exhaust, defeat a friendly upgrade]: Deal 2 damage to a base. |
| ASH_015 | Emperor Palpatine — According to My Design | Action [Exhaust]: Choose an exhausted friendly unit. Give an Advantage token to it for each other friendly unit. |
| HMW_001 | Asajj Ventress — No Time For Regret | Action [Exhaust]: Attack with a unit. For this attack, replace any Raid it has or gains with Restore, or vice versa. |
| HMW_002 | Maz Kanata — Eclectic Pirate Queen | Action [Exhaust]: Play a Fringe or Underworld unit from your hand. It costs 1 resource less. Give a Weakness token to it. |
| HMW_003 | Doctor Hemlock — Emotion Has No Place Here | Action [1 resource, Exhaust]: Give a Weakness token to a unit without a Weakness token on it. |
| HMW_005 | Jar Jar Binks — Bombad General | Action [1 resource, Exhaust]: If you gave a token upgrade to a unit this phase, deal 1 damage to a unit and heal 1 damage from a base. |
| HMW_006 | Omega — Close Your Eyes and Focus | Action [1 resource, Exhaust]: Attack with a Heroism unit. It gains Grit for this attack. (It gets +1/+0 for each damage on it.) |
| HMW_008 | General Grievous — Separatist Warlord | Action [Exhaust]: Play 2 units from your hand (one at a time, paying their costs). |
| HMW_009 | Chewbacca — Relentless Rebel | Action [2 resources, Exhaust]: Attack with a unit, even if it's exhausted. It can't attack bases for this attack. |
| HMW_010 | Tarfful — Fighting from the Shadowlands | Action [2 resources, Exhaust, discard a card from your hand]: Create a Beast token. |
| HMW_012 | Poggle the Lesser — Let the Executions Begin | Action [1 resource, Exhaust]: Ready a friendly Creature unit and deal 1 damage to it. |
| HMW_015 | Bossk — Cruel Hunter | Action [Exhaust]: Heal 1 damage from a damaged enemy unit and give a Weakness token to it. |
| HMW_016 | Maul — Old Master | Action [Exhaust]: Play a unit from your hand. It costs 1 resource less. Then, defeat it. (When Played abilities resolve after the unit is defeated.) |
| HMW_017 | Osha — Haunted By Her Past | Action [Exhaust]: If a friendly Heroism unit was defeated this phase, play a Villainy unit from your resources, ignoring its Villainy aspect penalties. If you do, you may resource a card from your hand. |
| HMW_018 | The Warrior — Deft Duelist | Action [1 resource, Exhaust]: Play a unit with 3 or less power from your hand (paying its cost) and give it Ambush for this phase. |
| IC27_001 | Darth Vader — No One to Stop Us | Action [1 resource, Exhaust, defeat a friendly unit]: Draw a card and heal 2 damage from your base. |
| IC27_002 | Rey — In Balance | Action [1 resource, Exhaust]: Give an Experience token to a damaged unit. |
| IC27_003 | Kylo Ren — Supreme Leader | Action [Exhaust, discard a card from your hand]: Give an Experience token to a unit. |
| IC27_008 | Princess Leia — On a Diplomatic Mission | Action [1 resource, Exhaust]: Draw a card, then put a card from your hand on the top or bottom of your deck. |
