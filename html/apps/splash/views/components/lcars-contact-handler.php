<?php
// LCARS Contact Form Handler (backend)
// This is a stub for AJAX POST requests from lcars-contact.php
// TODO: Integrate SendGrid, Gmail API, or SMTP for real email delivery

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $interest = $_POST['interest'] ?? '';
    $message = $_POST['message'] ?? '';

    // Validate required fields
    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Name and email required.']);
        exit;
    }

    // Compose email
    $to = 'yourgmailaddress@gmail.com'; // TODO: Replace with your Gmail
    $subject = "Mediabrain Inquiry: $interest";
    $body = "Name: $name\nEmail: $email\nInterest: $interest\nMessage: $message";
    $headers = "From: $email\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";

    // For demo, just return success
    // TODO: Use mail(), SendGrid, or Gmail API for real delivery
    // mail($to, $subject, $body, $headers);
    echo json_encode(['status' => 'success', 'message' => 'Transmission sent! Medabrain will respond soon.']);
    exit;
}
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
