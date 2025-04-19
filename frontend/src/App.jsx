import React, { useEffect, useRef, useState } from 'react';
import './App.css';

const WS_URL = 'ws://localhost:2346';
const API_BASE = 'http://localhost/websockets-php/backend/api';

function App() {
  // Auth/User state
  const [currentUser, setCurrentUser] = useState(() => {
    const stored = localStorage.getItem('currentUser');
    return stored ? JSON.parse(stored) : null;
  });
  const [users, setUsers] = useState([]);
  const [selectedUser, setSelectedUser] = useState(null);
  const [messages, setMessages] = useState([]);
  const [message, setMessage] = useState('');
  const [loginState, setLoginState] = useState({ username: '', password: '', error: '', isRegister: false });
  const [wsStatus, setWsStatus] = useState('DISCONNECTED');
  const [notifications, setNotifications] = useState({});
  const wsRef = useRef(null);
  const chatEndRef = useRef(null);

  // Fetch user list
  useEffect(() => {
    if (currentUser) {
      fetch(`${API_BASE}/users.php`)
        .then(res => res.json())
        .then(data => setUsers(data));
    }
  }, [currentUser]);

  // Fetch messages when chat changes
  useEffect(() => {
    if (currentUser && selectedUser) {
      fetch(`${API_BASE}/messages.php?from_id=${currentUser.id}&to_id=${selectedUser.id}`)
        .then(res => res.json())
        .then(data => setMessages(data));
    }
  }, [currentUser, selectedUser]);

  // WebSocket connection
  useEffect(() => {
    if (!currentUser) return;
    let ws = new window.WebSocket(WS_URL);
    wsRef.current = ws;
    ws.onopen = () => {
      setWsStatus('CONNECTED');
      console.log('[WebSocket] Connected. currentUser:', currentUser);
      const loginMsg = { type: 'login', user_id: currentUser.id };
      console.log('[WebSocket] Sending login message:', loginMsg);
      ws.send(JSON.stringify(loginMsg));
    };
    ws.onclose = () => setWsStatus('DISCONNECTED');
    ws.onerror = () => setWsStatus('ERROR');
    ws.onmessage = (event) => {
      console.log('[WebSocket] Received:', event.data); // Debug log for all incoming messages
      try {
        const msg = JSON.parse(event.data);
        if (msg.type === 'chat') {
          // Real-time: If chat with sender/recipient is open, append immediately
          if (
            selectedUser &&
            ((parseInt(msg.from_id) === parseInt(currentUser.id) && parseInt(msg.to_id) === parseInt(selectedUser.id)) ||
              (parseInt(msg.from_id) === parseInt(selectedUser.id) && parseInt(msg.to_id) === parseInt(currentUser.id)))
          ) {
            setMessages((prev) => [...prev, msg]);
          } else if (parseInt(msg.to_id) === parseInt(currentUser.id)) {
            // Show notification badge for sender
            setNotifications(prev => ({ ...prev, [msg.from_id]: (prev[msg.from_id] || 0) + 1 }));
            // Optionally, show a toast or notification here
          }
        }
      } catch (err) {
        // Ignore
      }
    };

    return () => ws.close();
    // eslint-disable-next-line
  }, [currentUser, selectedUser, users]);

  // Scroll to chat end
  useEffect(() => {
    chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Auth handlers
  const handleLogin = async () => {
    setLoginState(s => ({ ...s, error: '' }));
    const endpoint = `${API_BASE}/users.php`;
    const body = JSON.stringify({
      action: loginState.isRegister ? 'register' : 'login',
      username: loginState.username,
      password: loginState.password
    });
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body
    });
    const data = await res.json();
    if (data.error) {
      setLoginState(s => ({ ...s, error: data.error }));
    } else {
      localStorage.setItem('currentUser', JSON.stringify(data));
      setCurrentUser(data);
    }
  };
  const handleLogout = () => {
    localStorage.removeItem('currentUser');
    setCurrentUser(null);
    setSelectedUser(null);
    setMessages([]);
    wsRef.current?.close();
  };

  // Send message
  const handleSend = (e) => {
    e.preventDefault();
    if (!message.trim() || !selectedUser) return;
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    // Debug: Log wsRef state
    console.log('[handleSend] wsRef.current:', wsRef.current);
    if (wsRef.current) {
      console.log('[handleSend] wsRef.current.readyState:', wsRef.current.readyState);
    }
    // Send via WebSocket only (UI updates only on WebSocket receipt)
    if (wsRef.current && wsRef.current.readyState === 1) {
      // Send chat message in required backend format
      // Contract: { type: 'chat', to_id: <string>, message: <string>, time: <string> }
      wsRef.current.send(JSON.stringify({
        type: 'chat',
        to_id: String(selectedUser.id),
        message,
        time
      }));
    } else {
      console.warn('[handleSend] WebSocket is not open!');
    }
    // Store in DB (do not update UI here)
    fetch(`${API_BASE}/messages.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ from_id: currentUser.id, to_id: selectedUser.id, message })
    });
    setMessage('');
  };



  if (!currentUser) {
    return (
      <div className="login-outer">
        <div className="login-card">
          <div className="login-avatar">
            <svg width="54" height="54" viewBox="0 0 54 54" fill="none"><circle cx="27" cy="27" r="27" fill="#8f5fff"/><path d="M27 29c5.523 0 10-4.03 10-9s-4.477-9-10-9-10 4.03-10 9 4.477 9 10 9zm0 3c-6.627 0-20 3.29-20 9.857V47a2 2 0 002 2h36a2 2 0 002-2v-5.143C47 35.29 33.627 32 27 32z" fill="#fff"/></svg>
          </div>
          <h2 className="login-title">Welcome to Purple Chat</h2>
          <div className="login-form-group">
            <label htmlFor="login-username">Username</label>
            <input
              id="login-username"
              type="text"
              placeholder="Enter your username"
              value={loginState.username}
              onChange={e => setLoginState(s => ({ ...s, username: e.target.value }))}
              autoFocus
            />
          </div>
          <div className="login-form-group">
            <label htmlFor="login-password">Password</label>
            <input
              id="login-password"
              type="password"
              placeholder="Enter your password"
              value={loginState.password}
              onChange={e => setLoginState(s => ({ ...s, password: e.target.value }))}
            />
          </div>
          <button className="login-btn" onClick={handleLogin}>{loginState.isRegister ? 'Register' : 'Login'}</button>
          <button type="button" onClick={() => setLoginState(s => ({ ...s, isRegister: !s.isRegister, error: '' }))}>
            {loginState.isRegister ? 'Switch to Login' : 'Switch to Register'}
          </button>
          {loginState.error && <div style={{ color: 'crimson', marginTop: 10 }}>{loginState.error}</div>}
          <div className="login-tip">Tip: Pick any username to join the chat instantly.</div>
        </div>
      </div>
    );
  }

  return (
    <div className="app-shell">
      <div className="topbar" style={{ position: 'relative', borderRadius: '16px 16px 0 0', boxShadow: 'none' }}>
        <span>Purple Chat</span>
        <span className="user-info">
          <span className="avatar-circle">{currentUser.username[0].toUpperCase()}</span>
          {currentUser.username}
          <button onClick={handleLogout} style={{ marginLeft: 18, background: 'none', border: 'none', color: '#fff', fontSize: '0.95em', cursor: 'pointer', fontWeight: 400 }}>Logout</button>
        </span>
      </div>
      <div className="chat-container with-topbar">
        <aside className="sidebar">
          <div className="sidebar-header">Users</div>
          <ul id="user-list">
            {users.filter(u => u.id !== currentUser.id).map(user => (
              <li
                key={user.id}
                className={`user-list-item${selectedUser && user.id === selectedUser.id ? ' active' : ''}`}
                onClick={() => {
                  setSelectedUser(user);
                  setNotifications(prev => ({ ...prev, [user.id]: 0 }));
                }}
              >
                <span className="avatar-circle">{user.username[0].toUpperCase()}</span>
                <span>{user.username}</span>
                {notifications[user.id] > 0 && <span className="notif-badge">{notifications[user.id]}</span>}
              </li>
            ))}
          </ul>
        </aside>
        <main className="chat-main">
          <header className="chat-header">
            <span id="current-user">
              {selectedUser ? <><span className="avatar-circle">{selectedUser.username[0].toUpperCase()}</span> {selectedUser.username}</> : 'Select a user'}
            </span>
          </header>
          <div className="chat-messages" id="chat-messages">
            {!selectedUser && (
              <div className="empty-chat">
                Select a user to start chatting
              </div>
            )}
            {selectedUser && messages.length === 0 && (
              <div className="empty-chat">No messages yet. Say hi!</div>
            )}
            {selectedUser && messages.length > 0 && (
              <>
                <div className="day-divider">Today</div>
                {messages.map((msg, idx) => (
                  <div
                    key={idx}
                    className={`message ${parseInt(msg.from_id) === parseInt(currentUser.id) ? 'sent' : 'received'}`}
                  >
                    <div className="message-content">{msg.message}</div>
                    <span className="message-time">{msg.time}</span>
                  </div>
                ))}
              </>
            )}
            <div ref={chatEndRef} />
          </div>
          {selectedUser && (
            <form id="message-form" onSubmit={handleSend}>
              <input
                type="text"
                id="message-input"
                placeholder="Type a message..."
                value={message}
                onChange={e => setMessage(e.target.value)}
                autoComplete="off"
              />
              <button type="submit">Send</button>
            </form>
          )}
          <div style={{ marginTop: 8, fontSize: '0.9em', color: '#888' }}>WebSocket: {wsStatus}</div>
        </main>
      </div>
    </div>
  );
}

export default App
