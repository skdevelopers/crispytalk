# CrispyTalk.info

**CrispyTalk** is a real-time audio/video communication platform powered by WebRTC, Laravel, and Flutter, designed for low-latency, high-performance experiences on both web and mobile. The platform is optimized for low-end devices and unstable internet connections, featuring server-side transcoding and TURN/STUN support.

---

## 🌐 Infrastructure Overview

CrispyTalk.info is a multi-service platform built on a custom-optimized infrastructure:

### 🔸 **Main Domain**: [`https://crispytalk.info`](https://crispytalk.info)
- **API**: Laravel 11 backend with PostgreSQL
- **Path**: `/home/crispytalk/public_html`
- **Subfolder API Path**: `/home/crispytalk/public_html/api`
- Handles:
    - Authentication
    - Video metadata
    - User profiles
    - Notification APIs (via Redis)

### 🔸 **RTC Server**: [`https://rtc.crispytalk.info`](https://rtc.crispytalk.info)
- Node.js with Socket.io
- Real-time signaling server for WebRTC
- TURN/STUN handling through Coturn
- Path: `/home/crispytalk/public_html/rtc`

### 🔸 **Tech Stack**
| Component     | Tech                         |
|---------------|------------------------------|
| Backend API   | Laravel 11 + PostgreSQL      |
| Realtime RTC  | Node.js + Socket.io          |
| TURN/STUN     | Coturn                       |
| Frontend App  | Flutter (Android/iOS/Web)    |
| Real-time Bus | Redis                        |
| Media Tools   | FFmpeg for video processing  |
| Web Server    | Nginx (for API + RTC)        |
| WordPress     | Reserved (not used here)     |

---

## 🔧 Server Setup Summary

- **Server Provider**: DigitalOcean Droplet
- **Wildcard SSL**: Configured for `*.crispytalk.info`
- **PHP**: 8.3
- **Database**: PostgreSQL
- **Message Queue**: Redis (used for real-time events)
- **Reverse Proxy**: Nginx (optimized for Node.js + Laravel APIs)
- **Ports & Firewalls**: Opened for WebRTC (typically 3478 for STUN/TURN, and WebSockets)

---

## ⚙️ Folder Structure

/home/crispytalk/ ├── public_html/ │ ├── api/ # Laravel API (Laravel 11) │ └── rtc/

# RTC Node.js Signaling Server

🔐 Security Notes
Wildcard SSL ensures all subdomains are secured.
Laravel API protected by sanctum/token-based auth.
RTC and Redis secured and not exposed publicly.
Nginx config optimized for resource and socket proxying.

📱 Mobile App Integration
The Flutter app connects to:

API: https://crispytalk.info/api
RTC Signaling: https://rtc.crispytalk.info via WebSocket
Ensure appropriate CORS and socket configurations are enabled.

📄 License
This project is proprietary and developed for the CrispyTalk Platform under EVEREST BUY LIMITED.

👤 Author
Mian Salman
Senior Software Engineer
CTO, Everest Buy Limited
📧 everestbuypk@gmail.com
🌐 everestbuys.com

🛠 Future Plans
Add AI moderation for calls
Multi-language support
Admin dashboard with analytics
Firebase integration for push notifications
