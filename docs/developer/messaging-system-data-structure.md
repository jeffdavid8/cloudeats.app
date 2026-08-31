# Messaging System: JSON Data Structure

This document defines the JSON structure for a single message object in the file-based messaging system.

## Message Object (`message.json`)

Each message is stored as a separate JSON file. The structure is as follows:

```json
{
  "message_id": "msg_1a2b3c4d5e6f7g8h",
  "thread_id": "thread_9h8g7f6e5d4c3b2a",
  "sender_id": "user_12345",
  "recipients": [
    {
      "user_id": "user_67890",
      "status": "unread"
    },
    {
      "user_id": "user_54321",
      "status": "read"
    }
  ],
  "subject": "Project Update & Next Steps",
  "content": "Here is the latest update on the project...",
  "timestamp": "2025-11-30T14:30:00Z",
  "attachments": [
    {
      "file_id": "file_a1b2c3d4",
      "filename": "project-plan.pdf",
      "gcs_uri": "gs://mediabrain-attachments/msg_1a2b3c4d5e6f7g8h/project-plan.pdf",
      "size_bytes": 1048576,
      "mime_type": "application/pdf"
    }
  ]
}
```

### Field Definitions

- **`message_id`** (string, required): A unique identifier for the message (e.g., a UUID or a randomly generated string). This is also the filename (e.g., `msg_1a2b3c4d5e6f7g8h.json`).
- **`thread_id`** (string, required): A unique identifier that groups related messages together into a conversation thread.
- **`sender_id`** (string, required): The user ID of the person who sent the message.
- **`recipients`** (array of objects, required): A list of all users who received the message. Each recipient object contains:
    - **`user_id`** (string, required): The recipient's user ID.
    - **`status`** (string, required): The read status for that specific recipient. Can be `'read'` or `'unread'`.
- **`subject`** (string, optional): The subject line of the message.
- **`content`** (string, required): The main body of the message, in plain text or HTML.
- **`timestamp`** (string, required): An ISO 8601 formatted string representing when the message was sent.
- **`attachments`** (array of objects, optional): A list of files attached to the message. Each attachment object contains:
  - **`file_id`** (string, required): A unique ID for the attachment.
  - **`filename`** (string, required): The original name of the uploaded file.
  - **`gcs_uri`** (string, required): The full Google Cloud Storage URI where the attachment is stored.
  - **`size_bytes`** (integer, required): The size of the file in bytes.
  - **`mime_type`** (string, required): The MIME type of the file (e.g., `image/jpeg`, `application/pdf`).
