<?php
session_start();
require 'config.php';

// --- Technician login verification ---
if (!isset($_SESSION['technician_logged_in']) || $_SESSION['technician_logged_in'] !== true) {
    header("Location: technician_login.html");
    exit();
}
if (!isset($_GET['complaint_id'])) {
    die("Complaint ID missing.");
}

$complaint_id = intval($_GET['complaint_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with User</title>
<style>
/* === DARK PROFESSIONAL CHAT THEME === */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    margin: 0;
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
    color: #fff;
    background-attachment: fixed;
    position: relative;
    overflow: hidden;
}

/* Header Bar */
.chat-header {
    width: 90%;
    max-width: 680px;
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    color: #fff;
    font-weight: 600;
    font-size: 18px;
    padding: 14px 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
    z-index: 2;
}

.chat-title {
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.3px;
}

/* Chat Box */
.chat-box {
    border-radius: 0 0 15px 15px;
    width: 90%;
    max-width: 680px;
    height: 460px;
    padding: 20px;
    background: rgba(30, 30, 30, 0.9);
    backdrop-filter: blur(6px);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
    position: relative;
    z-index: 1;
}

/* Message bubbles */
.message {
    padding: 12px 18px;
    border-radius: 22px;
    max-width: 75%;
    word-wrap: break-word;
    font-size: 15px;
    line-height: 1.5;
    position: relative;
}

/* User message (left side, light blue) */
.user {
    background: linear-gradient(135deg, #2196f3, #64b5f6);
    margin-right: auto;
    color: #ffffff;
    border-top-left-radius: 4px;
}

/* Technician message (right side, teal) */
.technician {
    background: linear-gradient(135deg, #26a69a, #4db6ac);
    margin-left: auto;
    color: #e0f2f1;
    border-top-right-radius: 4px;
}

/* Input form */
form {
    margin-top: 20px;
    width: 90%;
    max-width: 680px;
    display: flex;
    gap: 10px;
    z-index: 1;
}

input[type="text"] {
    flex: 1;
    padding: 12px 18px;
    border-radius: 25px;
    border: 1px solid #444;
    font-size: 15px;
    outline: none;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    transition: all 0.2s ease;
}

input[type="text"]::placeholder {
    color: rgba(255,255,255,0.6);
}

input[type="text"]:focus {
    border-color: #00c6ff;
    box-shadow: 0 0 10px rgba(0,198,255,0.4);
}

/* Send button */
button {
    padding: 12px 22px;
    border: none;
    border-radius: 25px;
    background: linear-gradient(135deg, #00c6ff, #0072ff);
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(0, 198, 255, 0.25);
}

button:hover {
    background: linear-gradient(135deg, #0097e6, #0056b3);
    box-shadow: 0 5px 15px rgba(0, 198, 255, 0.35);
}

/* Scrollbar */
.chat-box::-webkit-scrollbar {
    width: 8px;
}
.chat-box::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}
.chat-box::-webkit-scrollbar-track {
    background-color: rgba(0, 0, 0, 0.2);
}
</style>
</head>
<body>

<div class="chat-header">
    <div class="chat-title">🛠️ Chat with User — Complaint #<?= htmlspecialchars($complaint_id) ?></div>
</div>

<div class="chat-box" id="chatBox"></div>

<form id="chatForm">
    <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($complaint_id) ?>">
    <input type="text" name="message" placeholder="Type your reply..." required>
    <button type="submit">Send</button>
</form>

<script>
const chatBox = document.getElementById('chatBox');
const chatForm = document.getElementById('chatForm');
const complaintId = <?= $complaint_id ?>;

function fetchMessages() {
    fetch(`fetch_messages.php?complaint_id=${complaintId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                chatBox.innerHTML = '';
                data.messages.forEach(msg => {
                    const div = document.createElement('div');
                    div.classList.add('message', msg.sender_type);
                    div.textContent = msg.message;
                    chatBox.appendChild(div);
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            } else {
                console.error('Fetch error:', data.message);
            }
        })
        .catch(err => console.error('Error fetching messages:', err));
}

fetchMessages();
setInterval(fetchMessages, 3000);

chatForm.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(chatForm);
    formData.append('sender_type', 'technician');

    fetch('save_message.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            chatForm.reset();
            fetchMessages();
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error('Error sending message:', err);
        alert('Failed to send message.');
    });
});
</script>

</body>
</html>