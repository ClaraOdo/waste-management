WasteWatch - Community Waste Collection & Reporting System
SDG 11: Sustainable Cities and Communities

SETUP INSTRUCTIONS (XAMPP)

1. Install XAMPP if not already installed.

2. Copy the whole "wastewatch" folder into your XAMPP htdocs directory, e.g.:
   C:\xampp\htdocs\wastewatch        (Windows)
   /Applications/XAMPP/htdocs/wastewatch or use Sequel Pro  (macOS) 

3. Start Apache and MySQL from the XAMPP Control Panel.

4. Create the database:
   a. Open http://localhost/phpmyadmin
   b. Click "Import"
   c. Choose the file: database/wastewatch.sql
   d. Click "Go"
   This creates the "wastewatch" database, all tables, and seed data
   (zones, a sample schedule, sample reports, and the two accounts below).

5. Check includes/db.php if your MySQL uses a non-default username/password.
   Defaults used: host=localhost, user=root, password=(empty).

6. Open the system in your browser:
   http://localhost/wastewatch/

7. Log in with one of the accounts below (see also System Documentation):

   Role      Username   Password
   --------  ---------  --------------
   Admin     admin      Admin@2026
   Resident  testuser   Resident@2026

   Or register a new resident account from the Register page.

FOLDER OVERVIEW
   index.php, login.php, register.php, logout.php  - public/auth pages
   dashboard.php, report_issue.php,
   request_pickup.php, schedule.php                - resident pages
   admin/                                           - admin-only pages
   ajax/check_username.php                          - Fetch API endpoint
   includes/                                        - db connection, helpers, header/footer
   assets/css, assets/js                            - stylesheet and client-side JS
   database/wastewatch.sql                          - full schema + seed data

No external services or API keys are required. All styling/scripts are
self contained, the system works fully offline once imported into XAMPP.
