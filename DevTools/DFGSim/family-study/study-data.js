'use strict';
// Isolated design data. Never imported by DFGSim or the card compiler.
window.DFGFamilyStudy = (() => {
  const colors = ['Red', 'Orange', 'Yellow', 'Green', 'Blue', 'Purple'];
  const palette = {Red:'#9c433c',Orange:'#a46225',Yellow:'#8b751b',Green:'#416951',Blue:'#39657d',Purple:'#765a83'};
  const families = [];
  const row = (name, attack, block, text, bet, extra = {}) => ({name, attack, block, text, bet, ...extra});
  const names = text => text.split('|');
  function family(category, color, title, question, axis, scope, variants, notes = {}) {
    const n = families.length + 1;
    const i = colors.indexOf(color);
    const sharedRoutes = [colors[(i + 1) % 6], colors[(i + 3) % 6]];
    const f = {id:`F${String(n).padStart(2,'0')}`, number:n, category, color, title, question, axis, scope,
      bonus:'Gain +1 attack this round.', reaction:'Gain +1 block this round.', routes:sharedRoutes,
      crossing:'Compare the variant against both the condition it targets and the condition it gives up. A larger payoff is only useful when its requirement can be met.',
      caution:'These are unbalanced candidates. Include burns, routes, objectives, and repeated exchanges before inferring strategic superiority.', ...notes};
    f.variants = variants.map((v,j) => ({id:`${f.id}-${j+1}`, cost:0, ...v}));
    families.push(f);
  }
  const existing = 'Existing vocabulary', proposed = 'New system';
  const R = 'Recovery', O = 'Offense', Q = 'Reads', E = 'Resources', T = 'Follow-through', S = 'Statuses', C = 'Responses', M = 'Movement';

  family(R,'Green','A little room to breathe','How much guard would you give up for a better recovery against a quiet opponent?','Healing ↔ block; then healing ↔ chip attack',proposed,[
    row('Open Breath',0,0,'Recover 4 life on reveal.','Largest recovery into a passive exchange.',{model:{heal:4}}),
    row('Measured Breath',0,2,'Recover 3 life on reveal.','The middle ground wins its own band of incoming attacks.',{model:{heal:3}}),
    row('Sheltered Breath',0,4,'Recover 2 life on reveal.','Less upside; more protection against heavy attacks.',{model:{heal:2}}),
    row('Parting Breath',1,0,'Recover 3 life on reveal.','Trade 1 recovery for a chip threat into an open guard.',{model:{heal:3}}),
    row('Steady Return',1,2,'Recover 2 life on reveal.','A small attack while preserving moderate safety.',{model:{heal:2}}),
    row('Covered Return',1,4,'Recover 1 life on reveal.','Protect recovery and threaten a low-block setup.',{model:{heal:1}})
  ],{lab:true,crossing:'At opposing attack 0/2/4, the first three variants respectively lead on your life change when healing is not capped. The attacking variants trade recovery for outgoing pressure.',caution:'Reveal healing is a proposed timing, unlike current Driving Palm recovery. At full life it is wasted; it can prevent a knockout that post-damage healing cannot.'});

  family(R,'Green','Earn your recovery','Would you rather hit reliably or recover more when the hit lands?','Attack ↔ on-hit healing',existing,
    names('Small Respite|Returning Palm|Restoring Touch|Renewing Strike|Deep Recovery|Second Wind').map((n,i)=>row(n,6-i,1,`If you deal damage, recover ${i+1} life after damage. This cannot prevent a knockout.`,`Attack ${6-i} makes the hit easier; recovery ${i+1} makes success more valuable.`,{model:{heal:i+1,healOnHit:true,healTiming:'after'}})),
    {lab:true,crossing:'Increasing opposing block can shut off the larger recoveries first. Lower opposing block lets the riskier versions earn more life.',caution:'Post-damage recovery never revives a knocked-out fighter. Healing caps can reverse apparent efficiency.'});

  family(R,'Blue','Recover behind a read','How much protection should accompany a reward for taking no damage?','Block ↔ conditional recovery',proposed,
    names('Still Water|Quiet Center|Patient Breath|Guarded Rest|Safe Harbor|Deep Shelter').map((n,i)=>row(n,0,i+1,`If you take no exchange damage, recover ${6-i} life after damage.`,`Block ${i+1} protects a recovery payoff of ${6-i}.`,{model:{heal:6-i,cleanHeal:true,healTiming:'after'}})),
    {lab:true,crossing:'Each extra block opens another attack threshold, while the size of a successful recovery shrinks.',caution:'Exchange damage means combat plus direct retaliation, excluding status ticks. The explorer has no retaliation or status damage.'});

  family(R,'Green','Now or next round','What is the right price for giving the opponent time to finish you?','Immediate recovery ↔ delayed recovery',proposed,[
    row('Measured Breath',0,2,'Recover 3 life on reveal.','Shared baseline from F01-2: recover now instead of waiting.',{referenceOf:'F01-2'}),
    row('Long Breath',0,2,'Recover 4 life on reveal next round.','Better amount if you survive and reach the next exchange.'),
    row('Deep Breath',0,2,'Recover 6 life on reveal two rounds from now.','Largest delayed recovery; most time exposed.'),
    row('Steady Return',1,2,'Recover 2 life on reveal.','Shared baseline from F01-5: immediate recovery with a chip threat.',{referenceOf:'F01-5'}),
    row('Passing Renewal',1,2,'Recover 4 life on reveal next round.','The same combat profile as Steady Return, with more recovery later.'),
    row('Passing Revival',1,2,'Recover 6 life on reveal two rounds from now.','The same combat profile; the largest recovery has the longest delay.')
  ],{crossing:'Compare 0/2 with recovery 3 now, 4 next round, or 6 two rounds later. The 1/2 branch compares recovery 2 now, 4 next round, or 6 two rounds later. Immediate versions are shared reference cards, not new weaker copies.',caution:'Quick Breath was removed because Measured Breath strictly dominated it with identical burns and routes. Passing Relief was also dominated by Steady Return. Queued recovery is public, follows hand changes, cannot revive a defeated fighter, and same-source queues refresh rather than accumulate.'});

  family(R,'Green','Recover or remove','How much raw recovery is a useful cleanse worth?','Healing ↔ cleanse breadth',proposed,[
    row('Mend',0,3,'Recover 3 life on reveal.','Best recovery if there is no condition to remove.'),
    row('Bind the Cut',0,3,'Recover 2 life and remove Bleed on reveal.','Efficient answer to one known source of attrition.'),
    row('Set the Guard',0,3,'Recover 2 life and remove Exposed on reveal.','Specialize against defensive vulnerability.'),
    row('Regain Strength',0,3,'Recover 2 life and remove Weakened on reveal.','Recover your offensive options.'),
    row('Clear the Mark',0,3,'Recover 2 life and remove Marked on reveal.','Undo an incoming setup.'),
    row('Full Reset',0,3,'Recover 1 life and remove one condition of your choice on reveal.','Give up recovery for broader coverage.')
  ],{crossing:'Narrow versions preserve an extra point of recovery; the broad version has fewer dead matchups.',caution:'Pick an existing condition with the hidden plan; cleanse does not grant immunity to applications later this round. All candidates still function with no condition present.'});

  family(O,'Red','The basic exchange','Where does one more point of attack stop being worth one point of guard?','Attack ↔ block',existing,
    names('All In|Forward Step|Firm Advance|Balanced Strike|Guarded Touch|Defensive Touch').map((n,i)=>row(n,7-i,i,'','A direct allocation of the same seven printed stat points.',{model:{}})),
    {lab:true,crossing:'Block only saves life up to incoming damage; attack only matters above opposing block. Allocation value depends on both sides of the matchup.',caution:'Equal printed totals are not equal strength. Printed-stat counters can favor or punish particular allocations.'});

  family(O,'Orange','Through the guard','When is reliable penetration better than a larger raw hit?','Raw attack ↔ penetration',existing,
    names('Full Force|Narrow Force|Split Guard|Thread the Guard|Fine Opening|Needle Point').map((n,i)=>row(n,7-i,1,i?`For your damage calculation, ignore ${i} opposing block this round.`:'No additional main effect.',`Trade ${i} raw attack for ${i} penetration.`,{model:{pierce:i}})),
    {lab:true,crossing:'Raw attack leads into low block. At sufficiently high block these versions can tie on direct damage; printed reads and source conditions then matter.',caution:'This is an intentional control family: equal attack-for-pierce trades do not guarantee six distinct winners. Some variants may be redundant. Use the ties to identify where another cost or benefit is necessary.'});

  family(O,'Purple','Which defense are you breaking?','Can a narrower bypass justify more attack?','Broad penetration ↔ source-specific penetration',existing,[
    row('Broad Fracture',3,2,'Ignore 3 opposing block for your damage calculation.','Low base attack; works against any block source.'),
    row('Hard Fracture',4,1,'Ignore 2 opposing block for your damage calculation.','More raw threat, less guard.'),
    row('Break the Setup',5,2,'Ignore block granted by opposing bonus burns.','Punish committed defense; ordinary guard remains.'),
    row('Beat the Reaction',5,2,'Ignore block granted by opposing reaction burns.','Punish late defense; bonus guard remains.'),
    row('Clean Break',6,1,'If you committed no bonus, ignore block granted by opposing reaction burns.','Higher threat with a conservation requirement.'),
    row('Patient Fracture',4,3,'Ignore block granted by opposing bonus burns.','Defensive version of the same specialized read.')
  ],{crossing:'Change the source of block, not just its total. An identical displayed block value can produce a different winner.',caution:'An ordinary attack/block matrix cannot measure these bypasses without recording where each block modifier came from.'});

  family(O,'Red','Power with a gift','How much help can you afford to give the opponent in exchange for your own burst?','Bonus strength ↔ opposing benefit',existing,
    names('Sharp Commitment|Bold Commitment|Reckless Commitment|Braced Commitment|Heavy Commitment|Wild Commitment').map((n,i)=>row(n,3,2,'No additional main effect.',i<3?'Attack gift can turn a safe exchange into a damaging trade.':'A block gift can cancel part of your own damage increase.',{bonus:`Gain +${i+2} attack this round. Your opponent gains +${i<3?i+1:i-2} ${i<3?'attack':'block'} this round.`})),
    {axis:'Bonus burn payoff ↔ opponent gift',crossing:'Compare offensive gifts when you expect to block everything with defensive gifts when you already have surplus penetration.',caution:'The bonus is an alternative use of this card, not a free effect of playing it as main. Evaluate both modes and the permanent loss.'});

  family(O,'Purple','The cost of missing','How much more force is a longer punish window worth?','Attack ↔ next-round recovery penalty',proposed,
    names('Heavy Swing|Loaded Swing|Wide Swing|Crushing Swing|Overextended Swing|Desperate Swing').map((n,i)=>row(n,5+i,0,i?`If you deal no combat damage, you have -${i} block next round, to a minimum of 0.`:'No additional main effect.',`Attack ${5+i}; a miss exposes ${i} block next round.`)),
    {crossing:'The largest swing is attractive against known weak defense. Its risk increases when the opponent can react high enough to force a miss.',caution:'A new generic penalty vocabulary is needed beyond Haymaker’s fixed -2. Penalizing block already at zero can make the supposed cost disappear.'});

  family(Q,'Blue','Read the commitment','How narrow can a high-attack read become before its larger payoff stops helping?','Trigger threshold ↔ defensive reward',existing,
    names('Watch the Hands|Read the Step|Read the Strike|Read the Charge|Read the Lunge|Read the All-In').map((n,i)=>row(n,2,2,`If the opposing main has printed attack ${i+3} or more, gain +${i+1} block this round.`,`Threshold ${i+3} rewards ${i+1} block.`,{model:{attackThreshold:i+3,readBlock:i+1}})),
    {lab:true,crossing:'A broader read turns on sooner, while narrower reads can survive attacks the broad version cannot.',caution:'The lab treats opposing attack as printed, with no opposing modifiers. In the real game, printed attack and final attack are different inputs.'});

  family(Q,'Orange','Find a smaller opening','Would you trade frequency for a bigger punish against a particular guard?','Printed-block threshold ↔ attack reward',existing,
    names('Probe the Guard|Test the Guard|Open the Guard|Split the Guard|Crack the Guard|Shatter the Guard').map((n,i)=>row(n,2,2,`If the opposing main has printed block ${i+2} or more, gain +${i+1} attack this round.`,`Threshold ${i+2} rewards ${i+1} attack.`,{model:{blockThreshold:i+2,readAttack:i+1}})),
    {lab:true,crossing:'At each new threshold another version overtakes the earlier reward, but drops back to base attack below that threshold.',caution:'Reaction block cannot turn on a printed-block read. The lab assumes all opposing block is printed.'});

  family(Q,'Blue','A precise prediction','How much should a one-value read pay compared with a broad category read?','Exact printed-attack target',existing,
    names('Read Stillness|Read the Touch|Read the Probe|Read Balance|Read the Drive|Read Force').map((n,i)=>row(n,2,2,`If the opposing main has printed attack exactly ${i}, gain +2 attack and +2 block this round.`,`Specialize against printed attack ${i}.`,{model:{exactAttack:i,readAttack:2,readBlock:2}})),
    {lab:true,crossing:'Equal rewards isolate the value of predicting a particular statline. The active hand changes how likely each exact value is.',caution:'An exact target with little representation in the legal opposing hand can be functionally blank.'});

  family(Q,'Yellow','Punish the hidden bonus','Do you want a reliable fallback or a dramatic read reward?','Base block ↔ bonus-read block',existing,
    names('Loose Read|Light Read|Balanced Read|Firm Read|Safe Read|Closed Read').map((n,i)=>row(n,3,i,`If the opponent committed a bonus burn, gain +${6-i} block this round.`,`Without a bonus: ${i} block. With one: 6 block.`)),
    {crossing:'All versions reach 6 block on the read. Higher base block is strictly better in this isolated family.',caution:'Intentional dominance control: these cannot all be viable with identical other properties. Use this family to reject empty variation; attach an actual compensation before promoting the low-base versions.'});

  family(Q,'Red','Keep something in reserve','How much baseline strength should you surrender for a clean, unassisted move?','Printed attack ↔ no-bonus attack',existing,
    [[2,5],[3,4],[4,2],[5,1],[6,0],[3,3]].map(([a,g],i)=>row(names('Quiet Strike|Clean Strike|Sure Strike|Ready Strike|Plain Strike|Covered Strike')[i],a,i===5?3:2,g?`If you committed no bonus, gain +${g} attack this round.`:'No additional main effect.',i===5?'Give up some clean-hit output for extra printed block.':`Without a bonus: ${a+g} attack. With one: ${a}.`)),
    {crossing:'The low-base versions can lead when conserving; higher base versions retain strength when using a powerful bonus.',caution:'Own reaction burns do not disable a no-bonus effect. Compare printed-stat vulnerabilities separately.'});

  family(E,'Green','A larger reserve','Does superior conversion repay the effort of reaching a higher stamina threshold?','Required stamina ↔ recovery efficiency',proposed,
    [2,4,7,10,14,18].map((h,i)=>row(names('Catch Breath|Gather Breath|Restore Rhythm|Deep Renewal|Whole Recovery|Complete Renewal')[i],0,0,`Recover ${h} life on reveal.`,i?`Requires ${i} stamina held; ${h} recovery before the life cap.`:'Always available, but with the smallest recovery.',{cost:i,model:{heal:h}})),
    {lab:true,crossing:'Low reserves leave expensive versions unavailable. High reserves unlock better conversion, but the life cap wastes large recoveries.',caution:'The numbers are deliberately wide to expose thresholds, not balanced costs. A required-cost main is illegal when unaffordable; a complete moveset must retain zero-cost options.'});

  family(E,'Purple','Pay for the opening','Can a higher setup threshold buy more efficient raw power without becoming automatic?','Required stamina ↔ attack',proposed,
    [3,5,7,10,13,17].map((a,i)=>row(names('Unloaded Fist|Set Fist|Loaded Fist|Focused Fist|Charged Impact|Full Release')[i],a,0,'No additional main effect.',`Requires ${i} stamina for ${a} attack.`,{cost:i,model:{}})),
    {lab:true,crossing:'An affordable version wins by dealing enough damage now; the largest version may waste scarce stamina on overkill.',caution:'The explorer deliberately does not price stamina consumption, so its highest immediate result is not a recommendation.'});

  family(E,'Red','Cash out the advantage','Would you build toward a specialist currency or accept a weaker generic spend?','Token specificity ↔ payout',proposed,[
    row('Red Release',3,1,'Required cost: spend Red advantage. Gain +3 attack this round.','Efficient if the route reliably earns Red.',{costLabel:'1 RED'}),
    row('Orange Release',3,1,'Required cost: spend Orange advantage. Ignore 3 opposing block this round.','A specialist anti-guard cashout.',{costLabel:'1 ORANGE'}),
    row('Open Release',3,1,'Required cost: spend one advantage of any color. Gain +2 attack this round.','Less efficient, more often available.',{costLabel:'1 ANY'}),
    row('Paired Release',3,1,'Required cost: spend two different advantages. Gain +6 attack this round.','Higher setup for stronger conversion.',{costLabel:'2 ANY'}),
    row('Mixed Release',3,1,'Required cost: spend two different advantages. Gain +3 attack and +3 block this round.','Trade focused offense for a safer cashout.',{costLabel:'2 ANY'}),
    row('Precise Release',3,1,'Required cost: spend Red and Orange advantage. Gain +4 attack and ignore 3 block this round.','Most restrictive package; specialist payoff.',{costLabel:'RED + ORG'})
  ],{crossing:'Matchups and the earn/spend route determine whether flexibility or a larger specialist cashout is worth a slot.',caution:'These are mandatory-cost cards. Current advantage spends are optional, so this family requires new legality rules.'});

  family(E,'Green','Make space to recover','When can you afford to sacrifice a hit to recharge?','Attack ↔ stamina generation',proposed,
    names('Press On|Quick Reset|Small Reset|Measured Reset|Deep Reset|Full Reset').map((n,i)=>row(n,6-i,1,i?`Gain ${i} stamina after damage, up to the study cap of 6.`:'No additional main effect.',`Attack ${6-i} now; restore ${i} stamina for later.`)),
    {crossing:'At full stamina the recovery is wasted. At low stamina a weaker exchange can unlock an important next-round cashout.',caution:'Stamina arrives after this exchange and cannot pay current costs. Constant generation may create an obvious repeating spend/recover cycle.'});

  family(E,'Purple','Hold the threat','Can resource management matter even when you do not spend the resource?','Unspent stamina threshold ↔ attack reward',proposed,
    names('Ready Knuckle|Set Knuckle|Steady Knuckle|Loaded Knuckle|Patient Knuckle|Reserved Knuckle').map((n,i)=>row(n,3,1,`If you began this round with at least ${i+1} stamina, gain +${i+1} attack this round.`,`Hold ${i+1} stamina to unlock a +${i+1} payoff.`,{model:{reserveThreshold:i+1,reserveAttack:i+1}})),
    {lab:true,crossing:'A narrow threshold buys a bigger free reward but turns off below its reserve requirement.',caution:'At full stamina the highest threshold dominates this exchange. Check whether spending elsewhere ever creates a credible reason to play lower-threshold versions.'});

  family(T,'Red','Leave a threat behind','How much damage now is a point of next-round pressure worth?','Current attack ↔ on-hit attack carry',existing,
    names('Present Force|Following Force|Continuing Force|Rising Force|Gathering Force|Future Force').map((n,i)=>row(n,6-i,1,i?`If you deal damage, gain +${i} attack next round.`:'No additional main effect.',`Attack ${6-i} must connect to earn ${i} future attack.`)),
    {crossing:'Stronger future pressure is hardest to earn. A high-block main can completely shut down the most ambitious setup.',caution:'Carry uses the largest qualifying source, not a sum. Repeated setup, Brace, and next-round routes determine its real value.'});

  family(T,'Green','A guarded preparation','How much safety should accompany an ambitious next-round reward?','Current block ↔ clean-defense attack carry',existing,
    names('Open Preparation|Light Preparation|Balanced Preparation|Firm Preparation|Safe Preparation|Closed Preparation').map((n,i)=>row(n,1,i+1,6-i>0?`If you take no damage, gain +${6-i} attack next round.`:'No additional main effect.',`Block ${i+1} must preserve a ${6-i}-attack setup.`)),
    {crossing:'Small attacks allow exposed versions to set up more pressure. Large attacks make reliable defense more valuable.',caution:'The +6 end of the family is an extreme candidate. Next-round amplification must be measured over complete games.'});

  family(T,'Purple','Show the next punch','Can a visible threat buy you space even when you do not follow through?','Immediate attack ↔ telegraphed Heavy reward',proposed,
    names('Short Windup|Set Windup|Wide Windup|Long Windup|Deep Windup|Full Windup').map((n,i)=>row(n,6-i,0,`Your next-round main gains +${i+1} attack if it has the Heavy tag.`,`Attack ${6-i} now, visible +${i+1} Heavy threat next round.`)),
    {crossing:'The opponent can prepare for Heavy, inviting you to take another route. Bigger setups concede more present pressure.',caution:'Heavy is new metadata. The reward expires after next round and is lost if you choose another tag; it does not stack with another copy of itself.'});

  family(T,'Purple','A move you cannot repeat','How much extra strength should temporary loss of reuse buy?','Attack ↔ exhaustion duration',proposed,
    names('Repeatable Blow|Breathless Blow|Taxing Blow|Draining Blow|Exhausting Blow|Spent Blow').map((n,i)=>row(n,4+i,1,i?`After this exchange, this card cannot be your main for the next ${i} round${i===1?'':'s'}.`:'No additional main effect.',i?`Trade ${i} rounds of main reuse for ${i} attack.`:'A weaker main that remains reusable.')),
    {crossing:'A planned route away reduces the cost of a short cooldown. Returning early exposes its cost.',caution:'The exhausted card remains in its hand, may be burned, and does not count as an empty hand. These rules need a fallback action if all active-hand mains are exhausted.'});

  family(T,'Blue','The objective ladder','How much immediate strength should a more accessible objective give up?','Printed attack ↔ damage threshold for advantage',proposed,
    names('Ambitious Read|Demanding Read|Measured Read|Practical Read|Small Success|Any Opening').map((n,i)=>row(n,6-i,2,`Objective: Deal ${6-i}+ damage this round: gain Green advantage.`,`Attack ${6-i}; reward threshold ${6-i}.`)),
    {crossing:'With only base attack and block, each version needs an open guard to meet its own threshold. Bonus support and printed-stat reads change their relative prospects.',caution:'Current objective parsing supports a small fixed vocabulary; these arbitrary current-round thresholds need explicit support. Lower thresholds can become attractive with a reusable attack bonus.'});

  family(S,'Orange','A cut that keeps costing','How much immediate damage can a longer injury replace?','Attack ↔ Bleed duration',proposed,
    names('Clean Cut|Shallow Cut|Lingering Cut|Deep Cut|Open Cut|Lasting Cut').map((n,i)=>row(n,6-i,1,i?`If you deal exchange damage, apply Bleed for ${i} rounds.`:'No additional main effect.',`Attack ${6-i}; up to ${i} delayed damage if it lands.`)),
    {crossing:'Racing and cleansing favor immediate force. A long surviving match without removal increases the value of duration.',caution:'A 1-attack applicator may fail to apply at all. Bleed ticks for 1 per eligible round, starting next round; refreshing does not add potency.'});

  family(S,'Orange','Open the guard tomorrow','What is a delayed defensive vulnerability worth compared with hitting harder now?','Current attack ↔ Exposed magnitude',proposed,
    names('Solid Sweep|Tilting Sweep|Staggering Sweep|Opening Sweep|Unbalancing Sweep|Breaking Sweep').map((n,i)=>row(n,6-i,1,i?`If you deal exchange damage, apply Exposed ${i} for next round.`:'No additional main effect.',`Attack ${6-i} now; -${i} opposing block next round on hit.`)),
    {crossing:'The delayed version needs a hit and a follow-up that exploits block loss. An opponent with low block may barely care.',caution:'Exposed floors final block at zero. Do not combine it with recovery penalties without checking total loss of agency.'});

  family(S,'Blue','Mark the opening','Would a bigger future payoff justify a weaker initial hit?','Current attack ↔ Marked payoff',proposed,
    names('Direct Touch|Pointed Touch|Guiding Touch|Measured Touch|Marking Touch|Perfect Mark').map((n,i)=>row(n,6-i,2,i?`If you deal exchange damage, apply Marked ${i} for two rounds.`:'No additional main effect.',`Attack ${6-i}; the next qualifying Strike gets +${i} attack.`)),
    {crossing:'Mark is valuable only if a suitable Strike can be reached and used before expiry. It advertises that route.',caution:'Marked is consumed on a qualifying revealed Strike, whether it eventually hits or not. Multiple applications refresh rather than add magnitude.'});

  family(S,'Purple','Make the injury count','How much should an application threshold improve a delayed payoff?','Damage threshold ↔ Bleed duration',proposed,
    names('Small Wound|Firm Wound|Heavy Wound|Deep Wound|Grievous Wound|Decisive Wound').map((n,i)=>row(n,5,0,`If you deal ${i+1}+ exchange damage, apply Bleed for ${i+1} rounds.`,`Threshold ${i+1} for ${i+1} potential future ticks.`)),
    {crossing:'A low threshold applies more often; a high threshold needs extra resources or an exposed opponent.',caution:'The 6-damage threshold cannot be reached by the unmodified main. Explicitly account for enabling burns, tokens, and carry.'});

  family(S,'Blue','Defend while it works','Would you rather apply a longer condition or remain safe while applying it?','Block ↔ Weakened duration',proposed,
    names('Exposed Touch|Light Touch|Balanced Touch|Sheltered Touch|Guarded Touch|Closed Touch').map((n,i)=>row(n,3,i,i<5?`If you deal exchange damage, apply Weakened 1 for ${5-i} rounds.`:'No additional main effect.',`Block ${i}; ${5-i} rounds of reduced opposing attack on hit.`)),
    {crossing:'Long duration matters against follow-up aggression. Extra block matters immediately and can preserve your ability to survive the setup.',caution:'Weakened means -1 attack, floored at zero. The effect does not stop direct damage or non-attack utility.'});

  family(C,'Green','A place for the antidote','What is the cost of making an answer broadly useful?','Block ↔ cleanse coverage',proposed,[
    row('Plain Shelter',0,6,'No additional main effect.','Strongest immediate guard; no condition answer.'),
    row('Bloodless Shelter',0,5,'Remove Bleed on reveal.','Protect against immediate attacks and delayed attrition.'),
    row('Steady Shelter',0,5,'Remove Weakened on reveal.','Restore offense while defending.'),
    row('Whole Shelter',0,5,'Remove Exposed on reveal.','Recover lost guard before the exchange.'),
    row('Unmarked Shelter',0,5,'Remove Marked on reveal.','Clear the advertised follow-up.'),
    row('Universal Shelter',0,4,'Remove one condition of your choice on reveal.','Lower guard buys a flexible answer.')
  ],{crossing:'Narrow answers retain more block. The broad answer improves coverage across uncertain opposing movesets.',caution:'Do not make a broad cleanse mandatory in every hand. Short natural expiry and useful non-cleanse modes preserve alternatives.'});

  family(C,'Blue','Six ways to react','How much of your permanent reaction burn should go to each side of a trade?','Reaction attack ↔ reaction block',existing,
    names('Shielding Check|Braced Check|Guarded Check|Balanced Check|Driving Check|Striking Check').map((n,i)=>row(n,3,2,'No additional main effect.',`As a reaction: +${i} attack and +${5-i} block.`,{reaction:`Gain +${i} attack and +${5-i} block this round.`})),
    {crossing:'Surplus block is wasted when you already take zero; surplus attack is wasted if it still cannot connect or the opponent is already defeated.',caution:'These are burn-mode variations. Playing the main gives identical combat numbers; the sacrifice value differs.'});

  family(C,'Red','Main or fuel','Which version would you keep, and which would you burn?','Main attack ↔ bonus attack',existing,
    names('Kept Strike|Ready Fuel|Split Purpose|Useful Fuel|Rich Fuel|Pure Fuel').map((n,i)=>row(n,7-i,1,'No additional main effect.',`Reusable attack ${7-i}; permanent bonus +${i+1} attack.`,{bonus:`Gain +${i+1} attack this round.`})),
    {crossing:'The strong main loses more option value when burned. A dedicated fuel card offers a weaker reusable fallback.',caution:'A moveset packed with excellent fuel can become weak after it burns its best sources. Compare full-hand inventories, not just one bonus.'});

  family(C,'Blue','Deny the reward','What is a wider resource-denial trigger worth in printed stats?','Printed attack ↔ denial threshold',proposed,
    names('Demanding Denial|Forceful Denial|Firm Denial|Measured Denial|Narrow Denial|Quiet Denial').map((n,i)=>row(n,6-i,2,`If you deal ${6-i}+ exchange damage, the opponent cannot earn advantage this round.`,`Attack ${6-i}; denial needs ${6-i} damage.`)),
    {crossing:'Carried attack and bonus support can make lower-threshold denial easier to activate even with a weak printed main.',caution:'Denial must be determined for both players before awarding objectives. It cannot remove a token already held.'});

  family(C,'Yellow','Keep an escape route','What defensive opportunity cost should buy insurance against control?','Attack ↔ escape coverage',proposed,[
    row('Plain Exit',5,2,'No additional main effect.','Best immediate attack; no special escape.'),
    row('Uncut Exit',4,2,'Ignore one opposing chosen route cut this round.','Insurance against Driving Tackle-like control.'),
    row('Undirected Exit',4,2,'Your next hand cannot be chosen by the opponent this round.','Insurance against forced destinations.'),
    row('Unmoved Exit',4,2,'Your main cannot be relocated this round.','Keep your important tool in its intended hand.'),
    row('Free Exit',3,2,'Ignore chosen route cuts and opposing forced destinations this round.','Broader routing protection at lower force.'),
    row('Secure Exit',2,2,'Ignore chosen route cuts, opposing forced destinations, and relocation this round.','Maximum coverage; lowest immediate attack.')
  ],{crossing:'A narrow answer is more efficient when you know the specific threat. Broad insurance buys coverage against mixed control.',caution:'Immunity is a new rule and may erase specialist spend payoffs. It does not create routes or make an empty destination safe.'});

  family(M,'Yellow','The value of an exit','How many printed stat points is a useful route worth?','Attack ↔ destination breadth',existing,
    names('Red Door|Blue Door|Forked Door|Quiet Door|Open Door|Covered Door').map((n,i)=>row(n,[6,6,5,5,4,4][i],2,'No additional main effect.',i<2?'One precise route, no destination choice.':'More choices at the cost of immediate force.',{routes:[['Red'],['Blue'],['Red','Orange'],['Green','Blue'],['Red','Orange','Purple'],['Green','Blue','Purple']][i]})),
    {crossing:'Different destinations can matter more than their count. A single route into a powerful or depleted hand can be a gift or a liability.',caution:'One destination is supported by the local catalog validator but is outside the current two-/three-route set convention. Entering an empty printed destination loses.'});

  family(M,'Green','Permission to stay','What condition would you build around to remain in the same hand?','Condition for extra current-hand destination',proposed,[
    row('Patient Anchor',3,3,'If you committed no bonus, add Green to your legal next hands.','Stay through conservation.'),
    row('Guarded Anchor',3,3,'If you take no exchange damage, add Green to your legal next hands.','Stay through clean defense.'),
    row('Driving Anchor',3,3,'If you deal 3+ exchange damage, add Green to your legal next hands.','Stay through a substantial hit.'),
    row('Glancing Anchor',3,3,'If you take exactly 1 exchange damage, add Green to your legal next hands.','Stay by shaping a narrow trade.'),
    row('Reading Anchor',3,3,'If the opponent committed a bonus, add Green to your legal next hands.','Stay by predicting a resource commitment.'),
    row('Paid Anchor',3,3,'Optional spend: Green advantage. Add Green to your legal next hands.','A reliable stay at a resource cost.',{costLabel:'OPT GREEN'})
  ],{crossing:'Equal combat stats isolate how accessible the stay condition is in a particular moveset and matchup.',caution:'Conditional route additions beyond the existing fixed vocabulary need new support. Repeated stays must not create an unanswerable defensive loop.'});

  family(M,'Orange','Cut the next step','How much offense should a more attainable route cut surrender?','Attack ↔ damage threshold for route cut',proposed,
    names('Crushing Cutoff|Heavy Cutoff|Driving Cutoff|Measured Cutoff|Small Cutoff|Touch Cutoff').map((n,i)=>row(n,6-i,1,`If you deal ${6-i}+ exchange damage, prohibit one opposing legal next color if another remains.`,`Attack ${6-i}; a cut requires ${6-i} damage.`)),
    {crossing:'Burn support can make the small printed main a cheaper path to control; raw attack supports the higher threshold without spending.',caution:'Cuts resolve after route additions and relocation, before hidden hand choices. Never remove the final legal route.'});

  family(M,'Yellow','Move their tools','How much raw strength buys a more broadly applicable relocation read?','Attack ↔ opposing printed-attack trigger',proposed,
    names('Slip the Charge|Slip the Drive|Slip the Strike|Slip the Probe|Slip the Touch|Slip the Guard').map((n,i)=>row(n,6-i,2,`If the opposing main has printed attack ${7-i} or more, you may relocate that surviving main to another hand after damage.`,`Attack ${6-i}; relocation reads opposing attack ${7-i}+. `)),
    {crossing:'A narrow high-attack condition preserves immediate force; a broader condition disrupts more of the opposing inventory.',caution:'The condition does not require a hit. The trigger differs from Bait and Slip; indiscriminate relocation may be much stronger than the current specialist.'});

  family(M,'Yellow','Every road has a price','When is destination freedom worth exposing yourself this round?','Block ↔ number of printed routes',proposed,
    names('Open Horizon|Wide Horizon|Clear Horizon|Split Horizon|Narrow Horizon|Single Horizon').map((n,i)=>row(n,2,i,'No additional main effect.',`Block ${i}; ${6-i} printed destination${6-i===1?'':'s'}.`,{routes:colors.slice(0,6-i)})),
    {crossing:'The widest version reaches any hand but has no guard. Narrower routes preserve more immediate defense.',caution:'Four-to-six routes exceed the current catalog validator maximum of three. These are explicitly new-system drafts; printed empty destinations remain dangerous.'});

  // Reuse the complete baseline definition so references cannot drift into weaker copies.
  for (const f of families) for (let i=0;i<f.variants.length;i++) {
    const v=f.variants[i]; if(!v.referenceOf)continue;
    const sourceFamily=families.find(other=>other.variants.some(card=>card.id===v.referenceOf));
    const source=sourceFamily?.variants.find(card=>card.id===v.referenceOf);
    if(!source||source.referenceOf)throw new Error(`Invalid baseline reference: ${v.referenceOf}`);
    f.variants[i]={...source,bonus:source.bonus??sourceFamily.bonus,reaction:source.reaction??sourceFamily.reaction,routes:source.routes??sourceFamily.routes,id:v.id,bet:v.bet,referenceOf:v.referenceOf};
  }
  const conventions = [
    'All 240 study entries are hypothetical; shared baseline cards may appear in more than one family and are labeled Reference. Existing vocabulary means familiar effect concepts, not compiler-ready card text or a proven balance level.',
    'Main stats, rules, bonus, reaction, and destinations are printed on every draft. Within a family, unchanged fields are intentionally fixed. Card IDs use a study-only Fxx-x namespace.',
    'One main and at most one bonus are committed; a card cannot be both. Bonuses and reactions permanently burn their source. The payoff explorer uses neither.',
    'Life is capped at 40. Reveal recovery occurs before combat and is capped immediately. Post-damage recovery requires survival and cannot avert knockout. Reveal recovery is a new timing proposal.',
    'For resource drafts only: hypothetical stamina starts at 3, caps at 6, and regenerates 1 at the start of later rounds. Required costs are paid on reveal. Generated stamina arrives after damage. The explorer lets you set opening stamina directly.',
    'Statuses are public, follow hand changes, and activate next round. Bleed deals 1 status damage per eligible completed exchange; Weakened reduces attack; Exposed reduces block; Marked boosts and is consumed by the next revealed Strike. Floored stats never fall below zero.',
    'Same-name status reapplications use the larger remaining duration and larger magnitude, without adding them. Scheduled ticks never occur in an already-finished match. Exchange damage means combat plus retaliation, excluding status damage.',
    'Cleanse removes an existing chosen condition on reveal, without preventing a new application later that exchange. Existing burn locks, carry, and Haymaker recovery are not automatically cleanseable.',
    'Attack carry lasts one round and takes the largest qualifying amount rather than summing. New exhaustion, queued recovery, tags, immunity, and route triggers need their own runtime implementation.',
    'A shared bonus, reaction, and route set controls some variables, but cannot establish full-game balance. Some families are intentional negative controls: ties or dominance are findings, not errors to hide.',
    'Shortlisting is stored only in this browser when storage is available. The gallery makes no API calls and never writes to the playable game or hosted card editor.'
  ];
  return {colors,palette,families,conventions};
})();
