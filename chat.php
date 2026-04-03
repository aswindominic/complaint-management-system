<?php
session_start();
require 'config.php';

// (Temporary for testing – remove later)
$_SESSION['user_id'] = 1;

if (!isset($_GET['complaint_id'])) {
    die("Complaint ID missing.");
}

$complaint_id = intval($_GET['complaint_id']);
$user_id = $_SESSION['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaint Chat</title>
<style>
/* === PROFESSIONAL DARK THEME === */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    margin: 0;
    background: linear-gradient(135deg, #121212, #1c1c1c, #0d0d0d);
    background-attachment: fixed;
    position: relative;
    overflow: hidden;
    color: #e0e0e0;
}

/* Decorative blurred neon orbs */
body::before, body::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    filter: blur(120px);
    opacity: 0.2;
    z-index: 0;
    pointer-events: none;
}

body::before {
    width: 500px;
    height: 500px;
    background: #0d47a1;
    top: -150px;
    left: -120px;
}

body::after {
    width: 600px;
    height: 600px;
    background: #00695c;
    bottom: -200px;
    right: -150px;
}

/* Heading */
h2 {
    color: #ffffff;
    margin-bottom: 25px;
    font-weight: 600;
    letter-spacing: 0.5px;
    background: linear-gradient(90deg, #64b5f6, #26a69a);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    z-index: 1;
}

/* Chat Header */
.chat-header {
    width: 90%;
    max-width: 680px;
    background: linear-gradient(90deg, #2196f3, #00bfa5);
    color: #fff;
    font-weight: 600;
    font-size: 18px;
    padding: 14px 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.5);
    position: relative;
    z-index: 2;
}

.chat-title {
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.3px;
}

/* Chat box */
.chat-box {
    border-radius: 0 0 15px 15px;
    width: 90%;
    max-width: 680px;
    height: 460px;
    padding: 20px;
    background: rgba(28, 28, 28, 0.95);
    backdrop-filter: blur(8px);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
    box-shadow: 0 8px 35px rgba(0, 0, 0, 0.6);
    position: relative;
    z-index: 1;
    border: 1px solid rgba(255,255,255,0.1);
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
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

/* User message (right side) */
.user {
    background: linear-gradient(135deg, #1565c0, #1e88e5);
    margin-left: auto;
    color: #e3f2fd;
    border-top-right-radius: 4px;
}

/* Technician message (left side) */
.technician {
    background: linear-gradient(135deg, #00796b, #26a69a);
    margin-right: auto;
    color: #e0f2f1;
    border-top-left-radius: 4px;
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

/* Message input field */
input[type="text"] {
    flex: 1;
    padding: 12px 18px;
    border-radius: 25px;
    border: 1px solid #333;
    font-size: 15px;
    outline: none;
    background: #212121;
    color: #f5f5f5;
    transition: all 0.2s ease;
    box-shadow: inset 0 1px 4px rgba(0,0,0,0.3);
}

input[type="text"]:focus {
    border-color: #2196f3;
    box-shadow: 0 0 8px rgba(33,150,243,0.4);
    background: #2c2c2c;
}

/* Send button */
button {
    padding: 12px 22px;
    border: none;
    border-radius: 25px;
    background: linear-gradient(135deg, #2196f3, #00bfa5);
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 15px rgba(0, 191, 165, 0.3);
}

button:hover {
    background: linear-gradient(135deg, #1565c0, #00897b);
    box-shadow: 0 5px 20px rgba(0, 191, 165, 0.4);
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
    background-color: rgba(255, 255, 255, 0.05);
}
</style>
</head>
<body>

<div class="chat-header">
    <div class="chat-title">
        💬 Chat with Technician <span style="font-weight:normal; font-size:0.9em; color:#ddd;">(Complaint #<?= htmlspecialchars($complaint_id) ?>)</span>
    </div>
</div>

<div class="chat-box" id="chatBox"></div>

<form id="chatForm">
    <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($complaint_id) ?>">
    <input type="text" name="message" placeholder="Type your message..." required>
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
    div.textContent = msg.message; // ✅ Only show the message, no name
    chatBox.appendChild(div);
});
                chatBox.scrollTop = chatBox.scrollHeight;
            } else {
                console.error('Fetch error:', data.message);
            }
        })
        .catch(err => console.error('Error fetching messages:', err));
}

// Refresh messages every 3 seconds
fetchMessages();
setInterval(fetchMessages, 3000);

chatForm.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(chatForm);
    formData.append('sender_type', 'user');

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