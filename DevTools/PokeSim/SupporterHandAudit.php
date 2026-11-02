<?php
/** Read-only, in-process diagnostic. Object handles distinguish continuous hand
 * visits across zone compaction. A shuffle/redraw starts a new visit.
 * This never writes card properties, game state, or bot observations.
 */
class PokeSupporterHandAudit {
    private array $hand=[];
    private array $objects=[];
    private array $active=[];
    private ?array $resolution=null;
    private ?array $before=null;
    public array $visits=[];
    public array $effects=[];
    public array $competition=[];
    public array $legalCardTurns=[];
    public function __construct(private int $seat){$this->sync();}
    private function sync(): void {
        $hand=[];foreach(PokeObjects($this->seat,'Hand') as $obj)$hand[spl_object_id($obj)]=$obj;
        foreach($this->active as $key=>$index)if(!isset($hand[$key])){
            $cause=$this->resolution;$exit='other';
            if(($cause['playedObject']??null)===$key)$exit='played';
            elseif($cause){
                if(($cause['actor']===$this->seat&&in_array($cause['card'],['me02.5-192','me01-119'],true))||$cause['card']==='me04-082')$exit='shuffled';
                elseif($cause['actor']===$this->seat&&in_array($cause['card'],['sv06-145','me03-079','me01-131','me02.5-213','me04-080'],true))$exit='discarded';
            }
            $this->visits[$index]['exit']=$exit;
            $this->visits[$index]['exitTurn']=GetPlayerTurns($this->seat);
            $this->visits[$index]['exitCause']=$cause['card']??null;unset($this->active[$key]);
        }
        foreach($hand as $key=>$obj){
            if(!isset($this->hand[$key])&&$this->resolution&&$this->resolution['ownSupporter'])++$this->resolution['cardsGained'];
            if(CardTrainerType($obj->CardID)!=='Supporter'||isset($this->active[$key]))continue;
            $index=count($this->visits);$this->active[$key]=$index;$this->objects[$key]=$obj;
            $this->visits[]=['card'=>$obj->CardID,'entryTurn'=>GetPlayerTurns($this->seat),'exit'=>null,'exitTurn'=>null,'exitCause'=>null,
                'windows'=>[],'legalTurns'=>[],'carryovers'=>0,'legalCarryovers'=>0,'supporterLimitCarryovers'=>0,'firstTurnRuleCarryovers'=>0];
        }
        $this->hand=$hand;
    }
    public function beforeAction(array $view,array $action): void {
        $this->sync();$turn=GetPlayerTurns($this->seat);
        $this->before=['turnPlayer'=>GetTurnPlayer(),'turn'=>$turn];
        if($action['type']!=='decision'){
            $obj=isset($action['source'])?GetZoneObject($action['source']):null;
            $id=$obj?$obj->CardID:null;
            $ownSupporter=$action['player']===$this->seat&&$action['type']==='trainer'&&CardTrainerType($id??'')==='Supporter';
            $this->resolution=['actor'=>$action['player'],'card'=>$id,'ownSupporter'=>$ownSupporter,
                'playedObject'=>$ownSupporter?spl_object_id($obj):null,'turn'=>$turn,'cardsGained'=>0,
                'discardEnergyBefore'=>count(array_filter(PokeObjects($this->seat,'Discard'),fn($c)=>CardType($c->CardID)==='Energy'))];
        }
        // Only real action windows count; pending attack/prize choices cannot
        // provide a usable Supporter opportunity on the turn that just ended.
        if($view['viewer']!==$this->seat||$view['turnPlayer']!==$this->seat||$view['phase']!=='MAIN'||$view['decision'])return;
        $legal=[];foreach($view['actions'] as $a)if($a['type']==='trainer')$legal[$a['source']]=true;
        $legalIDs=[];
        foreach($this->active as $key=>$index){
            $obj=$this->hand[$key];$v=&$this->visits[$index];$v['windows'][$turn]=true;
            if(isset($legal[PokeRef($this->seat,'Hand',$obj->mzIndex)])){
                $v['legalTurns'][$turn]=true;$legalIDs[$obj->CardID]=true;$this->legalCardTurns[$obj->CardID][$turn]=true;
            }
            unset($v);
        }
        if($this->resolution['ownSupporter'])foreach(array_keys($legalIDs) as $id)if($id!==$this->resolution['card']){
            $winner=$this->resolution['card'];$this->competition[$id][$winner]=($this->competition[$id][$winner]??0)+1;
        }
    }
    public function afterAction(): void {
        $this->sync();$before=$this->before;
        if($before&&$before['turnPlayer']===$this->seat&&GetTurnPlayer()!==$this->seat&&!GetWinner()){
            foreach($this->active as $index){
                $v=&$this->visits[$index];$turn=$before['turn'];
                if(isset($v['windows'][$turn])){
                    ++$v['carryovers'];$v['legalCarryovers']+=(int)isset($v['legalTurns'][$turn]);
                    $v['supporterLimitCarryovers']+=(int)GetSupporterUsed($this->seat);
                    $v['firstTurnRuleCarryovers']+=(int)($turn===1&&$this->seat===GetFirstPlayer()&&CardName($v['card'])!=='Carmine');
                }unset($v);
            }
        }
        if($this->resolution&&!(new DecisionQueueController())->AnyQueuePending()){
            if($this->resolution['ownSupporter']){
                $r=$this->resolution;$r['energyDiscardDelta']=count(array_filter(PokeObjects($this->seat,'Discard'),fn($c)=>CardType($c->CardID)==='Energy'))-$r['discardEnergyBefore'];
                unset($r['playedObject'],$r['discardEnergyBefore']);$this->effects[]=$r;
            }
            $this->resolution=null;
        }
    }
    public function finish(): array {
        $this->sync();foreach($this->active as $index)$this->visits[$index]['exit']='remaining';
        return ['visits'=>$this->visits,'effects'=>$this->effects,'competition'=>$this->competition,'legalCardTurns'=>$this->legalCardTurns];
    }
}
