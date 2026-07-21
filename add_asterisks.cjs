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
    // Using [^] to match across lines, but we know it's on one line.
    const regex = new RegExp(`(<SearchableSelect\\s+v-model="form\\.${modelName}".*?)(/>)`, 'g');
    content = content.replace(regex, (match, p1, p2) => {
        if (!p1.includes(' required ')) {
            return `${p1} required ${p2}`;
        }
        return match;
    });
});

// Add <span class="text-red-500">*</span> to labels
const labelsToRequire = [
    'Nome Completo', 'Nascimento', 'Celular Principal', 'E-mail',
    'Nome do 2º Titular / Acompanhante',
    'CEP', 'Rua / Logradouro', 'Bairro', 'Número', 'Cidade', 'Estado',
    'Brinde / Cortesia'
];

labelsToRequire.forEach(labelText => {
    // Make sure we only replace inside <label ...>
    const regex = new RegExp(`(<label[^>]*>\\s*${labelText}\\s*)(</label>)`, 'g');
    content = content.replace(regex, `$1 <span class="text-red-500">*</span>$2`);
});

// Add auto scroll to submit
const submitFunctionPattern = `const submit = () => {`;
const newSubmitFunction = `const scrollToError = () => {
    import('vue').then(({ nextTick }) => {
        nextTick(() => {
            const firstErrorEl = document.querySelector('.border-red-500\\\\/50, .text-red-500');
            if (firstErrorEl) {
                firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
};

const submit = () => {`;
if (!content.includes('scrollToError')) {
    content = content.replace(submitFunctionPattern, newSubmitFunction);
}

const onSuccessEdit = `            onSuccess: () => {
                close();
            },`;
const newOnSuccessEdit = `            onSuccess: () => {
                close();
            },
            onError: () => {
                scrollToError();
            },`;
content = content.replace(onSuccessEdit, newOnSuccessEdit);
// replace again for the second occurrence (store)
content = content.replace(onSuccessEdit, newOnSuccessEdit);

fs.writeFileSync(filePath, content);
console.log('Update complete.');
