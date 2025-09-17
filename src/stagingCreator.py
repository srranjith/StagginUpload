import os
import argparse
import pandas as pd
from dotenv import load_dotenv
import google.generativeai as genai
from langchain.prompts import PromptTemplate

# Load environment variables
load_dotenv()

# Configure Gemini
genai.configure(api_key=os.getenv("GEMINI_API_KEY"))

# Create Gemini model
def get_llm():
    return genai.GenerativeModel("gemini-1.5-flash")  # You can switch to gemini-1.5-pro if needed

# Prompt template
prompt_template = PromptTemplate(
    input_variables=["table_name", "master_id", "headers"],
    template="""
Generate full SQL only. 
Do not skip headers except if they are empty. 
Do not summarize. 
Do not add comments like '...and so on'.

Input:
- Table name: {table_name}
- Master ID: {master_id}
- Excel headers: {headers}

Header cleanup rules:
- Skip empty headers entirely
- Remove special chars ( % / _ - . , ; : ( ) [ ]  " $ # @ ! ^ & * + = < > ~ `)
- Remove extra spaces, month names (jan, feb, mar…), years (2023, 2024…)
- Convert to snake_case (lowercase with underscores)
- if headers same then add _1, _2 etc

Output format:
1. CREATE TABLE IF NOT EXISTS {table_name} (
   id INT AUTO_INCREMENT PRIMARY KEY,
   master_id INT,
   all cleaned headers as VARCHAR(255),
   is_collection_processed INT DEFAULT 0,
   created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
2. One single INSERT INTO generic_excel_upload_definition_fields statement:
INSERT INTO generic_excel_upload_definition_fields (
  version, master_id, excel_column_name, table_column_name, `type`, required, `default`, partner_api_key, ui_type, ui_title, ui_key, additional_key, ui_grouping, ui_field_condition, ui_field_order, can_update_stage, file_json_path, created_at, updated_at, created_by, description, example, required_stage, file_storage, `length`, updated_by, column_indices, effective_date, is_video_file, file_extension
) VALUES
  (0, {master_id}, '<original_header>', '<cleaned_header>', '', 'Non Mandatory', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW(), 'BACKEND', NULL, NULL, NULL, NULL, NULL, NULL, '<column_indices>', NULL, 0, NULL),
  (<row2>),
  ...
  (<rowN>);
- `column_indices` = Excel style (A, B, C, …)
- Do not repeat the full INSERT keyword for each row.
"""
)


def process_excel(file_path: str, table_name: str, master_id: int, output_file: str = "output.sql", headers_row: int = 1):
    # Extract headers from Excel
    df = pd.read_excel(file_path, engine="openpyxl", header=headers_row-1)
    headers = [
    col.strip()
    for col in df.columns
    if isinstance(col, str) and col.strip() != "" and not col.lower().startswith("unnamed")
]
    # Build prompt
    prompt = prompt_template.format(
        table_name=table_name,
        master_id=master_id,
        headers=headers
    )

    # Run Gemini
    model = get_llm()
    response = model.generate_content(prompt)

    sql_output = response.text if hasattr(response, "text") else str(response)
    # Remove ```sql``` or ``` from the beginning/end if present
    sql_output = sql_output.replace("```sql", "").replace("```", "").strip()
    # Save to file
    with open(output_file, "w", encoding="utf-8") as f:
        f.write(sql_output)

    # Remove Excel file after processing
    try:
        os.remove(file_path)
    except OSError:
        pass

    return sql_output


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Generate SQL from Excel using Gemini API")
    parser.add_argument("--file", required=True, help="Path to Excel file (.xlsx)")
    parser.add_argument("--master_id", type=int, required=True, help="Master ID")
    parser.add_argument("--table_name", default="staging_payout", help="Target SQL table name")
    parser.add_argument("--headers_row", type=int, default=1, help="Row number of headers in Excel")
    parser.add_argument("--output", default="output.sql", help="Output SQL file")

    args = parser.parse_args()

    sql = process_excel(args.file, args.table_name, args.master_id, args.output, args.headers_row)
    print(sql)
