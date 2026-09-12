const fs = require('fs');
const filePath = 'c:/xampp/htdocs/2026/new_game_deriv/resources/js/game_v2/games/Double/RobotControl.vue';
let c = fs.readFileSync(filePath, 'utf8');

const lines = c.split('\n');
let replaced = 0;
for (let i = 0; i < lines.length; i++) {
    if (lines[i].indexOf('console.log([DEBUG FILTRO]') !== -1) {
        lines[i] = '        console.log([DEBUG FILTRO] Conta: \ | Base: \ | Mult: \ | Previsto: \ | Saldo: \ -> APROVADO: \);';
        replaced++;
    }
    if (lines[i].indexOf('console.log([DEBUG GRUPO]') !== -1) {
        lines[i] = '          console.log([DEBUG GRUPO] Adicionado ao grupo \: Conta \);';
        replaced++;
    }
}

fs.writeFileSync(filePath, lines.join('\n'), 'utf8');
console.log('Replaced ' + replaced + ' lines.');
