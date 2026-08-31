<?php

namespace MediaBrain\Includes\Services;

use FileStorageManager;
use League\CommonMark\CommonMarkConverter;
use HTMLPurifier;
use HTMLPurifier_Config;
use Ramsey\Uuid\Uuid;

/**
 * Service class for handling internal messaging.
 */
class MessagingService
{
    private $storageManager;

    /**
     * MessagingService constructor.
     *
     * @param FileStorageManagerInterface $storageManager An instance of a file storage manager.
     */
    public function __construct()
    {
        $this->storageManager = FileStorageManager::getInstance();
    }


    /**
     * Sends a message to one or more recipients.
     *
     * This method creates the message data structure and saves it to the
     * appropriate directories for the sender and each recipient using the
     * FileStorageManager.
     *
     * @param string $senderId The user ID of the sender.
     * @param array $recipientIds An array of user IDs for the recipients.
     * @param string $subject The message subject.
     * @param string $content The message body.
     * @param array $attachments An array of attachment metadata.
     * @param string|null $threadId Optional thread ID for conversation grouping.
     * @return string|false The new message ID on success, or false on failure.
     */
    public function sendMessage(string $senderId, array $recipientIds, string $subject, string $content, array $attachments, ?string $threadId = null)
    {
        // 1. Generate unique IDs and timestamp
        $messageId = Uuid::uuid4()->toString();
        $threadId = $threadId ?? Uuid::uuid4()->toString();
        $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');

        // 2. Construct the message JSON object
        $recipients = [];
        foreach ($recipientIds as $id) {
            $recipients[] = ['user_id' => $id, 'status' => 'unread'];
        }

        $messageData = [
            'message_id' => $messageId,
            'thread_id' => $threadId,
            'sender_id' => $senderId,
            'recipients' => $recipients,
            'subject' => $subject,
            'content' => $content,
            'timestamp' => $timestamp,
            'attachments' => $attachments
        ];

        $messageJson = json_encode($messageData, JSON_PRETTY_PRINT);

        // 3. Save to sender's "sent" directory
        $sentPath = "messages/{$senderId}/sent/{$messageId}.json";
        if (!$this->storageManager->writeFile($messageJson, "messages/{$senderId}/sent", "{$messageId}.json")) {
            // Optionally log an error here
            error_log("Failed to write to sender's sent folder for message: {$messageId}");
            return false;
        }

        // 4. Save to each recipient's "inbox" directory
        foreach ($recipientIds as $recipientId) {
            $inboxPath = "messages/{$recipientId}/inbox/{$messageId}.json";
            if (!$this->storageManager->writeFile($messageJson, "messages/{$recipientId}/inbox", "{$messageId}.json")) {
                // Log error and potentially implement a rollback/cleanup mechanism
                error_log("Failed to write to recipient {$recipientId}'s inbox for message: {$messageId}");
                // For simplicity, we continue, but in a real app, you might want to handle this failure more gracefully.
            }
        }

        return $messageId;
    }

    public function getInboxMessages(string $userId)
    {
        $inboxPath = "messages/{$userId}/inbox";

        $result = $this->storageManager->listFiles($inboxPath);
        $messages = [];

        if ($result['success']) {
            foreach ($result['files'] as $fileInfo) {
                // continue if file is not a .json file
                if (!strstr($fileInfo['name'], '.json')) {
                    continue;
                }

                $filename = $fileInfo['name'];
                $fileReadResult = $this->storageManager->readFile($inboxPath, $filename);
                
                if ($fileReadResult['success']) {
                    $jsonContent = $fileReadResult['data'];
                    $data = json_decode($jsonContent, true);

                    // Find the recipient's status for this message
                    $recipientStatus = 'unknown';
                    if (isset($data['recipients']) && is_array($data['recipients'])) {
                        foreach ($data['recipients'] as $recipient) {
                            if ($recipient['user_id'] === $userId) {
                                $recipientStatus = $recipient['status'];
                                break;
                            }
                        }
                    }

                    // Create a summary object for the inbox view
                    $messages[] = [
                        'message_id' => $data['message_id'] ?? null,
                        'thread_id' => $data['thread_id'] ?? null,
                        'sender_id' => $data['sender_id'] ?? null,
                        'subject' => $data['subject'] ?? '(No Subject)',
                        'timestamp' => $data['timestamp'] ?? date('c'),
                        'status' => $recipientStatus,
                    ];
                }
            }
        }

        return $messages;
    }

    public function getMessage(string $userId, string $messageId)
    {
        $messagePath = "messages/{$userId}/inbox/{$messageId}.json";
        
        
        $fileReadResult = $this->storageManager->readFile("messages/{$userId}/inbox", "{$messageId}.json");

        if (!$fileReadResult['success']) {
            return false; // Message not found in user's inbox
        }

        $jsonContent = $fileReadResult['data'];
        $data = json_decode($jsonContent, true);
        $needsUpdate = false;

        // Find the current user in the recipients list and update their status if 'unread'
        foreach ($data['recipients'] as &$recipient) {
            if ($recipient['user_id'] === $userId && $recipient['status'] === 'unread') {
                $recipient['status'] = 'read';
                $needsUpdate = true;
                break;
            }
        }
        unset($recipient); // Unset the reference to avoid side effects

        // If the status was changed, overwrite the message file in the user's inbox
        if ($needsUpdate) {
            $updatedJson = json_encode($data, JSON_PRETTY_PRINT);
            if (!$this->storageManager->writeFile($updatedJson, "messages/{$userId}/inbox", "{$messageId}.json")) {
                // Log the error but still return the message content.
                // The user should be able to read the message even if the status update fails.
                error_log("Failed to update message status to 'read' for message: {$messageId}, user: {$userId}");
            }
        }

        // --- Process content for safe display ---

        // 1. Convert Markdown to HTML
        $converter = new CommonMarkConverter();
        $htmlContent = $converter->convert($data['content'] ?? '');

        // 2. Sanitize the HTML to prevent XSS
        $config = HTMLPurifier_Config::createDefault();
        // You can configure HTMLPurifier further here if needed (e.g., allow certain tags)
        $purifier = new HTMLPurifier($config);
        $safeHtml = $purifier->purify($htmlContent);

        // Replace the raw content with the processed, safe HTML
        $data['content'] = $safeHtml;

        return $data;
    }
}