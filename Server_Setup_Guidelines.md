# دليل إعداد وتشغيل السيرفر ونشر التحديثات (Server Setup & Deployment Guidelines)

يوضح هذا الدليل الهيكلية البرمجية وإعدادات التشغيل الحالية لسيرفر مشروع **Club Saas**، ليكون مرجعاً لأي مطور أو مسؤول نظام يرغب في التعامل مع السيرفر أو نشر تحديثات جديدة.

---

## 🖥️ 1. مواصفات وإعدادات السيرفر الحالي (Server Environment)
* **نظام التشغيل (OS)**: Ubuntu 26.04 LTS.
* **عنوان الـ IP الخاص بالسيرفر**: `31.70.108.63`.
* **مسار المشروع على السيرفر**: `/var/www/club`.
* **المستودع (Git Branch)**: المشروع يسحب من فرع `server` (نظام Monorepo).

---

## ⚙️ 2. الخدمات وتوزيع الأدوار (Services Architecture)

### أ. الفرونت إند (Next.js Frontend):
* **المسار**: `/var/www/club/front`.
* **طريقة التشغيل**: يعمل كخدمة في الخلفية باستخدام أداة **PM2** على المنفذ المحلي `3000`.
* **اسم العملية في PM2**: `club-front`.
* **ملف البيئة**: `/var/www/club/front/.env.local` (يحتوي على رابط الـ API للباك إند `http://127.0.0.1`).

### ب. الباك إند (Laravel Backend):
* **المسار**: `/var/www/club/backend`.
* **طريقة التشغيل**: يعمل باستخدام **PHP 8.5-FPM** ويتم توجيهه عبر Nginx.
* **ملف البيئة**: `/var/www/club/backend/.env` (يحتوي على بيانات قاعدة البيانات وبيئات الإنتاج).
* **صلاحيات المجلدات**: مجلدات `storage` و `bootstrap/cache` مملوكة للمستخدم `www-data` بصلاحيات `775`.

### ج. قاعدة البيانات (MySQL Database):
* **النوع**: MySQL Server 8.4.
* **اسم قاعدة البيانات**: `db_clubs`.
* **اسم المستخدم**: `root` (مهيأ للاتصال المحلي عبر IP `127.0.0.1` بكلمة مرور).
* **كلمة المرور**: `3Q1l933qg5NZ3vO`.

### د. خادم الويب (Nginx Web Server):
* **المسار**: `/etc/nginx/sites-available/default`.
* **آلية التوجيه (Routing)**:
  * طلبات النطاق الرئيسي `/` يتم تمريرها (Reverse Proxy) إلى Next.js على `http://localhost:3000`.
  * طلبات الـ API التي تبدأ بـ `/api/v1` يتم توجيهها إلى مجلد الباك إند `/var/www/club/backend/public/index.php`.
  * طلبات الصور والملفات التي تبدأ بـ `/storage` يتم توجيهها إلى المجلد الرمزي `/var/www/club/backend/public/storage`.

---

## 🚀 3. خطوات نشر التحديثات الجديدة (Deployment Workflow)

عند إجراء أي تعديل برمجياً ودفعه إلى فرع `server` على GitHub، اتبع الخطوات التالية لتحديث السيرفر:

### الخطوة 1: الاتصال بالسيرفر عبر SSH:
```bash
ssh root@31.70.108.63
# أدخل كلمة المرور عند طلبها
```

### الخطوة 2: سحب الكود الجديد من Git:
```bash
cd /var/www/club
git pull origin server
```

### الخطوة 3: تحديث الباك إند (Laravel) - في حال وجود تعديلات باك إند:
```bash
cd /var/www/club/backend

# السماح للملحقات بالعمل بصلاحيات الروت
export COMPOSER_ALLOW_SUPERUSER=1

# تثبيت الاعتماديات الجديدة وعمل أوبتيمايز
composer install --no-dev --optimize-autoloader

# تشغيل الهجرات الجديدة (Migrations)
php artisan migrate --force

# إعادة بناء الكاش لتسريع الاستجابة
php artisan config:cache
php artisan route:cache
php artisan view:cache

# إعادة تعيين صلاحيات المجلدات لتجنب أخطاء الكتابة 500
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### الخطوة 4: تحديث الفرونت إند (Next.js) - في حال وجود تعديلات فرونت إند:
```bash
cd /var/www/club/front

# تثبيت المكتبات الجديدة
npm install

# بناء المشروع للإنتاج
npm run build

# إعادة تشغيل الفرونت إند عبر PM2
pm2 restart club-front
```

---

## 🛠️ 4. أوامر الصيانة والتشغيل الهامة (Useful Commands)

### إدارة خادم الويب والخدمات:
* **إعادة تشغيل Nginx**: `systemctl restart nginx`
* **إعادة تشغيل قاعدة البيانات**: `systemctl restart mysql`
* **إعادة تشغيل PHP-FPM**: `systemctl restart php8.5-fpm`
* **فحص سجلات الأخطاء لـ Nginx**: `tail -n 50 /var/log/nginx/error.log`

### إدارة تطبيق Next.js عبر PM2:
* **عرض حالة التطبيقات شغال/مطفأ**: `pm2 status`
* **عرض سجلات الأخطاء والمخرجات الحية**: `pm2 logs`
* **إعادة تشغيل الفرونت إند**: `pm2 restart club-front`
* **مراقبة استهلاك المعالج والرام بشكل حي**: `pm2 monit`

---

## 🔄 5. نظام النشر التلقائي (CI/CD)
تم تفعيل نظام النشر التلقائي بالكامل عبر **GitHub Actions**. بمجرد دفع أي تعديل لفرع `server` على GitHub، سيتصل النظام آلياً بالسيرفر وينفذ ملف `/root/deploy.sh` لتحديث البنية والواجهات بشكل ذكي ومؤتمت بالكامل.

