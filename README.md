# WHMCS Renewals Forecast Report (Services + Addons + Domains)

This is a custom WHMCS admin report (`/modules/reports/`) that helps you see **what will renew in a selected date range**, including:

- **Active Services / Hosting renewals**
- **Active Addons renewals**
- **Active Domain renewals**

The goal is to provide a clear **renewals pipeline** so you can estimate expected revenue and workload for a given month (for example, “next month renewals”).

---

## What this report shows

For the selected date range, the report lists only **Active** items:

### Services (Hosting)
- Table: `tblhosting`
- Uses: `nextduedate`
- Status filter: `domainstatus = Active`

### Addons
- Table: `tblhostingaddons`
- Uses: `nextduedate`
- Status filter: `status = Active`

### Domains
- Table: `tbldomains`
- Uses: `expirydate` (domain expires within the date range)
- Status filter: `status = Active`

All results are shown in one combined table and sorted by date.

---

## Filters

This report includes a compact filter bar (works with WHMCS admin templates even when `$reportdata['filters']` is not rendered).

### Available filters
- **Start Date**
- **End Date**
- **Type**
  - All
  - Services
  - Addons
  - Domains

### Default behavior
If no Start/End date is provided, the report defaults to the **current month**.

---

## Why use this report?

WHMCS includes standard reports, but many users want a simple way to answer:

✅ “Which customers have renewals next month?”  
✅ “How much is expected to renew next month?”  
✅ “Which renewals are coming regardless of billing cycle?”  
✅ “Show me domains expiring in a specific month”  

This report provides all of that in a single view.

---

## Installation

1. Upload the file:

