const { PDFDocument } = require('pdf-lib');
const fs = require('fs');

async function extractFields() {
    const pdfPath = process.argv[2];

    if (!pdfPath) {
        console.error(JSON.stringify({ error: 'Nenhum caminho de PDF fornecido.' }));
        process.exit(1);
    }

    try {
        const pdfBytes = fs.readFileSync(pdfPath);
        const pdfDoc = await PDFDocument.load(pdfBytes);
        const form = pdfDoc.getForm();
        const fields = form.getFields();

        const fieldNames = fields.map(f => {
            const type = f.constructor.name; // PDFTextField, PDFCheckBox, etc.
            return {
                name: f.getName(),
                type: type.replace('PDF', '')
            };
        });

        console.log(JSON.stringify({ success: true, fields: fieldNames }));
    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    }
}

extractFields();
