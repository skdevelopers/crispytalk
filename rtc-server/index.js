// Developed by Mian Salman
// GitHub: https://github.com/skdevelopers
// Website: https://everestbuys.com

// Import dependencies
const express = require('express');
const http = require('http');
const { createClient } = require('redis');
const { createAdapter } = require('@socket.io/redis-adapter');
const socketIo = require('socket.io');

// Initialize Express app and HTTP server
const app = express();
const server = http.createServer(app);

// Configure Socket.io with CORS
const io = socketIo(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

// Redis clients for pub/sub (used for scaling WebSocket connections)
const redisUrl = 'redis://localhost:6379';
const pubClient = createClient({ url: redisUrl });
const subClient = pubClient.duplicate();

// Redis adapter setup for scaling WebSocket servers
Promise.all([pubClient.connect(), subClient.connect()])
    .then(() => {
        io.adapter(createAdapter(pubClient, subClient));
        console.log('✅ Redis adapter connected successfully.');
    })
    .catch((err) => {
        console.error('❌ Redis connection error:', err.message);
    });

// ICE servers for WebRTC (STUN/TURN configuration)
const iceServers = [
    { urls: 'stun:rtc.crispytalk.info:3478' },
    {
        urls: 'turn:rtc.crispytalk.info:3478',
        username: 'webrtcuser',
        credential: 'webrtcpassword'
    }
];

// Middleware to parse JSON requests
app.use(express.json());

// Health check route to confirm server is running
app.get('/', (req, res) => {
    res.status(200).send('✅ WebRTC signaling server is running.');
});

// Notification route to broadcast events to all connected clients
app.post('/notify', (req, res) => {
    const { event, data } = req.body;

    if (!event || !data) {
        return res.status(400).json({ error: 'Event and data are required.' });
    }

    io.emit(event, data); // Broadcast event to all clients
    console.log(`📢 Broadcast event: ${event}`, data);

    res.status(200).json({ message: 'Notification broadcast successfully.' });
});

// Handle WebRTC signaling and chat events
io.on('connection', (socket) => {
    console.log(`🔗 New client connected: ${socket.id}`);

    // Send ICE servers to client upon connection
    socket.emit('iceServers', iceServers);

    // Chat message broadcasting
    socket.on('chatMessage', (data) => {
        socket.broadcast.emit('chatMessage', data);
    });

    // WebRTC signaling: offer, answer, and candidate
    ['offer', 'answer', 'candidate'].forEach((event) => {
        socket.on(event, (data) => {
            socket.broadcast.emit(event, data);
        });
    });

    // Handle client disconnection
    socket.on('disconnect', (reason) => {
        console.log(`❌ Client disconnected: ${socket.id} - Reason: ${reason}`);
    });

    // Error handling for socket events
    socket.on('error', (err) => {
        console.error(`⚠️ Socket error on ${socket.id}:`, err.message);
    });
});

// Start the WebRTC signaling server
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`🚀 WebRTC signaling server running on port ${PORT}`);
});
