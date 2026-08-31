# 1. Environment Setup

This guide covers the first-time setup for the MediaBrain local development environment. It is critical to follow these steps precisely to ensure the Nginx reverse proxy and all Docker containers operate correctly.

## 🐳 Docker Development Environment

Our local environment uses a multi-container Docker setup with an Nginx reverse proxy that handles SSL termination and routing.

### Container Architecture

The basic flow of a web request is:
`Internet → Nginx (Port 80/443) → MediaBrain Container (Port 8080) → Application`

- **`nginx`**: The reverse proxy. It handles SSL, routes domains, and optimizes static file serving.
- **`mediabrainapp-mediabrain-app-1`**: The main PHP/Apache application container for this project.
- **Other Containers**: The environment includes other supporting services like legacy PHP versions and MySQL databases.

## ⚙️ Initial Setup Steps

### Step 1: Clone the Repository

If you haven't already, clone the project to your local machine.

```bash
git clone https://github.com/jeffdavid8/mediabrain.net.git
cd mediabrain.net
```

### Step 2: Configure Your `hosts` File

You must map the local development domains to your loopback address. This is required for the Nginx proxy to route requests correctly.

1.  Open your `hosts` file with administrator privileges.
    -   **Windows**: `C:\Windows\System32\drivers\etc\hosts`
    -   **macOS/Linux**: `/etc/hosts`
2.  Add the following lines:

```
127.0.0.1       mediabrain.app.local
127.0.0.1       mediabrain.local
```

### Step 3: Start the Docker Environment

Launch all services using Docker Compose.

```bash
docker-compose up -d
```

The containers are configured with a specific startup order. The `up` command should handle this automatically.

### Step 4: Access the Application

Once the containers are running, access the application at the primary development URL:

-   **URL**: **https://mediabrain.app.local**
-   **Admin Interface**: `https://mediabrain.app.local/?app=admin`

**Note:** Always use the `https://` protocol. The server is configured to automatically redirect HTTP to HTTPS. Avoid using `http://localhost:8080`, as this bypasses the Nginx proxy and can lead to authentication and routing issues that don't exist in production.

## 🔧 Development Workflow

-   **Start Environment**: `docker-compose up -d`
-   **Check Status**: `docker ps` to ensure all containers are running.
-   **Make Changes**: Edit files in the `html/` directory. These are volume-mounted into the container, so changes are reflected instantly.
-   **Test Changes**: Simply refresh your browser. No container restart is needed for PHP file changes.
-   **Stop Environment**: `docker-compose down`
