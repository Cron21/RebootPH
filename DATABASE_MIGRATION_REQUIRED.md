# Database Migration Required

## Issue Found
The `proposal` table's `Status` column uses an ENUM type that only includes:
- `Approved`
- `Pending`  
- `Rejected`

But the application code tries to set it to `'Postponed'`, which causes a SQL error.

## Solution
You need to update the database schema to add `'Postponed'` as a valid status.

### Method 1: Using phpMyAdmin (Recommended)
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

### Method 2: Using SQL Query
Run this query in phpMyAdmin SQL tab:

```sql
ALTER TABLE proposal 
MODIFY COLUMN Status enum('Approved','Pending','Rejected','Postponed') NOT NULL DEFAULT 'Pending';
```

### Method 3: Using MySQL Command Line
```bash
mysql -u [username] -p [database_name] < migrations/add-postponed-status.sql
```

## Verification
After running the migration, verify it worked by running:

```sql
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'proposal' AND COLUMN_NAME = 'Status';
```

You should see: `enum('Approved','Pending','Rejected','Postponed')`

## What This Fixes
✅ Postpone event action will work correctly  
✅ Delete rejected proposals will work  
✅ Delete postponed events will work  
✅ All cascading deletions will function properly

## Files Updated
- `u569378998_rebootph.sql` - Schema dump updated with new enum
- `migrations/add-postponed-status.sql` - Migration script ready to run
