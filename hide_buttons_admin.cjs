const fs = require('fs');
const path = require('path');

const replacements = [
    {
        file: 'resources/js/Pages/Admin/ComplimentaryItems/Index.vue',
        replacements: [
            ['<button \n                                @click="openCreateModal"', '<button v-if="can(\'configuracoes.cortesias.gerenciar\')" \n                                @click="openCreateModal"'],
            ['<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">', '<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity" v-if="can(\'configuracoes.cortesias.gerenciar\')">'],
            ['<button \n                        @click="openCreateModal"\n                        class="border-2', '<button v-if="can(\'configuracoes.cortesias.gerenciar\')"\n                        @click="openCreateModal"\n                        class="border-2']
        ]
    },
    {
        file: 'resources/js/Pages/Admin/ContractTemplates/Index.vue',
        replacements: [
            ['<button \n                                @click="openCreateModal"', '<button v-if="can(\'configuracoes.modelos_contrato.gerenciar\')" \n                                @click="openCreateModal"'],
            ['<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">', '<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity" v-if="can(\'configuracoes.modelos_contrato.gerenciar\')">'],
            ['<button \n                        @click="openCreateModal"\n                        class="border-2', '<button v-if="can(\'configuracoes.modelos_contrato.gerenciar\')"\n                        @click="openCreateModal"\n                        class="border-2']
        ]
    },
    {
        file: 'resources/js/Pages/Admin/ProposalTemplates/Index.vue',
        replacements: [
            ['<button \n                                @click="openCreateModal"', '<button v-if="can(\'configuracoes.modelos_proposta.gerenciar\')" \n                                @click="openCreateModal"'],
            ['<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">', '<div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity" v-if="can(\'configuracoes.modelos_proposta.gerenciar\')">'],
            ['<button \n                        @click="openCreateModal"\n                        class="border-2', '<button v-if="can(\'configuracoes.modelos_proposta.gerenciar\')"\n                        @click="openCreateModal"\n                        class="border-2']
        ]
    },
    {
        file: 'resources/js/Pages/Admin/Product/Index.vue',
        replacements: [
            ['<button \n                                @click="openCreateModal"', '<button v-if="can(\'configuracoes.produtos.gerenciar\')" \n                                @click="openCreateModal"'],
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'configuracoes.produtos.gerenciar\')">'],
            ['<button \n                        @click="openCreateModal"\n                        class="border-2', '<button v-if="can(\'configuracoes.produtos.gerenciar\')"\n                        @click="openCreateModal"\n                        class="border-2']
        ]
    },
    {
        file: 'resources/js/Pages/Admin/Settings/PaymentMethod/Index.vue',
        replacements: [
            ['<button \n                                @click="openCreateModal"', '<button v-if="can(\'configuracoes.formas_pagamento.gerenciar\')" \n                                @click="openCreateModal"'],
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'configuracoes.formas_pagamento.gerenciar\')">'],
            ['<button \n                        @click="openCreateModal"\n                        class="border-2', '<button v-if="can(\'configuracoes.formas_pagamento.gerenciar\')"\n                        @click="openCreateModal"\n                        class="border-2']
        ]
    },
    {
        file: 'resources/js/Pages/Admin/Settings/ServiceColumns.vue',
        replacements: [
            ['<button \n                                @click="saveSettings"', '<button v-if="can(\'configuracoes.colunas.gerenciar\')" \n                                @click="saveSettings"']
        ]
    },
    {
        file: 'resources/js/Pages/Admin/Maintenance/Index.vue',
        replacements: [
            ['<button \n                                @click="openGlobalModal"', '<button v-if="can(\'configuracoes.manutencao.gerenciar\')" \n                                @click="openGlobalModal"'],
            ['<div class="flex items-center gap-2">', '<div class="flex items-center gap-2" v-if="can(\'configuracoes.manutencao.gerenciar\')">']
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
