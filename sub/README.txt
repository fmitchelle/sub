راهنمای نصب (بدون فایل دیتا)

1) فایل zip را داخل مسیر موردنظر در cPanel Extract کن.
2) پروژه از فایل‌های JSON در پوشه data/ استفاده می‌کند. چون zip را بدون data دادیم، پوشه data را خودت بساز:
   - یک پوشه به نام data کنار فایل‌ها بساز (مثلاً /subpanel/data)
   - سطح دسترسی پوشه data را 755 یا 775 بگذار.
3) اولین بار که صفحات را باز کنی، فایل‌های زیر خودکار ساخته می‌شوند:
   - data/settings.json
   - data/servers.json
   - data/users.json
4) ورود:
   - login.php
   - یوزر/پسورد پیش‌فرض: hosein / hosein
   - بعد از ورود از Settings رمز را عوض کن.
5) خروجی Subscription:
   - sub.php
   - اگر توکن گذاشتی: sub.php?token=YOURTOKEN
   - تست خام: sub.php?raw=1 (یا اگر توکن داری: ...&raw=1)

نکته: خروجی این پنل Subscription Base64 از لینک‌هاست (V2Ray/Xray clients).
