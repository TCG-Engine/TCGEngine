<?php
// Run from the repository root after generating the IAR card dictionary.
require_once __DIR__.'/../../zzImageConverter.php';
$cards=json_decode(file_get_contents(__DIR__.'/iar_catalog.json'),true,512,JSON_THROW_ON_ERROR);
$missing=[];
foreach($cards as $card){
    $id=$card['id'];
    $url=$card['image_url']??'';
    if($url==='')foreach($card['printings'] as $printing)if(!empty($printing['image_url'])){$url=$printing['image_url'];break;}
    if($url===''){$missing[]=$id;continue;}
    CheckImage($id,$url,'',rootPath:'FaBSim/');
}
if($missing)throw new RuntimeException('Missing image URLs: '.implode(', ',$missing));
echo "IAR image generation complete.\n";
