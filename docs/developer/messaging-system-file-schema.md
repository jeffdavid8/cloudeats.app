# Internal Messaging System: File & JSON Schema

This document outlines the file-based storage architecture for the internal messaging application. All data is stored as individual JSON files on a cloud file service (like Google Cloud Storage) accessed via the project's `FileStorageManager`.

---

## 1. Directory Structure

The messages are organized in a hierarchical structure to ensure messages are segregated by user and mailbox, preventing data collision and simplifying access control and retrieval.

The base path for all messages will be `messages/`.

```
messages/
└── {user_id}/
    ├── inbox/
    │   ├── {message_id_1}.json
    │   ├── {message_id_2}.json
    │   └── ...
    └── sent/
        ├── {message_id_3}.json
        ├── {message_id_4}.json
        └── ...
```

- **`{user_id}`**: The unique identifier for a user. This ensures a user can only access their own message directories.
- **`inbox/`**: Contains all messages received by the user.
- **`sent/`**: Contains all messages sent by the user.
- **`{message_id}.json`**: A uniquely named JSON file representing a single message. The `message_id` should be a unique identifier, such as a UUID or a timestamp combined with a random element.

File attachments will be stored in a separate top-level directory:

```
attachments/
└── {message_id}/
    ├── {original_filename_1.ext}
    └── {original_filename_2.ext}
```

- **`{message_id}`**: A directory corresponding to the ID of the message the files are attached to. This keeps all attachments for a single message grouped together.

---

## 2. Message JSON Schema

Each `.json` file in the `inbox` and `sent` directories will contain a single message object with the following structure. The `status` field may differ between the inbox and sent copies of the same message.

```json
{
  "message_id": "a-unique-identifier-string",
  "sender_id": "user-123",
  "sender_username": "john.doe",
  "recipients": [
    {
      "user_id": "user-456",
      "username": "jane.doe"
    },
    {
      "user_id": "user-789",
      "username": "admin"
    }
  ],
  "subject": "Project Update",
  "content": "Here is the latest update on the project status. Please review the attached documents.",
  "timestamp": "2025-11-30T10:00:00Z",
  "status": "unread",
  "attachments": [
    {
      "filename": "project-brief.pdf",
      "file_uri": "attachments/a-unique-identifier-string/project-brief.pdf",
      "filesize": 102400,
      "filetype": "application/pdf"
    }
  ]
}
```

### Field Definitions

| Field | Type | Description |
|---|---|---|
| `message_id` | String | A unique identifier for the message (e.g., UUID). |
| `sender_id` | String | The `user_id` of the message sender. |
| `sender_username` | String | The username of the sender, denormalized for easy display. |
| `recipients` | Array of Objects | A list of all users who will receive the message. |
| `recipients[].user_id` | String | The `user_id` of a recipient. |
| `recipients[].username`| String | The username of a recipient, for display purposes. |
| `subject` | String | The message subject line. Can be an empty string. |
| `content` | String | The main body of the message (plain text or HTML). |
| `timestamp` | String | ISO 8601 formatted timestamp of when the message was sent. |
| `status` | String | For messages in the `inbox`, the status is either `'unread'` or `'read'`. In the `sent` folder, this field may not be necessary or could be static (`'sent'`). |
| `attachments` | Array of Objects | A list of files attached to the message. |
| `attachments[].filename`| String | The original display name of the attached file. |
| `attachments[].file_uri`| String | The full path/URI to the file in cloud storage. |
| `attachments[].filesize`| Number | The file size in bytes. |
| `attachments[].filetype`| String | The MIME type of the file. |
