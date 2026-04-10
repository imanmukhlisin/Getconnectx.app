<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - ConnectX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar: Conversations List -->
        <div class="w-1/3 bg-white border-r flex flex-col">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="text-xl font-bold">Conversations</h2>
                <a href="/dashboard" class="text-blue-500 text-sm">Dashboard</a>
            </div>
            <div id="conversationList" class="flex-1 overflow-y-auto">
                <p class="p-4 text-gray-500">Loading chats...</p>
            </div>
        </div>

        <!-- Main: Chat Box -->
        <div class="flex-1 flex flex-col bg-white">
            <div id="chatHeader" class="p-4 border-b bg-gray-50 flex items-center justify-between hidden">
                <div>
                    <h3 id="recipientName" class="font-bold">Select a chat</h3>
                    <p id="recipientStatus" class="text-xs text-green-500">Online</p>
                </div>
            </div>

            <div id="messageDisplay" class="flex-1 p-4 overflow-y-auto space-y-4 flex flex-col">
                <div class="flex-1 flex items-center justify-center text-gray-400">
                    Select a conversation to start chatting
                </div>
            </div>

            <!-- Input Area -->
            <div id="inputArea" class="p-4 border-t bg-gray-50 hidden">
                <form id="chatForm" class="flex space-x-2">
                    <input type="text" id="messageInput" placeholder="Type a message..." class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition">Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Please login first.');
            window.location.href = '/login';
        }

        const SUPABASE_URL = "{{ env('SUPABASE_URL') }}";
        const SUPABASE_ANON_KEY = "{{ env('SUPABASE_ANON_KEY') }}";
        const supaClient = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

        let currentConversationId = null;
        let chatChannel = null;
        let authUser = null;

        // Initialize
        async function init() {
            await fetchProfile();
            await fetchConversations();
        }

        async function fetchProfile() {
            const resp = await fetch('/api/v1/profile', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await resp.json();
            if (data.status === 'success') {
                authUser = data.data;
            }
        }

        async function fetchConversations() {
            const resp = await fetch('/api/v1/chats', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await resp.json();
            const list = document.getElementById('conversationList');
            list.innerHTML = '';

            if (data.data.data.length === 0) {
                list.innerHTML = '<p class="p-4 text-gray-400">No conversations yet.</p>';
                return;
            }

            data.data.data.forEach(chat => {
                const recipient = chat.participants[0] || { name: 'Unknown' };
                const div = document.createElement('div');
                div.className = `p-4 hover:bg-gray-100 cursor-pointer border-b ${currentConversationId === chat.id ? 'bg-blue-50' : ''}`;
                div.innerHTML = `
                    <div class="font-bold">${recipient.name}</div>
                    <div class="text-sm text-gray-500 truncate">${chat.last_message?.content || 'No messages yet'}</div>
                `;
                div.onclick = () => selectConversation(chat.id, recipient.name);
                list.appendChild(div);
            });
        }

        async function selectConversation(id, name) {
            currentConversationId = id;
            document.getElementById('chatHeader').classList.remove('hidden');
            document.getElementById('inputArea').classList.remove('hidden');
            document.getElementById('recipientName').innerText = name;
            
            // Highlight selected in list
            fetchConversations(); 

            // Clear and load history
            const display = document.getElementById('messageDisplay');
            display.innerHTML = '<p class="text-center text-gray-400">Loading history...</p>';

            const resp = await fetch(`/api/v1/chats/${id}/messages`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await resp.json();
            display.innerHTML = '';
            
            if (data.data.data) {
                data.data.data.reverse().forEach(msg => appendMessage(msg));
            }

            scrollToBottom();
            setupRealtime(id);
        }

        function setupRealtime(convId) {
            if (chatChannel) {
                supaClient.removeChannel(chatChannel);
            }

            // Realtime subscription using Supabase Broadcast
            chatChannel = supaClient.channel(`chat_${convId}`)
                .on('broadcast', { event: 'message' }, ({ payload }) => {
                    if (payload.sender_id !== authUser.id) {
                        appendMessage(payload);
                        scrollToBottom();
                    }
                })
                .subscribe();
        }

        function appendMessage(msg) {
            const display = document.getElementById('messageDisplay');
            const isMe = msg.sender_id === authUser.id;
            
            const div = document.createElement('div');
            div.className = `max-w-[80%] p-3 rounded-lg ${isMe ? 'bg-blue-600 text-white self-end' : 'bg-gray-200 text-gray-800 self-start'}`;
            div.innerHTML = `
                <div class="text-xs opacity-75 mb-1">${isMe ? 'You' : (msg.sender?.name || 'User')}</div>
                <div>${msg.content}</div>
                <div class="text-[10px] opacity-50 mt-1 text-right">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
            `;
            display.appendChild(div);
        }

        document.getElementById('chatForm').onsubmit = async (e) => {
            e.preventDefault();
            const input = document.getElementById('messageInput');
            const content = input.value.trim();
            if (!content || !currentConversationId) return;

            input.value = '';

            try {
                const resp = await fetch(`/api/v1/chats/${currentConversationId}/messages`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}` 
                    },
                    body: JSON.stringify({ content })
                });
                const data = await resp.json();
                if (data.status === 'success') {
                    const newMsg = data.data;
                    appendMessage(newMsg);
                    scrollToBottom();

                    // Broadcast to others via Supabase
                    chatChannel.send({
                        type: 'broadcast',
                        event: 'message',
                        payload: newMsg
                    });
                }
            } catch (err) {
                console.error(err);
            }
        };

        function scrollToBottom() {
            const display = document.getElementById('messageDisplay');
            display.scrollTop = display.scrollHeight;
        }

        init();
    </script>
</body>
</html>
