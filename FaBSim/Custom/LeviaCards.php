<?php
function FaBLeviaStart(int $p): void {
    foreach(FaBChoiceRefs($p,'Arsenal',['base'=>'lady_barthimont']) as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(intval($o->FaceDown??1)===1&&!HasNoAbilities($o))FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$ref]);
    }
}
function FaBLeviaMentorReady(string $ref,int $eventPlayer,string $attackRef): bool {
    $mentor=FaBIdentityFromMZ($ref);$attack=FaBIdentityFromMZ($attackRef);
    return $mentor!==null&&$attack!==null&&$mentor['zone']==='Arsenal'&&$mentor['player']===$eventPlayer&&intval($mentor['object']->FaceDown??1)===0&&!HasNoAbilities($mentor['object'])&&FaBWTRIsAttackAction($attack['object']);
}
function FaBLeviaMentorLesson(int $p,int $uid,int $attackUID): bool {
    $mentor=FaBFindUID($uid);if(!$mentor||$mentor['zone']!=='Arsenal'||intval($mentor['object']->FaceDown??1)!==0)return false;
    $top=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($top);if(!$f)return false;
    $six=FaBMONBasePower($p,$f['object'])>=6;FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);
    if($six){FaBTagUID($attackUID,'DOMINATE');$o=FaBFindUID($uid)['object'];FaBSetObjectCounter($o,'LESSONS',intval(FaBObjectCounters($o)['LESSONS']??0)+1);}
    return intval(FaBObjectCounters(FaBFindUID($uid)['object'])['LESSONS']??0)>=2;
}
function FaBLessonCounters($obj): int {return intval($obj->FaceDown??1)===0?intval(FaBObjectCounters($obj)['LESSONS']??0):0;}
