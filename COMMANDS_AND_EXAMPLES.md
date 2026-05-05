# SynergyGig - Commands & Code Examples Reference

> Quick copy-paste commands and code patterns for each testing phase

---

## TABLE OF CONTENTS
1. [PHPStan Commands](#phpstan-commands)
2. [PHPUnit Commands](#phpunit-commands)
3. [Doctrine Doctor Commands](#doctrine-doctor-commands)
4. [Code Examples](#code-examples)
5. [Common Issues & Solutions](#common-issues--solutions)

---

## PHPSTAN COMMANDS

### Basic Analysis Commands

```bash
# Analyze entire src folder
vendor/bin/phpstan analyse src

# Analyze specific module
vendor/bin/phpstan analyse src/Controller/UserController.php
vendor/bin/phpstan analyse src/Entity/User.php
vendor/bin/phpstan analyse src/Service/UserManager.php

# Analyze with specific level (0-9, default 5)
vendor/bin/phpstan analyse src --level 8

# Generate baseline (save current state)
vendor/bin/phpstan analyse src --generate-baseline

# Check version
vendor/bin/phpstan version

# Save output to file
vendor/bin/phpstan analyse src > phpstan_report.txt 2>&1
```

### Configuration File

**File:** `phpstan.neon` (at project root)

```neon
parameters:
    level: 5
    paths:
        - src
    excludes:
        - tests
    ignoreErrors:
        # Temporary ignores (replace with actual line patterns)
        # - '#Call to an undefined method#'
```

### Output Capture for Before/After

```bash
# Clear cache first
php bin/console cache:clear

# Analyze and capture BEFORE
vendor/bin/phpstan analyse src/Controller/UserController.php 2>&1 | tee before_phpstan.txt

# [Make fixes]

# Analyze and capture AFTER
vendor/bin/phpstan analyse src/Controller/UserController.php 2>&1 | tee after_phpstan.txt

# Compare
diff before_phpstan.txt after_phpstan.txt
```

---

## PHPUNIT COMMANDS

### Test Creation

```bash
# Generate test file interactively
php bin/console make:test

# Generate specific test
php bin/console make:test --test-type=TestCase UserManagerTest

# Generate WebTestCase (for controllers)
php bin/console make:test --test-type=WebTestCase UserControllerTest
```

### Running Tests

```bash
# Run all tests
php vendor/bin/phpunit

# Run specific test file
php vendor/bin/phpunit tests/Service/UserManagerTest.php

# Run with testdox format (readable output)
php vendor/bin/phpunit tests/Service/UserManagerTest.php --testdox

# Run with coverage report
php vendor/bin/phpunit tests/ --coverage-text

# Run specific test method
php vendor/bin/phpunit tests/Service/UserManagerTest.php --filter testValidUser

# Run and generate HTML coverage report
php vendor/bin/phpunit --coverage-html=coverage/
```

### Test Output Capture

```bash
# Save BEFORE test results
php vendor/bin/phpunit tests/Service/UserManagerTest.php --testdox 2>&1 | tee before_tests.txt

# [Write/fix tests and code]

# Save AFTER test results
php vendor/bin/phpunit tests/Service/UserManagerTest.php --testdox 2>&1 | tee after_tests.txt

# Generate summary
echo "=== TEST RESULTS COMPARISON ===" >> test_comparison.txt
echo "BEFORE:" >> test_comparison.txt
cat before_tests.txt >> test_comparison.txt
echo -e "\n\nAFTER:" >> test_comparison.txt
cat after_tests.txt >> test_comparison.txt
```

### PHPUnit Configuration

**File:** `phpunit.xml.dist` (already exists in Symfony)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTestsThatDoNotTestAnything="true">
    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## DOCTRINE DOCTOR COMMANDS

### Server Management

```bash
# Start Symfony server
symfony server:start

# Start in background
symfony server:start -d

# Stop server
symfony server:stop

# Check server status
symfony server:status

# View logs
symfony server:log
```

### Cache Management

```bash
# Clear all caches
php bin/console cache:clear

# Clear specific cache (dev)
php bin/console cache:clear --env=dev

# Warmup cache
php bin/console cache:warmup
```

### Accessing Doctrine Doctor

**Browser Steps:**
1. Navigate to any page in dev environment
2. Look for Symfony Profiler toolbar at bottom right
3. Click on it to expand
4. Find "Doctrine Doctor" panel
5. Click to open profiler detail view

**Direct URL Pattern:**
```
http://127.0.0.1:8000/_profiler/latest?panel=doctrinedoctor
```

### Screenshots Automation Script

```bash
#!/bin/bash
# Script to help with taking screenshots (manual, but organized)

mkdir -p docs/quality-reports/screenshots/BEFORE
mkdir -p docs/quality-reports/screenshots/AFTER

echo "=== Taking BEFORE Screenshots ==="
echo "1. Navigate to your app page"
echo "2. Open Symfony Profiler"
echo "3. Click Doctrine Doctor"
echo "4. Take screenshots and save to: docs/quality-reports/screenshots/BEFORE/"
echo ""
echo "Screenshot names to use:"
echo "  - doctrine_doctor_dashboard.png (full dashboard)"
echo "  - doctrine_doctor_integrity.png"
echo "  - doctrine_doctor_security.png"
echo "  - doctrine_doctor_configuration.png"
echo "  - doctrine_doctor_performance.png"
```

### Database Inspection

```bash
# View database schema
php bin/console doctrine:schema:validate

# Show mapping info
php bin/console doctrine:mapping:info

# List all entities
php bin/console doctrine:mapping:entity-info
```

---

## CODE EXAMPLES

### Example 1: Add Return Type (PHPStan Fix)

**BEFORE:**
```php
<?php
namespace App\Service;

class UserManager {
    public function getName() {
        return $this->name;
    }
}
```

**AFTER:**
```php
<?php
namespace App\Service;

class UserManager {
    public function getName(): string {
        return $this->name;
    }
}
```

---

### Example 2: Add Parameter Types (PHPStan Fix)

**BEFORE:**
```php
public function process($data) {
    return array_merge($data, ['status' => 'active']);
}
```

**AFTER:**
```php
public function process(array $data): array {
    return array_merge($data, ['status' => 'active']);
}
```

---

### Example 3: Null Check (PHPStan Fix)

**BEFORE:**
```php
$user = $this->getUserRepository()->findById($id);
$userName = $user->getName();  // PHPStan error: user might be null
```

**AFTER:**
```php
$user = $this->getUserRepository()->findById($id);
if ($user === null) {
    throw new \Exception('User not found');
}
$userName = $user->getName();
```

---

### Example 4: Basic Unit Test

**File:** `tests/Service/UserManagerTest.php`

```php
<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $userManager;

    protected function setUp(): void
    {
        $this->userManager = new UserManager();
    }

    // Test 1: Valid user
    public function testValidUser(): void
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('john@example.com');
        $user->setPassword('SecurePass123');

        // This should not throw exception
        $this->assertTrue($this->userManager->validate($user));
    }

    // Test 2: Missing first name
    public function testUserMissingFirstName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $user = new User();
        $user->setLastName('Doe');
        $user->setEmail('john@example.com');
        $user->setPassword('SecurePass123');

        $this->userManager->validate($user);
    }

    // Test 3: Invalid email
    public function testUserInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('invalid-email');  // Not a valid email
        $user->setPassword('SecurePass123');

        $this->userManager->validate($user);
    }

    // Test 4: Short password
    public function testUserShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('john@example.com');
        $user->setPassword('short');  // Less than 8 characters

        $this->userManager->validate($user);
    }
}
```

---

### Example 5: Service with Validation (UserManager)

**File:** `src/Service/UserManager.php`

```php
<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        // Rule 1: First name required
        if (empty($user->getFirstName())) {
            throw new \InvalidArgumentException('First name is required');
        }

        // Rule 2: Last name required
        if (empty($user->getLastName())) {
            throw new \InvalidArgumentException('Last name is required');
        }

        // Rule 3: Valid email
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email is invalid');
        }

        // Rule 4: Password minimum 8 characters
        if (empty($user->getPassword()) || strlen($user->getPassword()) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters');
        }

        return true;
    }
}
```

---

### Example 6: Doctrine Doctor Fix - orphanRemoval

**BEFORE:** (Will show Doctrine Doctor error)
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private string $name;

    // PROBLEM: orphanRemoval is missing
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'author')]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }
}
```

**AFTER:** (Doctrine Doctor issue resolved)
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private string $name;

    // FIXED: Added orphanRemoval=true
    #[ORM\OneToMany(
        targetEntity: Book::class,
        mappedBy: 'author',
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }
}
```

---

### Example 7: Doctrine Doctor Fix - Nullable Constraints

**BEFORE:**
```php
#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
private User $user;
```

**AFTER:**
```php
#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(
    name: 'user_id',
    referencedColumnName: 'id',
    nullable: false  // Explicitly state it's not nullable
)]
private User $user;
```

---

## COMMON ISSUES & SOLUTIONS

### Issue 1: PHPStan Command Not Found

**Error:**
```
vendor/bin/phpstan: command not found
```

**Solution:**
```bash
# Check if PHPStan is installed
ls vendor/bin/phpstan

# If missing, install:
composer require --dev phpstan/phpstan

# Try again:
vendor/bin/phpstan version
```

---

### Issue 2: Doctrine Doctor Not Appearing in Profiler

**Symptom:** Toolbar shows but no Doctrine Doctor panel

**Solution:**
```php
// File: config/bundles.php
// Add this line:
AhmedBhs\DoctrineDoctor\DoctrineDoctorBundle::class => ['dev' => true],

// Then:
php bin/console cache:clear
symfony server:stop
symfony server:start
```

---

### Issue 3: Unit Tests Have Parse Errors

**Error:**
```
Parse error: syntax error, unexpected T_FUNCTION
```

**Solution:**
```bash
# Check PHP version
php -v

# Ensure test file uses proper namespace
# Should start with:
<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;

class YourTest extends TestCase
{
    // ...
}
```

---

### Issue 4: Cache Not Clearing Issues

**If cache clear fails:**
```bash
# Hard stop
symfony server:stop

# Delete cache directory manually
rm -rf var/cache/dev/
rm -rf var/cache/prod/

# Start fresh
php bin/console cache:clear
symfony server:start
```

---

### Issue 5: Screenshot Can't Find Profiler

**Solution:**
1. Make sure you're in **dev** environment
2. Check URL has `?_profiler` in it
3. Try: `http://127.0.0.1:8000/?_profiler`
4. Scroll to bottom - toolbar should appear

---

## Quick Copy-Paste Workflow

### For Each Module - Complete Workflow

```bash
#!/bin/bash
# Replace [MODULE] with actual module name

MODULE="User"
MODULE_LOWER="user"

echo "=== PHASE 1: BEFORE ==="
php bin/console cache:clear
vendor/bin/phpstan analyse src/Controller/${MODULE}Controller.php | tee before_phpstan_${MODULE_LOWER}.txt
php vendor/bin/phpunit tests/Service/${MODULE}ManagerTest.php --testdox | tee before_phpunit_${MODULE_LOWER}.txt
echo "✓ Take Doctrine Doctor screenshots now"

echo ""
echo "=== PHASE 2: FIXES ==="
echo "Edit files and make fixes..."
echo "Run: vendor/bin/phpstan analyse src/Controller/${MODULE}Controller.php"
echo "Run: php vendor/bin/phpunit tests/Service/${MODULE}ManagerTest.php --testdox"

echo ""
echo "=== PHASE 3: AFTER ==="
php bin/console cache:clear
vendor/bin/phpstan analyse src/Controller/${MODULE}Controller.php | tee after_phpstan_${MODULE_LOWER}.txt
php vendor/bin/phpunit tests/Service/${MODULE}ManagerTest.php --testdox | tee after_phpunit_${MODULE_LOWER}.txt
echo "✓ Take Doctrine Doctor screenshots now"

echo ""
echo "=== COMPARISON ==="
diff before_phpstan_${MODULE_LOWER}.txt after_phpstan_${MODULE_LOWER}.txt
```

---

## File Organization Reference

```
SynergyGig/
├── src/
│   ├── Controller/
│   │   ├── UserController.php
│   │   ├── PayrollController.php
│   │   └── ...
│   ├── Service/
│   │   ├── UserManager.php
│   │   ├── PayrollManager.php
│   │   └── ...
│   └── Entity/
│       ├── User.php
│       ├── Payroll.php
│       └── ...
├── tests/
│   └── Service/
│       ├── UserManagerTest.php
│       ├── PayrollManagerTest.php
│       └── ...
├── docs/
│   └── quality-reports/
│       ├── BEFORE/
│       │   ├── phpstan_user.md
│       │   ├── phpunit_user.md
│       │   └── doctrine_doctor_user.md
│       ├── AFTER/
│       │   ├── phpstan_user.md
│       │   ├── phpunit_user.md
│       │   └── doctrine_doctor_user.md
│       └── screenshots/
│           ├── BEFORE_phpstan_user.png
│           ├── BEFORE_phpunit_user.png
│           ├── AFTER_phpstan_user.png
│           └── AFTER_phpunit_user.png
└── TESTING_OPTIMIZATION_PLAN.md
```

---

## Time Estimates

| Task | Estimated Time | Notes |
|------|-----------------|-------|
| PHPStan analysis (per module) | 5-10 min | Depends on code size |
| Create unit tests (per module) | 15-30 min | 3-5 test methods |
| Make tests pass (per module) | 20-40 min | Write service code |
| Doctrine Doctor analysis | 10-15 min | Per module |
| Fix Doctrine Doctor issues | 20-45 min | Per module, priority-based |
| Documentation | 5-10 min | Per module |
| **Total per module** | **1.5-2.5 hours** | Conservative estimate |

---

## Success Criteria

✅ **PHPStan:**
- [ ] 0 errors (or <5 if very large codebase)
- [ ] All type hints present
- [ ] No undefined method calls

✅ **Unit Tests:**
- [ ] 100% tests passing
- [ ] 3+ test methods per entity
- [ ] >70% code coverage

✅ **Doctrine Doctor:**
- [ ] 0 critical issues
- [ ] <5 warnings
- [ ] Entity relationships properly configured

---
