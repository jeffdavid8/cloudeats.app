PROMPT: NEIGHBORHUB FILE UPLOAD API ROUTER CASE
Act as a Principal Backend Engineer. Update the primary API router:
/html/apps/neighborhub/neighborhub.api.php

Add a new action switch rule: case 'upload_image':

It must handle the following execution pipeline:
1. Validate Request: Verify that $_FILES['image'] is present and contains zero upload errors.
2. Authenticate: Ensure the active user is logged in. If uploading a merchant logo, verify their email matches the merchant staff owner roster.
3. Instantiate Storage: Grab an instance of FileStorageManager (html\includes\storage\FileStorageManager.php). 
4. Execute Upload:
   - Determine target context path: if $_POST['type'] === 'product', use 'apps/neighborhub/products/${merchant_id}/'. If 'merchant', use 'apps/neighborhub/merchants/${merchant_id}/'.
   - Generate a unique cryptographically secure filename (e.g., using bin2hex(random_bytes(16)) + extension).
   - Pass the $_FILES['image'] payload to $storageManager->uploadFile() with process_image options enabled to optimize dimensions to a clean web standard (e.g., maximum 800px width, converted to webp or jpeg).
5. Output Public Target URL: Upon successful completion, return a structured JSON string outputting the persistent storage public URL path:
   {
     "success": true,
     "public_url": "https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/products/unique_filename.jpg"
   }
   