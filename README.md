# Purple Chat App (PHP + Workerman)

A scalable chat application using core PHP, Workerman for real-time WebSocket messaging, and a WhatsApp-inspired purple-themed UI.

## Structure
- `backend/` — Core PHP REST APIs
- `ws-server/` — Workerman WebSocket server
- `frontend/` — HTML/CSS/JS frontend

## Setup
1. Install dependencies: `composer require workerman/workerman`
2. Set up a MySQL database (see `backend/config.php`)
3. Start the WebSocket server: `php ws-server/chat-server.php start`
4. Serve the frontend (e.g., via Apache, Nginx, or PHP built-in server)

## Features
- Real-time messaging
- AJAX APIs for user and message management
- Responsive, WhatsApp-like UI (purple theme)

---

For production, add authentication, validation, and security best practices.
