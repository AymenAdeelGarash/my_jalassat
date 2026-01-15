/**
 * Galassat Main JS - Complete & Stable Luxury Edition
 */

// 1. Global Variables
let cart = [];
let RAW_PHONE = typeof STORE_PHONE !== 'undefined' ? STORE_PHONE : "+966561365655";
const WHATSAPP_NUMBER = RAW_PHONE.replace(/[^\d]/g, '');
if (typeof selectedDayType === 'undefined') {
    window.selectedDayType = 'regular';
}
let appliedDiscount = 0;
let appliedCouponCode = '';
let currentGalleryImages = [];
let currentGalleryIndex = 0;

// DOM Elements
const cartSidebar = document.getElementById('cartSidebar');
const cartTrigger = document.getElementById('cartTrigger');
const closeCart = document.getElementById('closeCart');
const cartItemsContainer = document.getElementById('cartItems');
const cartTotalElement = document.getElementById('cartTotal');
const cartCountElement = document.querySelector('.cart-count');

// --- Force Visibility Fixes ---
const emergencyLoader = document.getElementById('loader');
if (emergencyLoader) {
    setTimeout(() => emergencyLoader.style.display = 'none', 300);
}

// --- Cart Core Logic ---

function updateCartTotal() {
    if (!cartTotalElement) return;
    let total = cart.reduce((sum, i) => sum + i.price, 0);
    let finalTotal = total - appliedDiscount;
    if (finalTotal < 0) finalTotal = 0;

    let html = `${total} ر.س`;
    if (appliedDiscount > 0) {
        html = `<span style="text-decoration:line-through; font-size:0.8em; opacity:0.7">${total}</span> ${finalTotal} ر.س`;
    }
    cartTotalElement.innerHTML = html;
}

function updateCartUI() {
    if (!cartItemsContainer) return;
    cartItemsContainer.innerHTML = '';

    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<div style="text-align:center; padding:40px; opacity:0.5;">سلتك فارغة.. اختر جلستك الآن</div>';
    }

    cart.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <img src="${item.image}" alt="">
            <div class="cart-item-info">
                <h4>${item.name}</h4>
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%">
                    <p style="margin:0; font-weight:bold">${item.price} ر.س</p>
                    <span style="font-size:0.7rem; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px;">${item.bookingDay || ''}</span>
                </div>
                <small onclick="removeFromCart(${index})" style="cursor:pointer;color:#ff4d4d; display:block; margin-top:5px;">حذف</small>
            </div>
        `;
        cartItemsContainer.appendChild(div);
    });

    if (cartCountElement) cartCountElement.innerText = cart.length;
    updateCartTotal();
    renderAddons(); // Refresh upsell section
}

function renderAddons() {
    // Disabled as we use PHP server-side rendering for the sidebar now
    return;
}

function addAddonToCartById(id) {
    if (typeof ALL_ADDONS === 'undefined') return;
    const addon = ALL_ADDONS.find(a => a.id === id);
    if (addon) {
        addAddonToCart(addon);
    }
}

function addToCart(product) {
    cart.push(product);
    updateCartUI();
    openCart();
}

function addAddonToCart(addon) {
    const item = {
        ...addon,
        price: parseInt(addon.price),
        type: 'addon',
        bookingDay: 'ملحق'
    };
    cart.push(item);
    updateCartUI();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function openCart() {
    if (cartSidebar) {
        cartSidebar.classList.add('open');
        checkAvailability(); // Refresh slots whenever cart opens
    }
}

function closeCartSidebar() {
    if (cartSidebar) cartSidebar.classList.remove('open');
}

// --- Multi-Feature Functions ---

// 1. Coupon System
async function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    const msg = document.getElementById('couponMsg');
    const total = cart.reduce((sum, i) => sum + i.price, 0);

    if (!code) return;

    try {
        const response = await fetch(`api/check_coupon.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code, total: total })
        });
        const data = await response.json();

        if (data.valid) {
            appliedDiscount = data.discount_amount;
            appliedCouponCode = code;
            msg.style.color = '#4CAF50';
            msg.innerText = data.message;
            msg.style.display = 'block';
            updateCartTotal();
        } else {
            msg.style.color = '#ff4d4d';
            msg.innerText = data.message || 'كود غير صالح';
            msg.style.display = 'block';
            appliedDiscount = 0;
            appliedCouponCode = '';
            updateCartTotal();
        }
    } catch (e) {
        console.error("Coupon Error", e);
    }
}

// 1.1 Review Submission
async function submitReview(event) {
    event.preventDefault();
    const btn = document.getElementById('submitReviewBtn');
    const status = document.getElementById('reviewStatus');

    const payload = {
        name: document.getElementById('revName').value,
        rating: document.getElementById('revRating').value,
        comment: document.getElementById('revComment').value
    };

    btn.disabled = true;
    btn.innerText = 'جاري الإرسال...';

    try {
        const response = await fetch('api/submit_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();

        status.style.display = 'block';
        status.style.padding = '15px';
        status.style.borderRadius = '10px';
        status.style.marginBottom = '20px';
        status.style.background = data.success ? 'rgba(76, 175, 80, 0.1)' : 'rgba(244, 67, 54, 0.1)';
        status.style.border = data.success ? '1px solid #4CAF50' : '1px solid #f44336';
        status.style.color = data.success ? '#4CAF50' : '#f44336';
        status.innerHTML = data.success
            ? '<i class="fa-solid fa-check-circle"></i> ' + data.message
            : '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;

        if (data.success) {
            document.getElementById('reviewForm').reset();
        }
    } catch (e) {
        status.style.display = 'block';
        status.style.background = 'rgba(244, 67, 54, 0.2)';
        status.innerText = 'حدث خطأ أثناء الإرسال.';
    } finally {
        btn.disabled = false;
        btn.innerText = 'إرسال التقييم';
    }
}

// 2. Location System
function getCurrentLocation() {
    const locInput = document.getElementById('bookingLocation');
    if (navigator.geolocation) {
        locInput.placeholder = "جاري تحديد الموقع...";
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const url = `https://www.google.com/maps?q=${pos.coords.latitude},${pos.coords.longitude}`;
                locInput.value = url;
            },
            () => {
                alert("لم نتمكن من الوصول لموقعك. يرجى كتابة العنوان يدوياً.");
                locInput.placeholder = "مثال: حي النرجس شارع...";
            }
        );
    }
}

// 3. Category Filter
function filterByCategory(category) {
    const cards = document.querySelectorAll('.product-card');
    const btns = document.querySelectorAll('.filter-btn');

    btns.forEach(btn => btn.classList.remove('active'));
    if (event) event.target.classList.add('active');

    cards.forEach(card => {
        const cat = card.getAttribute('data-category');
        if (category === 'all' || cat === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// 4. Booking Logic
function setBookingDay(type) {
    selectedDayType = type;

    // Fix UI: Highlight the correct button
    document.querySelectorAll('.day-btn').forEach(btn => {
        const isTarget = (type === 'weekend' && btn.innerText.includes('الويكند')) ||
            (type === 'regular' && btn.innerText.includes('الأسبوع'));
        btn.classList.toggle('active', isTarget);
    });

    document.querySelectorAll('.price').forEach(priceEl => {
        const regular = priceEl.getAttribute('data-regular');
        const weekend = priceEl.getAttribute('data-weekend');
        if (regular && weekend) {
            priceEl.innerHTML = `${type === 'weekend' ? weekend : regular} <small>ر.س</small>`;
        }
    });

    // Update cart items if day type changes
    cart.forEach(item => {
        if (item.type !== 'addon' && item.price_regular && item.price_weekend) {
            item.price = (type === 'weekend') ? parseInt(item.price_weekend) : parseInt(item.price_regular);
            item.bookingDay = (type === 'weekend') ? 'الويكند' : 'وسط الأسبوع';
        }
    });
    updateCartUI();
}

function triggerAddToCart(productId) {
    if (typeof ALL_PRODUCTS === 'undefined') return;
    const product = ALL_PRODUCTS.find(p => p.id == productId);
    if (product) {
        logClick(productId, product.name);
        const price = selectedDayType === 'weekend' ? product.price_weekend : product.price_regular;
        addToCart({
            ...product,
            price: parseInt(price),
            bookingDay: selectedDayType === 'weekend' ? 'الويكند' : 'وسط الأسبوع'
        });
    }
}

// 5. Product Detail Modal
function openProductDetail(productId) {
    const product = ALL_PRODUCTS.find(p => p.id == productId);
    if (!product) return;

    document.getElementById('detailMainImg').src = product.image;
    document.getElementById('detailTitle').innerText = product.name;
    document.getElementById('detailLongDesc').innerText = product.long_description || product.description;
    document.getElementById('detailBadge').innerText = product.badge || 'جلسة شتوية';

    const price = selectedDayType === 'weekend' ? product.price_weekend : product.price_regular;
    document.getElementById('detailPrice').innerHTML = `${price} <small>ر.س</small>`;

    document.getElementById('detailAddBtn').onclick = () => {
        triggerAddToCart(productId);
        logClick(productId, product.name);
        closeProductDetail();
    };

    document.getElementById('productDetailModal').style.display = 'block';
}

function closeProductDetail() {
    document.getElementById('productDetailModal').style.display = 'none';
}

// 6. Gallery Logic
function openGallery(productId) {
    const product = ALL_PRODUCTS.find(p => p.id == productId);
    if (!product || !product.gallery || product.gallery.length === 0) return;

    currentGalleryImages = product.gallery;
    currentGalleryIndex = 0;

    const modal = document.getElementById('galleryModal');
    const mainImg = document.getElementById('galleryMainImage');
    const thumbs = document.getElementById('galleryThumbs');

    mainImg.src = currentGalleryImages[0];
    thumbs.innerHTML = '';

    currentGalleryImages.forEach((src, idx) => {
        const img = document.createElement('img');
        img.src = src;
        img.style.width = '60px';
        img.style.height = '60px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '5px';
        img.style.cursor = 'pointer';
        img.onclick = () => {
            currentGalleryIndex = idx;
            mainImg.src = src;
        };
        thumbs.appendChild(img);
    });

    modal.style.display = 'flex';
}

function closeGallery() {
    document.getElementById('galleryModal').style.display = 'none';
}

function nextImage() {
    currentGalleryIndex = (currentGalleryIndex + 1) % currentGalleryImages.length;
    document.getElementById('galleryMainImage').src = currentGalleryImages[currentGalleryIndex];
}

function prevImage() {
    currentGalleryIndex = (currentGalleryIndex - 1 + currentGalleryImages.length) % currentGalleryImages.length;
    document.getElementById('galleryMainImage').src = currentGalleryImages[currentGalleryIndex];
}

// 7. AR Logic
function openAR(url) {
    const modal = document.getElementById('arModal');
    const viewer = document.getElementById('arViewer');
    if (modal && viewer) {
        viewer.src = url;
        modal.style.display = 'block';
    }
}
function closeAR() {
    document.getElementById('arModal').style.display = 'none';
}

// 8. Availability Checker
// 8. Availability Checker
async function checkAvailability() {
    const dateInput = document.getElementById('bookingDate');
    const date = dateInput.value;
    const timeSelect = document.getElementById('bookingTime');
    const endTimeSelect = document.getElementById('bookingEndTime');
    if (!date) return;

    // Determine the product_id to check for (first session in cart)
    const session = cart.find(i => i.type !== 'addon');
    const productId = session ? session.id : null;

    // --- Logical Improvement: Auto-switch pricing based on selected date ---
    const day = new Date(date).getDay(); // 0 (Sun) to 6 (Sat)
    const isActuallyWeekend = (day === 4 || day === 5);
    const newDayType = isActuallyWeekend ? 'weekend' : 'regular';
    if (newDayType !== selectedDayType) {
        setBookingDay(newDayType);
    }

    // Reset options
    Array.from(timeSelect.options).forEach(opt => {
        opt.disabled = false;
        opt.text = opt.text.replace(' (محجوز)', '');
    });

    try {
        const response = await fetch('api/check_availability.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ date: date, product_id: productId })
        });
        const data = await response.json();

        // New Interval Logic
        if (data.booked_intervals) {
            // Simple logic: Disable START times that fall within any booked interval
            // For rigorous checks, we would need to check [SelectedStart, SelectedEnd] overlap.
            // But visually updating the Start Time dropdown is a good first line of defense.

            data.booked_intervals.forEach(interval => {
                const startH = parseInt(interval.start.split(':')[0]);
                const endH = parseInt(interval.end.split(':')[0]);

                // Handle intervals crossing midnight? (Start < End usually within a day session logic)
                // Assuming session is 17:00 to 06:00.

                // Disable options
                Array.from(timeSelect.options).forEach(opt => {
                    const optH = parseInt(opt.value.split(':')[0]);
                    // Check if specific hour is booked
                    // Simplification: If hour is >= Start and < End, it's blocked.

                    let isBlocked = false;
                    if (startH <= endH) {
                        // Normal case: 17:00 to 20:00
                        if (optH >= startH && optH < endH) isBlocked = true;
                    } else {
                        // Late night case: 22:00 to 02:00
                        if (optH >= startH || optH < endH) isBlocked = true;
                    }

                    if (isBlocked) {
                        opt.disabled = true;
                        opt.text += ' (محجوز)';
                    }
                });
            });
        }
    } catch (e) {
        console.error("Availability Error", e);
    }
}

// 9. Analytics
async function logClick(productId, productName) {
    try {
        await fetch('api/log_click.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, product_name: productName })
        });
    } catch (e) { }
}

// WhatsApp Checkout
async function sendWhatsApp() {
    const date = document.getElementById('bookingDate').value;
    const time = document.getElementById('bookingTime').value;
    const endTime = document.getElementById('bookingEndTime').value;
    const location = document.getElementById('bookingLocation').value;

    if (!date || !time || !endTime || !location) return alert('الرجاء إكمال بيانات الحجز (التاريخ، وقت الدخول، وقت المغادرة، الموقع)');

    let total = cart.reduce((sum, i) => sum + i.price, 0);
    let finalTotal = total - appliedDiscount;
    const checkoutBtn = document.getElementById('checkoutBtn');

    // 1. Save to Database first for professionalism
    checkoutBtn.disabled = true;
    checkoutBtn.innerText = "جاري الحفظ...";

    try {
        const response = await fetch('api/create_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                date: date,
                time: time,
                end_time: endTime, // Send end time
                location: location,
                cart: cart,
                total: finalTotal,
                discount: appliedDiscount,
                coupon: appliedCouponCode
            })
        });
        const orderData = await response.json();

        if (!orderData.success) {
            alert('حدث خطأ أثناء حفظ الطلب. حاول مجدداً.');
            checkoutBtn.disabled = false;
            checkoutBtn.innerText = 'تأكيد الطلب والموقع';
            return;
        }

        // 2. Prepare WhatsApp Message
        let itemsStr = cart.map(i => {
            let label = i.bookingDay ? ` [${i.bookingDay}]` : '';
            return `• ${i.name}${label} (${i.price} ر.س)`;
        }).join('%0A');

        let message = `*طلب حجز جديد* (رقم: ${orderData.order_id})%0A%0A`;
        message += `*تاريخ الحجز:* ${date}%0A`;
        message += `*وقت الوصول:* ${time}%0A`;
        message += `*وقت المغادرة:* ${endTime}%0A`;
        message += `*موقع الحجز:* ${location}%0A%0A`;
        message += `*تفاصيل الطلب:*%0A${itemsStr}%0A`;

        if (appliedDiscount > 0) {
            message += `%0A*المجموع:* ${total} ر.س%0A`;
            message += `*الخصم (${appliedCouponCode}):* -${appliedDiscount} ر.س%0A`;
        }
        message += `%0A*الإجمالي النهائي: ${finalTotal} ر.س*`;
        message += `%0A%0A_يرجى تحويل العربون (50%) وإرسال الإيصال هنا لتأكيد الحجز_`;

        const waUrl = `https://wa.me/${WHATSAPP_NUMBER}?text=${message}`;
        window.open(waUrl, '_blank');

        // Success feedback
        checkoutBtn.innerText = "تم إرسال الطلب!";
        setTimeout(() => {
            cart = [];
            updateCartUI();
            closeCartSidebar();
            alert('شكراً لك! تم إرسال طلبك للواتساب. يرجى إرسال إيصال تحويل العربون هناك لتأكيد الحجز.');
            checkoutBtn.disabled = false;
            checkoutBtn.innerText = 'تأكيد الطلب والموقع';
        }, 2000);

    } catch (e) {
        console.error(e);
        alert('حدث خطأ غير متوقع.');
        checkoutBtn.disabled = false;
        checkoutBtn.innerText = 'تأكيد الطلب والموقع';
    }
}

// Initializers
window.addEventListener('load', () => {
    if (cartTrigger) cartTrigger.addEventListener('click', openCart);
    if (closeCart) closeCart.addEventListener('click', closeCartSidebar);

    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            if (cart.length === 0) return alert('السلة فارغة!');
            sendWhatsApp();
        });
    }

    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
        dateInput.value = dateInput.min; // Set default to today
        dateInput.addEventListener('change', checkAvailability);
        checkAvailability(); // Initial check for today
    }
});

function copyToClipboard(id) {
    const text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text).then(() => alert('تم النسخ!'));
}

/* CUSTOMER AUTH FUNCTIONS */
function openAuthModal() {
    document.getElementById('authModal').style.display = 'block';
    toggleAuthMode('login');
}

function closeAuthModal() {
    document.getElementById('authModal').style.display = 'none';
}

function toggleAuthMode(mode) {
    if (mode === 'register') {
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('registerForm').style.display = 'block';
    } else {
        document.getElementById('loginForm').style.display = 'block';
        document.getElementById('registerForm').style.display = 'none';
    }
}

async function handleLogin() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const statusDiv = document.getElementById('loginStatus');

    if (!email || !password) {
        statusDiv.innerText = 'يرجى ملء جميع الحقول';
        statusDiv.className = 'status-error';
        statusDiv.style.display = 'block';
        return;
    }

    try {
        const response = await fetch('api/auth_customer.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const res = await response.json();

        if (res.success) {
            statusDiv.innerText = res.message + ' جاري التحديث...';
            statusDiv.className = 'status-success';
            statusDiv.style.display = 'block';
            setTimeout(() => {
                if (res.redirect) window.location.href = res.redirect;
                else location.reload();
            }, 1000);
        } else {
            statusDiv.innerText = res.message;
            statusDiv.className = 'status-error';
            statusDiv.style.display = 'block';
        }
    } catch (e) {
        alert('حدث خطأ في الاتصال');
    }
}

async function handleRegister() {
    const name = document.getElementById('regName').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPassword').value;
    const statusDiv = document.getElementById('regStatus');

    if (!name || !email || !password) {
        statusDiv.innerText = 'يرجى ملء جميع الحقول بشكل صحيح';
        statusDiv.className = 'status-error';
        statusDiv.style.display = 'block';
        return;
    }

    try {
        const response = await fetch('api/auth_customer.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, password })
        });
        const res = await response.json();

        if (res.success) {
            statusDiv.innerText = 'تم إنشاء الحساب بنجاح! جاري التحديث...';
            statusDiv.className = 'status-success';
            statusDiv.style.display = 'block';
            setTimeout(() => location.reload(), 1500);
        } else {
            statusDiv.innerText = res.message;
            statusDiv.className = 'status-error';
            statusDiv.style.display = 'block';
        }
    } catch (e) {
        alert('حدث خطأ في الاتصال');
    }
}

async function logoutCustomer() {
    await fetch('api/auth_customer.php?action=logout');
    location.reload();
}

