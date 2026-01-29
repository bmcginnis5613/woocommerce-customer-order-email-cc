# WooCommerce - Customer Order Email CC

Automatically send copies of all WooCommerce customer emails to additional email addresses stored in the customer's profile.

## Features

- ✅ Adds a custom field to WordPress user profiles for additional email addresses
- ✅ Automatically CC's those addresses on all WooCommerce related customer emails
- ✅ Supports multiple email addresses
- ✅ Validates email addresses before saving
- ✅ Works with registered customers (not guest checkouts)

## Usage

### Adding Additional Email Addresses to a Customer Profile

1. Go to **WordPress Admin → Users**
2. Click on a user to edit their profile
3. Scroll down to the **"WooCommerce Customer Order Email CC"** section
4. In the **"Additional Email Addresses"** field, enter email addresses separated by commas:
   ```
   manager@company.com, assistant@company.com
   ```
5. Click **"Update user"**

### What Happens Next

When WooCommerce sends any email to this customer (order confirmation, order failed, etc.), the additional email addresses will automatically receive a copy via carbon copy.
