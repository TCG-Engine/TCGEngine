'use strict';
// Revision 2: retain the first draft in study-data.js for the visible before/after critique.
(() => {
  const d=window.DFGFamilyStudy, families=d.families;
  const row=(name,attack,block,text,bet,extra={})=>({name,attack,block,text,bet,...extra});
  const blank='No additional main effect.';
  function revise(id, critique, changes, test, patch={}) {
    const f=families.find(f=>f.id===id);
    f.before=f.variants.map(v=>({name:v.name,attack:v.attack,block:v.block,text:v.text,cost:v.cost,bonus:v.bonus??f.bonus,reaction:v.reaction??f.reaction,routes:v.routes??f.routes}));
    Object.assign(f,patch);
    f.review={critique,changes,test};
    f.variants=f.variants.map((v,i)=>({cost:0,...v,id:`${id}-${i+1}`}));
    f.caution=test;
  }
  const rows=(spec,build)=>spec.map((v,i)=>build(v,i));

  revise('F01','The healing/block trio has real crossings, but the attacking branch was presented as equally efficient without explaining its dependence on opposing block and the life cap.',
    'Keep all six. Preserve the requested trio and distinguish recovery-first cards from chip hybrids. Do not add a free burn or route improvement to rescue a weak matchup.',
    'Compare attack 0, 2, and 4 while wounded, then repeat near 40 life and against block 0 versus block 1. Repeated recovery may still stall a complete game.',
    {crossing:'Wounded into attack 0/2/4, the first three respectively maximize your life change. The chip branch becomes interesting when opposing block is zero or healing would overflow.'});

  revise('F02','The six-step ladder ended in 1-attack heal-6 cards that often cannot trigger. Several middle entries merely exchanged outgoing damage for healing one-for-one.',
    'Keep three attainable hit thresholds and make recovery grow faster than attack falls: 5/1 heal 1, 4/1 heal 3, 3/1 heal 5.',
    'Against block 4 only the 5-attack main heals; against block 3 the middle main can earn its reward; against block 0 the risky recovery leads when wounded. Check survival before any heal.',
    {variants:rows([[5,1],[4,3],[3,5]],([a,h],i)=>row(['Small Respite','Returning Palm','Deep Recovery'][i],a,1,`If you deal exchange damage, recover ${h} life after damage. This cannot prevent a knockout.`,`Attack ${a} must connect to recover ${h}.`,{model:{heal:h,healOnHit:true,healTiming:'after'}})),crossing:'Stronger healing has a narrower hit window. The middle version now has an actual upside over the safer hitter rather than only tying its life-swing metric.'});

  revise('F03','The basic idea works, but six adjacent block thresholds obscured the three distinct bets: ambitious recovery, balanced recovery, reliable guard.',
    'Use block 1/3/5 with conditional healing 5/3/1. Keep the zero-damage requirement and post-damage timing explicit.',
    'At incoming attack 1, 3, and 5 respectively, test which version both qualifies and produces the best recovery. Test direct retaliation separately before promotion.',
    {variants:rows([[1,5],[3,3],[5,1]],([b,h],i)=>row(['Still Water','Patient Breath','Deep Shelter'][i],0,b,`If you take no exchange damage, recover ${h} life after damage.`,`Block ${b} protects a ${h}-life recovery.`,{model:{heal:h,cleanHeal:true,healTiming:'after'}})),crossing:'The reward falls as the range of attacks you can fully block widens. No recovery occurs if even 1 exchange damage gets through.'});

  revise('F04','The original immediate cards were weaker copies of other healing cards. Fixing those references still left an undefined queue that could accumulate recovery indefinitely.',
    'Keep Measured Breath and Steady Return as shared references. Keep the larger delayed recoveries, but use one public pending-recovery slot per fighter; a new delayed recovery replaces the old queue.',
    'Test survival until maturity, cap waste, replacing a pending queue, and repeated hand routes. A due queue resolves before a newly revealed card schedules another one.',
    {crossing:'The 0/2 branch offers 3 life now, 4 next reveal, or 6 two reveals later. The 1/2 branch offers 2 now, 4 next reveal, or 6 two reveals later. Waiting earns more only if the match and recovery window last.'});

  revise('F05','Mend was 0/3 heal 3, making it a free improvement over Measured Breath at 0/2 heal 3. That is exactly the cross-family mistake the study should catch.',
    'Reuse Measured Breath as the baseline. All narrow healing-cleanses are 0/2 heal 2; the broad cleanse is 0/2 heal 1.',
    'Compare no condition, each named condition, and multiple conditions. A broad cleanse removes one condition, not the whole inventory.',
    {variants:[
      row('Measured Breath',0,2,'Recover 3 life on reveal.','Shared immediate-recovery baseline.',{referenceOf:'F01-2'}),
      ...['Bleed','Exposed','Weakened','Marked'].map((s,i)=>row(['Bind the Cut','Set the Guard','Regain Strength','Clear the Mark'][i],0,2,`Recover 2 life and remove ${s} on reveal.`,`Give up 1 recovery to answer ${s}.`)),
      row('Full Reset',0,2,'Recover 1 life and remove one condition of your choice on reveal.','Give up 2 recovery for flexible coverage.')
    ],crossing:'Raw recovery leads without a relevant condition. A narrow answer preserves more healing than the flexible answer when its specific target is present.'});

  revise('F06','Equal printed totals are a useful baseline, but the original notes implied equal budgets might imply equal strength. Thresholds and wasted surplus make that false.',
    'Keep the six seven-point allocations as the clean numerical baseline. Mark All In as Heavy so the proposed telegraph package has an actual reachable payoff card.',
    'Look for both a favorable and an unfavorable matchup for each allocation. Count lethal threats and objective access separately from total printed points.',
    {crossing:'Attack and block stop helping at different thresholds. This family is a baseline for measuring those thresholds, not an asserted balance curve.',variants:families[5].variants.map((v,i)=>({...v,tags:i===0?['Strike','Heavy']:['Strike']}))});

  revise('F07','Trading one attack for one penetration gave the same or worse damage at every opposing block. Calling that a control did not make it a worthwhile candidate family.',
    'Replace it with 6 attack / no penetration, 5 / ignore 2, and 4 / ignore 4, all at block 1.',
    'At opposing block 0/2/4, the three versions should respectively deal the most damage. Retain a regression check for those crossings.',
    {variants:rows([[6,0],[5,2],[4,4]],([a,p],i)=>row(['Full Force','Thread the Guard','Needle Point'][i],a,1,p?`Ignore ${p} opposing block for your damage calculation.`:blank,`Attack ${a}; penetration ${p}.`,{model:{pierce:p}})),crossing:'Against block 0, raw force wins; against block 2, moderate penetration wins; against block 4, deep penetration wins.'});

  revise('F08','The source-bypass idea is distinct, but six versions mixed source specialization with several unrelated stat allocations.',
    'Keep four: broad penetration, bonus-source bypass, reaction-source bypass, and a lower-block conservation variant. Hold the first three at block 1.',
    'Use equally large defenses made from printed block, bonus block, and reaction block. Do not infer a source counter from a single total-block number.',
    {variants:[
      row('Broad Fracture',4,1,'Ignore 2 opposing block for your damage calculation.','Smaller attack, but reliable against any source.'),
      row('Break the Setup',5,1,'Ignore block granted by opposing bonus burns.','Higher attack for reading committed defense.'),
      row('Beat the Reaction',5,1,'Ignore block granted by opposing reaction burns.','Higher attack for reading late defense.'),
      row('Clean Break',6,0,'If you committed no bonus, ignore block granted by opposing reaction burns.','More attack costs guard and bonus flexibility.')
    ],crossing:'Narrow bypasses retain an extra point of attack but fail against the wrong defense source. Clean Break pays a separate, visible safety cost.'});

  revise('F09','Six progressively larger gifts hid arithmetic cancellations. Bonuses that add attack while gifting the same extra block can resolve identically without penetration.',
    'Keep three burn profiles: +2 attack / gift 1 attack, +4 attack / gift 3 attack, and +3 attack / gift 1 block. Main and reaction remain fixed.',
    'Evaluate this card as a permanently burned bonus, not a main receiving its own bonus. Test both lethal trades and attacks that fail to get through gifted block.',
    {variants:[
      row('Sharp Commitment',3,2,blank,'Modest boost and modest danger.',{bonus:'Gain +2 attack. Your opponent gains +1 attack this round.'}),
      row('Reckless Commitment',3,2,blank,'Break a damage threshold at the cost of a much harder return hit.',{bonus:'Gain +4 attack. Your opponent gains +3 attack this round.'}),
      row('Braced Commitment',3,2,blank,'Preserve your own life but reinforce the guard you must penetrate.',{bonus:'Gain +3 attack. Your opponent gains +1 block this round.'})
    ],crossing:'Attack gifts are attractive when already safely blocking. A block gift can be safer for survival but worse for connecting your attack.'});

  revise('F10','Higher attack made the miss-triggered cost less likely to occur, and a -5 block penalty is often indistinguishable from -2 after flooring. The supposed risk did not scale reliably.',
    'Use three attacks, 6/0, 7/0, and 8/0. The latter two impose -1/-2 final block next round whenever used, whether they hit or miss. All are Heavy.',
    'Compare an immediate knockout with a surviving opponent’s next attack. Inspect routes into already-low-block mains, where the delayed cost can become cheap.',
    {title:'The price of commitment',question:'How much present force is a guaranteed recovery window worth?',axis:'Attack ↔ guaranteed next-round vulnerability',tags:['Strike','Heavy'],variants:[
      row('Heavy Swing',6,0,blank,'Least force, no future exposure.'),
      row('Loaded Swing',7,0,'After this exchange, you have -1 final block next round, to a minimum of 0.','One extra attack buys a visible future weakness.'),
      row('Overextended Swing',8,0,'After this exchange, you have -2 final block next round, to a minimum of 0.','Greatest immediate force; the cost applies even on a hit.')
    ],crossing:'The price no longer disappears merely because the bigger attack connects. It still matters only if a subsequent exchange happens.'});

  revise('F11','The threshold idea was sound, but six adjacent values blurred distinctions and overstated the need for six separate cards.',
    'Use printed-attack thresholds 3/5/7 and block rewards 1/3/5, all on a 2/2 main.',
    'Test attacks just below, exactly at, and above every threshold. Printed attack, not carried or reaction attack, determines the read.',
    {variants:rows([[3,1],[5,3],[7,5]],([t,b],i)=>row(['Watch the Hands','Read the Strike','Read the All-In'][i],2,2,`If the opposing main has printed attack ${t}+, gain +${b} block this round.`,`A ${t}+ read buys ${b} block.`,{model:{attackThreshold:t,readBlock:b}})),crossing:'The broad read covers smaller attacks; the narrow read earns enough block to meet a larger threat.'});

  revise('F12','The original rewards often merely chased the rising block threshold without creating enough payoff to justify the specialization.',
    'Use thresholds 2/4/6 and attack bonuses 2/4/6 on the same 2/2 main.',
    'At block 2/4/6, confirm that the newly qualifying variant has a real outgoing-damage advantage. Check low-block opponents where every read is blank.',
    {variants:rows([[2,2],[4,4],[6,6]],([t,a],i)=>row(['Probe the Guard','Open the Guard','Shatter the Guard'][i],2,2,`If the opposing main has printed block ${t}+, gain +${a} attack this round.`,`A ${t}+ guard read buys ${a} attack.`,{model:{blockThreshold:t,readAttack:a}})),crossing:'Each specialization opens a different band of high-block opponents, while returning to only 2 attack below its threshold.'});

  revise('F13','Six exact-value cards included targets that might scarcely exist in the relevant hand. Exactness itself is not a reason to print every integer.',
    'Keep three target values, 2/3/5, with identical +3 attack and +2 block rewards. The attack reward must beat the plain 4/2 alternative on a correct low-attack read; surplus block against printed attack 2 is not enough.',
    'Count actual opposing opportunities for each printed value and compare against the broader F11 reads. Do not balance by assuming every value is equally common.',
    {variants:[2,3,5].map((t,i)=>row(['Read the Probe','Read Balance','Read Force'][i],2,2,`If the opposing main has printed attack exactly ${t}, gain +3 attack and +2 block this round.`,`A precise read of printed attack ${t}.`,{model:{exactAttack:t,readAttack:3,readBlock:2}})),crossing:'Equal rewards make these alternative predictions, not an efficiency ladder. Each fails against values its siblings target.'});

  revise('F14','Every original version reached the same block on a successful read, while higher-base versions were better on a miss. The entire ladder was dominated.',
    'Rebuild with base block 1/3/5 and bonus-read additions 6/3/0: final block 7/6/5 on the read. Keep attack 3.',
    'Without an opposing bonus, high base guard wins. With one, exposed guard has the greatest upside. Test the middle around attacks 3 and 6.',
    {lab:true,variants:rows([[1,6],[3,3],[5,0]],([b,g],i)=>row(['Loose Read','Balanced Read','Closed Read'][i],3,b,g?`If the opponent committed a bonus, gain +${g} block this round.`:blank,`Block ${b} normally; ${b+g} against a bonus.`,{model:{bonusBlock:g}})),crossing:'Safety and successful-read payoff now move in opposite directions. No version has both the best fallback and the best reward.'});

  revise('F15','Some conservation versions matched a sibling’s no-bonus attack while keeping more printed attack. They had no numerical reason to coexist.',
    'Use printed attack 2/4/5 with no-bonus bonuses 5/2/0, all at block 2. Clean totals are 7/6/5; bonus-supported bases are 2/4/5.',
    'Compare no bonus with an actual strong bonus commitment. Own reactions still do not turn conservation off.',
    {lab:true,variants:rows([[2,5],[4,2],[5,0]],([a,g],i)=>row(['Quiet Strike','Sure Strike','Firm Advance'][i],a,2,g?`If you committed no bonus, gain +${g} attack this round.`:blank,`Conserve: ${a+g} attack. Commit a bonus: ${a} before its effect.`,i===2?{referenceOf:'F06-3'}:{model:{noBonusAttack:g}})),crossing:'The clean hitter gives up more printed strength. The flexible hitter accepts lower unassisted output to support a powerful bonus. Firm Advance is reused from F06 rather than renamed.'});

  revise('F16','Free heal 2 was strictly below Open Breath’s free heal 4 in the same hand. The high end also jumped to heal 18 with little justification.',
    'Reuse Open Breath as the free baseline; spend 2 stamina to heal 7 or 4 stamina to heal 11, keeping 0/0 stats.',
    'Test stamina 0/2/4 and different missing-life amounts. The larger conversion is more efficient only before overheal and resource opportunity costs.',
    {variants:[
      row('Open Breath',0,0,'Recover 4 life on reveal.','Shared zero-cost recovery baseline.',{referenceOf:'F01-1'}),
      row('Deep Renewal',0,0,'Recover 7 life on reveal.','Pay 2 stamina for 3 extra recovery.',{cost:2,model:{heal:7}}),
      row('Whole Recovery',0,0,'Recover 11 life on reveal.','Pay 4 stamina for 7 extra recovery.',{cost:4,model:{heal:11}})
    ],crossing:'A higher threshold buys better incremental conversion, but only a wounded fighter with sufficient reserves can use all of it.'});

  revise('F17','Attack 17 pushed the family into a different damage scale, while its free baseline was weak enough to be eclipsed by ordinary attacks.',
    'Use 4/2 for free, 7/2 for 2 stamina, and 11/2 for 4 stamina. Make the family Heavy and keep the safety profile fixed.',
    'Measure damage gained per stamina against different guards and how many affordable future turns remain. The explorer must not call the largest affordable spend strategically optimal.',
    {tags:['Strike','Heavy'],variants:rows([[4,0],[7,2],[11,4]],([a,c],i)=>row(['Set Fist','Loaded Fist','Full Release'][i],a,2,blank,`${a} attack costs ${c} stamina; block stays 2.`,{cost:c,model:{}})),crossing:'Higher spending breaks larger guards; cheaper versions preserve reserves and remain usable after an earlier cashout.'});

  revise('F18','Six token packages mixed specificity, quantity, penetration, and safety before establishing a simple comparison. Mandatory token costs were easy to miss.',
    'Keep four packages with explicit required costs: any one, Red, any two different, and Red+Orange.',
    'Compare legal inventories, not hypothetical token ownership. Spending a held token can open a future objective slot, which the local metric does not price.',
    {variants:[
      row('Open Release',3,1,'Required: spend one advantage of any color. Gain +2 attack.','Broadest access, smallest payoff.',{costLabel:'1 ANY'}),
      row('Red Release',3,1,'Required: spend Red advantage. Gain +3 attack.','One specific color buys an extra point.',{costLabel:'1 RED'}),
      row('Paired Release',3,1,'Required: spend two different advantages. Gain +5 attack and +1 block.','A larger, safer cashout needs two resources.',{costLabel:'2 ANY'}),
      row('Precise Release',3,1,'Required: spend Red and Orange advantage. Gain +4 attack and ignore 2 block.','Specific pair specializes in breaking guard.',{costLabel:'RED + ORG'})
    ],crossing:'The any-color spender wins on availability; the specific spenders win on conversion when their earn routes work.'});

  revise('F19','Generating 5 of a 6-point resource in one free exchange almost refilled the meter. Six linear entries concealed likely spend/recharge loops.',
    'Use four profiles: attack 5/4/2/0, each at block 2, generating 0/1/2/3 stamina after damage. The free baseline trades extra guard against F02’s on-hit recovery, rather than being strictly worse.',
    'Audit repeated cashout/recharge sequences, full-meter waste, and whether zero-attack recovery gives the opponent a genuinely useful punish opportunity.',
    {variants:rows([[5,0],[4,1],[2,2],[0,3]],([a,g],i)=>row(['Press On','Quick Reset','Measured Reset','Full Reset'][i],a,2,g?`Gain ${g} stamina after damage, up to 6.`:blank,`Deal with ${a} attack now; recover ${g} stamina for later.`)),crossing:'More substantial regeneration now demands a more substantial sacrifice in immediate attack.'});

  revise('F20','Holding a full meter made the six-point free bonus automatic, with no consumption. The setup incentive could overwhelm actually using stamina.',
    'Keep reserve thresholds 1/3/5 and rewards 1/2/3 attack on a 4/1 main. That guard/attack split avoids making the low-threshold version strictly worse than F17’s free 4/2. Do not pretend the retained stamina was spent.',
    'Test low reserves, a full meter, and a complete route containing attractive spenders. If holding remains the obvious strategy, cut this mechanic rather than increasing its reward.',
    {variants:rows([[1,1],[3,2],[5,3]],([t,a],i)=>row(['Ready Knuckle','Steady Knuckle','Reserved Knuckle'][i],4,1,`If you began this round with at least ${t} stamina, gain +${a} attack.`,`Hold ${t} stamina for +${a}; keep the reserve.`,{model:{reserveThreshold:t,reserveAttack:a}})),crossing:'Low thresholds work after spending; high thresholds reward maintaining the reserve. The decision lives across rounds, not in a full-meter snapshot.'});

  revise('F21','The weak end asked a 1-attack main to hit for a huge carry reward. It often could not start its own plan, and +5 pressure was excessive.',
    'Reuse Forward Step’s 6/1 baseline. Compare 4/1 with on-hit carry 3 and 3/1 with carry 4. A delayed reward must offer some upside over simply hitting harder now.',
    'Test the setup against block 2/3/4 and then an actual next-hand main. A miss, Brace reset, or match ending can erase the future payoff.',
    {variants:rows([[6,0],[4,3],[3,4]],([a,g],i)=>row(['Forward Step','Following Force','Future Force'][i],a,1,g?`If you deal exchange damage, gain +${g} attack next round.`:blank,`Attack ${a} now; on hit, ${g} pressure next round.`,i===0?{referenceOf:'F06-2'}:{})),crossing:'Delayed pressure can exceed the immediate attack sacrificed, but its smaller setup attack must first connect.'});

  revise('F22','A clean defense granting +6 attack was a giant payoff for a reusable setup, and six grades added little explanatory value.',
    'Use 1 attack with block 1/3/5 and clean-defense carry 3/2/1.',
    'At incoming attack 1/3/5, compare the reachable carry. Check repeated guard/pressure cycles and no-progress recovery loops.',
    {variants:rows([[1,3],[3,2],[5,1]],([b,g],i)=>row(['Open Preparation','Firm Preparation','Safe Preparation'][i],1,b,`If you take no exchange damage, gain +${g} attack next round.`,`Block ${b} protects +${g} future attack.`)),crossing:'The exposed preparation earns more on a quiet read. Reliable defense earns less pressure.'});

  revise('F23','The payoff relied on an undefined Heavy tag and reached +6. It advertised a sequence without ensuring the proposed pool contained a reachable follow-up.',
    'Define Strike/Guard/Heavy metadata. Use three 1-block setups at attack 4/3/2 with next-round Heavy rewards 1/2/3. Print Red/Orange destinations; Red has a Heavy candidate.',
    'Test a Heavy continuation, an abandoned telegraph, and an opponent preparing the obvious counter. The bonus expires even when no Heavy is used.',
    {routes:['Red','Orange'],variants:rows([[4,1],[3,2],[2,3]],([a,g],i)=>row(['Short Windup','Long Windup','Full Windup'][i],a,1,`Your next-round main gains +${g} attack if it has Heavy.`,`Attack ${a} now; show a +${g} Heavy threat.`)),crossing:'Larger preparation concedes more current attack, but the visible threat can induce a response you choose to exploit instead.'});

  revise('F24','The cooldown often cost nothing: the original printed routes already forced Purple to leave, so next-round reuse was unavailable anyway. Fully disabled mains also left the all-exhausted case unresolved.',
    'Give all versions Purple/Red routes so immediate reuse is possible. Use 5/1 reusable, 6/1 Exhaust 1, and 7/1 Exhaust 2. Exhaustion weakens reuse to 2/1 instead of making the main illegal.',
    'Compare staying Purple, leaving for one round, and returning before refresh. Playing the exhausted 2/1 mode does not restart its timer; bonus and reaction use remain legal.',
    {routes:['Purple','Red'],tags:['Strike','Heavy'],variants:[
      row('Repeatable Blow',5,1,blank,'Can stay Purple and repeat at full strength.'),
      row('Taxing Blow',6,1,'After use, Exhaust 1: next round this main is 2/1 with no main ability.','One stronger hit sacrifices immediate strong reuse.'),
      row('Draining Blow',7,1,'After use, Exhaust 2: for the next two rounds this main is 2/1 with no main ability.','Greater force restricts an early return, even after leaving.')
    ],crossing:'Cooldown now intersects an actual printed stay route. Recovery preserves a legal fallback rather than creating an accidental new defeat condition.'});

  revise('F25','Reducing attack and the objective threshold by the same amount left every card needing the same open guard. That barely changed objective access.',
    'Hold the main at 3/2 and change reward quality: deal 1 for Green, deal 3 for one chosen color, deal 5 for Green and Blue.',
    'Check threshold access with no support, a +1 burn, and +2 support. Apply token caps and denial after determining both outcomes.',
    {axis:'Damage threshold ↔ objective reward',variants:[
      row('Small Success',3,2,'Objective: Deal 1+ exchange damage: gain Green advantage.','Accessible objective, fixed currency.'),
      row('Chosen Success',3,2,'Objective: Deal 3+ exchange damage: gain one advantage of your choice.','A clean hit buys resource flexibility.'),
      row('Paired Success',3,2,'Objective: Deal 5+ exchange damage: gain Green and Blue advantage.','Needs support; rewards a two-token setup.')
    ],crossing:'The harder objective now has a better reward instead of a stat reduction that cancels its easier threshold.'});

  revise('F26','Five rounds of Bleed is difficult to evaluate and the weakest applicator could rarely land. Lifetime damage was treated too much like immediate damage.',
    'Reuse Full Force’s 6/1 baseline from F07 instead of a strictly weaker 5/1. Compare 5/1 with Bleed 2 and 4/1 with Bleed 3. Cap duration at three rounds.',
    'Count ticks actually received before cleansing, expiry, or defeat. Compare an immediate lethal hit with an equal-looking total spread across future rounds.',
    {variants:rows([[6,0],[5,2],[4,3]],([a,t],i)=>row(['Full Force','Lingering Cut','Deep Cut'][i],a,1,t?`If you deal exchange damage, apply Bleed for ${t} rounds.`:blank,`Attack ${a}; up to ${t} later damage if the condition lasts.`,i===0?{referenceOf:'F07-1'}:{})),crossing:'A longer game without removal rewards delayed attrition. A short race or a cleanse favors immediate force.'});

  revise('F27','Large block penalties frequently floored to the same result, and the weakest applicators could not create the status. The plain baseline duplicated F26.',
    'Reuse Full Force from F07. Compare it with 5/1 applying Exposed 1 and 4/1 applying Exposed 2, each for next round only.',
    'Test next-round guards 0/1/2/4 and a cleanse. Applying Exposed to an already unguarded opponent should not be valued as extra guaranteed damage.',
    {variants:[
      row('Full Force',6,1,blank,'Shared immediate-force baseline.',{referenceOf:'F07-1'}),
      row('Tilting Sweep',5,1,'If you deal exchange damage, apply Exposed 1 for next round.','Trade 1 current attack for a small punish window.'),
      row('Opening Sweep',4,1,'If you deal exchange damage, apply Exposed 2 for next round.','A harder setup buys a larger opening.')
    ],crossing:'Exposed changes a future defense rather than current life. Its value depends on the opponent’s next guard and your reachable follow-up.'});

  revise('F28','The family depended on an undefined Strike tag, while a 1-attack mark applicator was unlikely to land and a +5 follow-up was extreme.',
    'Define Strike and use three 2-block mains: attack 4 with no mark, attack 3 with Marked 2, attack 2 with Marked 3. Duration stays two rounds.',
    'Check a reachable Strike, an expired or cleansed mark, and a revealed Strike that is ultimately blocked. Consume the mark on a qualifying revealed Strike, not on a hit.',
    {variants:rows([[4,0],[3,2],[2,3]],([a,g],i)=>row(['Direct Touch','Guiding Touch','Perfect Mark'][i],a,2,g?`If you deal exchange damage, apply Marked ${g} for two rounds.`:blank,`Attack ${a} now; the next qualifying Strike receives +${g}.`)),crossing:'Marked permits two rounds of routing flexibility but only one boosted Strike. Unconditional carry and target-specific setup are different tools.'});

  revise('F29','The six-round endpoint was excessive and one threshold was unreachable without extra support. The page did not make that setup requirement sufficiently concrete.',
    'Use fixed 4/1 stats with damage thresholds 1/3/5 and Bleed durations 1/2/3.',
    'Threshold 5 requires at least +1 attack into zero block. Test whether the extra tick repays that support and the chance of a failed application.',
    {variants:[1,3,5].map((t,i)=>row(['Small Wound','Deep Wound','Grievous Wound'][i],4,1,`If you deal ${t}+ exchange damage, apply Bleed for ${i+1} rounds.`,`Deal ${t} damage for ${i+1} potential ticks.`)),crossing:'Low thresholds reward reliability; high thresholds buy duration only after a larger successful commitment.'});

  revise('F30','Five turns of reduced attack risks a defensive lock, and six variations obscured whether duration or present safety was doing the work.',
    'Use 3/1 with Weakened 1 for two rounds, 3/3 with Weakened 1 for one round, or 1/5 with no status. Keeping the plain 3/5 would eclipse several Blue read cards, so its extra guard also costs attack.',
    'Test cleanse access, already-low attack, and alternating reapplications. No status may activate immediately or recursively trigger another application.',
    {variants:[
      row('Exposed Touch',3,1,'If you deal exchange damage, apply Weakened 1 for two rounds.','Longer suppression costs current safety.'),
      row('Sheltered Touch',3,3,'If you deal exchange damage, apply Weakened 1 for next round.','Shorter suppression with a useful guard.'),
      row('Closed Touch',1,5,blank,'Give up attack and suppression for immediate defense.')
    ],crossing:'Future suppression requires a hit and time to matter. Immediate block helps even when the attack is stopped.'});

  revise('F31','A zero-attack main cleansing a one-round Weakened condition often gains nothing: its attack was already zero and the condition would expire. Defense was also extremely generous.',
    'Give every version 1 attack. Plain guard is 1/5; narrow cleanses are 1/4; a broad one-condition cleanse is 1/3. Plain guard trades one extra block against Covered Return’s heal 1 rather than being a weaker copy.',
    'Test a condition with one versus two rounds remaining, and whether clearing Weakened restores the chip hit. Compare this guard-led family with the healing-led F05.',
    {variants:[
      row('Plain Shelter',1,5,blank,'Best immediate guard, no removal.'),
      ...['Bleed','Weakened','Exposed','Marked'].map((s,i)=>row(['Bloodless Shelter','Steady Shelter','Whole Shelter','Unmarked Shelter'][i],1,4,`Remove ${s} on reveal.`,`Narrow answer to ${s}, with a live chip threat.`)),
      row('Universal Shelter',1,3,'Remove one condition of your choice on reveal.','Give up another block for coverage.')
    ],crossing:'A narrow cleanse gives up less guard. Unlike pure healing, cleanse value depends on what remains of the particular condition.'});

  revise('F32','Five-point reactions substantially exceeded much of the current pool. Six adjacent allocations made the comparison look more precise than the evidence supports.',
    'Use four reactions sharing three points: +0/+3, +1/+2, +2/+1, +3/+0. Keep the main 3/2 and the bonus +1 attack.',
    'Evaluate the burn against actual lethal thresholds, including the permanent loss of this main and its routes. Reactions cannot be assessed by main-only lab results.',
    {title:'Four ways to react',variants:rows([[0,3],[1,2],[2,1],[3,0]],([a,b],i)=>row(['Shielding Check','Guarded Check','Driving Check','Striking Check'][i],3,2,blank,`Reaction: ${a} attack and ${b} block.`,{reaction:[a?`Gain +${a} attack`:null,b?`gain +${b} block`:null].filter(Boolean).join(' and ')+' this round.'})),crossing:'Pure guard can avert lethal; pure attack can force it. Mixed reactions matter when both sides are close to a threshold.'});

  revise('F33','The original 7/1 main with a +1 attack bonus freely improved F06’s 7/0 main in the same hand and routes. It also extended to implausible +6 fuel.',
    'Reuse Forward Step at 6/1 as the +1 bonus baseline, then use mains 5/1, 4/1, and 3/1 with bonuses +2/+3/+4.',
    'Compare preserving the main with burning it into a different main. Track availability after several burns, not just the best one-round bonus package.',
    {variants:[
      row('Forward Step',6,1,blank,'Shared reusable-main baseline.',{referenceOf:'F06-2'}),
      ...[[5,2],[4,3],[3,4]].map(([a,g],i)=>row(['Ready Fuel','Rich Fuel','Pure Fuel'][i],a,1,blank,`Reusable attack ${a}; permanent bonus +${g}.`,{bonus:`Gain +${g} attack this round.`}))
    ],crossing:'Better fuel now costs reusable attack. Neither mode is a free improvement over the shared baseline.'});

  revise('F34','Attack and required damage fell together, largely cancelling objective access just as in F25. The family was another copy of the same arithmetic problem.',
    'Hold the main at 3/2. Compare denial for dealing 3+, dealing any damage without own burns, or dealing damage while taking none.',
    'Construct a supported 3-damage trade, a clean 1-damage hit, and an unassisted 1-damage trade. Each trigger should have a situation where another fails.',
    {axis:'Different commitment conditions for the same denial',variants:[
      row('Forceful Denial',3,2,'If you deal 3+ exchange damage, the opponent cannot earn advantage this round.','Allows burns and trades, but needs a substantial hit.'),
      row('Efficient Denial',3,2,'If you deal exchange damage without your own bonus or reaction burn, the opponent cannot earn advantage this round.','A small unassisted trade can deny the reward.'),
      row('Clean Denial',3,2,'If you deal exchange damage and take none, the opponent cannot earn advantage this round.','Allows support, but requires a clean exchange.')
    ],crossing:'The conditions now describe different plans instead of six ways to need the same unblocked attack.'});

  revise('F35','Blanket immunity risked deleting an entire control package at no interactive cost. The weakest broad versions were overtaxed in stats before their flexibility was measured.',
    'Use a 5/1 plain attack, three 4/1 free narrow protections, and a 4/1 flexible protection requiring one optional advantage. The flexible card chooses one protection, not all three.',
    'Commit token and mode with the hidden plan. Test each control separately and combinations; spent advantage is consumed even if the protected effect never occurs.',
    {variants:[
      row('Plain Exit',5,1,blank,'Largest hit, no protection.'),
      row('Uncut Exit',4,1,'Ignore one opposing chosen route cut this round.','Specialize against chosen cuts.'),
      row('Undirected Exit',4,1,'Your next hand cannot be chosen by the opponent this round.','Specialize against forced destinations.'),
      row('Unmoved Exit',4,1,'Your main cannot be relocated this round.','Specialize against displacement.'),
      row('Prepared Exit',4,1,'Optional: spend one advantage. Commit one mode: ignore one route cut; prevent opposing next-hand choice; or prevent main relocation this round.','Pay for one flexible answer, not blanket immunity.',{costLabel:'OPT ANY'})
    ],crossing:'Narrow cards save a token when the threat is known. The flexible answer covers more matchups with one moveset slot.'});

  revise('F36','Route count and route identity changed together, so the family could not explain which difference paid for the extra attack.',
    'Use nested destinations: 6/1 to Red, 5/1 to Red/Blue, 4/1 to Red/Blue/Purple. Keep route count isolated here; move identity comparisons to F40.',
    'Test a desirable Red hand, then a depleted or empty Red hand. A route is valuable because of available tools, not merely because its color is printed.',
    {variants:rows([[6,['Red']],[5,['Red','Blue']],[4,['Red','Blue','Purple']]],([a,r],i)=>row(['Red Door','Forked Door','Open Door'][i],a,1,blank,`${a} attack for ${r.length} printed destination${r.length===1?'':'s'}.`,{routes:r})),crossing:'Each additional destination costs one attack while retaining every earlier destination. This isolates the price of choice.'});

  revise('F37','Exact-one-damage and opponent-bonus stay triggers had no compensating payoff despite being narrower or less controllable. Six arbitrary conditions were not six equally compelling cards.',
    'Keep no-bonus, clean-defense, and 3+ damage triggers at 3/2. The reliable optional-Green-spend version is 3/3 to compensate its resource cost.',
    'Test a useful stay versus a necessary exit, spending availability, and repeated same-hand defense. Permission adds a choice; it never requires staying.',
    {variants:[
      row('Patient Anchor',3,2,'If you committed no bonus, add Green to your legal next hands.','Conserve to preserve the stay route.'),
      row('Guarded Anchor',3,2,'If you take no exchange damage, add Green to your legal next hands.','Earn the stay by defending.'),
      row('Driving Anchor',3,2,'If you deal 3+ exchange damage, add Green to your legal next hands.','Earn the stay by landing a meaningful hit.'),
      row('Paid Anchor',3,3,'Optional: spend Green advantage. Add Green to your legal next hands.','Pay for certainty and an extra printed block.',{costLabel:'OPT GREEN'})
    ],crossing:'The free stay routes ask for different execution. The paid route trades a held resource for reliability and slightly better guard.'});

  revise('F38','Attack equalled the required damage on every card. Without special modifiers all variants cut only against zero block, so the promised access tradeoff did not exist.',
    'Use attack 5/4/3, block 1, and required damage 5/3/1. The trigger margin now changes rather than cancelling.',
    'Without support, cuts should be possible against opposing block up to 0/1/2 respectively. Repeat with the same added attack on all three.',
    {variants:rows([[5,5],[4,3],[3,1]],([a,t],i)=>row(['Crushing Cutoff','Measured Cutoff','Touch Cutoff'][i],a,1,`If you deal ${t}+ exchange damage, prohibit one opposing legal next color if another remains.`,`Attack ${a}; a route cut requires ${t} damage.`)),crossing:'The stronger hit has the narrowest cut window. The smaller hit can earn control through more opposing guard.'});

  revise('F39','A broadly triggered, permanent relocation with no hit or resource requirement could dominate normal play by removing essential tools too cheaply.',
    'Use three 1-block mains at attack 5/4/3, reading opposing printed attack 7/5/3+. Relocation requires an optional Yellow spend and is limited to a different printed destination of that opposing card.',
    'The destination must already contain an available opposing card. Test no legal destination, an unspent token, a failed read, and whether persistent tool denial remains too strong.',
    {variants:rows([[5,7],[4,5],[3,3]],([a,t],i)=>row(['Slip the Charge','Slip the Strike','Slip the Probe'][i],a,1,`Optional: spend Yellow advantage. If the opposing main has printed attack ${t}+, you may relocate that surviving main to a different one of its printed destinations that already contains an available card.`,`Attack ${a}; spend Yellow and read ${t}+ to move a tool.`,{costLabel:'OPT YELLOW'})),crossing:'Broader access costs immediate attack, while every relocation now risks a token and has a bounded set of destinations.'});

  revise('F40','Four-to-six printed routes introduced a new rules exception and largely repeated F36’s count tradeoff. Slicing the color array also made some destinations arbitrarily more available.',
    'Rebuild as three 3/2 mains with exactly two routes each: Red/Orange, Green/Blue, and Blue/Purple. Change only destination identity.',
    'Use actual inventories and an earn/spend sequence to show why each pair is useful. No claim of superiority can come from an attack/block-only matrix.',
    {title:'Which road matters?',question:'When the combat is identical, which future hands do you want?',axis:'Destination identity, with count and stats fixed',scope:'Existing vocabulary',variants:[
      row('Forward Horizon',3,2,blank,'Reach pressure and grappling.',{routes:['Red','Orange']}),
      row('Quiet Horizon',3,2,blank,'Reach defense and counterplay.',{routes:['Green','Blue']}),
      row('Patient Horizon',3,2,blank,'Reach counterplay and power.',{routes:['Blue','Purple']})
    ],crossing:'The printed exchange is identical. The card choice is a wager on which tools and token payoffs you will want next.'});

  // Refresh all references after revisions, including full mode/route/tag metadata.
  for(const f of families)for(let i=0;i<f.variants.length;i++) {
    const v=f.variants[i];if(!v.referenceOf)continue;
    const sourceFamily=families.find(sf=>sf.variants.some(s=>s.id===v.referenceOf));
    const source=sourceFamily?.variants.find(s=>s.id===v.referenceOf);
    if(!source||source.referenceOf)throw new Error(`Invalid refined reference ${v.referenceOf}`);
    f.variants[i]={...source,bonus:source.bonus??sourceFamily.bonus,reaction:source.reaction??sourceFamily.reaction,routes:source.routes??sourceFamily.routes,tags:source.tags??sourceFamily.tags??(source.attack?['Strike']:['Guard']),id:v.id,bet:v.bet,referenceOf:v.referenceOf};
  }
  for(const f of families)for(const v of f.variants) {
    v.tags??=f.tags??(v.attack?['Strike']:['Guard']);
    if(v.text===blank)v.text='';
    if(!v.text)v.model??={};
  }
  d.revision=2;
  d.entryCount=families.reduce((n,f)=>n+f.variants.length,0);
  d.conventions=[
    'Revision 2 retains all 40 questions but trims each family to the number of defensible comparisons it currently supports. Counts include labeled references; entries are not a count of distinct printable cards.',
    'All cards remain proposals. Existing vocabulary means familiar concepts, not compiler-ready text, proven balance, or cards legal in the live game. This gallery does not call any game API.',
    'Every family has an explicit self-critique, refinement, and next test. Original entries remain visible in its expandable first-draft comparison. Shared references copy their complete source definitions.',
    'Life caps at 40. Reveal healing happens before combat and is capped immediately. After-damage recovery requires survival and cannot prevent knockout. Both attacks and retaliation resolve simultaneously.',
    'One public pending-recovery queue per fighter: a delayed recovery replaces any not-yet-due queue. A due queue heals at the start of reveal, before newly revealed effects. It cannot revive a defeated fighter.',
    'For stamina proposals: start at 3, cap at 6, regenerate 1 at the start of later rounds. Required costs must be affordable at commitment and are paid on joint reveal. Generated stamina arrives after damage. Optional spends and modes are committed hidden and pay even on failure.',
    'Attack carry lasts one next round and takes the largest qualifying amount, not the sum. The hypothetical Heavy telegraph uses this same carry channel, applies only to a Heavy main, and expires unused otherwise.',
    'Tags are intrinsic metadata and do not change when a card changes hands. Strike marks attacks; Guard marks non-attacking techniques; Heavy identifies selected committed attacks. A tag alone grants no bonus.',
    'Exhaustion starts after use. The named card remains in its hand and may still be burned. Until refresh its main is 2/1 with no main ability; playing that mode does not restart the timer. Thus an exhausted hand retains a legal main.',
    'Statuses are public and activate next round. Bleed deals 1 status damage after each eligible exchange, for at most three rounds. Weakened reduces attack; Exposed reduces final block; both floor at zero. Marked adds attack to the next opposing Strike against its bearer within two rounds, then is consumed on reveal even if blocked.',
    'Reapplying a status replaces its whole magnitude/duration pair only if the new pair is at least as strong in both fields; otherwise the existing instance remains. Never combine the potency of one application with the duration of another. Incomparable reapplications leave the existing condition untouched.',
    'Exchange damage is combat plus direct retaliation, excluding status ticks. Snapshot exchange-based triggers first; status ticks cannot satisfy objectives, trigger retaliation, or turn a blocked exchange into a hit. Resolve both eligible ticks together, determine defeat before recovery, and do not tick after an already-terminal exchange.',
    'Reveal cleanses resolve before status modifiers or Marked consumption. They remove one existing specified/chosen condition, do not undo past ticks, and do not prevent a fresh application later that exchange. Current live carry, burn lock, and Haymaker recovery are not automatically conditions.',
    'Printed empty destinations remain legal and dangerous; entering an empty hand loses. Route cuts never remove the last option. Optional added routes specifying availability must contain a card. Relocation does not change printed routes, tags, or stats.',
    'The explorer is a one-exchange model with independent inputs for printed attack/block, assumed here to equal final opposing stats. It omits burn effects, direct retaliation, queues, statuses, future rewards, token value, and routes. A local score or benchmark witness does not prove full-game balance.',
    'The original forced six-entry pattern and dominance controls are retired. No card is retained merely to reach a target count. Unknown full-game power and defensive loops remain open research questions.',
    'Shortlists remain browser-local family IDs. Family IDs and permalinks are stable; obsolete individual variant IDs must not be treated as legal card identities.'
  ];
})();
