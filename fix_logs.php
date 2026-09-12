<?php
$file = 'c:/xampp/htdocs/2026/new_game_deriv/resources/js/game_v2/games/Double/RobotControl.vue';
$c = file_get_contents($file);

$search1 = "        console.log([DEBUG FILTRO] Conta:  | Base:  | Mult:  | Previsto:  | Saldo:  -> APROVADO: );";
$replace1 = "        console.log([DEBUG FILTRO] Conta: \ | Base: \ | Mult: \ | Previsto: \ | Saldo: \ -> APROVADO: \);";
$c = str_replace($search1, $replace1, $c);

$search2 = "          console.log([DEBUG GRUPO] Adicionado ao grupo : Conta );";
$replace2 = "          console.log([DEBUG GRUPO] Adicionado ao grupo \: Conta \);";
$c = str_replace($search2, $replace2, $c);

file_put_contents($file, $c);
echo "Fixed logs!";
?>
