# OAuth Provider Setup Guide

This guide explains how to register your app with Google, Facebook, and Apple for OAuth login, and how to configure your credentials in MediaBrain.

---

## 1. Google OAuth Setup

**Register:**
- Go to [Google Cloud Console – Credentials](https://console.cloud.google.com/apis/credentials)
- Create a new project (if needed)
- Go to "APIs & Services" > "Credentials"
- Click "Create Credentials" > "OAuth client ID"
- Set application type to "Web application"
- Add authorized redirect URI:
  - `https://yourdomain.com/oauth/google.php?action=callback`
- Save and copy your **Client ID** and **Client Secret**

**Configure:**
Edit `C:\var\data\mediabrain\oauth_config.json` (Windows) or `/var/data/mediabrain/oauth_config.json` (Linux/Docker):

```json
"google": {
  "enabled": true,
  "client_id": "YOUR_GOOGLE_CLIENT_ID",
  "client_secret": "YOUR_GOOGLE_CLIENT_SECRET",
  "scopes": ["openid", "email", "profile"]
}
```

---

## 2. Facebook Login Setup

**Register:**
- Go to [Facebook for Developers – My Apps](https://developers.facebook.com/apps/)
- Click "Create App"
- Choose "Consumer" and follow the steps
- Add "Facebook Login" as a product
- Set up OAuth redirect URI:
  - `https://yourdomain.com/oauth/facebook.php?action=callback`
- Copy your **App ID** and **App Secret**

**Configure:**
Edit your config file:

```json
"facebook": {
  "enabled": true,
  "client_id": "YOUR_FACEBOOK_APP_ID",
  "client_secret": "YOUR_FACEBOOK_APP_SECRET",
  "scopes": ["email", "public_profile"]
}
```

---

## 3. Apple Sign In Setup

**Register:**
- Go to [Apple Developer Account](https://developer.apple.com/account/)
- Enroll in the Apple Developer Program (paid)
- Go to "Certificates, Identifiers & Profiles"
- Register a new "Identifier" for your app
- Configure "Sign In with Apple" and set up a "Service ID"
- Add your domain and redirect URI:
  - `https://yourdomain.com/oauth/apple.php?action=callback`
- Generate a key for Sign In with Apple and copy:
  - **Client ID**
  - **Team ID**
  - **Key ID**
  - Download the private key file (`.p8`)

**Configure:**
Edit your config file:

```json
"apple": {
  "enabled": true,
  "client_id": "YOUR_APPLE_CLIENT_ID",
  "team_id": "YOUR_APPLE_TEAM_ID",
  "key_id": "YOUR_APPLE_KEY_ID",
  "private_key_path": "C:\\var\\data\\mediabrain\\AuthKey_YOUR_KEY_ID.p8",
  "scopes": ["name", "email"]
}
```

---

## Notes
- All credentials go in `oauth_config.json`.
- The backend is already wired to use these values.
- After setup, your login and contact forms will support Google, Facebook, and Apple authentication.

---

For more details, see the source code in `html/includes/OAuthHandler.php`.
