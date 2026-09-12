const fs = require('fs');
const filePath = 'c:/xampp/htdocs/2026/new_game_deriv/resources/js/game_v2/games/Double/RobotControl.vue';
let c = fs.readFileSync(filePath, 'utf8');

const searchBlock =               }).catch(err => {
                  console.error('[CopyTrading][Robo] Error bulk purchase chunk:', err);
              });;

const replaceBlock =               }).catch(err => {
                  console.error('[CopyTrading][Robo] Error bulk purchase chunk:', err);
                  
                  // Injeta o erro de rede/timeout nas contas para salvar como fantasma
                  const apiClient = getDerivAPIClient();
                  let targetEntry = null;
                  if (apiClient && reqId && apiClient.entryRequests?.[reqId]) {
                      targetEntry = apiClient.entryRequests[reqId];
                  } else if (apiClient && apiClient.lastEntryData) {
                      targetEntry = apiClient.lastEntryData;
                  }
                  
                  if (targetEntry && targetEntry.followersAccounts) {
                      targetEntry.followersAccounts = targetEntry.followersAccounts.map(acc => {
                          if (chunkAccounts.some(ca => ca.account_id === acc.account_id)) {
                              return { ...acc, error_message: 'Falha de Rede ou Timeout: ' + err.message, contract_id: null, bulk_contract_id: null };
                          }
                          return acc;
                      });
                  }
              });;

if (c.includes(searchBlock)) {
    c = c.replace(searchBlock, replaceBlock);
    fs.writeFileSync(filePath, c, 'utf8');
    console.log('Success!');
} else {
    console.log('Search block not found!');
}
