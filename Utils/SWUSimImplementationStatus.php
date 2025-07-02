<?php

$file = fopen("./Output/SWUSimImplementation.php", "w");
fwrite($file, "<?php\n");

if ($file === false) {
  die("Unable to open file for writing.");
}

//$ch = curl_init();

//curl_setopt($ch, CURLOPT_URL, "https://api.www.karabast.net/api/get-unimplemented");
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//$response = curl_exec($ch);
//curl_close($ch);

$data = json_decode($response, true);
fwrite($file, "echo \"<script>\n");
fwrite($file, "function KarabastImplemented(cardID) {\n");
fwrite($file, "  switch(cardID) {\n");
//for($i=0; $i<count($data); ++$i) {
//  fwrite($file, "    case '" . $data[$i]["id"] . "': return false;\n");
//}
fwrite($file, "    default: return true;\n");
fwrite($file, "  }\n");
fwrite($file, "}\n");

fwrite($file, "function PetranakiImplemented(cardID) {\n");
fwrite($file, "  switch(cardID) {\n");

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://petranaki.net/Arena/api/UnimplementedCards.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
for($i=0; $i<count($data); ++$i) {
  fwrite($file, "    case '" . $data[$i] . "': return false;\n");
}

fwrite($file, "    default: return true;\n");
fwrite($file, "  }\n");
fwrite($file, "}\n");


fwrite($file, "</script>\";\n");
fclose($file);

?>
