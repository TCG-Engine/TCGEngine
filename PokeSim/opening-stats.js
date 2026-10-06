(function(root){
    function summarize(games){
        const groups=new Map();
        for(const game of games)for(const row of game.openingStats||[]){
            const key=row.deck+':'+row.order;
            if(!groups.has(key))groups.set(key,{deck:row.deck,order:row.order,goal:row.goal,games:0,reached:0,earlyEnded:0,incomplete:0,declared:0,resolved:0,goalAttacks:0,enabled:0,blockers:{},conversion:{enabled:{games:0,wins:0},missed:{games:0,wins:0}}});
            const g=groups.get(key);
            if(game.status!=='complete'||row.status==='incomplete'){g.incomplete++;continue;}
            g.games++;
            if(row.status==='game_ended_before_opportunity'){g.earlyEnded++;continue;}
            if(row.status!=='complete')continue;
            g.reached++;g.declared+=Number(row.attackDeclared);g.resolved+=Number(row.attackResolved);
            g.goalAttacks+=Number(row.goalAttack);g.enabled+=Number(row.fullyEnabledAttack);
            for(const b of row.blockers)g.blockers[b]=(g.blockers[b]||0)+1;
            const c=g.conversion[row.fullyEnabledAttack?'enabled':'missed'];c.games++;c.wins+=Number(game.winner===row.player);
        }
        return [...groups.values()];
    }
    const labels={attacker_unavailable:'Attacker unavailable',energy_shortfall:'Energy shortfall',access_to_active:'Powered attacker on Bench',goal_prerequisite_unmet:'Goal prerequisite unmet',special_condition:'Special condition',legal_attack_unused:'Legal attack unused',legal_goal_unused:'Legal goal unused',fallback_attack:'Fallback attack',attack_failed_to_resolve:'Attack failed to resolve'};
    function deckName(key){return key==='sinistcha'?'Dhelmise / Sinistcha':key==='dhelmise-v2'?'dhelmise v2':key==='brisbane-lopunny'?'Brisbane Lopunny':key==='relicanth-v2-draw'?'relicanth v2 - draw':key==='relicanth-v3-meta-tune'?'Relicanth-v3-meta-tune':key==='relicanth-v4-colress'?'Relicanth v4 - Colress':key==='relicanth-v5-bastiodon'?'Relicanth v5 - Bastiodon':key==='relicanth-v6-explorers-guidance'?"Relicanth v6 - Explorer's Guidance":key==='relicanth-v7-lanas-aid'?"Relicanth v7 - Lana's Aid":key;}
    function rate(n,d){return d?(100*n/d).toFixed(1)+'% ('+n+'/'+d+')':'—';}
    function el(tag,value){const node=document.createElement(tag);if(value!==undefined)node.textContent=value;return node;}
    function render(container,games){
        container.replaceChildren(el('h2','First eligible attack'));
        container.append(el('p','Going second: own turn 1. Going first: own turn 2. Goal readiness describes the deck’s plan; it is separate from attack legality.'));
        const groups=summarize(games);
        if(!groups.length){container.append(el('p','Run a new batch to collect opening statistics. Older results have no opening tracking.'));return;}
        for(const g of groups){
            const section=el('details');section.open=true;
            section.append(el('summary',g.deck+' · going '+g.order+' · '+g.goal));
            const scroll=el('div');scroll.className='results-scroll';const table=el('table'),body=el('tbody');
            const values=[['Completed tracked games',g.games],['Reached opportunity',g.reached],['Ended before opportunity',g.earlyEnded],['Incomplete games excluded',g.incomplete],
                ['Any attack declared / reached',rate(g.declared,g.reached)],['Attack resolved / reached',rate(g.resolved,g.reached)],
                ['Goal attack / reached',rate(g.goalAttacks,g.reached)],['Fully enabled goal / reached',rate(g.enabled,g.reached)],['Fully enabled goal / all completed',rate(g.enabled,g.games)],
                ['Win rate with enabled opening',rate(g.conversion.enabled.wins,g.conversion.enabled.games)],['Win rate after missed opening',rate(g.conversion.missed.wins,g.conversion.missed.games)]];
            for(const [label,value] of values){const tr=el('tr');tr.append(el('th',label),el('td',value));body.append(tr);}
            table.append(body);scroll.append(table);section.append(scroll);
            section.append(el('p','Observed blockers among '+(g.reached-g.enabled)+' missed goals; multiple blockers can apply.'));
            const list=el('ul');for(const [key,count] of Object.entries(g.blockers))list.append(el('li',(labels[key]||key)+': '+count));
            section.append(list);container.append(section);
        }
        container.append(el('p','Compare paired seeds on fresh validation batches and across opponents. These rates describe this bot’s play; opening conversion is an association.'));
    }
    function match(container,game){
        container.replaceChildren();
        for(const row of game?.openingStats||[]){
            const details=el('details');details.append(el('summary','Seat '+row.player+' · '+row.goal+' · '+row.status+(row.fullyEnabledAttack?' · enabled goal':' · '+(row.blockers.map(b=>labels[b]||b).join(', ')||'goal missed'))));
            details.append(el('p','Eligible own turn: '+row.eligibleTurn+' · Attack declared: '+(row.attackDeclared?'yes':'no')+' · Resolved: '+(row.attackResolved?'yes':'no')+' · Damage: '+row.damage+' · Prizes taken: '+row.prizesTaken));
            const milestones=el('ul');
            for(const [name,point] of Object.entries(row.milestones))milestones.append(el('li',name.replace(/([A-Z])/g,' $1')+' · turn '+point.turn+', action '+point.step));
            details.append(milestones);
            const trace=el('ol');
            for(const step of row.trace){
                const parts=['Turn '+step.turn+' · '+step.type];
                if(step.sourceName)parts.push(step.sourceName);
                if(step.targetName)parts.push('→ '+step.targetName);
                if(step.copySourceName)parts.push('copy '+step.copySourceName);
                if(step.prompt)parts.push(step.prompt);
                if(step.choices?.length)parts.push(step.choices.join(', '));
                else if(step.type==='decision')parts.push(step.value);
                trace.append(el('li',parts.join(' · ')));
            }
            details.append(trace);
            if(row.traceTruncated)details.append(el('p','Trace limited to the first 160 actions. Replay this seed and starting order for the full match.'));
            const snapshot=el('details');snapshot.append(el('summary','Setup details and available resources'),el('pre',JSON.stringify({start:row.startSnapshot,final:row.snapshot},null,2)));
            details.append(snapshot);container.append(details);
        }
    }
    const api={summarize,render,match};root.PokeOpeningStats=api;
    if(typeof module!=='undefined')module.exports=api;
})(globalThis);
