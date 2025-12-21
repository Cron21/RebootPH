# Database Migration Required

## Issue Found
The `proposal` table's `Status` column has two issues:

1. **Missing ENUM value**: Only includes `Approved`, `Pending`, `Rejected` but code tries to use `Postponed`
2. **Invalid data**: Some proposals have empty Status values instead of a valid enum value

## Solution
You need to run TWO migrations in order:

### Step 1: Add 'Postponed' to Status enum

**Using phpMyAdmin (Recommended):**
1. Open phpMyAdmin
2. Select the `u569378998_rebootph` database
3. Click on the `proposal` table
4. Go to **Structure** tab
5. Find the `Status` column and click the **Edit** (pencil icon)
6. In the **Values** field, change from:
   ```
   Approved,Pending,Rejected
   ```
   To:
   ```
   Approved,Pending,Rejected,Postponed
   ```
7. Click **Save**

**Using SQL Query:**
```sql
ALTER TABLE proposal 
MODIFY COLUMN Status enum('Approved','Pending','Rejected','Postponed') NOT NULL DEFAULT 'Pending';
```

### Step 2: Fix invalid empty statuses

**Run this query in phpMyAdmin SQL tab:**

```sql
UPDATE proposal 
SET Status = 'Pending' 
WHERE Status = '' OR Status IS NULL;
```

Or paste the entire contents of:
`migrations/fix-invalid-statuses.sql`

## Verification
After running both migrations, run these verification queries:

**Simple verification - just run this query:**
```sql
DESCRIBE proposal;
```
Look for the `Status` row - the **Type** column should show:
```
enum('Approved','Pending','Rejected','Postponed')
```

**Or check for invalid statuses:**
```sql
SELECT ProposalID, Title, Status FROM proposal 
WHERE Status NOT IN ('Approved', 'Pending', 'Rejected', 'Postponed') 
OR Status = '' OR Status IS NULL;
```
Should return: **(empty result set)**

## What This Fixes
✅ Postpone event action will work correctly  
✅ Delete rejected proposals will work  
✅ Delete postponed events will work  
✅ All cascading deletions will function properly  
✅ No more "invalid enum value" errors  

## Files Created
- `migrations/add-postponed-status.sql` - Adds Postponed to enum
- `migrations/fix-invalid-statuses.sql` - Fixes empty status values

