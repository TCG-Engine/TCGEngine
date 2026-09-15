<?php
function FaBProfessorBotDeck(): array {return json_decode(file_get_contents(__DIR__.'/ProfessorDeck.json'),true,512,JSON_THROW_ON_ERROR);}
function FaBBotDeck(string $profile): array {
    return match($profile){'uzuri'=>json_decode(file_get_contents(__DIR__.'/UzuriDeck.json'),true,512,JSON_THROW_ON_ERROR),'arakni'=>json_decode(file_get_contents(__DIR__.'/ArakniDeck.json'),true,512,JSON_THROW_ON_ERROR),'dromai'=>json_decode(file_get_contents(__DIR__.'/DromaiDeck.json'),true,512,JSON_THROW_ON_ERROR),'fai'=>FaBFaiBotDeck(),'professor'=>FaBProfessorBotDeck(),'ira'=>json_decode(file_get_contents(__DIR__.'/IraDeck.json'),true,512,JSON_THROW_ON_ERROR),'boltyn'=>json_decode(file_get_contents(__DIR__.'/BoltynDeck.json'),true,512,JSON_THROW_ON_ERROR),'levia'=>json_decode(file_get_contents(__DIR__.'/LeviaDeck.json'),true,512,JSON_THROW_ON_ERROR),'prism'=>json_decode(file_get_contents(__DIR__.'/PrismDeck.json'),true,512,JSON_THROW_ON_ERROR),'lexi'=>json_decode(file_get_contents(__DIR__.'/LexiDeck.json'),true,512,JSON_THROW_ON_ERROR),default=>throw new InvalidArgumentException('Unknown FaB bot profile.')};
}
// Pinned Fabrary 01HXDKBQNQB7TF0GNJN0MQ7CWC export; Salt the Wound fills its 39-card main.
function FaBFaiBotDeck(): array {
    $pairs=['ancestral_empowerment_red','art_of_war_yellow','blaze_headlong_red','brand_with_cinderclaw_blue','brand_with_cinderclaw_red','brand_with_cinderclaw_yellow','breaking_point_red','double_strike_red','lava_vein_loyalty_blue','lava_vein_loyalty_red','phoenix_flame_red','phoenix_form_red','promise_of_plenty_red','red_hot_red','rise_from_the_ashes_red','rising_resentment_red','ronin_renegade_red','scar_for_a_scar_red','stab_wound_blue'];
    $cards=[];foreach($pairs as $id){$cards[]=$id;$cards[]=$id;}$cards[]='snatch_red';$cards[]='salt_the_wound_yellow';
    return ['success'=>true,'hero'=>'fai','weapons'=>['harmonized_kodachi','harmonized_kodachi'],
        'equipment'=>['fyendals_spring_tunic','mask_of_momentum','snapdragon_scalers','stubby_hammerers'],
        'mainDeck'=>$cards,'inventory'=>['tenacity_yellow'],'unresolved'=>[]];
}
