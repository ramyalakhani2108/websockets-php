// Utility functions for group chat API
export const fetchUserGroups = async (userId, API_BASE) => {
  const res = await fetch(`${API_BASE}/groups.php?user_id=${userId}`);
  return res.json();
};

export const fetchGroupMessages = async (groupId, API_BASE) => {
  const res = await fetch(`${API_BASE}/groups.php?group_id=${groupId}`);
  return res.json();
};

export const createGroup = async (name, userId, API_BASE) => {
  const res = await fetch(`${API_BASE}/groups.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'create', name, created_by: userId })
  });
  return res.json();
};

export const addGroupMember = async (groupId, userId, API_BASE) => {
  const res = await fetch(`${API_BASE}/groups.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'add_member', group_id: groupId, user_id: userId })
  });
  return res.json();
};

export const sendGroupMessage = async (groupId, fromId, message, API_BASE) => {
  const res = await fetch(`${API_BASE}/groups.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'send_message', group_id: groupId, message })
  });
  return res.json();
};
