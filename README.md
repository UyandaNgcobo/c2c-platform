
# C2C Marketplace Platform 

A custom-built, high-performance Consumer-to-Consumer (C2C) marketplace engineered with raw PHP, MySQL, and Vanilla JavaScript. This platform features a scalable CSS routing architecture, dynamic search prediction, and a comprehensive Trust & Safety moderation engine.

## Tech Stack

* **Backend:** PHP (Native, session-based routing)
* **Database:** MySQL (Prepared Statements)
* **Frontend:** HTML5, Vanilla JavaScript (ES6+ AJAX), Modular CSS
* **Environment:** Localhost is XAMPP

---

## Directory Architecture

/c2c-platform
├── /styles/               # Modular CSS Architecture
│   ├── base.css           # Resets and root CSS variables
│   ├── layout.css         # Structural grids and wrappers
│   ├── components.css     # Buttons, inputs, and reusable UI elements
│   ├── shared.css         # Global utilities and cross-page elements
│   ├── auth.css           # Login & Registration styling
│   ├── sell.css           # Form layouts and image dropzones
│   ├── search.css         # Search results, filtering sidebar, and grids
│   ├── storefront.css     # Seller profile cards and split-layout stores
│   └── admin.css          # Moderation dashboard and data tables
├── /includes/             # Shared PHP Components
│   ├── db.php             # MySQL connection instance
│   ├── header.php         # Master header (CSS Router Map & Navbar Logic)
│   ├── sidebar.php        # Account dashboard navigation
│   └── footer.php         # Standard footer
├── /images/               # Uploaded listing images
├── index.php              # Homepage / Main Feed
├── login.php              # Authentication entry
├── register.php           # User creation (Password hashing & duplicate checks)
├── account.php            # User dashboard (Appeals & Notifications)
├── search.php             # Master search engine & filtering
├── ajax-search.php        # JSON endpoint for the autocomplete dropdown
├── sellers.php            # Seller discovery directory
├── storefront.php         # Dedicated seller inventory & profile
├── sell.php               # Listing creation UI
├── process-listing.php    # Backend listing processor (Binds to session ID)
├── product.php            # Single product view
├── report.php             # Trust & Safety reporting form
└── admin.php              # Super-Admin Moderation Dashboard
