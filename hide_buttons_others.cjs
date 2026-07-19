const fs = require('fs');
const path = require('path');

const replacements = [
    {
        file: 'resources/js/Pages/Roles/Index.vue',
        replacements: [
            ['<button \n                                @click="openCreateModal"', '<button v-if="can(\'cargos.gerenciar\')" \n                                @click="openCreateModal"'],
            ['<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">', '<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity" v-if="can(\'cargos.gerenciar\')">'],
            ['<button \n                        @click="openCreateModal"\n                        class="border-2', '<button v-if="can(\'cargos.gerenciar\')"\n                        @click="openCreateModal"\n                        class="border-2']
        ]
    },
    {
        file: 'resources/js/Pages/Sales/Scheduling/Index.vue',
        replacements: [
            ['<button @click="openCreateModal"', '<button v-if="can(\'agendamentos.gerenciar\')" @click="openCreateModal"'],
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'agendamentos.gerenciar\')">']
        ]
    },
    {
        file: 'resources/js/Pages/Finance/Receivable/Index.vue',
        replacements: [
            ['<button \n                                @click="openBulkPayModal"', '<button v-if="can(\'recebiveis.gerenciar\')" \n                                @click="openBulkPayModal"'],
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'recebiveis.gerenciar\')">']
        ]
    },
    {
        file: 'resources/js/Pages/Finance/SalesControl/Index.vue',
        replacements: [
            ['<button \n                                @click="openAuditModal"', '<button v-if="can(\'controle_vendas.gerenciar\')" \n                                @click="openAuditModal"'],
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'controle_vendas.gerenciar\')">']
        ]
    },
    {
        file: 'resources/js/Pages/AfterSales/Welcome/Index.vue',
        replacements: [
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'pos_venda.boas_vindas.gerenciar\')">']
        ]
    },
    {
        file: 'resources/js/Pages/AfterSales/ContractDelivery/Index.vue',
        replacements: [
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'pos_venda.gestao_contratos.gerenciar\')">']
        ]
    }
];

replacements.forEach(job => {
    const filePath = path.join(__dirname, job.file);
    if (!fs.existsSync(filePath)) {
        console.error("Not found: " + filePath);
        return;
    }
    let content = fs.readFileSync(filePath, 'utf8');
    job.replacements.forEach(rep => {
        content = content.replace(rep[0], rep[1]);
    });
    fs.writeFileSync(filePath, content, 'utf8');
    console.log("Updated: " + filePath);
});
