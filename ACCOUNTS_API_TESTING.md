# Accounts Module - API Testing Guide

**Module:** Accounts  
**Total Endpoints:** 24  
**Date:** December 15, 2025

---

## 🧪 Testing Prerequisites

1. **Server Running:**
   ```powershell
   cd C:\xampp\htdocs\hrms\new-backend
   php artisan serve --port=8001
   ```

2. **Get Auth Token:**
   ```http
   POST http://localhost:8001/api/v1/auth/login
   Content-Type: application/json

   {
     "email": "admin@example.com",
     "password": "password"
   }
   ```

3. **Use Token in Headers:**
   ```
   Authorization: Bearer {your-token-here}
   ```

---

## 📋 Testing Checklist

### **1. Chart of Accounts (8 endpoints)**

#### ✅ Test 1: List All Accounts
```http
GET http://localhost:8001/api/v1/chart-of-accounts
Authorization: Bearer {token}
```

**Expected:** 200 OK, array of accounts

---

#### ✅ Test 2: Get Tree Structure
```http
GET http://localhost:8001/api/v1/chart-of-accounts/tree
Authorization: Bearer {token}
```

**Expected:** 200 OK, nested tree structure

---

#### ✅ Test 3: Get Transaction Accounts Only
```http
GET http://localhost:8001/api/v1/chart-of-accounts/transaction-accounts
Authorization: Bearer {token}
```

**Expected:** 200 OK, filtered accounts where IsTransaction=1

---

#### ✅ Test 4: Get Accounts by Type (Asset)
```http
GET http://localhost:8001/api/v1/chart-of-accounts/by-type/A
Authorization: Bearer {token}
```

**Expected:** 200 OK, asset accounts only

---

#### ✅ Test 5: Create New Account
```http
POST http://localhost:8001/api/v1/chart-of-accounts
Authorization: Bearer {token}
Content-Type: application/json

{
  "HeadCode": "999999999",
  "HeadName": "Test Account",
  "PHeadName": "Cash & Cash Equivalent",
  "HeadLevel": 3,
  "HeadType": "A",
  "IsActive": true,
  "IsTransaction": true,
  "IsGL": false
}
```

**Expected:** 201 Created

---

#### ✅ Test 6: Get Specific Account
```http
GET http://localhost:8001/api/v1/chart-of-accounts/999999999
Authorization: Bearer {token}
```

**Expected:** 200 OK, account details

---

#### ✅ Test 7: Update Account
```http
PUT http://localhost:8001/api/v1/chart-of-accounts/999999999
Authorization: Bearer {token}
Content-Type: application/json

{
  "HeadName": "Test Account Updated",
  "IsActive": true
}
```

**Expected:** 200 OK, updated account

---

#### ✅ Test 8: Delete Account
```http
DELETE http://localhost:8001/api/v1/chart-of-accounts/999999999
Authorization: Bearer {token}
```

**Expected:** 200 OK

---

### **2. Vouchers (8 endpoints)**

#### ✅ Test 9: List All Vouchers
```http
GET http://localhost:8001/api/v1/vouchers?vtype=DV&is_approved=0
Authorization: Bearer {token}
```

**Expected:** 200 OK, paginated voucher list

---

#### ✅ Test 10: Create Debit Voucher
```http
POST http://localhost:8001/api/v1/vouchers/debit
Authorization: Bearer {token}
Content-Type: application/json

{
  "voucher_date": "2025-12-15",
  "debit_account": {
    "account_code": "102010001",
    "amount": 5000.00
  },
  "credit_accounts": [
    {
      "account_code": "102010002",
      "amount": 5000.00,
      "narration": "Test payment"
    }
  ],
  "narration": "Test debit voucher"
}
```

**Expected:** 201 Created, voucher number returned

**Note:** Save the voucher_no for next tests

---

#### ✅ Test 11: Get Voucher Details
```http
GET http://localhost:8001/api/v1/vouchers/{voucher_no}
Authorization: Bearer {token}
```

**Expected:** 200 OK, voucher details with transactions

---

#### ✅ Test 12: Create Credit Voucher
```http
POST http://localhost:8001/api/v1/vouchers/credit
Authorization: Bearer {token}
Content-Type: application/json

{
  "voucher_date": "2025-12-15",
  "debit_accounts": [
    {
      "account_code": "102010001",
      "amount": 3000.00,
      "narration": "Test receipt"
    }
  ],
  "credit_account": {
    "account_code": "102010002",
    "amount": 3000.00
  },
  "narration": "Test credit voucher"
}
```

**Expected:** 201 Created

---

#### ✅ Test 13: Create Contra Voucher
```http
POST http://localhost:8001/api/v1/vouchers/contra
Authorization: Bearer {token}
Content-Type: application/json

{
  "voucher_date": "2025-12-15",
  "debit_account": "102010001",
  "credit_account": "102010002",
  "amount": 1000.00,
  "narration": "Bank to cash transfer"
}
```

**Expected:** 201 Created

---

#### ✅ Test 14: Create Journal Voucher
```http
POST http://localhost:8001/api/v1/vouchers/journal
Authorization: Bearer {token}
Content-Type: application/json

{
  "voucher_date": "2025-12-15",
  "entries": [
    {
      "account_code": "102010001",
      "debit": 2000.00,
      "credit": 0,
      "narration": "Adjustment entry"
    },
    {
      "account_code": "102010002",
      "debit": 0,
      "credit": 2000.00,
      "narration": "Adjustment entry"
    }
  ],
  "narration": "Test journal voucher"
}
```

**Expected:** 201 Created

---

#### ✅ Test 15: Approve Voucher
```http
PUT http://localhost:8001/api/v1/vouchers/{voucher_no}/approve
Authorization: Bearer {token}
```

**Expected:** 200 OK

---

#### ✅ Test 16: Delete Unapproved Voucher
```http
DELETE http://localhost:8001/api/v1/vouchers/{voucher_no}
Authorization: Bearer {token}
```

**Expected:** 200 OK (only if not approved)

---

### **3. Ledgers (4 endpoints)**

#### ✅ Test 17: General Ledger Report
```http
GET http://localhost:8001/api/v1/ledgers/general?account_code=102010001&from_date=2025-01-01&to_date=2025-12-31
Authorization: Bearer {token}
```

**Expected:** 200 OK, ledger with opening/closing balance

---

#### ✅ Test 18: Cash Book
```http
GET http://localhost:8001/api/v1/ledgers/cash-book?from_date=2025-01-01&to_date=2025-12-31
Authorization: Bearer {token}
```

**Expected:** 200 OK, cash receipts and payments

---

#### ✅ Test 19: Bank Book
```http
GET http://localhost:8001/api/v1/ledgers/bank-book?from_date=2025-01-01&to_date=2025-12-31
Authorization: Bearer {token}
```

**Expected:** 200 OK, bank deposits and withdrawals

---

#### ✅ Test 20: Account Balance
```http
GET http://localhost:8001/api/v1/ledgers/account-balance/102010001?as_of_date=2025-12-31
Authorization: Bearer {token}
```

**Expected:** 200 OK, account balance as of date

---

### **4. Financial Reports (4 endpoints)**

#### ✅ Test 21: Trial Balance
```http
GET http://localhost:8001/api/v1/financial-reports/trial-balance?from_date=2025-01-01&to_date=2025-12-31&with_opening=true
Authorization: Bearer {token}
```

**Expected:** 200 OK, trial balance with totals

**Verify:** total_debit should equal total_credit

---

#### ✅ Test 22: Profit & Loss Statement
```http
GET http://localhost:8001/api/v1/financial-reports/profit-loss?from_date=2025-01-01&to_date=2025-12-31
Authorization: Bearer {token}
```

**Expected:** 200 OK, income vs expenses

---

#### ✅ Test 23: Balance Sheet
```http
GET http://localhost:8001/api/v1/financial-reports/balance-sheet?as_of_date=2025-12-31
Authorization: Bearer {token}
```

**Expected:** 200 OK, assets vs liabilities + equity

**Verify:** Assets = Liabilities + Equity

---

#### ✅ Test 24: Cash Flow Statement
```http
GET http://localhost:8001/api/v1/financial-reports/cash-flow?from_date=2025-01-01&to_date=2025-12-31
Authorization: Bearer {token}
```

**Expected:** 200 OK, cash flows categorized

---

## 🔍 Validation Tests

### **Test 25: Debit-Credit Balance Validation**
```http
POST http://localhost:8001/api/v1/vouchers/debit
Authorization: Bearer {token}
Content-Type: application/json

{
  "voucher_date": "2025-12-15",
  "debit_account": {
    "account_code": "102010001",
    "amount": 5000.00
  },
  "credit_accounts": [
    {
      "account_code": "102010002",
      "amount": 3000.00,
      "narration": "Unbalanced entry"
    }
  ]
}
```

**Expected:** 422 Unprocessable Entity  
**Error:** "Debit and Credit amounts must be equal"

---

### **Test 26: Invalid Account Code**
```http
POST http://localhost:8001/api/v1/vouchers/debit
Authorization: Bearer {token}
Content-Type: application/json

{
  "voucher_date": "2025-12-15",
  "debit_account": {
    "account_code": "INVALID_CODE",
    "amount": 5000.00
  },
  "credit_accounts": [
    {
      "account_code": "102010002",
      "amount": 5000.00
    }
  ]
}
```

**Expected:** 422 Unprocessable Entity  
**Error:** Validation error for account_code

---

### **Test 27: Delete Account with Transactions**
```http
DELETE http://localhost:8001/api/v1/chart-of-accounts/102010001
Authorization: Bearer {token}
```

**Expected:** 422 Unprocessable Entity  
**Error:** "Cannot delete account with existing transactions"

---

### **Test 28: Delete Approved Voucher**
```http
DELETE http://localhost:8001/api/v1/vouchers/{approved_voucher_no}
Authorization: Bearer {token}
```

**Expected:** 422 Unprocessable Entity  
**Error:** "Cannot delete approved voucher"

---

## 📊 Test Results Summary

| Test Category | Total Tests | Status |
|---------------|-------------|--------|
| Chart of Accounts | 8 | ⏳ Pending |
| Vouchers | 8 | ⏳ Pending |
| Ledgers | 4 | ⏳ Pending |
| Financial Reports | 4 | ⏳ Pending |
| Validation Tests | 4 | ⏳ Pending |
| **Total** | **28** | **Ready** |

---

## 🛠️ Testing Tools

### **Option 1: Postman**
1. Download Postman
2. Create new collection "HRMS Accounts"
3. Import the test requests
4. Set environment variable for token

### **Option 2: Swagger UI**
1. Navigate to: `http://localhost:8001/api/documentation`
2. Click "Authorize" and enter token
3. Test endpoints directly in browser

### **Option 3: cURL**
```bash
curl -X GET "http://localhost:8001/api/v1/chart-of-accounts" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

---

## ✅ Test Success Criteria

1. ✅ All GET requests return 200 OK
2. ✅ All POST requests return 201 Created
3. ✅ All PUT requests return 200 OK
4. ✅ All DELETE requests return 200 OK (when valid)
5. ✅ Validation errors return 422 with clear error messages
6. ✅ Unauthorized requests return 401
7. ✅ Trial balance is balanced (Debit = Credit)
8. ✅ Balance sheet is balanced (Assets = Liabilities + Equity)
9. ✅ Voucher numbering is sequential
10. ✅ Double-entry validation works

---

## 🐛 Common Issues & Solutions

### Issue 1: "Unauthenticated" Error
**Solution:** Ensure token is in Authorization header: `Bearer {token}`

### Issue 2: "Account not found"
**Solution:** Check if account exists in database. Use GET /chart-of-accounts first.

### Issue 3: "Debit and Credit must be equal"
**Solution:** Verify sum of debits equals sum of credits in your request.

### Issue 4: Trial balance not balancing
**Solution:** Check for unapproved or unposted transactions. Ensure IsPosted=1.

### Issue 5: "Cannot delete account"
**Solution:** Account has transactions or children. Mark as inactive instead.

---

## 📝 Notes

- All dates should be in format: `YYYY-MM-DD`
- All amounts should be decimal with 2 places: `1000.00`
- Voucher numbers are auto-generated
- Transactions are grouped by voucher number
- Approved vouchers cannot be deleted or modified
- Accounts with transactions cannot be deleted

---

**Testing Status:** Ready for Testing  
**Last Updated:** December 15, 2025

