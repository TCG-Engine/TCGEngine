// Standalone abstract goldfish model, independent of generated FaBSim files.
export const VERSION = 2;
export const defaults = {size:60, bellows:3, blue6Block:12, blue6Nonblock:9, blue6Cost:3, blue5Cost:3, arsenal:0};
const sum = a => a.reduce((x,y)=>x+y,0);
const minus = (a,i) => a.map((n,j)=>n-(i===j));
function choose(n,k) { let x=1; for(let i=1;i<=k;i++) x=x*(n-i+1)/i; return x; }

// Unordered draws without replacement; probabilities are hypergeometric.
export function draws(deck,n) {
    n=Math.min(n,sum(deck)); const out=[], hand=deck.map(()=>0), denom=choose(sum(deck),n);
    function visit(i,left,weight) {
        if(i===deck.length) { if(!left) out.push({hand:[...hand],deck:deck.map((x,j)=>x-hand[j]),p:weight/denom}); return; }
        for(let k=0;k<=Math.min(deck[i],left);k++) {hand[i]=k;visit(i+1,left-k,weight*choose(deck[i],k));}
    }
    visit(0,n,1);return out;
}

// Only pitch while resources are short. Stop immediately once the cost is met.
export function payments(hand,resources,cost,pitch=[2,1,3,3,3]) {
    const out=new Map();
    function visit(h,r) {
        if(r>=cost) {out.set(h.join(',')+':'+r,{hand:h,resources:r-cost});return;}
        // Prefer blue fives, then nonblocking blue sixes on equal-value lines.
        for(const i of [3,4,2,0,1].filter(i=>i<h.length))if(h[i])visit(minus(h,i),r+pitch[i]);
    }
    visit(hand,resources);return [...out.values()];
}
const terminal = (b,failed=false) => new Map([[`${b},0,${+failed},0`,1]]);
function add(to,from,p=1,damage=0,block=0) {
    for(const [key,v] of from) {const [b,d,f,k=0]=key.split(',').map(Number), next=`${b},${d+damage},${f},${k+block}`;to.set(next,(to.get(next)||0)+p*v);}
}
const mean = dist => [...dist].reduce((v,[key,p])=>{const [,d,,k=0]=key.split(',').map(Number);return v+(d+k)*p;},0);
export function stats(dist,bellows=null,metric='cycle') {
    let mass=0,total=0,squares=0,min=Infinity,max=-Infinity,failed=0;const histogram={};
    for(const [key,p] of dist) {
        const [b,d,f,k=0]=key.split(',').map(Number);if(bellows!==null&&b!==bellows)continue;
        const value=metric==='damage'?d:metric==='block'?k:d+k;
        mass+=p;total+=p*value;squares+=p*value*value;failed+=p*f;min=Math.min(min,value);max=Math.max(max,value);histogram[value]=(histogram[value]||0)+p;
    }
    if(!mass)return {probability:0,mean:null,min:null,max:null,sd:null,failedRate:null,histogram:{}};
    for(const d in histogram)histogram[d]/=mass;
    const avg=total/mass;return {probability:mass,mean:avg,min,max,sd:Math.sqrt(Math.max(0,squares/mass-avg*avg)),failedRate:failed/mass,histogram};
}
export function validate(config,reds) {
    for(const key of Object.keys(defaults))if(!Number.isInteger(config[key])||config[key]<0)throw Error(`${key} must be a nonnegative integer`);
    if(config.size<4+config.arsenal||config.size>80||config.bellows<1||config.bellows>3||config.blue6Cost>6||config.blue5Cost>6||config.arsenal>1)throw Error('Use deck size 4–80 (at least 5 with arsenal), 1–3 Bellows, costs 0–6, and arsenal 0 or 1');
    if(!Number.isInteger(reds)||reds<0||reds+config.bellows+config.blue6Block+config.blue6Nonblock>config.size)throw Error('Card counts exceed deck size');
}
// Card categories: Bellow, red seven, blocking blue six, blue five, nonblocking blue six.
// Keep nonblocks distinct: they cannot defend, even though their offense matches blue sixes.
export function createSolver(options={}) {
    const config={...defaults,...options};
    const costs=[1,2,config.blue6Cost,config.blue5Cost,config.blue6Cost],power=[0,7,6,5,6],block=[3,3,3,3,0],memo=new Map();
    // The memo only includes information the player knows. Future draws are chance nodes.
    function solve(hand,remaining,resources,b,claws,hit,force=false,arsenal=false) {
        const key=[hand.join(','),remaining.join(','),resources,b,claws,+hit,+force,+arsenal].join('|');
        if(memo.has(key))return memo.get(key);
        let best=force?null:terminal(b),score=force?-Infinity:0;
        function consider(dist){const m=mean(dist);if(m>score+1e-10){best=dist;score=m;}}
        if(hand[0]||arsenal) {
            // The dedicated arsenal Bellow is always played first. It cannot be pitched/discarded.
            for(const pay of payments(arsenal?hand:minus(hand,0),resources,1)) {
                if(!sum(pay.hand))continue; // Must still have a card to discard after pitching.
                const dist=new Map(),n=sum(pay.hand);
                pay.hand.forEach((count,i)=>{
                    if(!count)return;const p=count/n,h=minus(pay.hand,i);
                    if(power[i]<6){add(dist,terminal(b+1,true),p);return;} // No action point remains.
                    for(const draw of draws(remaining,2))add(dist,solve(h.map((v,j)=>v+draw.hand[j]),draw.deck,pay.resources,b+1,claws,true),p*draw.p);
                });
                consider(dist);
            }
        }
        if(!force) {
            // Abstract attacks have no text/go again. They end the action sequence.
            for(let i=1;i<hand.length;i++)if(hand[i]&&payments(minus(hand,i),resources,costs[i]).length) {
                const dist=new Map();add(dist,terminal(b),1,power[i]+2*b);consider(dist);
            }
            if(claws<2)for(const pay of payments(hand,resources,2)) {
                const dist=new Map();add(dist,hit?solve(pay.hand,remaining,pay.resources,b,claws+1,hit):terminal(b),1,3+2*b);consider(dist);
            }
        }
        best??=terminal(b);memo.set(key,best);return best;
    }
    function opening(hand,remaining) {
        let best=null,bestScore=-Infinity,blocked=null;
        const kept=[...hand];
        // Defend using only cards known BEFORE pitching, discarding, or drawing.
        function visit(i,value) {
            if(i===hand.length) {
                if(!config.arsenal&&!kept[0])return;
                if(sum(kept)<(config.arsenal?2:3))return; // preserve a legal focused first action
                const dist=new Map();add(dist,solve([...kept],remaining,0,0,0,false,true,!!config.arsenal),1,0,value);
                const score=mean(dist);
                if(score>bestScore+1e-10){best=dist;bestScore=score;blocked=hand.map((n,j)=>n-kept[j]);}
                return;
            }
            for(let n=0;n<=(block[i]?hand[i]:0);n++){kept[i]=hand[i]-n;visit(i+1,value+n*block[i]);}kept[i]=hand[i];
        }
        visit(0,0);
        best??=solve(hand,remaining,0,0,0,false,true,!!config.arsenal);
        return {dist:best,blocked:blocked??hand.map(()=>0)};
    }
    return {opening,states:()=>memo.size};
}
export function summarize(dist) {
    const summary=n=>({...stats(dist,n),damage:stats(dist,n,'damage'),block:stats(dist,n,'block')});
    return {all:summary(null),groups:[0,1,2,3].map(summary)};
}
export function analyze(reds,options={}) {
    const config={...defaults,...options};validate(config,reds);
    const deck=[config.bellows-config.arsenal,reds,config.blue6Block,config.size-config.bellows-reds-config.blue6Block-config.blue6Nonblock,config.blue6Nonblock];
    const solver=createSolver(config);
    const dist=new Map();let openingProbability=0,openingBellows=[0,0,0,0];
    for(const opening of draws(deck,4)) {
        if(!config.arsenal&&!opening.hand[0])continue;
        openingProbability+=opening.p;openingBellows[opening.hand[0]]+=opening.p;
        add(dist,solver.opening(opening.hand,opening.deck).dist,opening.p);
    }
    for(const [k,p] of dist)dist.set(k,p/openingProbability);
    return {reds,blue5:deck[3],config,openingProbability,openingBellows:openingBellows.map(p=>p/openingProbability),...summarize(dist),states:solver.states()};
}
