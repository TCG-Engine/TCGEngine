<?php
function FaBPrismStart(int $p): void {
    foreach(FaBChoiceRefs($p,'Arsenal',['base'=>'the_librarian']) as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(intval($o->FaceDown??1)===1&&!HasNoAbilities($o))FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$ref]);
    }
}
function FaBPrismShieldCreated(int $p): void {
    foreach(FaBChoiceRefs($p,'Arsenal',['base'=>'the_librarian']) as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(intval($o->FaceDown??1)!==0||HasNoAbilities($o))continue;
        $turn=intval(GetTurnNumber());
        if(intval(FaBObjectCounters($o)['LIBRARIAN_TURN']??-1)===$turn)continue;
        // Reserve this turn before resolving: creating several tokens earns one lesson.
        FaBSetObjectCounter($o,'LIBRARIAN_TURN',$turn);
        FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>$ref]);
    }
}
