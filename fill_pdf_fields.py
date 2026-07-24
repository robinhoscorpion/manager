import sys
import json
import pypdf
from pypdf import PdfReader, PdfWriter

def main():
    if len(sys.argv) < 4:
        print(json.dumps({"error": "Argumentos insuficientes. Uso: python fill_pdf_fields.py <input> <output> <json_file_path>"}))
        sys.exit(1)

    input_pdf_path = sys.argv[1]
    output_pdf_path = sys.argv[2]
    json_file_path = sys.argv[3]

    try:
        with open(json_file_path, 'r', encoding='utf-8') as f:
            tags_data = json.load(f)

        reader = PdfReader(input_pdf_path)
        writer = PdfWriter()

        # Add all pages to the writer
        writer.append(reader)

        # Iterate through all pages and update form fields with the dictionary
        for page in writer.pages:
            try:
                writer.update_page_form_field_values(page, tags_data)
            except Exception as e:
                # ignore pages with errors like "IndirectObject has no len()"
                pass

        # NeedAppearances flag tells the PDF viewer to render the field text properly
        # pypdf does not set this automatically by default for some viewers, 
        # but update_page_form_field_values usually bakes it or sets it if required.
        if "/AcroForm" in writer.root_object:
            writer.root_object["/AcroForm"][pypdf.generic.NameObject("/NeedAppearances")] = pypdf.generic.BooleanObject(True)

        with open(output_pdf_path, 'wb') as f:
            writer.write(f)

        print(json.dumps({"success": True, "outputPath": output_pdf_path}))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
