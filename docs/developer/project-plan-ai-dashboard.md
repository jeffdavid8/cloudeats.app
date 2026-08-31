# Project Plan: AI Researcher & Real-Time Dashboard

This document outlines the development steps for two new portfolio-piece applications: the AI Research Assistant and the Real-Time Collaborative Dashboard.

Each task is presented as a checklist item. As you complete a task, you can mark it as done by changing `[ ]` to `[x]`.

---

## 🚀 Project 1: Internal Messaging System (File-Based)

*Goal: Create a robust, internal messaging application using a serverless, file-based storage system. All data will be stored as JSON files on Google Cloud Storage, managed via the project's `FileStorageManager`.*

### Phase 1: Backend & Data Structure
- [x] **Define JSON Data Structure**: The data structure for messages is finalized and documented in [`messaging-system-data-structure.md`](./messaging-system-data-structure.md).
- [x] **Define Directory Structure**: The GCS storage layout is documented in [`messaging-system-directory-structure.md`](./messaging-system-directory-structure.md).
- [x] **Create Messaging Service Class**: The `MessagingService.php` class has been created in `html/includes/services/`.
- [x] **Implement Core Service Logic**:
    - `sendMessage()`: Creates the message JSON, and saves it to the sender's "sent" directory and each recipient's "inbox" directory on GCS.
    - `getInboxMessages(userId)`: Lists JSON files in a user's inbox directory, reads their contents, and returns a summary.
    - `getMessage(userId, messageId)`: Reads a specific message JSON file and updates its status to "read" by overwriting the file.
- [x] **API Endpoint Scaffolding**: In a relevant API file, create the actions (`send_message`, `get_messages`, etc.) to interface with the new `MessagingService`.

### Phase 2: Core UI & Functionality
- [x] **Create App Module**: Create the directory `html/apps/messages/` with standard app files: `messages.app.php`, and a `views/` directory.
- [x] **Integrate into Main Menu**: Add the new "Messages" app to the main application navigation menu to make it accessible.
- [x] **Develop Messaging UI**: Create the main view for the messaging system in `html/apps/messages/views/pages/main.php`. The UI should include:
    - An inbox view page to list received messages.
    - A message detail view.
    - A compose view (/apps/messages/views/modals/compose.php) with fields for recipient, subject, and message body.
- [x] **Frontend-Backend Integration**: Write the necessary JavaScript to connect the UI to the backend API for sending, receiving, and reading messages.

### Phase 3: Advanced Features & Polish
- [ ] **File Attachments with Google Cloud Storage**:
    - [ ] Extend the "Compose" UI to include a file input.
    - [ ] Use the `FileStorageManager` to upload the attachment to a separate GCS path (e.g., `attachments/{message_id}/{filename}`).
    - [ ] Add a reference to the attachment's GCS URI in the message's JSON data.
- [ ] **User Search**: Implement a way to search/list users for the "recipient" field, likely by reading the main project `users.json` file.
- [ ] **Performance & Caching**: Since listing and reading many files from GCS for every request can be slow, implement a server-side caching layer (e.g., using a temporary local file or a more advanced in-memory cache if available) for user inboxes to improve performance.
- [ ] **UI/UX Polish**: Refine the user interface, add loading indicators for API calls, and provide clear feedback for success and error states.
- [ ] **Documentation**: Create a `README.md` for the messaging system, explaining its API and file-based architecture.
---

## 🚀 Project 2: AI Research Assistant App ("Researcher")

*Goal: Create a new app that uses a large language model (LLM) to perform research, summarize articles, and generate reports, showcasing AI/LLM integration skills.*

### Phase 1: Backend Scaffolding & Service
- [x] **Create App Module**: Create the directory `html/apps/researcher/`.
- [x] **Create Core App Files**: Inside the new directory, create the standard app files: `researcher.app.php`, `researcher.api.php`, and a basic `views/main.php`.
- [x] **Add to Menu**: Integrate the new app into the main navigation menu so it's accessible.
- [x] **Create AI Service Class**: Create a new PHP class `html/includes/services/AIService.php`. This class will be responsible for all communication with the external AI API (e.g., Google Gemini).
- [x] **Implement AI Service Methods**: In `AIService.php`, implement initial methods:
    - [x] `__construct()`: To handle API key and client setup.
    - [x] `generateText(prompt)`: A basic method to send a prompt to the LLM and get a response.

### Phase 2: API and UI Core
- [x] **Create API Endpoint**: In `researcher.api.php`, add a new `switch` statement and create a `case` for `start_research`.
- [x] **Connect API to Service**: In the `start_research` case, instantiate `AIService` and call the `generateText` method with a test prompt. Return the AI's response as JSON.
- [x] **Build Basic UI**: In `views/main.php`, create a simple UI with:
    - [x] A `<textarea>` for the user to enter their research topic.
    - [x] A `<button>` to submit the research request.
    - [x] A `<div>` to display the results.
- [x] **Frontend JavaScript**: Write JavaScript to:
    - [x] Handle the button click.
    - [x] Use the `mb.ajax()` helper to call the `/?api=researcher&action=start_research` endpoint.
    - [x] Display the JSON response in the results `<div>`.

### Phase 3: Advanced Features & Polish
- [x] **Asynchronous Task Handling**: For long research tasks, modify the API to start a background job instead of blocking.
    - [x] The `start_research` action could write the task to a queue (e.g., a database table or a file-based queue).
    - [ ] A separate script (e.g., a cron job or a manual trigger) would process the queue.
- [x] **Real-time Progress Updates**:
    - [x] Create a new API endpoint `action=get_research_status`.
    - [x] Have the frontend poll this endpoint to show live progress (e.g., "Searching...", "Summarizing...", "Done.").
- [x] **Web Search Integration**: Enhance `AIService` with a method that can perform a web search (e.g., using Google Custom Search API) to find relevant articles.
- [x] **Multi-Step Agent Logic**: Create a master function in `AIService` that orchestrates the entire research process:
    1.  Take a topic.
    2.  Search the web for relevant URLs.
    3.  For each URL, call the LLM to summarize the content.
    4.  Combine the summaries into a final report.
    5.  Save the report.

---

## 🚀 Project 3: Real-Time Collaborative Dashboard App ("Dashboard")

*Goal: Create a Trello-like project board that uses WebSockets for real-time collaboration, showcasing modern, interactive frontend skills.*

### Phase 1: Backend Scaffolding & Data Model
- [x] **Create App Module**: Create the directory `html/apps/dashboard/` with `dashboard.app.php`, `dashboard.api.php`, and `views/main.php`.
- [x] **Add to Menu**: Add the "Dashboard" app to the main navigation menu.
- [x] **Define Data Structure**: Plan the JSON structure for your data. This will likely be stored in `html/json/dashboards.json`.
    - Example: An object containing `boards`, where each board has `columns`, and each column has `cards`.
- [x] **Create CRUD API Endpoints**: In `dashboard.api.php`, create the basic API actions for non-real-time functionality:
    - [x] `get_board`: Fetch the data for a specific board.
    - [ ] `add_card`: Add a new card to a column.
    - [ ] `move_card`: Update the column and position of a card.
    - [ ] `update_card_text`: Change the text of a card.

### Phase 2: Core UI Development
- [x] **Build Board UI**: In `views/main.php`, use HTML/CSS to create the visual layout of a Trello-like board with columns and cards.
- [x] **Frontend JavaScript (No Real-Time Yet)**: Write JavaScript to:
    - [x] Fetch board data using `mb.ajax()` and the `get_board` action.
    - [x] Dynamically render the columns and cards based on the fetched data.
    - [ ] Implement drag-and-drop functionality for cards (e.g., using a library like SortableJS).
    - [ ] When a card is moved, call the `move_card` API endpoint to save the new state.
- [ ] **Test Core Functionality**: Ensure you can create, move, and edit cards, and that the changes persist after a page reload.

### Phase 3: Real-Time WebSocket Integration
- [ ] **Choose & Install WebSocket Server**:
    - [ ] **Option A (PHP-based)**: Install a PHP WebSocket library like `ratchet/pawl`.
    - [ ] **Option B (Cloud Service)**: Sign up for a free tier of a service like Pusher or Ably and get your API keys.
- [ ] **Create WebSocket Server Script**: Create a script (e.g., `websocket-server.php`) that starts the WebSocket server. This server will handle connections and broadcast messages.
- [ ] **Integrate API with WebSockets**: Modify your API endpoints in `dashboard.api.php`. After a successful database update, publish a message to the WebSocket server.
    - Example: After `move_card` successfully updates the JSON file, it should send a message like `{'event': 'card-moved', 'data': ...}` to the WebSocket server, which then broadcasts it.
- [ ] **Connect Frontend to WebSockets**: In your dashboard's JavaScript:
    - [ ] Establish a connection to your WebSocket server.
    - [ ] Add a listener that waits for messages from the server (e.g., `card-moved`).
    - [ ] When a message is received, update the UI dynamically *without* a full page reload (e.g., move the card element in the DOM).
- [ ] **Test Real-Time Sync**: Open the same board in two different browser windows. Move a card in one window and verify that it moves instantly in the second window.
