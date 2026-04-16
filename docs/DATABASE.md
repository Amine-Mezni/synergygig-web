# Database Schema Reference

## Entity Relationships Diagram

```
User (1) ──────────────────┐
         │                 │ (M) ChatRoom
         │                 │
Message (M) ──┐            │
              └──────────┐  │
                         │  │
                    Message Message
                    1 per  1 per
                    User  Room

Call (1) ──────────────┐
         │             │ (M) Call
         └─────────────┘
         (Caller & Receiver)

Interview (1) ─────────────┐
             │             │ (M) Interview
             │ Scheduled   │
             │ Attended    │
         (Interviewer)     │
                           │
              User ---> (Interviewee)

Project (1) ─────────────────┐
          │                  │ (M) Task
          └────────┐         │
                   │         │
               (Manager)  Status: TODO, IN_PROGRESS, DONE

Leave (M) ◀─────────┘ User
Question (M) ◀─────┘
Interview (M) ◀──√
Attendance (M) ◀─┘

TrainingCourse (1) ─────────┐
                  │          │ (M) TrainingEnrollment
                  └────┐     │
                       │     │
                    User ◀──┘

Offer (1) ──────────────┐
        │               │ (M) OfferApplication
        │               │
    Department       Job Seeker

Contract (1) ◀───────────┐
           │             │ (M) ContractParty
           │             │
        User ◀─────┐     │
                   │     │
             Employee & Employer

Group (1) ────────────────┐
       │                  │ (M) GroupMember
       │                  │
       └──────────────────┐
                          │
                      User ◀────────┘

Post (1) ◀──────────────┐
        │               │ (M) Post
    Author          Group
        │               
        │         (N) Comments
        │            (N) Reactions
        │         
        └─ Comment
           (N) Reactions
```

---

## Core Entities

### User
```sql
CREATE TABLE users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  avatar_url VARCHAR(500),
  bio TEXT,
  department_id BIGINT,
  is_active BOOLEAN DEFAULT true,
  role ENUM('ADMIN','HR','MANAGER','EMPLOYEE','GIG_WORKER'),
  
  -- Authentication
  email_verified_at DATETIME,
  face_id_encoding LONGBLOB, -- MediaPipe face encoding
  
  -- Timestamps
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL, -- Soft delete
  
  FOREIGN KEY (department_id) REFERENCES departments(id),
  INDEX (email),
  INDEX (department_id),
  INDEX (is_active),
  INDEX (role)
);
```

### ChatRoom
```sql
CREATE TABLE chat_rooms (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  type ENUM('DIRECT','GROUP','TEAM','ANNOUNCEMENT'),
  avatar_url VARCHAR(500),
  created_by BIGINT NOT NULL,
  is_archived BOOLEAN DEFAULT false,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX (type),
  INDEX (is_archived)
);
```

### Message
```sql
CREATE TABLE messages (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  chat_room_id BIGINT NOT NULL,
  author_id BIGINT NOT NULL,
  content LONGTEXT NOT NULL,
  
  -- Media attachments
  attachment_url VARCHAR(500),
  attachment_type ENUM('IMAGE','VIDEO','AUDIO','DOCUMENT'),
  
  -- Reactions
  reaction_emojis JSON, -- {"👍": [user_id, ...], "❤️": [...]}
  
  -- States
  is_edited BOOLEAN DEFAULT false,
  is_deleted BOOLEAN DEFAULT false,
  is_pinned BOOLEAN DEFAULT false,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (chat_room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(id),
  INDEX (chat_room_id),
  INDEX (author_id),
  INDEX (created_at),
  INDEX (is_deleted)
);
```

### Call
```sql
CREATE TABLE calls (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  caller_id BIGINT NOT NULL,
  receiver_id BIGINT NOT NULL,
  
  -- Call details
  type ENUM('AUDIO','VIDEO','SCREEN_SHARE'),
  status ENUM('INITIATING','RINGING','CONNECTED','ENDED','MISSED','REJECTED'),
  
  -- Durations
  started_at DATETIME,
  ended_at DATETIME,
  duration_seconds INT DEFAULT 0,
  
  -- Recording
  recording_url VARCHAR(500),
  
  -- Transcript (if recorded)
  transcript LONGTEXT,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (caller_id) REFERENCES users(id),
  FOREIGN KEY (receiver_id) REFERENCES users(id),
  INDEX (caller_id),
  INDEX (receiver_id),
  INDEX (status),
  INDEX (started_at)
);
```

### Interview
```sql
CREATE TABLE interviews (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  interviewer_id BIGINT NOT NULL,
  interviewee_id BIGINT NOT NULL,
  position_title VARCHAR(255),
  
  status ENUM('SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED'),
  scheduled_at DATETIME,
  duration_minutes INT,
  
  -- Feedback
  rating_score INT (1-5),
  feedback TEXT,
  
  -- Recording
  recording_url VARCHAR(500),
  transcript TEXT,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (interviewer_id) REFERENCES users(id),
  FOREIGN KEY (interviewee_id) REFERENCES users(id),
  INDEX (status),
  INDEX (scheduled_at)
);
```

### Project
```sql
CREATE TABLE projects (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  manager_id BIGINT NOT NULL,
  
  status ENUM('PLANNING','ACTIVE','PAUSED','COMPLETED','ARCHIVED'),
  start_date DATE,
  end_date DATE,
  
  -- Metadata
  department_id BIGINT,
  budget_amount DECIMAL(12,2),
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  
  FOREIGN KEY (manager_id) REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES departments(id),
  INDEX (manager_id),
  INDEX (status)
);
```

### Task
```sql
CREATE TABLE tasks (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  project_id BIGINT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  assigned_to BIGINT,
  
  status ENUM('TODO','IN_PROGRESS','REVIEW','DONE') DEFAULT 'TODO',
  priority ENUM('LOW','MEDIUM','HIGH','URGENT') DEFAULT 'MEDIUM',
  
  -- Dates
  due_date DATE,
  started_at DATETIME,
  completed_at DATETIME,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX (project_id),
  INDEX (status),
  INDEX (due_date)
);
```

### Leave
```sql
CREATE TABLE leaves (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  
  -- Leave details
  type ENUM('PAID','UNPAID','SICK','EMERGENCY','MATERNITY'),
  reason TEXT,
  
  -- Dates
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  
  -- Approval
  status ENUM('PENDING','APPROVED','REJECTED','CANCELLED'),
  approved_by BIGINT,
  approval_notes TEXT,
  approved_at DATETIME,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id),
  INDEX (user_id),
  INDEX (status),
  INDEX (start_date),
  INDEX (end_date)
);
```

### Attendance
```sql
CREATE TABLE attendance (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  date_recorded DATE NOT NULL,
  
  -- Times
  check_in_time TIME,
  check_out_time TIME,
  
  -- Status
  status ENUM('PRESENT','ABSENT','LATE','HALF_DAY','ON_LEAVE'),
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  UNIQUE KEY unique_user_date (user_id, date_recorded),
  INDEX (date_recorded)
);
```

### Payroll
```sql
CREATE TABLE payroll (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  month_year DATE NOT NULL,
  
  -- Earnings
  base_salary DECIMAL(12,2),
  allowances DECIMAL(12,2) DEFAULT 0,
  bonus DECIMAL(12,2) DEFAULT 0,
  
  -- Deductions
  tax DECIMAL(12,2) DEFAULT 0,
  insurance DECIMAL(12,2) DEFAULT 0,
  
  -- Totals
  gross_salary DECIMAL(12,2) GENERATED ALWAYS AS
    (base_salary + allowances + bonus) STORED,
  net_salary DECIMAL(12,2) GENERATED ALWAYS AS
    (gross_salary - tax - insurance) STORED,
  
  -- Status
  status ENUM('DRAFT','FINALIZED','PAID') DEFAULT 'DRAFT',
  paid_at DATETIME,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  UNIQUE KEY unique_user_month (user_id, month_year),
  INDEX (status)
);
```

---

## HR Module Entities

### TrainingCourse
```sql
CREATE TABLE training_courses (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  instructor_id BIGINT,
  
  duration_hours INT,
  level ENUM('BEGINNER','INTERMEDIATE','ADVANCED'),
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (instructor_id) REFERENCES users(id),
  INDEX (level)
);
```

### TrainingEnrollment
```sql
CREATE TABLE training_enrollments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  course_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  
  status ENUM('ENROLLED','IN_PROGRESS','COMPLETED','FAILED') DEFAULT 'ENROLLED',
  progress_percent INT DEFAULT 0,
  
  completed_at DATETIME,
  certificate_url VARCHAR(500),
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (course_id) REFERENCES training_courses(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  UNIQUE KEY unique_user_course (course_id, user_id),
  INDEX (status)
);
```

---

## Job/Contract Entities

### Offer
```sql
CREATE TABLE offers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  job_title VARCHAR(255) NOT NULL,
  description TEXT,
  department_id BIGINT,
  manager_id BIGINT NOT NULL,
  
  requirements TEXT,
  salary_min DECIMAL(12,2),
  salary_max DECIMAL(12,2),
  job_type ENUM('FULL_TIME','PART_TIME','CONTRACT','GIG'),
  
  status ENUM('OPEN','CLOSED','FILLED') DEFAULT 'OPEN',
  posted_date DATE,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (department_id) REFERENCES departments(id),
  FOREIGN KEY (manager_id) REFERENCES users(id),
  INDEX (status),
  INDEX (job_type)
);
```

### Contract
```sql
CREATE TABLE contracts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  offer_id BIGINT,
  
  -- Contract details
  title VARCHAR(255) NOT NULL,
  contract_type ENUM('EMPLOYMENT','FREELANCE','VENDOR'),
  
  -- Status
  status ENUM('DRAFT','SIGNED','ACTIVE','EXPIRED','TERMINATED'),
  
  -- Dates
  start_date DATE,
  end_date DATE,
  
  -- Financial
  salary DECIMAL(12,2),
  signing_url VARCHAR(500), -- PDF
  
  signed_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (offer_id) REFERENCES offers(id),
  INDEX (status)
);
```

---

## Community Entities

### Group
```sql
CREATE TABLE groups (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  creator_id BIGINT NOT NULL,
  
  is_public BOOLEAN DEFAULT true,
  member_count INT DEFAULT 0,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (creator_id) REFERENCES users(id),
  INDEX (is_public)
);
```

### Post
```sql
CREATE TABLE posts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  group_id BIGINT NOT NULL,
  author_id BIGINT NOT NULL,
  
  content LONGTEXT NOT NULL,
  attachment_url VARCHAR(500),
  
  reactions_count INT DEFAULT 0,
  comments_count INT DEFAULT 0,
  
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  
  FOREIGN KEY (group_id) REFERENCES groups(id),
  FOREIGN KEY (author_id) REFERENCES users(id),
  INDEX (group_id),
  INDEX (created_at)
);
```

---

## Query Optimization

### Indexes Created By Default
```sql
-- User lookups
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_department ON users(department_id);

-- Chat queries
CREATE INDEX idx_message_room ON messages(chat_room_id, created_at);
CREATE INDEX idx_message_author ON messages(author_id);

-- Timeline queries
CREATE INDEX idx_attendance_date ON attendance(user_id, date_recorded);
CREATE INDEX idx_leaves_date_range ON leaves(user_id, start_date, end_date);

-- Frequent filters
CREATE INDEX idx_task_project_status ON tasks(project_id, status);
CREATE INDEX idx_call_initiator ON calls(caller_id, started_at);
```

### Query Patterns to Avoid
- ❌ `SELECT * FROM messages WHERE content LIKE '%keyword%'` → Use full-text search indexes
- ❌ N+1 queries (fetch user, then fetch each message's author) → Use JOIN or eager loading
- ❌ Large result sets without pagination → Always use LIMIT/OFFSET

---

## See Also
- [ARCHITECTURE.md](ARCHITECTURE.md) - System design
- [../SPRINT_PLAN.md](../SPRINT_PLAN.md) - Entity creation tasks
