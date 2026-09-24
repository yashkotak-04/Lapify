import os
import zipfile

def package_project():
    root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    zip_filename = os.path.join(root_dir, "lapify-infinityfree-deployment.zip")

    exclude_dirs = {'.git', '.venv', '__pycache__', 'scratch'}
    exclude_extensions = {'.docx', '.pyc'}
    exclude_files = {'lapify-infinityfree-deployment.zip', 'Lapify_Viva_500_Questions_and_Answers.docx', 'Lapify_Exhibition_Speech_and_Presentation_Guide.docx'}

    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(root_dir):
            # Prune excluded directories
            dirs[:] = [d for d in dirs if d not in exclude_dirs]

            for file in files:
                if file in exclude_files:
                    continue
                _, ext = os.path.splitext(file)
                if ext in exclude_extensions and file != 'generate_speech_doc.py':
                    continue

                full_path = os.path.join(root, file)
                rel_path = os.path.relpath(full_path, root_dir)

                # Normalize path for zip
                zip_path = rel_path.replace("\\", "/")
                zipf.write(full_path, zip_path)

    print(f"Created deployment ZIP: {zip_filename} ({os.path.getsize(zip_filename)} bytes)")

if __name__ == "__main__":
    package_project()
