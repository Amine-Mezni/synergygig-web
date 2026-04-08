# SynergyGig Web Architecture

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend Layer                        │
│  (Browser: ES6 + Alpine.js + Bootstrap + Tailwind CSS)     │
└────────┬────────────────────────────────────────────────────┘
         │ HTTP/HTTPS
         │ WebSocket
         │
┌────────▼────────────────────────────────────────────────────┐
│                    Reverse Proxy (Nginx)                      │
│  • Static file serving                                       │
│  • SSL termination                                           │
│  • Load balancing                                            │
│  • Rate limiting                                             │
└────────┬────────────────────────────────────────────────────┘
         │ FastCGI
         │
┌────────▼────────────────────────────────────────────────────┐
│              Application Server (PHP-FPM + Symfony)          │
├────────────────────────────────────────────────────────────┤
│  Controllers            → Route handling & validation        │
│  Services              → Business logic & transactions      │
│  Repositories          → Data access & queries              │
│  Security              → JWT/Session auth & authorization   │
│  Event Listeners       → Lifecycle hooks & notifications    │
│  Form Types            → Data validation & transformation   │
│  Commands              → Background jobs & maintenance      │
└────────┬────────────────────────────────────────────────────┘
         │ MySQL Protocol
         │ Redis Protocol
         │ AMQP
         │
    ┌────┴────┬──────────┬──────────┐
    │          │          │          │
┌───▼──┐  ┌────▼─┐  ┌────▼──┐  ┌───▼────┐
│MySQL │  │Redis │  │RabbitMQ│ │Elasticsearch│
│(Data)│  │Cache │  │(Events)│ │(Logs)   │
└──────┘  └──────┘  └────────┘ └────────┘
```

---

## Component Breakdown

### 1. Frontend Layer
**Technology:** HTML5 + Alpine.js + Bootstrap 5 + Tailwind CSS  
**Responsibilities:**
- Server-rendered Twig templates (progressive enhancement)
- Lightweight interactivity with Alpine.js components
- Responsive UI with Bootstrap + Tailwind
- Real-time updates via WebSocket
- JWT token management (localStorage)

**No JavaScript build step** - files served directly (Alpine.js via CDN)

---

### 2. API Layer (REST + WebSocket)

#### REST API Structure
```
GET    /api/resources             → List + paginate + filter
GET    /api/resources/{id}        → Single resource
POST   /api/resources             → Create
PUT    /api/resources/{id}        → Update (full)
PATCH  /api/resources/{id}        → Partial update
DELETE /api/resources/{id}        → Delete
```

#### WebSocket Events (Real-time)
**Connection URL:** `wss://example.com/ws`

**Event Format:**
```json
{
  "event": "message:new",
  "data": {
    "messageId": 123,
    "sender": "john",
    "content": "Hello",
    "timestamp": "2024-01-01T10:00:00Z"
  }
}
```

**Common Events:**
- `message:new` - Chat message received
- `message:edited` - Message updated
- `message:deleted` - Message deleted
- `call:incoming` - Incoming call received
- `call:accepted` - Call was accepted
- `user:typing` - User is typing indicator
- `notification:new` - System notification

---

### 3. Application Core (Symfony 7.1)

#### Request Lifecycle
```
Request → Route Matcher → Controller → Service → Repository → Database
                                          ↓
                         Return Response (JSON/HTML)
                                          ↓
                    EventListener (Post-processing) → Response
```

#### Key Symfony Bundles
- **FrameworkBundle** - Core framework
- **TwigBundle** - Template engine
- **SecurityBundle** - Authentication/Authorization
- **DoctrineBundle** - ORM for database
- **LexikJWTAuthenticationBundle** - JWT tokens
- **NelmioCorsBundle** - CORS handling
- **MonologBundle** - Logging
- **MessengerBundle** - Async message processing

---

### 4. Database Layer (Doctrine ORM)

#### Entity-Repository-Service Pattern
```
Controller
    ↓ (calls)
Service (UserService)
    ↓ (uses)
Repository (UserRepository)
    ↓ (queries)
Entity (User)
    ↓ (maps to)
MySQL Table (users)
```

#### Doctrine Concepts
- **Entity** - PHP class mapping to database table
- **Repository** - Query builder for custom queries
- **Migrations** - Version control for schema changes
- **Relationships** - OneToMany, ManyToMany, ManyToOne
- **Lifecycle Hooks** - PrePersist, PostLoad, PreUpdate, PostUpdate

---

### 5. Authentication & Authorization

#### JWT Flow (API Clients)
```
1. POST /api/auth/login
   Credentials → Token Generation → Return JWT
2. Client stores JWT in localStorage
3. Next requests include: Authorization: Bearer <token>
4. Server validates JWT signature
5. Request continues with user context
```

#### Session Flow (Web Browsers)
```
1. POST /api/auth/login
   Credentials → Session creation → HTTP Set-Cookie header
2. Browser stores cookie automatically
3. Next requests include cookie automatically
4. Server validates session from Redis
5. Request continues with user context
```

#### Authorization (RBAC)
```
Roles: Admin > Manager > Employee > GigWorker

Checks:
- @IsGranted("ROLE_ADMIN") - Require admin role
- @IsGranted("EDIT", subject="user") - Custom voter for ownership
```

---

### 6. Real-Time Communication (WebSocket)

#### Architecture
```
Client (Browser)
    ↓ WebSocket Connection
Server (PHP-FPM)
    ↓ Message Broker
RabbitMQ (Message Queue)
    ↓ Workers Process
Swoole WebSocket Handler
    ↓ Broadcast to All Connected Clients
All Clients Receive Update
```

#### Message Flow
1. Client sends message via WebSocket
2. Handler validates & stores in database
3. Event published to RabbitMQ
4. Swoole broadcast to all connected clients
5. Clients receive update in real-time

---

### 7. Caching Strategy

#### Cache Layers (Best to Worst Performance)
1. **Browser Cache** - Static assets (CSS, JS, images)
2. **Redis Cache** - Hot application data (user profiles, permissions)
3. **Query Cache** - Doctrine result set cache
4. **Database** - Source of truth

#### When to Cache
- User data (rarely changes)
- Role/permission checks
- System settings/configuration
- Computed aggregations (analytics)

#### Cache Invalidation
- Manual: `redis` CLI or cache:clear command
- Automatic: TTL expiration
- On-write: Invalidate when data changes

---

### 8. Async Processing (Background Jobs)

#### Use Cases
- Send emails (transactional)
- Generate PDFs
- Process video/images
- Send notifications
- Data migrations
- Analytics computations

#### Flow
```
Request Handler
    ↓
Dispatch AsyncMessage to RabbitMQ
    ↓
Consumer Worker Process
    ↓
Execute Async Handler
    ↓
Update database with results
    ↓
System completes silently
```

---

### 9. Logging & Monitoring

#### Log Levels (PSR-3)
```
DEBUG    → Detailed debugging info
INFO     → Informational messages
NOTICE   → Normal but significant
WARNING  → Warning conditions
ERROR    → Error conditions
CRITICAL → Critical conditions
```

#### Logging in Code
```php
// Inject LoggerInterface
$this->logger->info('User logged in', ['userId' => $user->getId()]);
$this->logger->error('Database error', ['exception' => $e]);
```

#### Log Aggregation (ELK Stack)
```
Application Logs → Logstash → Elasticsearch → Kibana Dashboard
```

---

### 10. Error Handling

#### Error Response Format
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "email": "Invalid email format",
      "password": "Too short (min 8 chars)"
    }
  }
}
```

#### HTTP Status Codes
- `200` - Success
- `201` - Created
- `204` - No Content
- `400` - Bad Request (validation error)
- `401` - Unauthorized (no/invalid token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `409` - Conflict (resource already exists)
- `422` - Unprocessable Entity
- `429` - Too Many Requests
- `500` - Internal Server Error
- `503` - Service Unavailable

---

## Design Patterns Used

### Service Layer Pattern
```php
class UserService {
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger
    ) {}

    public function createUser(array $data): User {
        $user = new User();
        $user->setEmail($data['email']);
        $this->repository->save($user);
        $this->logger->info('User created', ['email' => $data['email']]);
        return $user;
    }
}
```

### Repository Pattern
```php
class UserRepository {
    public function findActiveByRole(string $role): array {
        return $this->createQueryBuilder('u')
            ->where('u.role = :role')
            ->andWhere('u.isActive = true')
            ->setParameter('role', $role)
            ->getQuery()
            ->getResult();
    }
}
```

### Event-Driven Pattern
```php
#[AsEventListener(event: User::CREATED_EVENT)]
public function onUserCreated(UserCreatedEvent $event): void {
    $this->sendWelcomeEmail($event->getUser());
}
```

---

## Security Considerations

### JWT Token Security
- Store in httpOnly cookie (not localStorage)
- Short expiration (15 min), refresh token (7 days)
- Verify signature on every request
- Invalidate on logout

### Input Validation
- All user input validated via Symfony Form Types
- Database constraints enforced at ORM level
- SQL injection prevented by parameterized queries

### CORS Configuration
- Only allow trusted origins
- HTTP OPTIONS pre-flight requests handled
- Credentials included correctly

### Password Security
- Hashed with bcrypt (Symfony Security)
- Never logged or exposed in errors
- Reset via secure token link

---

## Performance Optimization

### Query Optimization
- N+1 query prevention (eager loading)
- Database indexing on frequent queries
- Query result caching for read-heavy operations

### Asset Optimization
- CSS/JS minification
- Image optimization
- Browser caching headers

### Database Optimization
- Connection pooling
- Slow query logging
- Query analysis with EXPLAIN

---

## Deployment Architecture

### Development (Docker Compose)
```
1 PHP container + 1 MySQL + 1 Redis + 1 Nginx
All on single machine
```

### Staging (Kubernetes)
```
Multiple Replicas of Each Service
- PHP pods (auto-scaling)
- MySQL (with replication)
- Redis (with sentinel)
- Nginx ingress controller
```

### Production (Kubernetes + CDN)
```
Same as Staging with:
- SSL termination at ingress
- CloudFlare CDN for static assets
- Database automated backups
- Monitoring & alerting
```

---

## Technology Rationale

| Component | Choice | Why |
|-----------|--------|-----|
| Framework | Symfony 7 | LTS, mature, industry standard |
| ORM | Doctrine | Feature-rich, migrations, relationship management |
| Frontend | Alpine.js | Lightweight, no build step, progressive enhancement |
| Real-time | Swoole + RabbitMQ | Native PHP, scalable, event-driven |
| Cache | Redis | Fast, in-memory, supports complex data structures |
| Auth | JWT + Sessions | Dual support for APIs and browsers |
| Async | Messenger | Native Symfony, RabbitMQ integration |
| Logging | Monolog + ELK | Centralized, searchable, standardized |
| Testing | PHPUnit + Behat | Industry standard, comprehensive |
| CI/CD | GitHub Actions | Free, integrated, easy setup |
| Monitoring | Prometheus + Grafana | Open-source, metrics-based, alerting |

---

## Next Steps
- Read [DATABASE.md](DATABASE.md) for schema documentation
- Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues
- Review [API.md](API.md) for endpoint documentation
