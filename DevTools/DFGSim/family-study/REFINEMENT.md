# DFG card-family refinement — revision 2

2026-10-23. Reviewed all 40 families. Reduced 240 first-draft entries to 143 refined entries, including labeled shared references. No simulator rules or live cards changed.

## Method and limits

Critique first, reduce padding, repair measurable crossings, then check definitions and rendering. The finite numerical screen covers 8316 input contexts and 120 pairs sharing hand, burns, routes, and tags. It checks life, outgoing damage, incoming damage, survival, affordability, and remaining stamina. It excludes status/queue timing, opponent life, other printed-stat counters, future resources, routing outcomes, and mixed strategies. No sampled flag is a whole-game dominance proof, and no absence of flags is proof of balance.

## Family-by-family review

### F01: A little room to breathe (6 → 6)

**Critique:** The healing/block trio has real crossings, but the attacking branch was presented as equally efficient without explaining its dependence on opposing block and the life cap.

**Refinement:** Keep all six. Preserve the requested trio and distinguish recovery-first cards from chip hybrids. Do not add a free burn or route improvement to rescue a weak matchup.

**Next test:** Compare attack 0, 2, and 4 while wounded, then repeat near 40 life and against block 0 versus block 1. Repeated recovery may still stall a complete game.

| Card | Attack / block | Main rule |
|---|---|---|
| Open Breath | 0 / 0 | Recover 4 life on reveal. |
| Measured Breath | 0 / 2 | Recover 3 life on reveal. |
| Sheltered Breath | 0 / 4 | Recover 2 life on reveal. |
| Parting Breath | 1 / 0 | Recover 3 life on reveal. |
| Steady Return | 1 / 2 | Recover 2 life on reveal. |
| Covered Return | 1 / 4 | Recover 1 life on reveal. |

### F02: Earn your recovery (6 → 3)

**Critique:** The six-step ladder ended in 1-attack heal-6 cards that often cannot trigger. Several middle entries merely exchanged outgoing damage for healing one-for-one.

**Refinement:** Keep three attainable hit thresholds and make recovery grow faster than attack falls: 5/1 heal 1, 4/1 heal 3, 3/1 heal 5.

**Next test:** Against block 4 only the 5-attack main heals; against block 3 the middle main can earn its reward; against block 0 the risky recovery leads when wounded. Check survival before any heal.

| Card | Attack / block | Main rule |
|---|---|---|
| Small Respite | 5 / 1 | If you deal exchange damage, recover 1 life after damage. This cannot prevent a knockout. |
| Returning Palm | 4 / 1 | If you deal exchange damage, recover 3 life after damage. This cannot prevent a knockout. |
| Deep Recovery | 3 / 1 | If you deal exchange damage, recover 5 life after damage. This cannot prevent a knockout. |

### F03: Recover behind a read (6 → 3)

**Critique:** The basic idea works, but six adjacent block thresholds obscured the three distinct bets: ambitious recovery, balanced recovery, reliable guard.

**Refinement:** Use block 1/3/5 with conditional healing 5/3/1. Keep the zero-damage requirement and post-damage timing explicit.

**Next test:** At incoming attack 1, 3, and 5 respectively, test which version both qualifies and produces the best recovery. Test direct retaliation separately before promotion.

| Card | Attack / block | Main rule |
|---|---|---|
| Still Water | 0 / 1 | If you take no exchange damage, recover 5 life after damage. |
| Patient Breath | 0 / 3 | If you take no exchange damage, recover 3 life after damage. |
| Deep Shelter | 0 / 5 | If you take no exchange damage, recover 1 life after damage. |

### F04: Now or next round (6 → 6)

**Critique:** The original immediate cards were weaker copies of other healing cards. Fixing those references still left an undefined queue that could accumulate recovery indefinitely.

**Refinement:** Keep Measured Breath and Steady Return as shared references. Keep the larger delayed recoveries, but use one public pending-recovery slot per fighter; a new delayed recovery replaces the old queue.

**Next test:** Test survival until maturity, cap waste, replacing a pending queue, and repeated hand routes. A due queue resolves before a newly revealed card schedules another one.

| Card | Attack / block | Main rule |
|---|---|---|
| Measured Breath (reference F01-2) | 0 / 2 | Recover 3 life on reveal. |
| Long Breath | 0 / 2 | Recover 4 life on reveal next round. |
| Deep Breath | 0 / 2 | Recover 6 life on reveal two rounds from now. |
| Steady Return (reference F01-5) | 1 / 2 | Recover 2 life on reveal. |
| Passing Renewal | 1 / 2 | Recover 4 life on reveal next round. |
| Passing Revival | 1 / 2 | Recover 6 life on reveal two rounds from now. |

### F05: Recover or remove (6 → 6)

**Critique:** Mend was 0/3 heal 3, making it a free improvement over Measured Breath at 0/2 heal 3. That is exactly the cross-family mistake the study should catch.

**Refinement:** Reuse Measured Breath as the baseline. All narrow healing-cleanses are 0/2 heal 2; the broad cleanse is 0/2 heal 1.

**Next test:** Compare no condition, each named condition, and multiple conditions. A broad cleanse removes one condition, not the whole inventory.

| Card | Attack / block | Main rule |
|---|---|---|
| Measured Breath (reference F01-2) | 0 / 2 | Recover 3 life on reveal. |
| Bind the Cut | 0 / 2 | Recover 2 life and remove Bleed on reveal. |
| Set the Guard | 0 / 2 | Recover 2 life and remove Exposed on reveal. |
| Regain Strength | 0 / 2 | Recover 2 life and remove Weakened on reveal. |
| Clear the Mark | 0 / 2 | Recover 2 life and remove Marked on reveal. |
| Full Reset | 0 / 2 | Recover 1 life and remove one condition of your choice on reveal. |

### F06: The basic exchange (6 → 6)

**Critique:** Equal printed totals are a useful baseline, but the original notes implied equal budgets might imply equal strength. Thresholds and wasted surplus make that false.

**Refinement:** Keep the six seven-point allocations as the clean numerical baseline. Mark All In as Heavy so the proposed telegraph package has an actual reachable payoff card.

**Next test:** Look for both a favorable and an unfavorable matchup for each allocation. Count lethal threats and objective access separately from total printed points.

| Card | Attack / block | Main rule |
|---|---|---|
| All In | 7 / 0 | No main effect. |
| Forward Step | 6 / 1 | No main effect. |
| Firm Advance | 5 / 2 | No main effect. |
| Balanced Strike | 4 / 3 | No main effect. |
| Guarded Touch | 3 / 4 | No main effect. |
| Defensive Touch | 2 / 5 | No main effect. |

### F07: Through the guard (6 → 3)

**Critique:** Trading one attack for one penetration gave the same or worse damage at every opposing block. Calling that a control did not make it a worthwhile candidate family.

**Refinement:** Replace it with 6 attack / no penetration, 5 / ignore 2, and 4 / ignore 4, all at block 1.

**Next test:** At opposing block 0/2/4, the three versions should respectively deal the most damage. Retain a regression check for those crossings.

| Card | Attack / block | Main rule |
|---|---|---|
| Full Force | 6 / 1 | No main effect. |
| Thread the Guard | 5 / 1 | Ignore 2 opposing block for your damage calculation. |
| Needle Point | 4 / 1 | Ignore 4 opposing block for your damage calculation. |

### F08: Which defense are you breaking? (6 → 4)

**Critique:** The source-bypass idea is distinct, but six versions mixed source specialization with several unrelated stat allocations.

**Refinement:** Keep four: broad penetration, bonus-source bypass, reaction-source bypass, and a lower-block conservation variant. Hold the first three at block 1.

**Next test:** Use equally large defenses made from printed block, bonus block, and reaction block. Do not infer a source counter from a single total-block number.

| Card | Attack / block | Main rule |
|---|---|---|
| Broad Fracture | 4 / 1 | Ignore 2 opposing block for your damage calculation. |
| Break the Setup | 5 / 1 | Ignore block granted by opposing bonus burns. |
| Beat the Reaction | 5 / 1 | Ignore block granted by opposing reaction burns. |
| Clean Break | 6 / 0 | If you committed no bonus, ignore block granted by opposing reaction burns. |

### F09: Power with a gift (6 → 3)

**Critique:** Six progressively larger gifts hid arithmetic cancellations. Bonuses that add attack while gifting the same extra block can resolve identically without penetration.

**Refinement:** Keep three burn profiles: +2 attack / gift 1 attack, +4 attack / gift 3 attack, and +3 attack / gift 1 block. Main and reaction remain fixed.

**Next test:** Evaluate this card as a permanently burned bonus, not a main receiving its own bonus. Test both lethal trades and attacks that fail to get through gifted block.

| Card | Attack / block | Main rule |
|---|---|---|
| Sharp Commitment | 3 / 2 | No main effect. |
| Reckless Commitment | 3 / 2 | No main effect. |
| Braced Commitment | 3 / 2 | No main effect. |

### F10: The price of commitment (6 → 3)

**Critique:** Higher attack made the miss-triggered cost less likely to occur, and a -5 block penalty is often indistinguishable from -2 after flooring. The supposed risk did not scale reliably.

**Refinement:** Use three attacks, 6/0, 7/0, and 8/0. The latter two impose -1/-2 final block next round whenever used, whether they hit or miss. All are Heavy.

**Next test:** Compare an immediate knockout with a surviving opponent’s next attack. Inspect routes into already-low-block mains, where the delayed cost can become cheap.

| Card | Attack / block | Main rule |
|---|---|---|
| Heavy Swing | 6 / 0 | No main effect. |
| Loaded Swing | 7 / 0 | After this exchange, you have -1 final block next round, to a minimum of 0. |
| Overextended Swing | 8 / 0 | After this exchange, you have -2 final block next round, to a minimum of 0. |

### F11: Read the commitment (6 → 3)

**Critique:** The threshold idea was sound, but six adjacent values blurred distinctions and overstated the need for six separate cards.

**Refinement:** Use printed-attack thresholds 3/5/7 and block rewards 1/3/5, all on a 2/2 main.

**Next test:** Test attacks just below, exactly at, and above every threshold. Printed attack, not carried or reaction attack, determines the read.

| Card | Attack / block | Main rule |
|---|---|---|
| Watch the Hands | 2 / 2 | If the opposing main has printed attack 3+, gain +1 block this round. |
| Read the Strike | 2 / 2 | If the opposing main has printed attack 5+, gain +3 block this round. |
| Read the All-In | 2 / 2 | If the opposing main has printed attack 7+, gain +5 block this round. |

### F12: Find a smaller opening (6 → 3)

**Critique:** The original rewards often merely chased the rising block threshold without creating enough payoff to justify the specialization.

**Refinement:** Use thresholds 2/4/6 and attack bonuses 2/4/6 on the same 2/2 main.

**Next test:** At block 2/4/6, confirm that the newly qualifying variant has a real outgoing-damage advantage. Check low-block opponents where every read is blank.

| Card | Attack / block | Main rule |
|---|---|---|
| Probe the Guard | 2 / 2 | If the opposing main has printed block 2+, gain +2 attack this round. |
| Open the Guard | 2 / 2 | If the opposing main has printed block 4+, gain +4 attack this round. |
| Shatter the Guard | 2 / 2 | If the opposing main has printed block 6+, gain +6 attack this round. |

### F13: A precise prediction (6 → 3)

**Critique:** Six exact-value cards included targets that might scarcely exist in the relevant hand. Exactness itself is not a reason to print every integer.

**Refinement:** Keep three target values, 2/3/5, with identical +3 attack and +2 block rewards. The attack reward must beat the plain 4/2 alternative on a correct low-attack read; surplus block against printed attack 2 is not enough.

**Next test:** Count actual opposing opportunities for each printed value and compare against the broader F11 reads. Do not balance by assuming every value is equally common.

| Card | Attack / block | Main rule |
|---|---|---|
| Read the Probe | 2 / 2 | If the opposing main has printed attack exactly 2, gain +3 attack and +2 block this round. |
| Read Balance | 2 / 2 | If the opposing main has printed attack exactly 3, gain +3 attack and +2 block this round. |
| Read Force | 2 / 2 | If the opposing main has printed attack exactly 5, gain +3 attack and +2 block this round. |

### F14: Punish the hidden bonus (6 → 3)

**Critique:** Every original version reached the same block on a successful read, while higher-base versions were better on a miss. The entire ladder was dominated.

**Refinement:** Rebuild with base block 1/3/5 and bonus-read additions 6/3/0: final block 7/6/5 on the read. Keep attack 3.

**Next test:** Without an opposing bonus, high base guard wins. With one, exposed guard has the greatest upside. Test the middle around attacks 3 and 6.

| Card | Attack / block | Main rule |
|---|---|---|
| Loose Read | 3 / 1 | If the opponent committed a bonus, gain +6 block this round. |
| Balanced Read | 3 / 3 | If the opponent committed a bonus, gain +3 block this round. |
| Closed Read | 3 / 5 | No main effect. |

### F15: Keep something in reserve (6 → 3)

**Critique:** Some conservation versions matched a sibling’s no-bonus attack while keeping more printed attack. They had no numerical reason to coexist.

**Refinement:** Use printed attack 2/4/5 with no-bonus bonuses 5/2/0, all at block 2. Clean totals are 7/6/5; bonus-supported bases are 2/4/5.

**Next test:** Compare no bonus with an actual strong bonus commitment. Own reactions still do not turn conservation off.

| Card | Attack / block | Main rule |
|---|---|---|
| Quiet Strike | 2 / 2 | If you committed no bonus, gain +5 attack this round. |
| Sure Strike | 4 / 2 | If you committed no bonus, gain +2 attack this round. |
| Firm Advance (reference F06-3) | 5 / 2 | No main effect. |

### F16: A larger reserve (6 → 3)

**Critique:** Free heal 2 was strictly below Open Breath’s free heal 4 in the same hand. The high end also jumped to heal 18 with little justification.

**Refinement:** Reuse Open Breath as the free baseline; spend 2 stamina to heal 7 or 4 stamina to heal 11, keeping 0/0 stats.

**Next test:** Test stamina 0/2/4 and different missing-life amounts. The larger conversion is more efficient only before overheal and resource opportunity costs.

| Card | Attack / block | Main rule |
|---|---|---|
| Open Breath (reference F01-1) | 0 / 0 | Recover 4 life on reveal. |
| Deep Renewal | 0 / 0; cost 2 stamina | Recover 7 life on reveal. |
| Whole Recovery | 0 / 0; cost 4 stamina | Recover 11 life on reveal. |

### F17: Pay for the opening (6 → 3)

**Critique:** Attack 17 pushed the family into a different damage scale, while its free baseline was weak enough to be eclipsed by ordinary attacks.

**Refinement:** Use 4/2 for free, 7/2 for 2 stamina, and 11/2 for 4 stamina. Make the family Heavy and keep the safety profile fixed.

**Next test:** Measure damage gained per stamina against different guards and how many affordable future turns remain. The explorer must not call the largest affordable spend strategically optimal.

| Card | Attack / block | Main rule |
|---|---|---|
| Set Fist | 4 / 2 | No main effect. |
| Loaded Fist | 7 / 2; cost 2 stamina | No main effect. |
| Full Release | 11 / 2; cost 4 stamina | No main effect. |

### F18: Cash out the advantage (6 → 4)

**Critique:** Six token packages mixed specificity, quantity, penetration, and safety before establishing a simple comparison. Mandatory token costs were easy to miss.

**Refinement:** Keep four packages with explicit required costs: any one, Red, any two different, and Red+Orange.

**Next test:** Compare legal inventories, not hypothetical token ownership. Spending a held token can open a future objective slot, which the local metric does not price.

| Card | Attack / block | Main rule |
|---|---|---|
| Open Release | 3 / 1 | Required: spend one advantage of any color. Gain +2 attack. |
| Red Release | 3 / 1 | Required: spend Red advantage. Gain +3 attack. |
| Paired Release | 3 / 1 | Required: spend two different advantages. Gain +5 attack and +1 block. |
| Precise Release | 3 / 1 | Required: spend Red and Orange advantage. Gain +4 attack and ignore 2 block. |

### F19: Make space to recover (6 → 4)

**Critique:** Generating 5 of a 6-point resource in one free exchange almost refilled the meter. Six linear entries concealed likely spend/recharge loops.

**Refinement:** Use four profiles: attack 5/4/2/0, each at block 2, generating 0/1/2/3 stamina after damage. The free baseline trades extra guard against F02’s on-hit recovery, rather than being strictly worse.

**Next test:** Audit repeated cashout/recharge sequences, full-meter waste, and whether zero-attack recovery gives the opponent a genuinely useful punish opportunity.

| Card | Attack / block | Main rule |
|---|---|---|
| Press On | 5 / 2 | No main effect. |
| Quick Reset | 4 / 2 | Gain 1 stamina after damage, up to 6. |
| Measured Reset | 2 / 2 | Gain 2 stamina after damage, up to 6. |
| Full Reset | 0 / 2 | Gain 3 stamina after damage, up to 6. |

### F20: Hold the threat (6 → 3)

**Critique:** Holding a full meter made the six-point free bonus automatic, with no consumption. The setup incentive could overwhelm actually using stamina.

**Refinement:** Keep reserve thresholds 1/3/5 and rewards 1/2/3 attack on a 4/1 main. That guard/attack split avoids making the low-threshold version strictly worse than F17’s free 4/2. Do not pretend the retained stamina was spent.

**Next test:** Test low reserves, a full meter, and a complete route containing attractive spenders. If holding remains the obvious strategy, cut this mechanic rather than increasing its reward.

| Card | Attack / block | Main rule |
|---|---|---|
| Ready Knuckle | 4 / 1 | If you began this round with at least 1 stamina, gain +1 attack. |
| Steady Knuckle | 4 / 1 | If you began this round with at least 3 stamina, gain +2 attack. |
| Reserved Knuckle | 4 / 1 | If you began this round with at least 5 stamina, gain +3 attack. |

### F21: Leave a threat behind (6 → 3)

**Critique:** The weak end asked a 1-attack main to hit for a huge carry reward. It often could not start its own plan, and +5 pressure was excessive.

**Refinement:** Reuse Forward Step’s 6/1 baseline. Compare 4/1 with on-hit carry 3 and 3/1 with carry 4. A delayed reward must offer some upside over simply hitting harder now.

**Next test:** Test the setup against block 2/3/4 and then an actual next-hand main. A miss, Brace reset, or match ending can erase the future payoff.

| Card | Attack / block | Main rule |
|---|---|---|
| Forward Step (reference F06-2) | 6 / 1 | No main effect. |
| Following Force | 4 / 1 | If you deal exchange damage, gain +3 attack next round. |
| Future Force | 3 / 1 | If you deal exchange damage, gain +4 attack next round. |

### F22: A guarded preparation (6 → 3)

**Critique:** A clean defense granting +6 attack was a giant payoff for a reusable setup, and six grades added little explanatory value.

**Refinement:** Use 1 attack with block 1/3/5 and clean-defense carry 3/2/1.

**Next test:** At incoming attack 1/3/5, compare the reachable carry. Check repeated guard/pressure cycles and no-progress recovery loops.

| Card | Attack / block | Main rule |
|---|---|---|
| Open Preparation | 1 / 1 | If you take no exchange damage, gain +3 attack next round. |
| Firm Preparation | 1 / 3 | If you take no exchange damage, gain +2 attack next round. |
| Safe Preparation | 1 / 5 | If you take no exchange damage, gain +1 attack next round. |

### F23: Show the next punch (6 → 3)

**Critique:** The payoff relied on an undefined Heavy tag and reached +6. It advertised a sequence without ensuring the proposed pool contained a reachable follow-up.

**Refinement:** Define Strike/Guard/Heavy metadata. Use three 1-block setups at attack 4/3/2 with next-round Heavy rewards 1/2/3. Print Red/Orange destinations; Red has a Heavy candidate.

**Next test:** Test a Heavy continuation, an abandoned telegraph, and an opponent preparing the obvious counter. The bonus expires even when no Heavy is used.

| Card | Attack / block | Main rule |
|---|---|---|
| Short Windup | 4 / 1 | Your next-round main gains +1 attack if it has Heavy. |
| Long Windup | 3 / 1 | Your next-round main gains +2 attack if it has Heavy. |
| Full Windup | 2 / 1 | Your next-round main gains +3 attack if it has Heavy. |

### F24: A move you cannot repeat (6 → 3)

**Critique:** The cooldown often cost nothing: the original printed routes already forced Purple to leave, so next-round reuse was unavailable anyway. Fully disabled mains also left the all-exhausted case unresolved.

**Refinement:** Give all versions Purple/Red routes so immediate reuse is possible. Use 5/1 reusable, 6/1 Exhaust 1, and 7/1 Exhaust 2. Exhaustion weakens reuse to 2/1 instead of making the main illegal.

**Next test:** Compare staying Purple, leaving for one round, and returning before refresh. Playing the exhausted 2/1 mode does not restart its timer; bonus and reaction use remain legal.

| Card | Attack / block | Main rule |
|---|---|---|
| Repeatable Blow | 5 / 1 | No main effect. |
| Taxing Blow | 6 / 1 | After use, Exhaust 1: next round this main is 2/1 with no main ability. |
| Draining Blow | 7 / 1 | After use, Exhaust 2: for the next two rounds this main is 2/1 with no main ability. |

### F25: The objective ladder (6 → 3)

**Critique:** Reducing attack and the objective threshold by the same amount left every card needing the same open guard. That barely changed objective access.

**Refinement:** Hold the main at 3/2 and change reward quality: deal 1 for Green, deal 3 for one chosen color, deal 5 for Green and Blue.

**Next test:** Check threshold access with no support, a +1 burn, and +2 support. Apply token caps and denial after determining both outcomes.

| Card | Attack / block | Main rule |
|---|---|---|
| Small Success | 3 / 2 | Objective: Deal 1+ exchange damage: gain Green advantage. |
| Chosen Success | 3 / 2 | Objective: Deal 3+ exchange damage: gain one advantage of your choice. |
| Paired Success | 3 / 2 | Objective: Deal 5+ exchange damage: gain Green and Blue advantage. |

### F26: A cut that keeps costing (6 → 3)

**Critique:** Five rounds of Bleed is difficult to evaluate and the weakest applicator could rarely land. Lifetime damage was treated too much like immediate damage.

**Refinement:** Reuse Full Force’s 6/1 baseline from F07 instead of a strictly weaker 5/1. Compare 5/1 with Bleed 2 and 4/1 with Bleed 3. Cap duration at three rounds.

**Next test:** Count ticks actually received before cleansing, expiry, or defeat. Compare an immediate lethal hit with an equal-looking total spread across future rounds.

| Card | Attack / block | Main rule |
|---|---|---|
| Full Force (reference F07-1) | 6 / 1 | No main effect. |
| Lingering Cut | 5 / 1 | If you deal exchange damage, apply Bleed for 2 rounds. |
| Deep Cut | 4 / 1 | If you deal exchange damage, apply Bleed for 3 rounds. |

### F27: Open the guard tomorrow (6 → 3)

**Critique:** Large block penalties frequently floored to the same result, and the weakest applicators could not create the status. The plain baseline duplicated F26.

**Refinement:** Reuse Full Force from F07. Compare it with 5/1 applying Exposed 1 and 4/1 applying Exposed 2, each for next round only.

**Next test:** Test next-round guards 0/1/2/4 and a cleanse. Applying Exposed to an already unguarded opponent should not be valued as extra guaranteed damage.

| Card | Attack / block | Main rule |
|---|---|---|
| Full Force (reference F07-1) | 6 / 1 | No main effect. |
| Tilting Sweep | 5 / 1 | If you deal exchange damage, apply Exposed 1 for next round. |
| Opening Sweep | 4 / 1 | If you deal exchange damage, apply Exposed 2 for next round. |

### F28: Mark the opening (6 → 3)

**Critique:** The family depended on an undefined Strike tag, while a 1-attack mark applicator was unlikely to land and a +5 follow-up was extreme.

**Refinement:** Define Strike and use three 2-block mains: attack 4 with no mark, attack 3 with Marked 2, attack 2 with Marked 3. Duration stays two rounds.

**Next test:** Check a reachable Strike, an expired or cleansed mark, and a revealed Strike that is ultimately blocked. Consume the mark on a qualifying revealed Strike, not on a hit.

| Card | Attack / block | Main rule |
|---|---|---|
| Direct Touch | 4 / 2 | No main effect. |
| Guiding Touch | 3 / 2 | If you deal exchange damage, apply Marked 2 for two rounds. |
| Perfect Mark | 2 / 2 | If you deal exchange damage, apply Marked 3 for two rounds. |

### F29: Make the injury count (6 → 3)

**Critique:** The six-round endpoint was excessive and one threshold was unreachable without extra support. The page did not make that setup requirement sufficiently concrete.

**Refinement:** Use fixed 4/1 stats with damage thresholds 1/3/5 and Bleed durations 1/2/3.

**Next test:** Threshold 5 requires at least +1 attack into zero block. Test whether the extra tick repays that support and the chance of a failed application.

| Card | Attack / block | Main rule |
|---|---|---|
| Small Wound | 4 / 1 | If you deal 1+ exchange damage, apply Bleed for 1 rounds. |
| Deep Wound | 4 / 1 | If you deal 3+ exchange damage, apply Bleed for 2 rounds. |
| Grievous Wound | 4 / 1 | If you deal 5+ exchange damage, apply Bleed for 3 rounds. |

### F30: Defend while it works (6 → 3)

**Critique:** Five turns of reduced attack risks a defensive lock, and six variations obscured whether duration or present safety was doing the work.

**Refinement:** Use 3/1 with Weakened 1 for two rounds, 3/3 with Weakened 1 for one round, or 1/5 with no status. Keeping the plain 3/5 would eclipse several Blue read cards, so its extra guard also costs attack.

**Next test:** Test cleanse access, already-low attack, and alternating reapplications. No status may activate immediately or recursively trigger another application.

| Card | Attack / block | Main rule |
|---|---|---|
| Exposed Touch | 3 / 1 | If you deal exchange damage, apply Weakened 1 for two rounds. |
| Sheltered Touch | 3 / 3 | If you deal exchange damage, apply Weakened 1 for next round. |
| Closed Touch | 1 / 5 | No main effect. |

### F31: A place for the antidote (6 → 6)

**Critique:** A zero-attack main cleansing a one-round Weakened condition often gains nothing: its attack was already zero and the condition would expire. Defense was also extremely generous.

**Refinement:** Give every version 1 attack. Plain guard is 1/5; narrow cleanses are 1/4; a broad one-condition cleanse is 1/3. Plain guard trades one extra block against Covered Return’s heal 1 rather than being a weaker copy.

**Next test:** Test a condition with one versus two rounds remaining, and whether clearing Weakened restores the chip hit. Compare this guard-led family with the healing-led F05.

| Card | Attack / block | Main rule |
|---|---|---|
| Plain Shelter | 1 / 5 | No main effect. |
| Bloodless Shelter | 1 / 4 | Remove Bleed on reveal. |
| Steady Shelter | 1 / 4 | Remove Weakened on reveal. |
| Whole Shelter | 1 / 4 | Remove Exposed on reveal. |
| Unmarked Shelter | 1 / 4 | Remove Marked on reveal. |
| Universal Shelter | 1 / 3 | Remove one condition of your choice on reveal. |

### F32: Four ways to react (6 → 4)

**Critique:** Five-point reactions substantially exceeded much of the current pool. Six adjacent allocations made the comparison look more precise than the evidence supports.

**Refinement:** Use four reactions sharing three points: +0/+3, +1/+2, +2/+1, +3/+0. Keep the main 3/2 and the bonus +1 attack.

**Next test:** Evaluate the burn against actual lethal thresholds, including the permanent loss of this main and its routes. Reactions cannot be assessed by main-only lab results.

| Card | Attack / block | Main rule |
|---|---|---|
| Shielding Check | 3 / 2 | No main effect. |
| Guarded Check | 3 / 2 | No main effect. |
| Driving Check | 3 / 2 | No main effect. |
| Striking Check | 3 / 2 | No main effect. |

### F33: Main or fuel (6 → 4)

**Critique:** The original 7/1 main with a +1 attack bonus freely improved F06’s 7/0 main in the same hand and routes. It also extended to implausible +6 fuel.

**Refinement:** Reuse Forward Step at 6/1 as the +1 bonus baseline, then use mains 5/1, 4/1, and 3/1 with bonuses +2/+3/+4.

**Next test:** Compare preserving the main with burning it into a different main. Track availability after several burns, not just the best one-round bonus package.

| Card | Attack / block | Main rule |
|---|---|---|
| Forward Step (reference F06-2) | 6 / 1 | No main effect. |
| Ready Fuel | 5 / 1 | No main effect. |
| Rich Fuel | 4 / 1 | No main effect. |
| Pure Fuel | 3 / 1 | No main effect. |

### F34: Deny the reward (6 → 3)

**Critique:** Attack and required damage fell together, largely cancelling objective access just as in F25. The family was another copy of the same arithmetic problem.

**Refinement:** Hold the main at 3/2. Compare denial for dealing 3+, dealing any damage without own burns, or dealing damage while taking none.

**Next test:** Construct a supported 3-damage trade, a clean 1-damage hit, and an unassisted 1-damage trade. Each trigger should have a situation where another fails.

| Card | Attack / block | Main rule |
|---|---|---|
| Forceful Denial | 3 / 2 | If you deal 3+ exchange damage, the opponent cannot earn advantage this round. |
| Efficient Denial | 3 / 2 | If you deal exchange damage without your own bonus or reaction burn, the opponent cannot earn advantage this round. |
| Clean Denial | 3 / 2 | If you deal exchange damage and take none, the opponent cannot earn advantage this round. |

### F35: Keep an escape route (6 → 5)

**Critique:** Blanket immunity risked deleting an entire control package at no interactive cost. The weakest broad versions were overtaxed in stats before their flexibility was measured.

**Refinement:** Use a 5/1 plain attack, three 4/1 free narrow protections, and a 4/1 flexible protection requiring one optional advantage. The flexible card chooses one protection, not all three.

**Next test:** Commit token and mode with the hidden plan. Test each control separately and combinations; spent advantage is consumed even if the protected effect never occurs.

| Card | Attack / block | Main rule |
|---|---|---|
| Plain Exit | 5 / 1 | No main effect. |
| Uncut Exit | 4 / 1 | Ignore one opposing chosen route cut this round. |
| Undirected Exit | 4 / 1 | Your next hand cannot be chosen by the opponent this round. |
| Unmoved Exit | 4 / 1 | Your main cannot be relocated this round. |
| Prepared Exit | 4 / 1 | Optional: spend one advantage. Commit one mode: ignore one route cut; prevent opposing next-hand choice; or prevent main relocation this round. |

### F36: The value of an exit (6 → 3)

**Critique:** Route count and route identity changed together, so the family could not explain which difference paid for the extra attack.

**Refinement:** Use nested destinations: 6/1 to Red, 5/1 to Red/Blue, 4/1 to Red/Blue/Purple. Keep route count isolated here; move identity comparisons to F40.

**Next test:** Test a desirable Red hand, then a depleted or empty Red hand. A route is valuable because of available tools, not merely because its color is printed.

| Card | Attack / block | Main rule |
|---|---|---|
| Red Door | 6 / 1 | No main effect. |
| Forked Door | 5 / 1 | No main effect. |
| Open Door | 4 / 1 | No main effect. |

### F37: Permission to stay (6 → 4)

**Critique:** Exact-one-damage and opponent-bonus stay triggers had no compensating payoff despite being narrower or less controllable. Six arbitrary conditions were not six equally compelling cards.

**Refinement:** Keep no-bonus, clean-defense, and 3+ damage triggers at 3/2. The reliable optional-Green-spend version is 3/3 to compensate its resource cost.

**Next test:** Test a useful stay versus a necessary exit, spending availability, and repeated same-hand defense. Permission adds a choice; it never requires staying.

| Card | Attack / block | Main rule |
|---|---|---|
| Patient Anchor | 3 / 2 | If you committed no bonus, add Green to your legal next hands. |
| Guarded Anchor | 3 / 2 | If you take no exchange damage, add Green to your legal next hands. |
| Driving Anchor | 3 / 2 | If you deal 3+ exchange damage, add Green to your legal next hands. |
| Paid Anchor | 3 / 3 | Optional: spend Green advantage. Add Green to your legal next hands. |

### F38: Cut the next step (6 → 3)

**Critique:** Attack equalled the required damage on every card. Without special modifiers all variants cut only against zero block, so the promised access tradeoff did not exist.

**Refinement:** Use attack 5/4/3, block 1, and required damage 5/3/1. The trigger margin now changes rather than cancelling.

**Next test:** Without support, cuts should be possible against opposing block up to 0/1/2 respectively. Repeat with the same added attack on all three.

| Card | Attack / block | Main rule |
|---|---|---|
| Crushing Cutoff | 5 / 1 | If you deal 5+ exchange damage, prohibit one opposing legal next color if another remains. |
| Measured Cutoff | 4 / 1 | If you deal 3+ exchange damage, prohibit one opposing legal next color if another remains. |
| Touch Cutoff | 3 / 1 | If you deal 1+ exchange damage, prohibit one opposing legal next color if another remains. |

### F39: Move their tools (6 → 3)

**Critique:** A broadly triggered, permanent relocation with no hit or resource requirement could dominate normal play by removing essential tools too cheaply.

**Refinement:** Use three 1-block mains at attack 5/4/3, reading opposing printed attack 7/5/3+. Relocation requires an optional Yellow spend and is limited to a different printed destination of that opposing card.

**Next test:** The destination must already contain an available opposing card. Test no legal destination, an unspent token, a failed read, and whether persistent tool denial remains too strong.

| Card | Attack / block | Main rule |
|---|---|---|
| Slip the Charge | 5 / 1 | Optional: spend Yellow advantage. If the opposing main has printed attack 7+, you may relocate that surviving main to a different one of its printed destinations that already contains an available card. |
| Slip the Strike | 4 / 1 | Optional: spend Yellow advantage. If the opposing main has printed attack 5+, you may relocate that surviving main to a different one of its printed destinations that already contains an available card. |
| Slip the Probe | 3 / 1 | Optional: spend Yellow advantage. If the opposing main has printed attack 3+, you may relocate that surviving main to a different one of its printed destinations that already contains an available card. |

### F40: Which road matters? (6 → 3)

**Critique:** Four-to-six printed routes introduced a new rules exception and largely repeated F36’s count tradeoff. Slicing the color array also made some destinations arbitrarily more available.

**Refinement:** Rebuild as three 3/2 mains with exactly two routes each: Red/Orange, Green/Blue, and Blue/Purple. Change only destination identity.

**Next test:** Use actual inventories and an earn/spend sequence to show why each pair is useful. No claim of superiority can come from an attack/block-only matrix.

| Card | Attack / block | Main rule |
|---|---|---|
| Forward Horizon | 3 / 2 | No main effect. |
| Quiet Horizon | 3 / 2 | No main effect. |
| Patient Horizon | 3 / 2 | No main effect. |

## Numerical screen

No unexplained numerical dominance/equivalence flags among the screened pairs.
