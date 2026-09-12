const fs = require('fs');
const filePath = 'c:/xampp/htdocs/2026/new_game_deriv/resources/js/game_v2/games/Double/RobotControl.vue';
let c = fs.readFileSync(filePath, 'utf8');

c = c.replace(/console\.log\(\[DEBUG FILTRO\].*/g, 'console.log([DEBUG FILTRO] Conta: \ | Base: \ | Mult: \ | Previsto: \ | Saldo: \ -> APROVADO: \);');
c = c.replace(/console\.log\(\[DEBUG GRUPO\].*/g, 'console.log([DEBUG GRUPO] Adicionado ao grupo \: Conta \);');

fs.writeFileSync(filePath, c, 'utf8');

const check = fs.readFileSync(filePath, 'utf8');
const lines = check.split('\n');
console.log('--- 670 - 680 ---');
console.log(lines.slice(670, 680).join('\n'));
