const { PDFDocument, StandardFonts, rgb, PDFRef } = require('pdf-lib');
const fs = require('fs');
const path = require('path');

async function fillPdf() {
    try {
        const inputPdfPath = process.argv[2];
        const outputPdfPath = process.argv[3];
        const jsonFilePath = process.argv[4];

        if (!inputPdfPath || !outputPdfPath || !jsonFilePath) {
            throw new Error('Argumentos insuficientes. Uso: node fill_pdf_fields.cjs <input> <output> <json_file_path>');
        }

        const tagsDataStr = fs.readFileSync(jsonFilePath, 'utf8');
        const tagsData = JSON.parse(tagsDataStr);
        const pdfBytes = fs.readFileSync(inputPdfPath);
        const pdfDoc = await PDFDocument.load(pdfBytes);
        const form = pdfDoc.getForm();
        const pages = pdfDoc.getPages();
        const helveticaFont = await pdfDoc.embedFont(StandardFonts.Helvetica);
        const allFields = form.getFields();

        // Para cada campo mapeado, tentamos encontrar no PDF e preencher
        for (const [fieldName, value] of Object.entries(tagsData)) {
            try {
                // Busca segura pelo nome exato (evita falhas do getField() interno do pdf-lib com nomes complexos)
                const field = allFields.find(f => f.getName() === fieldName);
                if (field) {
                    const type = field.constructor.name;
                    const widgets = field.acroField.getWidgets();
                    
                    if (widgets && widgets.length > 0) {
                        const rect = widgets[0].getRectangle();
                        
                        let targetPage = null;
                        for (const page of pages) {
                            const annots = page.node.Annots();
                            if (annots) {
                                for (let i = 0; i < annots.size(); i++) {
                                    const annotRef = annots.get(i);
                                    if (annotRef && typeof annotRef === 'object') {
                                        const annotDict = pdfDoc.context.lookup(annotRef);
                                        if (annotDict === widgets[0].dict) {
                                            targetPage = page;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (targetPage) break;
                        }
                        
                        if (targetPage) {
                            if (type === 'PDFTextField') {
                                targetPage.drawText(String(value || ''), {
                                    x: rect.x + 2,
                                    y: rect.y + (rect.height / 2) - 4,
                                    size: 10,
                                    font: helveticaFont,
                                    color: rgb(0, 0, 0)
                                });
                            } else if (type === 'PDFCheckBox') {
                                if (value && String(value).toLowerCase() !== 'false' && value !== '0') {
                                    targetPage.drawText('X', {
                                        x: rect.x + (rect.width / 2) - 3,
                                        y: rect.y + (rect.height / 2) - 4,
                                        size: 12,
                                        font: helveticaFont,
                                        color: rgb(0, 0, 0)
                                    });
                                }
                            } else if (type === 'PDFRadioGroup') {
                                targetPage.drawText('X', {
                                    x: rect.x + (rect.width / 2) - 3,
                                    y: rect.y + (rect.height / 2) - 4,
                                    size: 12,
                                    font: helveticaFont,
                                    color: rgb(0, 0, 0)
                                });
                            }
                        }
                    }
                    
                    form.removeField(field);
                }
            } catch (err) {
                // ignoramos silenciosamente
            }
        }

        const filledPdfBytes = await pdfDoc.save();
        fs.writeFileSync(outputPdfPath, filledPdfBytes);
        
        console.log(JSON.stringify({ success: true, outputPath: outputPdfPath }));
    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    }
}

fillPdf();
