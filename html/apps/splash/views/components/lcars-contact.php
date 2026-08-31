    <link rel="stylesheet" href="/themes/startrek/components.css">
    <link rel="stylesheet" href="/themes/startrek/dashboard.css">
    <link rel="stylesheet" href="/themes/startrek/lcars-base.css">
    <link rel="stylesheet" href="/themes/startrek/lcars-contact.css">
    <link href="https://fonts.googleapis.com/css?family=Orbitron:700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #000;
            color: #FF9900;
            font-family: 'Orbitron', monospace;
            margin: 0;
            min-height: 100vh;
        }
        .lcars-console {
            max-width: 480px;
            margin: 40px auto;
            background: #111;
            border: 4px solid #FF9900;
            border-radius: 16px;
            box-shadow: 0 0 32px #FF9900AA;
            padding: 32px 24px 24px 24px;
            position: relative;
        }
        .lcars-header {
            font-size: 2rem;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 24px;
            color: #66ff99;
            text-shadow: 0 0 8px #66ff99;
        }
        .lcars-panel {
            background: linear-gradient(90deg, #FF9900 0%, #6699FF 100%);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 18px;
            color: #222;
            font-weight: bold;
            box-shadow: 0 0 12px #FF990055;
        }
        .lcars-auth {
            display: flex;
            justify-content: space-around;
            margin-bottom: 18px;
        }
        .lcars-auth-btn {
            background: #222;
            color: #FF9900;
            border: 2px solid #6699FF;
            border-radius: 8px;
            font-size: 1.1rem;
            padding: 8px 18px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            box-shadow: 0 0 8px #6699FF99;
        }
        .lcars-auth-btn:hover {
            background: #FF9900;
            color: #222;
        }
        .lcars-console-form {
            margin-top: 12px;
        }
        .lcars-console-label {
            font-size: 1rem;
            color: #66ff99;
            margin-bottom: 4px;
            display: block;
        }
        .lcars-console-input, .lcars-console-textarea {
            width: 100%;
            background: #222;
            color: #FF9900;
            border: 2px solid #6699FF;
            border-radius: 6px;
            font-size: 1rem;
            padding: 8px;
            margin-bottom: 12px;
            font-family: 'Orbitron', monospace;
        }
        .lcars-console-textarea {
            min-height: 60px;
            resize: vertical;
        }
        .lcars-console-transmit {
            width: 100%;
            background: linear-gradient(90deg, #FF9900 0%, #6699FF 100%);
            color: #222;
            border: none;
            border-radius: 8px;
            font-size: 1.2rem;
            font-family: 'Orbitron', monospace;
            font-weight: bold;
            padding: 12px;
            margin-top: 8px;
            cursor: pointer;
            box-shadow: 0 0 16px #FF990099;
            transition: background 0.2s, color 0.2s;
        }
        .lcars-console-transmit:hover {
            background: #66ff99;
            color: #222;
        }
        .lcars-console-status {
            text-align: center;
            margin-top: 16px;
            font-size: 1.1rem;
            color: #66ff99;
            text-shadow: 0 0 8px #66ff99;
        }
    </style>
</head>
<body>
    <div class="lcars-console">
        <div class="lcars-header">Mediabrain Command Console</div>
        <div class="lcars-panel">Authenticate with Medabrain</div>
        <div class="lcars-auth">
            <button class="lcars-auth-btn" id="auth-google">Google</button>
            <button class="lcars-auth-btn" id="auth-apple">Apple</button>
            <button class="lcars-auth-btn" id="auth-facebook">Facebook</button>
        </div>
        <form class="lcars-console-form" id="lcarsContactForm">
            <label class="lcars-console-label" for="name">Name</label>
            <input class="lcars-console-input" type="text" id="name" name="name" required>
            <label class="lcars-console-label" for="email">Email</label>
            <input class="lcars-console-input" type="email" id="email" name="email" required>
            <label class="lcars-console-label" for="interest">Interest</label>
            <input class="lcars-console-input" type="text" id="interest" name="interest" placeholder="e.g. Partnership, Demo, Feedback">
            <label class="lcars-console-label" for="message">Message (optional)</label>
            <textarea class="lcars-console-textarea" id="message" name="message"></textarea>
            <button class="lcars-console-transmit" type="submit">Transmit to Mediabrain Command</button>
        </form>
        <div class="lcars-console-status" id="lcarsStatus"></div>
    </div>
    <script src="/themes/startrek/lcars-contact.js"></script>
