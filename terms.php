<?php require_once 'config.php'; $settings = getSettings(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الشروط والأحكام | <?php echo $settings['store_name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .terms-page { padding: 120px 20px 60px; min-height: 80vh; }
        .terms-container { max-width: 800px; margin: 0 auto; background: var(--bg-surface); padding: 40px; border-radius: 20px; border: 1px solid var(--border); }
        .terms-container h1 { color: var(--primary); margin-bottom: 30px; font-size: 2rem; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .terms-section { margin-bottom: 30px; }
        .terms-section h3 { margin-bottom: 15px; color: #fff; display: flex; align-items: center; gap: 10px; }
        .terms-section ul { list-style: none; padding: 0; }
        .terms-section ul li { margin-bottom: 10px; padding-right: 20px; position: relative; color: var(--text-dim); line-height: 1.6; }
        .terms-section ul li::before { content: "•"; color: var(--primary); position: absolute; right: 0; font-weight: bold; }
        .back-btn { display: inline-block; margin-top: 20px; color: var(--primary); text-decoration: none; font-weight: bold; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <nav class="glass-nav">
        <div class="container">
            <div class="nav-content">
                <div class="logo"><span><?php echo $settings['store_name']; ?></span></div>
                <a href="index.php" class="back-btn">العودة للرئيسية</a>
            </div>
        </div>
    </nav>

    <section class="terms-page">
        <div class="terms-container fade-up">
            <h1>الشروط والأحكام</h1>
            
            <div class="terms-section">
                <h3><i class="fa-solid fa-file-contract"></i> أحكام الحجز والإلغاء</h3>
                <ul>
                    <li>يتم تأكيد الحجز فقط بعد تحويل مبلغ العربون (50% من القيمة).</li>
                    <li>العربون غير مسترد في حال الإلغاء قبل الموعد بأقل من 24 ساعة.</li>
                    <li>يمكن تأجيل الحجز لمرة واحدة فقط بشرط الإبلاغ قبل 48 ساعة.</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> المسؤولية والتأمين</h3>
                <ul>
                    <li>العميل مسؤول مسؤولية تامة عن أي تلفيات تلحق بالجلسة أو ملحقاتها خلال فترة التأجير.</li>
                    <li>يمنع استخدام الفحم أو الحطب داخل الخيام المغلقة لتجنب حوادث الاختناق (لا سمح الله).</li>
                    <li>المتجر غير مسؤول عن ضياع المقتنيات الشخصية للعميل.</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3><i class="fa-solid fa-clock"></i> التوقيت والخدمة</h3>
                <ul>
                    <li>يبدأ وقت استلام الجلسة حسب الموعد المتفق عليه، وأي تأخير من العميل يحسب من وقت الجلسة.</li>
                    <li>يقوم فريقنا بتركيب الجلسة واستلامها في الموقع المحدد من قبل العميل.</li>
                    <li>المناطق خارج النطاق العمراني للرياض قد تخضع لرسوم توصيل إضافية.</li>
                </ul>
            </div>
            
            <a href="index.php" class="btn btn-primary" style="margin-top:20px; display:inline-block; text-align:center;">موافق والعودة للمتجر</a>
        </div>
    </section>
</body>
</html>
