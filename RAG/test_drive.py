import gdown
import os

DRIVE_FOLDER_ID = "1WXMzsx8tAeVNyPMJunEV6yrtL3VuPzP5"
OUTPUT_PATH = "sample_docs/"

os.makedirs(OUTPUT_PATH, exist_ok=True)

url = f"https://drive.google.com/drive/folders/{DRIVE_FOLDER_ID}"

print("Downloading documents from Google Drive...")
gdown.download_folder(url, output=OUTPUT_PATH, quiet=False)
print("Done! Files downloaded to:", OUTPUT_PATH)