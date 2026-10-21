# Gofix Admin — easy Hinglish notes

## Local mein kaise kholen

Laragon start karke browser mein ye URL kholen:

`http://localhost/fixlify/Customer/Admin/login.php`

Admin login ke baad dashboard open hota hai. Sidebar se baaki pages par ja sakte hain.

## Har page ka kaam

- `dashboard.php` — total bookings, customers, providers aur services ka summary.
- `bookings.php` — booking dekhna, status update karna aur provider assign karna.
- `providers.php` — provider profile aur approval/status manage karna.
- `customers.php` — customer list aur account status manage karna.
- `services.php` — service add, edit, activate ya deactivate karna.
- `payments.php` — payment records aur payment status dekhna.
- `reports.php` — date range ke hisaab se booking/payment report.
- `admin_contact.php` — customer ke bheje messages padhna aur reply/manage karna.
- `login.php`, `auth.php`, `logout.php` — admin login, session check aur logout.
- `admin.php` — initial admin account banane wala setup page.
- `provider_document.php`, `provider_photo.php` — provider ke private ID document aur photo ko admin ko dikhate hain.
- `sidebar.php`, `admin-ui.css`, `admin-ui.js` — sab admin pages ka shared menu, design aur mobile menu.

## Teacher ko flow kaise samjhayen

1. Admin `login.php` mein email/password bhejta hai.
2. PHP database mein admin check karke session mein `admin_id` rakhta hai.
3. Har protected page `auth.php` include karta hai; login na ho to login page par bhejta hai.
4. Page `db.php` se database connection leta hai aur SQL se apna data load karta hai.
5. Forms `POST` ke through action bhejte hain; page database update karke message dikhata hai.
6. `sidebar.php` active page pehchaan kar ek hi navigation sab jagah dikhata hai.

## Customer booking se provider job tak

1. Customer `Customer/booking.php` mein service aur location deta hai.
2. Booking `pending` status mein save hoti hai; abhi kisi provider ko final job nahi milti.
3. Usi service aur matching area/village/city/PIN ke active providers ko `provider_notifications` mein request milti hai.
4. Provider pages har 10 second mein nayi request check karte hain; dashboard badge/toast update hota hai.
5. Provider accept karte waqt database booking ko tabhi assign karta hai jab woh abhi tak kisi aur ko assign na hui ho. Isliye pehla successful accept job leta hai; baaki providers ki request band ho jaati hai.
6. Admin `bookings.php` se open request kisi active provider ko assign kar sakta hai. Assignment ke baad baaki providers ki pending request band hoti hai.

## PHP file check command

Laragon PHP se kisi file ka syntax check:

```powershell
D:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe -l Customer\Admin\dashboard.php
```

`dashboard.php` ki jagah jis PHP file ko check karna ho uska path likhen. `No syntax errors detected` ka matlab syntax sahi hai.

## Code ke common words

- `$_SESSION` — login ke baad admin ki pehchaan yaad rakhta hai.
- `$_POST` — form se bheji hui value leta hai.
- `prepare()` / `bind_param()` — database query mein value ko safely bhejte hain.
- `htmlspecialchars()` — page par text dikhate waqt HTML ko safe banata hai.
- `require_once` — shared PHP file ko ek baar load karta hai.

## Shared design badalna

Admin menu `sidebar.php` mein hai. Rang, spacing aur mobile layout `admin-ui.css` mein hai. Mobile menu ka behavior `admin-ui.js` mein hai. In shared files ko edit karne se sab admin pages par badlav ek saath dikhega.
