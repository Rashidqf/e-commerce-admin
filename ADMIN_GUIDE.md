# Admin Quick Reference Guide

Fast reference for day-to-day admin tasks.

## Daily Tasks

### Morning Checklist
1. ✅ Check Analytics dashboard for overnight sales
2. ✅ Review pending customer reviews
3. ✅ Check for low stock alerts
4. ✅ Review new orders

### Evening Tasks
1. ✅ Update inventory after shipments
2. ✅ Approve quality customer reviews
3. ✅ Check pending orders status
4. ✅ Archive completed tasks

---

## Navigation Guide

### Main Menu
```
Dashboard          → Overview & key metrics
Products           → Manage catalog
Categories         → Organize products
Orders             → Manage customer orders
Customers          → View customer info
Coupons            → Create discount codes
Analytics          → View sales reports
Profile            → Edit admin account
```

### Product Management
```
Products (index)   → View all products
Add Product        → Create new product
Edit Product       → Modify existing
  ├─ Videos        → Add product videos
  ├─ Variants      → Create options
  ├─ Inventory     → Track stock
  └─ Reviews       → Manage ratings
```

---

## Common Tasks

### Add a New Product
1. Click Products > Add Product
2. Fill in Basic Information:
   - Name
   - Category
   - SKU
   - Price & Sale Price
   - Quantity
3. Fill in Description & Images:
   - Short/Long Description
   - Upload Main Image
   - Upload Gallery Images
4. Click "Save Product"
5. After creation:
   - Click Videos to add demo videos
   - Click Variants if product has options
   - Click Inventory to set stock

### Add Product Video (YouTube)
1. Go to Products > Edit
2. Click "Videos" button
3. Paste YouTube URL:
   - `https://www.youtube.com/watch?v=VIDEO_ID`
   - OR `https://youtu.be/VIDEO_ID`
4. Add optional title
5. Click "Add Video"

### Create Product Variant
1. Go to Products > Edit
2. Click "Variants" button
3. Fill variant info:
   - Name: "Large Red" or "256GB Space Gray"
   - SKU: "PROD-001-RED" (must be unique)
   - Price: Can differ from base
   - Stock: Quantity for this variant
   - Attributes: `{"size":"Large","color":"Red"}`
4. Click "Add Variant"

### Adjust Inventory
1. Go to Products > Edit
2. Click "Inventory" button
3. Enter new total quantity
4. Select reason:
   - Physical Stock Count
   - Restock/Purchase
   - Customer Return
   - Damaged/Defective
   - Loss/Theft
   - Manual Adjustment
5. Add notes (optional)
6. Click "Update Inventory"

### Approve Customer Reviews
1. Go to Products > Edit
2. Click "Reviews" button
3. Filter by "Pending" status
4. Read each review
5. Click "Approve" to publish
6. Or "Reject" if not suitable
7. Or "Delete" if spam

### Create Discount Coupon
1. Click "Coupons" in sidebar
2. Fill coupon form:
   - Code: "SUMMER20" (auto-uppercase)
   - Type: Fixed ($) or Percentage (%)
   - Value: 20 (for 20% or $20)
   - Min Order: (optional) e.g., $50
   - Max Uses: (optional) e.g., 100
   - Expiry Date: (optional)
3. Click "Create Coupon"

### Check Sales Analytics
1. Click "Analytics" in sidebar
2. View Key Metrics:
   - Total Revenue
   - Total Orders
   - Customer Count
   - Product Count
3. View Charts:
   - Sales trends (last 30 days)
   - Customer segments
4. View Reports:
   - Top selling products
   - Low stock alerts

---

## Quick Stats

### Dashboard Shortcuts
```
Cards show:
- Total Revenue          → All-time revenue
- Total Orders          → All-time orders
- Total Customers       → Registered count
- Active Products       → Plus low stock count

Badges show:
- Today's Revenue       → Sales from today
- This Month Orders     → Current month only
- Pending Orders        → Awaiting processing
- Pending Reviews       → New reviews to approve
- Low Stock Count       → Products < 10 units
```

### Product Quick Stats
When editing product:
- Average rating (0-5 stars)
- Total reviews (approved only)
- Current stock level
- Total inventory (product + variants)

---

## Database Concepts

### Products vs Variants
```
Product (Base)
├── Name: "T-Shirt"
├── Price: $20
├── Stock: 100
└── Variant 1: Large Red
    ├── SKU: TSHIRT-01-R-L
    ├── Price: $20
    └── Stock: 30
└── Variant 2: Large Blue
    ├── SKU: TSHIRT-01-B-L
    ├── Price: $20
    └── Stock: 25
```

### Order Status Flow
```
Order Created (pending)
    ↓
Payment Received (payment_status: paid)
    ↓
Processing (order_status: processing)
    ↓
Shipped (order_status: shipped)
    ↓
Delivered (order_status: delivered)
```

### Review Status
```
Submitted (pending)     → Admin approval needed
Published (approved)    → Shows on product page
Rejected (rejected)     → Hidden from public
Deleted                 → Permanently removed
```

### Coupon Usage
```
Created with max_uses: 100
After use: used_count increases
When used_count = max_uses: Coupon exhausted
Can disable coupon without deleting
```

---

## Troubleshooting Quick Fixes

### Video won't play
- Check URL format is correct
- Verify video is public
- Try different URL format
- Check browser allows embeds

### Variant not showing
- Verify status is "active"
- Check product ID matches
- Ensure quantity > 0
- Refresh page

### Coupon not working
- Check status is "active"
- Verify expiry date hasn't passed
- Check minimum order met
- Verify max uses not reached

### No analytics data
- Ensure orders exist
- Check correct date range
- Verify analytics tables populated
- Refresh page

---

## Time-Saving Tips

### Bulk Updates
1. Use inventory adjustment reason selection
2. Add batch notes for multiple changes
3. Approve multiple reviews at once

### Quick Navigation
- Use sidebar for main sections
- Quick action buttons on product edit
- Search/filter in all lists

### Mobile Friendly
- All pages responsive
- Touch-friendly buttons
- Mobile-optimized forms

---

## Monthly Tasks

### Month-End Review
1. Export sales report (from analytics)
2. Review top/bottom products
3. Check customer growth
4. Archive old coupons
5. Clean up inventory logs

### Inventory Audit
1. Do physical stock count
2. Update in Inventory management
3. Select "Physical Stock Count" reason
4. Compare with system count
5. Investigate discrepancies

### Promotional Planning
1. Review successful coupons
2. Plan next month promotions
3. Create new discount codes
4. Set appropriate expiry dates

---

## Security Reminders

✅ DO:
- Use strong admin password
- Change password regularly
- Log out when leaving
- Report suspicious activity
- Back up database weekly

❌ DON'T:
- Share login credentials
- Use same password everywhere
- Leave admin panel open
- Click suspicious links
- Forget to back up

---

## Performance Tips

### For Customers
- Use compressed images (< 2MB)
- Keep video titles concise
- Add clear product descriptions

### For Admin
- Review pending items daily
- Archive old logs monthly
- Check analytics weekly
- Update coupons regularly

---

## Helpful Formulas

### Discount Calculations
```
Fixed Discount:      Order Total - $Discount
Percentage Discount: Order Total × (1 - Percentage/100)

Example:
Fixed ($10): $100 order - $10 = $90
Percent (20%): $100 × (1 - 20/100) = $100 × 0.8 = $80
```

### Coupon Value Setting
```
For $20 off: Type=Fixed, Value=20
For 15% off: Type=Percentage, Value=15
```

### Stock Calculations
```
Base Product + All Variants = Total Available
Example:
Main: 50 units
Variant 1: 30 units
Variant 2: 25 units
Total: 105 units available
```

---

## Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Search | Ctrl+F (browser default) |
| Refresh | F5 or Ctrl+R |
| Back | Alt+← |
| Forward | Alt+→ |
| Admin menu | ? (if enabled) |

---

## Emergency Procedures

### Disable a Coupon Immediately
1. Go to Coupons
2. Click "Disable" on coupon
3. Done - no code can use it

### Stop Accepting a Product
1. Go to Products > Edit
2. Change Status to "Inactive"
3. Done - won't show to customers

### Urgent Inventory Adjustment
1. Go to Products > Edit
2. Click Inventory
3. Update quantity to safe level
4. Select "Adjustment" reason
5. Click Update

---

## Contact Information

For technical support:
- Check FEATURES.md for full documentation
- Check UPGRADE_GUIDE.md for setup
- Review troubleshooting sections
- Contact system administrator

---

**Remember:** When in doubt, check the documentation or contact support!

---

*Last Updated: Version 2.0*
