# SynergyGig - Complete Testing & Optimization Plan
## Before/After Methodology

---

## PHASE 1: PREPARATION & BASELINE DOCUMENTATION

### Step 1.1: Identify All Modules
- [ ] User Management
- [ ] HR Management
- [ ] Payroll
- [ ] Recruitment
- [ ] Training
- [ ] Communication/Community
- [ ] project/tasks
- [ ] Other modules (list here)

### Step 1.2: Create Documentation Structure
```
docs/
├── quality-reports/
│   ├── BEFORE/
│   │   ├── phpstan_errors.md
│   │   ├── phpunit_results.md
│   │   └── doctrine_doctor_issues.md
│   ├── AFTER/
│   │   ├── phpstan_errors.md
│   │   ├── phpunit_results.md
│   │   └── doctrine_doctor_issues.md
│   └── FINAL_REPORT.md
```

### Step 1.3: Take Screenshots Locations
- Screenshots stored in: `docs/quality-reports/screenshots/`
  - `BEFORE_phpstan_module_*.png`
  - `BEFORE_phpunit_module_*.png`
  - `BEFORE_doctrine_doctor_*.png`
  - (Same pattern for AFTER)

---

## PHASE 2: BEFORE ANALYSIS (Current State)

### Step 2.1: PHPStan Analysis (BEFORE)

**For Each Module:**
```bash
# Clear cache first
php bin/console cache:clear

# Analyze module
vendor/bin/phpstan analyse src/Controller/<ModuleName>Controller.php
vendor/bin/phpstan analyse src/Entity/<ModuleName>.php
vendor/bin/phpstan analyse src/Repository/<ModuleName>Repository.php
vendor/bin/phpstan analyse src/Service/<ModuleName>*.php
```

**Document:**
- [ ] Total errors count per module
- [ ] Error types (typing, null checks, undefined methods, etc.)
- [ ] Screenshot of full output
- [ ] Save to: `docs/quality-reports/BEFORE/phpstan_errors.md`

**Template:**
```markdown
# PHPStan - BEFORE Analysis

## Module: [Module Name]

**Date:** [Date]
**Total Errors:** [X]

### Errors by Type:
- Typing issues: [count]
- Null checks: [count]
- Undefined methods: [count]
- Other: [count]

### Top 5 Errors:
1. [error]
2. [error]
...

### Screenshot:
![Before PHPStan](screenshots/BEFORE_phpstan_module_[name].png)
```

---

### Step 2.2: PHPUnit - Unit Tests (BEFORE)

**For Each Module:**
```bash
# Create test files if not exist
php bin/console make:test

# Run tests for module
php vendor/bin/phpunit tests/Service/<ModuleName>Test.php --testdox

# Get coverage
php vendor/bin/phpunit tests/Service/<ModuleName>Test.php --coverage-text
```

**Document:**
- [ ] Number of test methods created
- [ ] Pass/fail count
- [ ] Code coverage %
- [ ] Screenshot of output
- [ ] Save to: `docs/quality-reports/BEFORE/phpunit_results.md`

**Template:**
```markdown
# Unit Tests - BEFORE Analysis

## Module: [Module Name]

**Date:** [Date]
**Test File:** tests/Service/[ModuleName]Test.php

### Test Results:
- Total Tests: [X]
- Passing: [X]
- Failing: [X]
- Code Coverage: [X%]

### Business Rules Tested:
1. Rule 1: [description]
2. Rule 2: [description]
...

### Screenshot:
![Before PHPUnit](screenshots/BEFORE_phpunit_module_[name].png)
```

---

### Step 2.3: Doctrine Doctor Analysis (BEFORE)

**Steps:**
```bash
1. Start Symfony server: symfony server:start
2. Navigate to any page in dev environment
3. Open Symfony Web Profiler (toolbar at bottom)
4. Click "Doctrine Doctor" panel
5. Screenshot the dashboard
6. Click each category: Integrity, Security, Configuration, Performance
7. Screenshot each category
```

**Document:**
- [ ] Total issues count
- [ ] Critical issues count
- [ ] Warnings count
- [ ] Info count
- [ ] Issues by category
- [ ] Screenshots of each section
- [ ] Save to: `docs/quality-reports/BEFORE/doctrine_doctor_issues.md`

**Template:**
```markdown
# Doctrine Doctor - BEFORE Analysis

**Date:** [Date]
**Database:** [DB name]
**Database Version:** [version]

### Issue Summary:
| Category | Count |
|----------|-------|
| Critical | [X]   |
| Warning  | [X]   |
| Info     | [X]   |
| **Total**| **[X]**|

### Issue Categories:
1. **Integrity Issues** ([X]):
   - Missing orphanRemoval
   - Cascade issues
   - ...

2. **Security Issues** ([X]):
   - Sensitive fields
   - ...

3. **Configuration Issues** ([X]):
   - Table naming
   - ...

4. **Performance Issues** ([X]):
   - N+1 queries
   - ...

### Screenshots:
- ![Dashboard](screenshots/BEFORE_doctrine_doctor_dashboard.png)
- ![Integrity](screenshots/BEFORE_doctrine_doctor_integrity.png)
- ![Security](screenshots/BEFORE_doctrine_doctor_security.png)
```

---

## PHASE 3: FIXING & OPTIMIZATION

### Step 3.1: Fix PHPStan Errors

**For Each Module, Fix Issues By Type:**

1. **Add Return Types:**
   ```php
   // Before
   public function getTitle() { return $this->title; }
   
   // After
   public function getTitle(): string { return $this->title; }
   ```

2. **Add Type Hints:**
   ```php
   // Before
   public function process($data) { ... }
   
   // After
   public function process(array $data): void { ... }
   ```

3. **Handle Null Checks:**
   ```php
   // Before
   $user->getId();
   
   // After
   if ($user !== null) {
       $user->getId();
   }
   ```

**After Each Fix:**
```bash
php bin/console cache:clear
vendor/bin/phpstan analyse [file_path]
```

---

### Step 3.2: Create & Run Unit Tests

**For Each Module, Create Tests For:**

1. **Valid Entity:**
   ```php
   public function testValidEntity() {
       $entity = new Entity();
       $entity->setField('value');
       $this->assertTrue($entity->isValid());
   }
   ```

2. **Invalid Data (Required Fields):**
   ```php
   public function testMissingRequiredField() {
       $this->expectException(InvalidArgumentException::class);
       $entity = new Entity();
       $entity->validate();
   }
   ```

3. **Business Rules:**
   ```php
   public function testBusinessRule() {
       // Test specific business logic
   }
   ```

**Run Tests:**
```bash
php vendor/bin/phpunit tests/Service/[ModuleName]Test.php --testdox
```

---

### Step 3.3: Fix Doctrine Doctor Issues

**Priority Order:**

1. **CRITICAL - Integrity** (Must fix):
   - Add `orphanRemoval=true` to OneToMany
   - Fix cascade configurations
   - Add nullable constraints

2. **HIGH - Security** (Should fix):
   - Mark sensitive fields
   - Add serialization exclusions

3. **MEDIUM - Configuration** (Should fix):
   - Fix table naming
   - Standardize column types

4. **LOW - Performance** (Nice to have):
   - Add indexes
   - Optimize queries

**Example Fix - orphanRemoval:**
```php
// Before
#[ORM\OneToMany(targetEntity: Child::class, mappedBy: 'parent')]
private Collection $children;

// After
#[ORM\OneToMany(targetEntity: Child::class, mappedBy: 'parent', orphanRemoval: true)]
private Collection $children;
```

**After Each Fix:**
```bash
symfony server:stop
php bin/console cache:clear
symfony server:start
# Reload page in browser
# Check Doctrine Doctor panel
```

---

## PHASE 4: AFTER ANALYSIS (Final State)

### Step 4.1: PHPStan Analysis (AFTER)
- [ ] Run same analysis as Step 2.1
- [ ] Document final error count
- [ ] Compare: `Initial Errors - Final Errors = Fixed`
- [ ] Screenshot
- [ ] Save to: `docs/quality-reports/AFTER/phpstan_errors.md`

### Step 4.2: PHPUnit (AFTER)
- [ ] Run same tests as Step 2.2
- [ ] Document final test count & pass rate
- [ ] Target: 100% pass rate
- [ ] Screenshot
- [ ] Save to: `docs/quality-reports/AFTER/phpunit_results.md`

### Step 4.3: Doctrine Doctor (AFTER)
- [ ] Run same analysis as Step 2.3
- [ ] Document final issue count
- [ ] Target: 0 critical, 0 warnings (or close)
- [ ] Screenshots
- [ ] Save to: `docs/quality-reports/AFTER/doctrine_doctor_issues.md`

---

## PHASE 5: FINAL REPORT GENERATION

### Step 5.1: Create Comparison Table

```markdown
# Final Quality Assurance Report - SynergyGig

## Executive Summary

| Tool | Before | After | Fixed |
|------|--------|-------|-------|
| **PHPStan Total Errors** | [X] | [Y] | [X-Y] ✓ |
| **Unit Tests Passing** | [X]% | [Y]% | +[Y-X]% ✓ |
| **Doctrine Doctor Issues** | [X] | [Y] | [X-Y] ✓ |

---

## Module-by-Module Results

### Module 1: [Module Name]

#### PHPStan
| Metric | Before | After | Status |
|--------|--------|-------|--------|
| Total Errors | [X] | [Y] | ✓ Fixed |
| Typing Errors | [X] | [Y] | ✓ |
| Null Checks | [X] | [Y] | ✓ |

**Screenshot Comparison:**
- [Before](screenshots/BEFORE_phpstan_module_1.png)
- [After](screenshots/AFTER_phpstan_module_1.png)

#### Unit Tests
| Metric | Before | After | Status |
|--------|--------|-------|--------|
| Tests Created | [X] | [Y] | ✓ |
| Tests Passing | [X]% | [Y]% | ✓ |
| Code Coverage | [X]% | [Y]% | ✓ |

**Test Results:**
- testValidEntity: ✓ PASS
- testMissingRequiredField: ✓ PASS
- testBusinessRule: ✓ PASS

**Screenshot Comparison:**
- [Before](screenshots/BEFORE_phpunit_module_1.png)
- [After](screenshots/AFTER_phpunit_module_1.png)

#### Doctrine Doctor
| Category | Before | After | Status |
|----------|--------|-------|--------|
| Critical | [X] | [Y] | ✓ |
| Warning | [X] | [Y] | ✓ |
| Info | [X] | [Y] | ✓ |

**Issues Fixed:**
- ✓ orphanRemoval on Books relationship
- ✓ Nullable constraints on fields
- ...

**Screenshot Comparison:**
- [Before Dashboard](screenshots/BEFORE_doctrine_doctor_dashboard.png)
- [After Dashboard](screenshots/AFTER_doctrine_doctor_dashboard.png)

---

[Repeat for each module]

---

## Overall Statistics

### Code Quality Improvement
- **Total PHPStan Errors Fixed:** [X] → [Y] (-[Z]%)
- **Unit Test Coverage:** [X]% → [Y]% (+[Z]%)
- **Database Integrity:** [X] issues → [Y] issues (-[Z]%)

### Time & Effort
- **Total Hours Spent:** [X]
- **Modules Improved:** [Y]
- **Tests Created:** [Z]

### Conclusion
✓ Application is now production-ready with:
- Zero critical code quality issues
- Comprehensive unit test coverage
- Optimized database configuration
```

---

## EXECUTION CHECKLIST

### BEFORE Phase
- [ ] PHPStan analysis all modules (document)
- [ ] Create unit tests (document baseline)
- [ ] Doctrine Doctor analysis (document)
- [ ] Screenshots taken for all

### FIXING Phase
- [ ] Module 1: PHPStan fixes
- [ ] Module 1: Unit tests created & passing
- [ ] Module 1: Doctrine Doctor issues fixed
- [ ] [Repeat for each module]

### AFTER Phase
- [ ] PHPStan re-analysis all modules
- [ ] PHPUnit confirm all tests passing
- [ ] Doctrine Doctor re-analysis
- [ ] Final screenshots

### REPORT Phase
- [ ] Comparison tables created
- [ ] Final report assembled
- [ ] Screenshots organized
- [ ] Documentation complete

---

## Commands Reference

```bash
# PHPStan
vendor/bin/phpstan analyse src/
vendor/bin/phpstan analyse src/Controller/UserController.php
vendor/bin/phpstan version

# PHPUnit
php bin/console make:test
php vendor/bin/phpunit tests/Service/UserManagerTest.php --testdox
php vendor/bin/phpunit tests/ --coverage-text

# Cache & Server
php bin/console cache:clear
symfony server:start
symfony server:stop

# Doctrine
php bin/console doctrine:schema:update --dump-sql
php bin/console doctrine:migrations:generate
```

---

## Template Files Ready to Use
- ✓ BEFORE Analysis templates
- ✓ AFTER Analysis templates
- ✓ Comparison templates
- ✓ Final report template
