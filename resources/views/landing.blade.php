<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="محاسب عام نظام محاسبي عربي بسيط وقوي لإدارة الفواتير والعملاء والمصروفات والتقارير للشركات الصغيرة والتجار وشركات الشحن.">
    <title>محاسب عام - نظام محاسبي بسيط لكنه قوي</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body>
    <header class="lp-header">
        <nav class="lp-nav" aria-label="القائمة الرئيسية">
            <a class="lp-brand" href="{{ url('/') }}" aria-label="محاسب عام">
                <span class="lp-brand-mark">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span>محاسب عام</span>
            </a>

            <div class="lp-nav-links">
                <a href="#features">المزايا</a>
                <a href="#workflow">طريقة العمل</a>
                <a href="#preview">النظام</a>
                <a href="#pricing">السعر</a>
            </div>

            <div class="lp-nav-actions">
                <a class="lp-login" href="{{ route('dev.login') }}">تسجيل الدخول</a>
                <a class="lp-btn lp-btn-primary lp-btn-small" href="{{ url('/admin') }}">تجربة النظام</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="lp-hero">
            <div class="lp-container lp-hero-grid">
                <div class="lp-hero-copy">
                    <span class="lp-eyebrow">للتجار، شركات الشحن، والشركات الصغيرة</span>
                    <h1>نظام محاسبي بسيط… لكنه قوي</h1>
                    <p class="lp-hero-text">إدارة الفواتير، العملاء، المصروفات، والتقارير بسهولة في مكان واحد، بلغة عربية واضحة وواجهة لا تحتاج محاسب محترف لتبدأ.</p>

                    <div class="lp-hero-actions">
                        <a class="lp-btn lp-btn-primary" href="{{ route('dev.login') }}">ابدأ الآن مجانًا</a>
                        <a class="lp-btn lp-btn-secondary" href="{{ url('/admin') }}">تجربة النظام</a>
                    </div>

                    <div class="lp-proof-strip" aria-label="مميزات سريعة">
                        <div>
                            <strong>+6</strong>
                            <span>أقسام جاهزة</span>
                        </div>
                        <div>
                            <strong>RTL</strong>
                            <span>عربي بالكامل</span>
                        </div>
                        <div>
                            <strong>PDF</strong>
                            <span>تقارير وفواتير</span>
                        </div>
                    </div>
                </div>

                <div class="lp-hero-visual" aria-label="معاينة لوحة محاسب عام">
                    <div class="lp-orbit lp-orbit-one"></div>
                    <div class="lp-orbit lp-orbit-two"></div>
                    <div class="lp-dashboard-card">
                        <div class="lp-window-bar">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="lp-dashboard-head">
                            <div>
                                <span>نظرة اليوم</span>
                                <strong>صافي الربح</strong>
                            </div>
                            <b>24,800 ج.م</b>
                        </div>
                        <div class="lp-metric-grid">
                            <div class="lp-metric-card income">
                                <span>دخل</span>
                                <strong>38,500</strong>
                            </div>
                            <div class="lp-metric-card expense">
                                <span>مصروفات</span>
                                <strong>13,700</strong>
                            </div>
                        </div>
                        <div class="lp-chart" aria-hidden="true">
                            <span class="lp-bar-62"></span>
                            <span class="lp-bar-82"></span>
                            <span class="lp-bar-45"></span>
                            <span class="lp-bar-74"></span>
                            <span class="lp-bar-58"></span>
                            <span class="lp-bar-92"></span>
                        </div>
                        <div class="lp-mini-table">
                            <div><span>فاتورة #1042</span><strong>مدفوعة</strong></div>
                            <div><span>مصروف شحن</span><strong>تم التسجيل</strong></div>
                            <div><span>عميل جديد</span><strong>نشط</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section" id="features">
            <div class="lp-container">
                <div class="lp-section-head">
                    <span class="lp-eyebrow">كل ما تحتاجه في شاشة واضحة</span>
                    <h2>محاسبة يومية بدون تعقيد</h2>
                    <p>بدل ما تضيع وقتك بين ملفات Excel ورسائل واتساب، اجمع عملياتك المالية في نظام واحد بسيط ومترتب.</p>
                </div>

                <div class="lp-features-grid">
                    <article class="lp-feature-card">
                        <span class="lp-feature-icon">ف</span>
                        <h3>إدارة الفواتير</h3>
                        <p>أنشئ فواتير مبيعات ومشتريات، تابع حالتها، واطبعها بشكل احترافي.</p>
                    </article>
                    <article class="lp-feature-card">
                        <span class="lp-feature-icon">ع</span>
                        <h3>متابعة العملاء</h3>
                        <p>اعرف أرصدة العملاء، آخر التعاملات، والمدفوعات المستحقة في لحظة.</p>
                    </article>
                    <article class="lp-feature-card">
                        <span class="lp-feature-icon">م</span>
                        <h3>تسجيل المصروفات</h3>
                        <p>سجل مصاريف التشغيل والشحن والمرتبات واربطها بتقارير الربحية.</p>
                    </article>
                    <article class="lp-feature-card">
                        <span class="lp-feature-icon">ت</span>
                        <h3>تقارير مالية لحظية</h3>
                        <p>تقارير دخل ومصروف، ربح وخسارة، وميزان مراجعة بأرقام مفهومة.</p>
                    </article>
                    <article class="lp-feature-card">
                        <span class="lp-feature-icon">ش</span>
                        <h3>إدارة الشركاء</h3>
                        <p>تابع مساهمات الشركاء، المسحوبات، والرّصيد الحالي لكل شريك.</p>
                    </article>
                    <article class="lp-feature-card">
                        <span class="lp-feature-icon">أ</span>
                        <h3>الأصول الثابتة</h3>
                        <p>سجل الأصول، تكلفة الشراء، وقيمة الإهلاك بصورة منظمة.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="lp-section lp-section-soft" id="workflow">
            <div class="lp-container">
                <div class="lp-section-head">
                    <span class="lp-eyebrow">ابدأ في دقائق</span>
                    <h2>ثلاث خطوات وتبقى حساباتك تحت السيطرة</h2>
                </div>

                <div class="lp-steps">
                    <article class="lp-step-card">
                        <span>1</span>
                        <h3>أضف عملائك</h3>
                        <p>سجل بيانات العملاء والموردين مرة واحدة، واستخدمها في كل عملية بعد ذلك.</p>
                    </article>
                    <article class="lp-step-card">
                        <span>2</span>
                        <h3>أنشئ فواتيرك</h3>
                        <p>اختر الأصناف، أضف الخصومات، وسجل الدفع أو الاستحقاق بسهولة.</p>
                    </article>
                    <article class="lp-step-card">
                        <span>3</span>
                        <h3>تابع أرباحك وتقاريرك</h3>
                        <p>شوف الدخل، المصروف، وصافي الربح بدون مصطلحات محاسبية مربكة.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="lp-section" id="preview">
            <div class="lp-container">
                <div class="lp-section-head">
                    <span class="lp-eyebrow">معاينات من النظام</span>
                    <h2>واجهة عملية تشبه شغل يومك الحقيقي</h2>
                    <p>لوحات مختصرة للفواتير والتقارير والمتابعة اليومية، مصممة لتفهم وضع شركتك بسرعة.</p>
                </div>

                <div class="lp-preview-grid">
                    <article class="lp-preview-card large">
                        <div class="lp-preview-top">
                            <span>Dashboard</span>
                            <strong>لوحة التحكم</strong>
                        </div>
                        <div class="lp-preview-kpis">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="lp-preview-bars">
                            <span class="lp-bar-72"></span>
                            <span class="lp-bar-38"></span>
                            <span class="lp-bar-86"></span>
                            <span class="lp-bar-55"></span>
                            <span class="lp-bar-68"></span>
                        </div>
                    </article>

                    <article class="lp-preview-card">
                        <div class="lp-preview-top">
                            <span>Invoices</span>
                            <strong>الفواتير</strong>
                        </div>
                        <div class="lp-lines">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </article>

                    <article class="lp-preview-card">
                        <div class="lp-preview-top">
                            <span>Reports</span>
                            <strong>التقارير</strong>
                        </div>
                        <div class="lp-donut"></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="lp-section lp-trust">
            <div class="lp-container lp-trust-grid">
                <div>
                    <span class="lp-eyebrow">مصمم للسوق العربي</span>
                    <h2>واضح لفريقك، قوي لمدير الحسابات</h2>
                </div>
                <div class="lp-trust-list">
                    <div>
                        <strong>مصمم للشركات العربية</strong>
                        <span>مصطلحات مفهومة واتجاه RTL حقيقي.</span>
                    </div>
                    <div>
                        <strong>يدعم اللغة العربية بالكامل</strong>
                        <span>من الواجهة إلى التقارير والفواتير.</span>
                    </div>
                    <div>
                        <strong>مناسب للشركات الصغيرة والمتوسطة</strong>
                        <span>ابدأ بسيطًا، وتوسع عندما يكبر نشاطك.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section" id="pricing">
            <div class="lp-container">
                <div class="lp-pricing-card">
                    <div>
                        <span class="lp-eyebrow">العرض الحالي</span>
                        <h2>ابدأ مجانًا الآن</h2>
                        <p>جرّب محاسب عام على بيانات شركتك، واكتشف كيف تتحول الحسابات اليومية من عبء إلى روتين واضح.</p>
                    </div>
                    <div class="lp-price-box">
                        <span>مجاني حاليًا</span>
                        <strong>0 ج.م</strong>
                        <a class="lp-btn lp-btn-primary" href="{{ route('dev.login') }}">إنشاء حساب</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-final-cta">
            <div class="lp-container">
                <h2>ابدأ الآن وخلّي حساباتك أسهل</h2>
                <p>نظام واحد للفواتير، المصروفات، العملاء، والتقارير. بسيط في الاستخدام وقابل للتوسع.</p>
                <a class="lp-btn lp-btn-light" href="{{ route('dev.login') }}">إنشاء حساب</a>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-container lp-footer-grid">
            <a class="lp-brand" href="{{ url('/') }}">
                <span class="lp-brand-mark">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span>محاسب عام</span>
            </a>
            <div class="lp-footer-links">
                <a href="{{ route('dev.login') }}">تسجيل الدخول</a>
                <a href="mailto:support@example.com">الدعم</a>
                <a href="mailto:hello@example.com">تواصل معنا</a>
            </div>
        </div>
    </footer>
</body>
</html>
