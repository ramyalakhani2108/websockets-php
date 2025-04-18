// --- Purple Chat: Clean Modern Frontend ---
let ws = null;
let currentUser = null;
let selectedUser = null;
let users = [];
let unreadCounts = {};
let latestMessages = {};

// --- UI Rendering ---
function renderLogin() {
    document.body.innerHTML = `
    <div class="login-container">
        <h2>Purple Chat Login</h2>
        <input id="login-username" placeholder="Username" autocomplete="username">
        <input id="login-password" type="password" placeholder="Password" autocomplete="current-password">
        <button id="login-btn">Login</button>
        <button id="register-btn">Register</button>
        <div id="login-error" style="color:crimson;margin-top:10px;"></div>
    </div>`;
    document.getElementById('login-btn').onclick = doLogin;
    document.getElementById('register-btn').onclick = doRegister;
}

function renderApp() {
    document.body.innerHTML = `
    <div class="chat-container">
        <aside class="sidebar">
            <div class="sidebar-header">Purple Chat
                <button onclick="logout()" style="float:right;background:none;border:none;color:#fff;font-size:0.8em;cursor:pointer;">Logout</button>
            </div>
            <ul id="user-list"></ul>
        </aside>
        <main class="chat-main">
            <header class="chat-header">
                <span id="current-user">Select a user</span>
            </header>
            <div class="chat-messages" id="chat-messages"></div>
            <form id="message-form">
                <input type="text" id="message-input" placeholder="Type a message...">
                <button type="submit">Send</button>
            </form>
        </main>
    </div>`;
    document.getElementById('message-form').addEventListener('submit', sendMessage);
    loadUsers();
    connectWS();
}

// --- Auth ---
function doLogin() {
    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;
    fetch('../backend/api/users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'login', username, password})
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            document.getElementById('login-error').textContent = data.error;
        } else {
            localStorage.setItem('currentUser', JSON.stringify(data));
            location.reload();
        }
    });
}

function doRegister() {
    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;
    fetch('../backend/api/users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'register', username, password})
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            document.getElementById('login-error').textContent = data.error;
        } else {
            localStorage.setItem('currentUser', JSON.stringify(data));
            location.reload();
        }
    });
}

function logout() {
    localStorage.removeItem('currentUser');
    location.reload();
}

// --- User List ---
function loadUsers() {
    fetch('../backend/api/users.php')
        .then(res => res.json())
        .then(data => {
            users = data;
            renderUserList();
        });
}

function renderUserList() {
    const list = document.getElementById('user-list');
    if (!list) return;
    list.innerHTML = '';
    users.forEach(user => {
        if (parseInt(user.id) === parseInt(currentUser.id)) return;
        const li = document.createElement('li');
        li.classList.add('user-list-item');
        li.onclick = () => selectUser(user);
        if (selectedUser && parseInt(user.id) === parseInt(selectedUser.id)) li.classList.add('active');
        const nameSpan = document.createElement('span');
        nameSpan.textContent = user.username;
        li.appendChild(nameSpan);
        if (latestMessages[user.id]) {
            const preview = document.createElement('div');
            preview.textContent = latestMessages[user.id];
            preview.className = 'message-preview';
            preview.style.fontSize = '0.9em';
            preview.style.color = '#a1a1aa';
            preview.style.marginTop = '2px';
            li.appendChild(preview);
        }
        if (unreadCounts[user.id]) {
            const badge = document.createElement('span');
            badge.textContent = unreadCounts[user.id];
            badge.style.background = '#a78bfa';
            badge.style.color = '#fff';
            badge.style.borderRadius = '10px';
            badge.style.fontSize = '0.8em';
            badge.style.padding = '2px 8px';
            badge.style.marginLeft = '8px';
            li.appendChild(badge);
        }
        list.appendChild(li);
    });
}

function selectUser(user) {
    selectedUser = user;
    document.getElementById('current-user').textContent = user.username;
    unreadCounts[user.id] = 0;
    renderUserList();
    loadMessages();
}

// --- Chat History ---
function loadMessages() {
    if (!selectedUser) return;
    fetch(`../backend/api/messages.php?from_id=${currentUser.id}&to_id=${selectedUser.id}`)
        .then(res => res.json())
        .then(messages => {
            const chat = document.getElementById('chat-messages');
            chat.innerHTML = '';
            messages.forEach(msg => appendMessage(msg));
            if (messages.length > 0) {
                const lastMsg = messages[messages.length-1];
                const otherId = parseInt(lastMsg.from_id) === parseInt(currentUser.id) ? lastMsg.to_id : lastMsg.from_id;
                latestMessages[otherId] = lastMsg.message;
                renderUserList();
            }
        });
}

// --- Send Message ---
function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    if (!message || !selectedUser) return;
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    // Send via WebSocket (always JSON)
    if (ws && ws.readyState === 1) {
        ws.send(JSON.stringify({
            type: 'chat',
            from_id: currentUser.id,
            to_id: selectedUser.id,
            message,
            time
        }));
    }
    // Store in DB via AJAX
    fetch('../backend/api/messages.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({from_id: currentUser.id, to_id: selectedUser.id, message})
    });
    input.value = '';
}

// --- Append Message ---
function appendMessage(msg) {
    if (!selectedUser) return;
    const isCurrentChat = (
        (parseInt(msg.from_id) === parseInt(currentUser.id) && parseInt(msg.to_id) === parseInt(selectedUser.id)) ||
        (parseInt(msg.from_id) === parseInt(selectedUser.id) && parseInt(msg.to_id) === parseInt(currentUser.id))
    );
    if (!isCurrentChat) return;
    const chat = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.className = 'message ' + (parseInt(msg.from_id) === parseInt(currentUser.id) ? 'sent' : 'received');
    div.innerHTML = `<div class="message-content">${msg.message}</div><span class="message-time">${msg.time}</span>`;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

// --- WebSocket Connection ---
function connectWS() {
    if (ws) ws.close();
    ws = new WebSocket('ws://localhost:2346');
    ws.onopen = function() {
        // Identify user to server
        ws.send(JSON.stringify({type: 'login', user_id: currentUser.id}));
    };
    ws.onmessage = function(event) {
        try {
            const msg = JSON.parse(event.data);
            if (msg.type === 'chat') {
                const otherId = parseInt(msg.from_id) === parseInt(currentUser.id) ? msg.to_id : msg.from_id;
                latestMessages[otherId] = msg.message;
                renderUserList();
                if (
                    selectedUser && (
                        (parseInt(msg.from_id) === parseInt(currentUser.id) && parseInt(msg.to_id) === parseInt(selectedUser.id)) ||
                        (parseInt(msg.from_id) === parseInt(selectedUser.id) && parseInt(msg.to_id) === parseInt(currentUser.id))
                    )
                ) {
                    appendMessage(msg);
                } else if (parseInt(msg.to_id) === parseInt(currentUser.id)) {
                    unreadCounts[msg.from_id] = (unreadCounts[msg.from_id] || 0) + 1;
                    renderUserList();
                }
            }
        } catch (err) {
            console.error('[WebSocket] Invalid JSON received:', event.data);
        }
    };
    ws.onerror = function(e) {
        console.error('[WebSocket] Error:', e);
    };
    ws.onclose = function() {
        setTimeout(connectWS, 2000); // Reconnect after 2s
    };
}

// --- Entry Point ---
(function() {
    const user = localStorage.getItem('currentUser');
    if (user) {
        currentUser = JSON.parse(user);
        renderApp();
    } else {
        renderLogin();
    }
})();


let ws;
let currentUser = null;
let selectedUser = null;

function showLogin() {
    document.body.innerHTML = `
    <div class="login-container">
        <h2>Purple Chat Login</h2>
        <input id="login-username" placeholder="Username" autocomplete="username">
        <input id="login-password" type="password" placeholder="Password" autocomplete="current-password">
        <button id="login-btn">Login</button>
        <button id="register-btn">Register</button>
        <div id="login-error" style="color:crimson;margin-top:10px;"></div>
    </div>`;
    document.getElementById('login-btn').onclick = doLogin;
    document.getElementById('register-btn').onclick = doRegister;
}

function doLogin() {
    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;
    fetch('../backend/api/users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'login', username, password})
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            document.getElementById('login-error').textContent = data.error;
        } else {
            localStorage.setItem('currentUser', JSON.stringify(data));
            location.reload();
        }
    });
}

function doRegister() {
    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;
    fetch('../backend/api/users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'register', username, password})
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            document.getElementById('login-error').textContent = data.error;
        } else {
            localStorage.setItem('currentUser', JSON.stringify(data));
            location.reload();
        }
    });
}

function logout() {
    localStorage.removeItem('currentUser');
    location.reload();
}

// Load user list
let unreadCounts = {};
let latestMessages = {};

function loadUsers() {
    fetch('../backend/api/users.php')
        .then(res => res.json())
        .then(users => {
            const list = document.getElementById('user-list');
            list.innerHTML = '';
            users.forEach(user => {
                if (parseInt(user.id) === parseInt(currentUser.id)) return; // skip self
                const li = document.createElement('li');
                li.classList.add('user-list-item');
                li.onclick = () => selectUser(user);
                if (selectedUser && parseInt(user.id) === parseInt(selectedUser.id)) li.classList.add('active');
                // Username
                const nameSpan = document.createElement('span');
                nameSpan.textContent = user.username;
                li.appendChild(nameSpan);
                // Latest message preview
                if (latestMessages[user.id]) {
                    const preview = document.createElement('div');
                    preview.textContent = latestMessages[user.id];
                    preview.className = 'message-preview';
                    preview.style.fontSize = '0.9em';
                    preview.style.color = '#a1a1aa';
                    preview.style.marginTop = '2px';
                    li.appendChild(preview);
                }
                // Add badge if unread
                if (unreadCounts[user.id]) {
                    const badge = document.createElement('span');
                    badge.textContent = unreadCounts[user.id];
                    badge.style.background = '#a78bfa';
                    badge.style.color = '#fff';
                    badge.style.borderRadius = '10px';
                    badge.style.fontSize = '0.8em';
                    badge.style.padding = '2px 8px';
                    badge.style.marginLeft = '8px';
                    li.appendChild(badge);
                }
                list.appendChild(li);
            });
        });
}

function selectUser(user) {
    selectedUser = user;
    document.getElementById('current-user').textContent = user.username;
    unreadCounts[user.id] = 0;
    loadUsers(); // update badges
    loadMessages();
}

// Load chat history
function loadMessages() {
    if (!selectedUser) return;
    fetch(`../backend/api/messages.php?from_id=${currentUser.id}&to_id=${selectedUser.id}`)
        .then(res => res.json())
        .then(messages => {
            const chat = document.getElementById('chat-messages');
            chat.innerHTML = '';
            messages.forEach(msg => {
                appendMessage(msg);
            });
            // Update preview for this chat
            if (messages.length > 0) {
                const lastMsg = messages[messages.length - 1];
                const otherId = parseInt(lastMsg.from_id) === parseInt(currentUser.id) ? lastMsg.to_id : lastMsg.from_id;
                latestMessages[otherId] = lastMsg.message;
                loadUsers();
            }
        });
}

// Send message
function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    if (!message || !selectedUser) return;
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    // Send via WebSocket (only JSON, never 'msg' or other plain strings)
    if (ws && ws.readyState === 1) {
        ws.send(JSON.stringify({
            type: 'chat',
            from_id: currentUser.id,
            to_id: selectedUser.id,
            message,
            time
        }));
    }
    // Store in DB via AJAX
    fetch('../backend/api/messages.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({from_id: currentUser.id, to_id: selectedUser.id, message})
    });
    input.value = '';
}

document.getElementById('message-form').addEventListener('submit', sendMessage);

// WebSocket connection
function connectWS() {
    ws = new WebSocket('ws://localhost:2346');
    ws.onopen = function() {
        // Identify user to server
        ws.send(JSON.stringify({type: 'login', user_id: currentUser.id}));
    };
    ws.onmessage = function(event) {
        console.log('[WebSocket] Received:', event.data);
        const msg = JSON.parse(event.data);
        if (msg.type === 'chat') {
            // Always update latest message preview
            const otherId = parseInt(msg.from_id) === parseInt(currentUser.id) ? msg.to_id : msg.from_id;
            latestMessages[otherId] = msg.message;
            loadUsers();
            // If chat with sender/recipient is open, append immediately
            if (
                selectedUser && (
                    (parseInt(msg.from_id) === parseInt(currentUser.id) && parseInt(msg.to_id) === parseInt(selectedUser.id)) ||
                    (parseInt(msg.from_id) === parseInt(selectedUser.id) && parseInt(msg.to_id) === parseInt(currentUser.id))
                )
            ) {
                console.log('[WebSocket] Appending message to open chat:', msg);
                appendMessage(msg);
            } else if (parseInt(msg.to_id) === parseInt(currentUser.id)) {
                // If message is for this user but not in open chat, increment badge
                unreadCounts[msg.from_id] = (unreadCounts[msg.from_id] || 0) + 1;
                loadUsers();
                console.log('[WebSocket] Message for another chat, badge incremented:', msg);
            }
        }
    };


}

function appendMessage(msg) {
    // Only append if chat is open with this user
    if (!selectedUser) return;
    const isCurrentChat = (
        (parseInt(msg.from_id) === parseInt(currentUser.id) && parseInt(msg.to_id) === parseInt(selectedUser.id)) ||
        (parseInt(msg.from_id) === parseInt(selectedUser.id) && parseInt(msg.to_id) === parseInt(currentUser.id))
    );
    if (!isCurrentChat) {
        // Not current chat, do not append
        return;
    }
    const chat = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.className = 'message ' + (parseInt(msg.from_id) === parseInt(currentUser.id) ? 'sent' : 'received');
    div.innerHTML = `<div class="message-content">${msg.message}</div><span class="message-time">${msg.time}</span>`;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
    console.log('[appendMessage] Appended message:', msg);
}

// Dummy login for demo
// Main app loader
function loadApp() {
    document.body.innerHTML = `
    <div class="chat-container">
        <aside class="sidebar">
            <div class="sidebar-header">Purple Chat
                <button onclick="logout()" style="float:right;background:none;border:none;color:#fff;font-size:0.8em;cursor:pointer;">Logout</button>
            </div>
            <ul id="user-list"></ul>
        </aside>
        <main class="chat-main">
            <header class="chat-header">
                <span id="current-user">Select a user</span>
            </header>
            <div class="chat-messages" id="chat-messages"></div>
            <form id="message-form">
                <input type="text" id="message-input" placeholder="Type a message...">
                <button type="submit">Send</button>
            </form>
        </main>
    </div>`;
    document.getElementById('message-form').addEventListener('submit', sendMessage);
    loadUsers();
    connectWS();
}

// Entry point
(function() {
    const user = localStorage.getItem('currentUser');
    if (user) {
        currentUser = JSON.parse(user);
        loadApp();
    } else {
        showLogin();
    }
})();
