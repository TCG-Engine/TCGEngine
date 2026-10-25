"""Pure planning for three fresh-Ice-Bolt disposable controls; no engine work."""
SLUG='card-ice-bolt-red-opponent-barrier-pay-floating'
CONTROL_KEYS={
 (SLUG,'Expected FaBSim state ["arcaneDealt"] to equal JSON {"1":{"2":4}}, got {"2":{"2":4}}'):'wrong_arcane_actor',
 (SLUG,'Expected FaBSim state ["resolvingReaction"] to be missing or null.'):'stale_resolution_marker',
 (SLUG,'Expected Stack count 1, got 0'):'premature_plain_source_clear',
}

def build_state_control_plan(original,reason,actions,assertions):
    mutation=CONTROL_KEYS.get((original,reason))
    if mutation is None:raise RuntimeError('Unregistered state assertion control')
    def match(a,**fields):return all(a.get(k)==v for k,v in fields.items())
    def unique(steps,label):
        values=set(steps)
        if len(values)!=1:raise RuntimeError('Fresh fixture needs one exact '+label+' checkpoint')
        return next(iter(values))
    def required(step,**fields):
        if not any(a.get('step')==step and match(a,**fields)for a in assertions):
            raise RuntimeError('Fresh fixture lacks native checkpoint '+str(fields))
    terminal=len(actions)
    required(terminal,type='game_state_field',rootName='FaBSim',path=['arcaneDealt'],comparison='equals',valueJson='{"1":{"2":4}}')
    required(terminal,type='game_state_field',rootName='FaBSim',path=['resolvingReaction'],comparison='missing_or_null')
    required(terminal,type='game_state_field',rootName='FaBSim',path=['pendingPayment'],comparison='missing_or_null')
    for zone,value in [('Stack',0),('p1Graveyard',1),('p1Health',20),('p2Health',16),('p1Resources',2),('p2Resources',0),('p1ActionPoints',0),('p2ActionPoints',0)]:
        required(terminal,type='zone_count'if zone in ['Stack','p1Graveyard']else'scalar_zone_equals',zone=zone,value=value)
    required(terminal,type='card_exists',zone='p1Graveyard',cardID='ice_bolt_red')
    required(terminal,type='decision_queue_empty',player='all')
    if mutation=='premature_plain_source_clear':
        prefix=unique((a['step']for a in assertions if match(a,type='card_property_equals',mzId='p2DecisionQueue-0',property='Type',value='MZMAYCHOOSE')),'pending Arcane Barrier')
        required(prefix,type='card_property_equals',mzId='p2DecisionQueue-0',property='Param',value='p2Equipment-0')
        required(prefix,type='zone_count',zone='Stack',value=1)
        required(prefix,type='card_exists',zone='Stack',cardID='ice_bolt_red')
        required(prefix,type='zone_count',zone='p1Graveyard',value=0)
        if not isinstance(prefix,int)or isinstance(prefix,bool)or not 0<prefix<terminal:raise RuntimeError('Invalid pending-spell prefix')
        if actions[prefix].get('mode')!=100 or actions[prefix].get('playerID')!=2 or actions[prefix].get('cardID')!='p2Equipment-0':raise RuntimeError('Expected supported defender Arcane Barrier answer next')
    else:prefix=terminal
    if not isinstance(prefix,int)or isinstance(prefix,bool)or prefix<1:raise RuntimeError('Invalid state-control prefix')
    retained=[dict(a,step=a['step']-prefix)for a in assertions if a['step']>=prefix]
    if not retained:raise RuntimeError('Lost persisted semantic checkpoint')
    return dict(mutation=mutation,prefix=prefix,keep_boundary=True,expected_failure_step=0,actions=actions[prefix:],assertions=retained)
