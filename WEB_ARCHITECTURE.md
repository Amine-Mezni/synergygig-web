# SynergyGig Web Version - Physical Architecture

## Overview
The SynergyGig web version is a containerized **Symfony 6.4.36** application deployed with **Docker Compose**, featuring a **Nginx** web server, **PHP 8.3-FPM** application runtime, and **MariaDB 10.4.32** database.

---

## Architecture Layers

### 1. **Client Layer**
- **Components**: Web Browser (Chrome, Firefox, Safari, Edge)
- **Protocol**: HTTP/HTTPS
- **Features**: 
  - Responsive Twig templates
  - Admin design system (Teal #2C666E, Dark #0A090C)
  - Realtime features via WebSocket

---

### 2. **Reverse Proxy & Web Server Layer**
**Container**: `web_synergygig_nginx`
```
Image: nginx:alpine
Port: 8000:80
```

**Responsibilities**:
- HTTP request routing
- Static file serving (CSS, JS, images)
- SSL/TLS termination (if configured)
- Load balancing
- Gzip compression

**Configuration**: 
- Mounted: `/docker/nginx/default.conf`
- Upstream: PHP-FPM on port 9000

---

### 3. **Application Server Layer**
**Container**: `web_synergygig_php`
```
Image: php:8.3-fpm-alpine
Port: 9000:9000
Extensions: pdo_mysql, bcmath, git, curl
```

**Components**:

#### **Symfony Framework** (6.4.36)
- **Routing System**: 23 controllers, 75+ routes
- **Security Layer**: 
  - Role-Based Access Control (Admin, HR Manager, Employee, Project Owner, Gig Worker)
  - Password hashing via Symfony hasher (BCrypt)
  - Session management
  
#### **Controllers** (23 total):
```
✓ Auth (login, signup, logout, password reset)
✓ Dashboard & HR Dashboard
✓ User Management
✓ Projects & Tasks
✓ Departments
✓ Contracts & Offers
✓ Leaves & Attendance
✓ Payroll
✓ Training & Certifications
✓ Interviews
✓ Chat & Messaging
✓ Calls & Notifications
✓ Community & Social
✓ Job Scanner
✓ Profile Management
✓ Landing Page
```

#### **Templating** (Twig):
- **Template Hierarchy**:
  ```
  base.html.twig (root)
    ↓
  layouts/admin.html.twig
    ↓
  individual templates (users/, projects/, etc.)
  ```

- **Template Blocks**:
  - `breadcrumb`: Navigation breadcrumbs
  - `page_title`: Page heading
  - `header_actions`: Action buttons
  - `content`: Main content area
  - `stylesheets`: CSS imports

- **Design System Classes**:
  ```css
  .card, .table-wrapper, .table
  .btn (primary|secondary|danger|ghost|sm)
  .form-group, .form-label, .form-control
  .badge, .avatar, .text-muted
  ```

#### **ORM** (Doctrine):
- Entity mapping for 26 database tables
- Query builder for complex queries
- Entity relationships (OneToMany, ManyToMany)
- Cascade operations

#### **Services**:
- Email sender (PHPMailer/SwiftMailer)
- File handler (uploads, PDFs)
- Cache manager
- API integration service
- Job scanning service

---

### 4. **Data Persistence Layer**
**Container**: MariaDB 10.4.32 (via XAMPP or Docker)
```
Host: 127.0.0.1 (localhost)
Port: 3306
User: root (default, no password)
Database: finale_synergygig
```

#### **Database Schema** (26 Tables):

**User Management**:
- `users` — User accounts, roles, profiles
- `user_follow` — Friend/follow relationships

**Core HR**:
- `departments` — Department records
- `leaves` — Leave requests
- `attendance` — Clock in/out records
- `payroll` — Salary & compensation

**Projects & Tasks**:
- `projects` — Project records
- `project_member` — Project team members
- `tasks` — Task items with kanban status
- `comments` — Task comments

**Contracts & Jobs**:
- `contracts` — Employment contracts
- `offers` — Job postings
- `external_job` — Scraped job listings

**Training & Development**:
- `training` — Training courses
- `training_enrollment` — Course enrollments
- `certificates` — Completion certificates
- `interviews` — Interview records

**Communication**:
- `chat_room` — Chat conversations
- `chat_room_member` — Room participants
- `messages` — Chat messages
- `notifications` — User notifications
- `calls` — Call records

**Community & Social**:
- `group` — Community groups
- `group_member` — Group members
- `posts` — Social posts
- `post_reaction` — Emoji reactions

**Configuration**:
- `config` — Application settings

---

### 5. **Supporting Services** (External/Optional)

#### **A. Face Recognition Service** (Python)
- Technology: OpenCV, TensorFlow, face_recognition library
- Purpose: User authentication via facial recognition
- Integration: REST API endpoint from application
- Runs on: Separate Python process or container

#### **B. N8N Automation Platform**
- Purpose: Workflow automation and job scraping
- Workflows:
  - LinkedIn job scraping
  - Reddit job scraping
  - RSS feed aggregation
  - Email automation
  - Contract generation
- Integration: Webhook endpoints

#### **C. External APIs**
- **AI/LLM**:
  - Groq Whisper (speech-to-text transcription)
  - OpenRouter (LLM gateway)
  - OpenCode (code generation)
  - Z.AI / GLM-5 (alternative AI provider)
  
- **Email**: SendGrid, Gmail, etc.

- **Storage**: Local filesystem or cloud storage

---

## Docker Compose Architecture

```yaml
version: '3.8'

services:
  ┌─ web_network (bridge)
  │
  ├─ nginx:alpine
  │  ├─ Ports: 8000:80
  │  ├─ Volumes: nginx.conf, /app (read-only)
  │  └─ Depends: php
  │
  └─ php:8.3-fpm-alpine
     ├─ Ports: 9000:9000
     ├─ Volumes: /app (application code)
     ├─ Environment: .env file
     └─ Depends: nginx
```

### Volume Mounts:
| Container | Path | Mode | Purpose |
|-----------|------|------|---------|
| nginx | `/etc/nginx/conf.d/default.conf` | ro | Web server config |
| nginx | `/app` | ro | Static files |
| php-fpm | `/app` | rw | Application code |
| php-fpm | `.env` | - | Environment variables |

### Environment Configuration (.env):
```env
# Database
DATABASE_URL=mysql://root@127.0.0.1:3306/finale_synergygig
DATABASE_DRIVER=pdo_mysql
SERVER_VERSION=mariadb-10.4.32

# Application
APP_ENV=dev|prod
APP_DEBUG=true|false
APP_SECRET=<secret-key>

# External Services
GROQ_API_KEY=<key>
OPENROUTER_API_KEY=<key>
OPENCODE_API_KEY=<key>
ZAI_API_KEY=<key>

# Email
MAILER_DSN=smtp://...

# File Upload
UPLOAD_DIR=/app/public/uploads
```

---

## Data Flow

### 1. **Request Flow** (Client → Server)
```
Browser (HTTP/HTTPS)
    ↓ Port 8000
Nginx Reverse Proxy
    ↓ Port 9000
PHP-FPM (Routes → Controller)
    ↓
Symfony Security (Auth Check)
    ↓
Route Handler (Controller Action)
    ↓
Doctrine ORM / Services
    ↓
Query / Execute
    ↓
Response (Twig Template)
    ↓
Nginx
    ↓
Browser (HTML + Assets)
```

### 2. **Database Query Flow**
```
Application (ORM)
    ↓
Doctrine Query Builder / DQL
    ↓
MySQL Protocol
    ↓
MariaDB Server
    ↓
Table Operations (SELECT, INSERT, UPDATE, DELETE)
    ↓
Return Result Set
    ↓
Application (Hydration)
    ↓
Entity Objects
```

### 3. **File Upload Flow**
```
Browser Upload Form
    ↓
Nginx (request routing)
    ↓
PHP-FPM (file processing)
    ↓
File Handler Service
    ↓
/app/public/uploads/ (persistent volume)
    ↓
Database record (filepath)
```

### 4. **External API Integration**
```
Application
    ↓
HTTP Client (curl/Guzzle)
    ↓
Internet
    ↓
External API (Groq, OpenRouter, etc.)
    ↓
Response (JSON)
    ↓
Application (parse & store)
    ↓
Database or Cache
```

---

## Deployment Checklist

### **Local Development**
```bash
# Start Docker services
docker-compose up -d

# Access application
http://localhost:8000

# Database
Host: 127.0.0.1:3306
User: root
Password: (blank)
```

### **Production Deployment**
1. ✓ Set `APP_ENV=prod` in `.env`
2. ✓ Configure SSL certificates for Nginx
3. ✓ Set strong `APP_SECRET`
4. ✓ Configure external API keys
5. ✓ Set up persistent storage for uploads
6. ✓ Configure database backups
7. ✓ Set up monitoring/logging
8. ✓ Configure rate limiting on Nginx
9. ✓ Enable CORS if needed
10. ✓ Test email delivery

---

## Performance Optimization

### **Web Server (Nginx)**
- Gzip compression enabled
- Browser caching headers
- Static file serving (offload from PHP)
- Connection pooling to PHP-FPM

### **Application (PHP)**
- OPcache for bytecode caching
- Doctrine Query cache
- Session storage optimization
- Lazy loading of entities

### **Database (MariaDB)**
- Indexed columns (user_id, created_at, etc.)
- Query optimization
- Connection pooling
- Slow query log monitoring

---

## Security Measures

### **Application Layer**
- ✓ CSRF token protection
- ✓ SQL Injection prevention (parameterized queries via ORM)
- ✓ XSS prevention (Twig auto-escaping)
- ✓ Password hashing (BCrypt via Symfony)
- ✓ Role-based access control (RBAC)
- ✓ Session fixation protection

### **Web Server Layer**
- ✓ Hide server version headers
- ✓ HTTPS/TLS encryption
- ✓ Rate limiting
- ✓ Request size limits
- ✓ Disable dangerous HTTP methods

### **Database Layer**
- ✓ User privileges (least privilege principle)
- ✓ Password encryption
- ✓ Backup encryption

---

## Monitoring & Logging

### **Application Logs**
- Location: `/app/var/log/` (in container)
- Logs: Access, errors, debug information

### **Web Server Logs**
- Access logs: HTTP requests
- Error logs: Server errors

### **Database Logs**
- Slow query log
- Error log
- Binary logs (for replication/backup)

---

## Scalability & Future Considerations

### **Horizontal Scaling**
- Multiple PHP-FPM containers behind Nginx load balancer
- Separate database server (replication/clustering)
- Cache layer (Redis) for sessions and queries
- CDN for static assets

### **Microservices** (Future)
- Extract job scraping to separate service
- Extract face recognition to separate service
- Extract email sending to queue system
- Extract file processing to worker processes

### **Message Queue**
- RabbitMQ or Redis for async tasks
- Background job processing
- Email delivery queue
- Notification delivery queue

---

## Technology Stack Summary

| Layer | Technology | Version |
|-------|-----------|---------|
| **Framework** | Symfony | 6.4.36 |
| **Language** | PHP | 8.3 |
| **Web Server** | Nginx | Alpine |
| **Database** | MariaDB | 10.4.32 |
| **ORM** | Doctrine | 2.x |
| **Templating** | Twig | 3.x |
| **Containerization** | Docker & Compose | 20.10+ |
| **Python** (Face Recognition) | Python | 3.8+ |
| **Automation** | N8N | Latest |

---

## Conclusion

The SynergyGig web version follows a **traditional three-tier architecture** (Presentation → Application → Data) with containerization for portability and consistency. The design emphasizes:

- **Separation of Concerns** (Nginx, PHP-FPM, MariaDB)
- **Scalability** (Docker containers)
- **Security** (RBAC, encryption, validation)
- **Maintainability** (Symfony framework conventions)
- **Extensibility** (External API integration, N8N workflows)

The architecture is production-ready and supports integration with AI services, job scraping, and real-time features via WebSocket and external APIs.
