const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'resources', 'js', 'Components', 'Sales', 'NewServiceModal.vue');
let content = fs.readFileSync(filePath, 'utf8');

// Add required prop to SearchableSelects
const selectsToRequire = [
    'local', 'opc_id', 'qualification', 'nacionalidade', 'profissao', 'estadoCivil',
    'tipoRelacionamento', 'nacionalidadeConjuge', 'estadoCivilConjuge', 'profissaoConjuge',
    'tempoJuntos'
];

selectsToRequire.forEach(modelName => {
    // regex to find <SearchableSelect v-model="form.MODEL" ... />
    const regex = new RegExp(`(<SearchableSelect\\s+v-model="form\\.${modelName}"[^>]*)(>)`, 'g');
    content = content.replace(regex, (match, p1, p2) => {
        if (!p1.includes(' required')) {
            return `${p1} required${p2}`;
        }
        return match;
    });
});

// Add <span class="text-red-500">*</span> to labels
const labelsToRequire = [
    'Nome Completo', 'Nascimento', 'Celular Principal', 'E-mail',
    'Nome do 2º Titular / Acompanhante',
    'CEP', 'Rua / Logradouro', 'Bairro', 'Número', 'Cidade', 'Estado',
    'Brindes / Cortesias' // this is a bit different, it might be Cortesia
];

labelsToRequire.forEach(labelText => {
    const regex = new RegExp(`(>\\s*${labelText}\\s*)(</label>)`, 'g');
    content = content.replace(regex, `$1 <span class="text-red-500">*</span>$2`);
});

fs.writeFileSync(filePath, content);
console.log('Update complete.');
