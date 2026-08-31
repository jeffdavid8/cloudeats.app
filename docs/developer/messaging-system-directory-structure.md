# Messaging System: Directory Structure

This document outlines the proposed directory structure for storing message-related data on Google Cloud Storage (GCS). The structure is designed to be user-centric, ensuring that data is organized logically and can be efficiently queried.

## GCS Bucket Root

All messaging data will be stored within a dedicated folder (e.g., `messages/`) at the root of the GCS bucket.

```
gs://[YOUR_BUCKET_NAME]/messages/
```

## Proposed Structure

```
messages/
└───{user_id}/
    ├───inbox/
    │   ├───{message_id_1}.json
    │   └───{message_id_2}.json
    ├───sent/
    │   ├───{message_id_3}.json
    │   └───{message_id_4}.json
    └───drafts/
        └───{draft_id_1}.json
```

### Directory Definitions

-   **`messages/`**: The root directory for all messaging data.
-   **`{user_id}/`**: A directory for each user, named after their unique user ID. This isolates user data and simplifies permission management.
-   **`inbox/`**: Contains all messages received by the user.
    -   `{message_id}.json`: A JSON file representing a single message, named after its unique message ID.
-   **`sent/`**: Contains copies of all messages sent by the user. This provides a clear record of sent items.
    -   `{message_id}.json`: A JSON file representing a sent message.
-   **`drafts/`**: Contains messages that have been composed but not yet sent.
    -   `{draft_id}.json`: A JSON file for a saved draft.

## Attachment Storage

File attachments will be stored in a separate directory to keep them organized and decoupled from the message JSON.

```
attachments/
└───{message_id}/
    └───{attachment_filename}
```

-   **`attachments/`**: The root directory for all file attachments.
-   **`{message_id}/`**: A directory for each message, named after the message ID it belongs to. This ensures that attachments from different messages do not have naming conflicts.
-   **`{attachment_filename}`**: The original filename of the uploaded attachment.

This structure allows for easy retrieval of all attachments for a specific message. The full GCS URI to the attachment will be stored in the message's JSON file.
