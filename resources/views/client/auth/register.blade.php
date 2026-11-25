<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>إنشاء حساب مسافر جديد | عبور</title>
    <meta name="description" content="@yield('meta_description', 'منصة عبور هي البوابة الرائدة لإدارة حجز الرحلات والوكالات، سجل الآن لتتمكن من إدارة وتتبع رحلاتك وحجوزاتك بكل سهولة وأمان.')">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.95);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 10s infinite alternate ease-in-out;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }

        .animate-slide-up {
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    {{-- ==================== منطق الفورم + قراءة الهوية ==================== --}}
    @verbatim
    <script>
        /* ---------- أدوات مساعدة ---------- */
        function toLatinDigits(s) {
            return (s || '')
                .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
                .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
        }

        function normAr(s) {
            return (s || '')
                .replace(/[\u064B-\u065F\u0670\u0640]/g, '')
                .replace(/[أإآٱ]/g, 'ا')
                .replace(/ى/g, 'ي')
                .replace(/ة/g, 'ه');
        }

        // خوارزمية التحقق من رقم الهوية / الإقامة السعودية
        function isValidSaudiId(id) {
            if (!/^[12]\d{9}$/.test(id)) return false;
            let sum = 0;
            for (let i = 0; i < 10; i++) {
                let n = parseInt(id[i], 10);
                if (i % 2 === 0) {
                    n *= 2;
                    if (n > 9) n -= 9;
                }
                sum += n;
            }
            return sum % 10 === 0;
        }

        function gregToHijri(y, m, d) {
            try {
                const dt = new Date(y, m - 1, d, 12);
                const parts = new Intl.DateTimeFormat('en-US-u-ca-islamic-umalqura-nu-latn', {
                    year: 'numeric', month: 'numeric', day: 'numeric'
                }).formatToParts(dt);
                const out = {};
                parts.forEach(p => {
                    if (p.type === 'year') out.y = parseInt(p.value, 10);
                    if (p.type === 'month') out.m = parseInt(p.value, 10);
                    if (p.type === 'day') out.d = parseInt(p.value, 10);
                });
                return (out.y && out.m && out.d) ? out : null;
            } catch (e) {
                return null;
            }
        }

        function isRealGregorian(y, m, d) {
            const dt = new Date(y, m - 1, d);
            return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
        }

        /* ---------- تجهيز الصورة: تكبير/تصغير + تدرج رمادي + تمديد التباين (بدون تحويل أبيض/أسود قاسٍ) ---------- */
        async function prepareCanvas(file) {
            const url = URL.createObjectURL(file);
            const img = await new Promise((resolve, reject) => {
                const i = new Image();
                i.onload = () => resolve(i);
                i.onerror = reject;
                i.src = url;
            });
            URL.revokeObjectURL(url);

            const scale = Math.min(2.5, Math.max(0.5, 1800 / img.width));
            const w = Math.round(img.width * scale);
            const h = Math.round(img.height * scale);

            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);

            const imgData = ctx.getImageData(0, 0, w, h);
            const px = imgData.data;
            const total = w * h;
            const gray = new Uint8ClampedArray(total);
            const hist = new Uint32Array(256);

            for (let i = 0, p = 0; i < px.length; i += 4, p++) {
                const g = Math.round(0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2]);
                gray[p] = g;
                hist[g]++;
            }

            let lo = 0, hi = 255, acc = 0;
            for (let v = 0; v < 256; v++) { acc += hist[v]; if (acc > total * 0.02) { lo = v; break; } }
            acc = 0;
            for (let v = 255; v >= 0; v--) { acc += hist[v]; if (acc > total * 0.02) { hi = v; break; } }
            if (hi - lo < 30) { lo = 0; hi = 255; }

            for (let i = 0, p = 0; i < px.length; i += 4, p++) {
                const v = Math.max(0, Math.min(255, (gray[p] - lo) * 255 / (hi - lo)));
                px[i] = px[i + 1] = px[i + 2] = v;
            }
            ctx.putImageData(imgData, 0, 0);
            return canvas;
        }

        /* ---------- قص جزء من الصورة وتكبيره ---------- */
        function cropCanvas(src, box, pad, scale) {
            const x0 = Math.max(0, Math.floor(box.x0 - pad));
            const y0 = Math.max(0, Math.floor(box.y0 - pad));
            const x1 = Math.min(src.width, Math.ceil(box.x1 + pad));
            const y1 = Math.min(src.height, Math.ceil(box.y1 + pad));
            const c = document.createElement('canvas');
            c.width = Math.max(1, Math.round((x1 - x0) * scale));
            c.height = Math.max(1, Math.round((y1 - y0) * scale));
            c.getContext('2d').drawImage(src, x0, y0, x1 - x0, y1 - y0, 0, 0, c.width, c.height);
            return c;
        }

        // الكلمات التي تقع يسار عنوان حقل (مثل "الاسم") في نفس الصف
        function wordsNextToLabel(words, labelRe) {
            const label = words.find(w => labelRe.test(normAr(w.text).replace(/[:：\s]/g, '')));
            if (!label) return null;
            const lh = label.bbox.y1 - label.bbox.y0;
            const cy = (label.bbox.y0 + label.bbox.y1) / 2;
            const row = words.filter(w => w !== label &&
                Math.abs((w.bbox.y0 + w.bbox.y1) / 2 - cy) <= lh * 0.8 &&
                w.bbox.x1 <= label.bbox.x0 + lh);
            return row.length ? { label, row } : null;
        }

        /* ---------- تشغيل OCR: أرقام (إنجليزي) + عربي + إعادة قراءة سطر الاسم بعد قصّه ---------- */
        async function runOcr(canvas) {
            const out = { ara: '', num: '', nameCrop: '' };

            let wNum = null;
            try {
                wNum = await Tesseract.createWorker('eng');
                await wNum.setParameters({
                    tessedit_char_whitelist: '0123456789/-.: ',
                    tessedit_pageseg_mode: '11'
                });
                out.num = (await wNum.recognize(canvas)).data.text || '';
            } finally {
                if (wNum) await wNum.terminate();
            }

            let wAra = null;
            try {
                wAra = await Tesseract.createWorker('ara');
                await wAra.setParameters({
                    tessedit_pageseg_mode: '11',
                    preserve_interword_spaces: '1'
                });
                const res = await wAra.recognize(canvas);
                out.ara = res.data.text || '';

                // إعادة قراءة سطر الاسم وحده كسطر واحد (أدق بكثير)
                try {
                    const hit = wordsNextToLabel(res.data.words || [], /^الاسم/);
                    if (hit) {
                        const box = {
                            x0: Math.min(...hit.row.map(w => w.bbox.x0)),
                            y0: Math.min(...hit.row.map(w => w.bbox.y0)),
                            x1: Math.max(...hit.row.map(w => w.bbox.x1)),
                            y1: Math.max(...hit.row.map(w => w.bbox.y1))
                        };
                        await wAra.setParameters({ tessedit_pageseg_mode: '7' });
                        const crop = cropCanvas(canvas, box, 14, 2);
                        out.nameCrop = (await wAra.recognize(crop)).data.text || '';
                    }
                } catch (e) { console.warn('Name crop failed', e); }
            } finally {
                if (wAra) await wAra.terminate();
            }

            return out;
        }

        /* ---------- استخراج رقم الهوية: فقط الأرقام التي تجتاز التحقق ---------- */
        function extractNationalId(texts) {
            const counts = new Map();
            texts.forEach(t => {
                toLatinDigits(t).split('\n').forEach(line => {
                    const compact = line.replace(/\s+/g, '');
                    const re = /(?:^|\D)([12]\d{9})(?!\d)/g;
                    let m;
                    while ((m = re.exec(compact)) !== null) {
                        if (isValidSaudiId(m[1])) counts.set(m[1], (counts.get(m[1]) || 0) + 1);
                    }
                });
            });
            let best = '', bestCount = 0;
            counts.forEach((c, id) => { if (c > bestCount) { best = id; bestCount = c; } });
            return best;
        }

        /* ---------- استخراج تاريخ الميلاد (هجري + ميلادي) مع مطابقة التقويمين ---------- */
        function extractBirthDates(texts) {
            const nowY = new Date().getFullYear();
            const nowH = (gregToHijri(nowY, new Date().getMonth() + 1, new Date().getDate()) || { y: 1447 }).y;
            const seen = new Set();
            const hList = [], gList = [];
            const re = /(\d{1,4})\s*[\/\-\.]\s*(\d{1,2})\s*[\/\-\.]\s*(\d{1,4})/g;

            texts.forEach(t => {
                const text = toLatinDigits(t);
                let m;
                re.lastIndex = 0;
                while ((m = re.exec(text)) !== null) {
                    let y, mo, d;
                    if (m[1].length === 4) { y = +m[1]; mo = +m[2]; d = +m[3]; }
                    else if (m[3].length === 4) { y = +m[3]; mo = +m[2]; d = +m[1]; }
                    else continue;

                    if (mo < 1 || mo > 12 || d < 1 || d > 31) continue;
                    const key = y + '-' + mo + '-' + d;
                    if (seen.has(key)) continue;
                    seen.add(key);

                    if (y >= 1350 && y <= nowH && d <= 30) hList.push({ y, m: mo, d });
                    else if (y >= 1930 && y <= nowY && isRealGregorian(y, mo, d)) gList.push({ y, m: mo, d });
                }
            });

            const cmp = (a, b) => (a.y * 10000 + a.m * 100 + a.d) - (b.y * 10000 + b.m * 100 + b.d);
            hList.sort(cmp);
            gList.sort(cmp);

            // 1) أفضل نتيجة: تاريخ ميلادي وهجري متطابقان
            for (const g of gList) {
                const conv = gregToHijri(g.y, g.m, g.d);
                if (!conv) continue;
                const hit = hList.find(h => h.y === conv.y && h.m === conv.m && Math.abs(h.d - conv.d) <= 1);
                if (hit) return { g, h: hit, confirmed: true };
            }
            // 2) ميلادي فقط (نحوّله للهجري)
            if (gList.length) {
                const g = gList[0];
                return { g, h: gregToHijri(g.y, g.m, g.d), confirmed: false };
            }
            // 3) هجري فقط
            if (hList.length) return { g: null, h: hList[0], confirmed: false };
            return null;
        }

        /* ---------- فصل الكلمات الملتصقة (مثل: عليالسبتي -> علي السبتي) ---------- */
        const COMMON_NAMES = ['علي', 'محمد', 'احمد', 'أحمد', 'سعد', 'سعود', 'ناصر', 'فهد', 'خالد', 'سالم', 'صالح',
            'عمر', 'يوسف', 'ابراهيم', 'إبراهيم', 'سلطان', 'فيصل', 'ماجد', 'مبارك', 'سليمان', 'حمد', 'حسن', 'حسين',
            'عثمان', 'بدر', 'تركي', 'نايف', 'مشعل', 'منصور', 'عادل', 'ياسر', 'زيد', 'راشد', 'دخيل', 'مطلق', 'عوض',
            'ضيف', 'هادي', 'جابر', 'سعيد', 'مسفر', 'ظافر', 'غازي', 'نواف', 'بندر', 'رشيد', 'سامي', 'طلال', 'عبدالله',
            'عبدالرحمن', 'عبدالعزيز', 'عبدالمجيد', 'عبدالهادي', 'عبدالكريم', 'عبدالرحيم'];

        function splitMergedWords(words) {
            const out = [];
            for (const w of words) {
                let done = false;
                for (const nm of COMMON_NAMES) {
                    if (w.length >= nm.length + 5 && w.startsWith(nm + 'ال')) {
                        out.push(nm, w.slice(nm.length));
                        done = true;
                        break;
                    }
                }
                if (!done) out.push(w);
            }
            return out;
        }

        /* ---------- فصل الكلمات الملتصقة (مثل: عليالسبتي -> علي السبتي) ---------- */
        const KNOWN_NAMES = new Set(['علي', 'محمد', 'احمد', 'خالد', 'سعد', 'سعود', 'فهد', 'ناصر', 'سلمان', 'سلطان',
            'ماجد', 'فيصل', 'عمر', 'عثمان', 'يوسف', 'ابراهيم', 'اسماعيل', 'صالح', 'سالم', 'حمد', 'حمود', 'مشعل',
            'بدر', 'تركي', 'نايف', 'نواف', 'متعب', 'مبارك', 'منصور', 'راشد', 'زياد', 'طلال', 'بندر', 'وليد',
            'هادي', 'حسن', 'حسين', 'ياسر', 'سامي', 'سعيد', 'جمال', 'كمال', 'جلال', 'هلال', 'عبدالله', 'عبدالرحمن',
            'عبدالعزيز', 'عبدالهادي', 'عبدالرحيم', 'عبدالكريم', 'عبدالمجيد', 'عبداللطيف', 'عبدالملك', 'عبدالمحسن']);

        function splitMergedWords(words) {
            const out = [];
            for (const w of words) {
                const n = normAr(w);
                let done = false;
                for (let idx = 2; idx <= n.length - 5; idx++) {
                    if (n.substr(idx, 2) !== 'ال') continue;
                    const prefix = n.slice(0, idx);
                    const rest = n.slice(idx + 2);
                    if (rest.length < 3 || /^(عبد|ابو|ام)$/.test(prefix) || /^(دين|له)$/.test(rest)) continue;
                    if (KNOWN_NAMES.has(prefix) || prefix.endsWith('ي')) {
                        out.push(w.slice(0, idx), w.slice(idx));
                        done = true;
                        break;
                    }
                }
                if (!done) out.push(w);
            }
            return out;
        }

        /* ---------- استخراج الاسم ---------- */
        const NAME_IGNORE = ['وزاره', 'الداخليه', 'المملكه', 'العربيه', 'السعوديه', 'الهويه', 'الوطنيه', 'تاريخ',
            'الاصدار', 'الانتهاء', 'مكان', 'الميلاد', 'الاسم', 'الجنس', 'الديانه', 'رقم', 'بطاقه', 'مقيم',
            'اقامه', 'صوره', 'سعودي', 'ذكر', 'انثي', 'الجنسيه', 'هجري', 'ميلادي', 'اسلام', 'مسلم',
            'رخصه', 'الاقامه'];

        function cleanNameCandidate(raw) {
            const ar = (raw || '').replace(/[^\u0600-\u06FF\s]/g, ' ').replace(/[\u064B-\u065F\u0670\u0640]/g, '');
            return ar.split(/\s+/).filter(w => w.length >= 2 && !NAME_IGNORE.includes(normAr(w)));
        }

        function extractName(ocr) {
            const candidates = [];

            // أ) سطر الاسم بعد قصّه وإعادة قراءته (الأدق)
            if (ocr.nameCrop) {
                const w = cleanNameCandidate(ocr.nameCrop.replace(/\n/g, ' '));
                if (w.length >= 2 && w.length <= 7) candidates.push({ words: w, score: 3 });
            }

            const lines = (ocr.ara || '').split('\n').map(l => l.trim()).filter(Boolean);

            // ب) ما بعد كلمة "الاسم" أو السطر التالي
            for (let i = 0; i < lines.length; i++) {
                if (/الاسم/.test(normAr(lines[i]))) {
                    const after = lines[i].replace(/.*(الاسم|الإسم)\s*[:：]?/, '');
                    let w = cleanNameCandidate(after);
                    if (w.length < 2 && lines[i + 1]) w = cleanNameCandidate(lines[i + 1]);
                    if (w.length >= 2 && w.length <= 7) candidates.push({ words: w, score: 2 });
                    break;
                }
            }

            // ج) احتياطي: سطر عربي نظيف من 3 إلى 5 كلمات
            for (const line of lines) {
                const arabicChars = (line.match(/[\u0600-\u06FF]/g) || []).length;
                if (arabicChars < line.replace(/\s/g, '').length * 0.8) continue;
                const raw = line.split(/\s+/).filter(w => w.length >= 2);
                if (raw.some(w => NAME_IGNORE.includes(normAr(w)))) continue;
                if (raw.length >= 3 && raw.length <= 5) candidates.push({ words: raw, score: 1 });
            }

            if (!candidates.length) return '';
            // نفضّل المصدر الأدق، ما لم يكن أقصر بكلمتين أو أكثر من غيره
            const maxLen = Math.max(...candidates.map(c => c.words.length));
            const good = candidates.filter(c => c.words.length >= maxLen - 1);
            good.sort((x, y) => y.score - x.score || y.words.length - x.words.length);
            return splitMergedWords(good[0].words).join(' ');
        }

        /* ---------- استخراج الجنس ---------- */
        function extractGender(araText) {
            const tokens = normAr(araText).split(/[\s:：\-\|]+/).filter(Boolean);
            if (tokens.some(t => t === 'انثي' || t === 'انثى' || t === 'أنثى')) return 'female';
            if (tokens.some(t => t === 'ذكر' || t === 'ذكرر')) return 'male';
            return '';
        }

        /* ---------- استخراج المدينة من سطر "مكان الميلاد" فقط ---------- */
        const CITY_KEYWORDS = [
            ['الرياض', ['رياض']], ['جدة', ['جده']], ['مكة المكرمة', ['مكه', 'مكرمه']],
            ['المدينة المنورة', ['مدينه', 'منوره']], ['الدمام', ['دمام']], ['الخبر', ['خبر']],
            ['الظهران', ['ظهران']], ['الهفوف', ['هفوف', 'احساء']], ['الطائف', ['طائف', 'طايف']],
            ['تبوك', ['تبوك']], ['أبها', ['ابها']], ['خميس مشيط', ['خميس', 'مشيط']],
            ['جازان', ['جازان', 'جيزان']], ['نجران', ['نجران']], ['حائل', ['حائل', 'حايل']],
            ['بريدة', ['بريده', 'قصيم']], ['عنيزة', ['عنيزه']], ['سكاكا', ['سكاكا', 'جوف']],
            ['عرعر', ['عرعر']], ['ينبع', ['ينبع']], ['الجبيل', ['جبيل']], ['القطيف', ['قطيف']],
            ['بيشة', ['بيشه']], ['الباحة', ['باحه']], ['القريات', ['قريات']], ['رابغ', ['رابغ']],
            ['الخرج', ['خرج']], ['الدوادمي', ['دوادمي']], ['المجمعة', ['مجمعه']]
        ];

        function extractCity(araText) {
            const lines = (araText || '').split('\n').map(l => l.trim()).filter(Boolean);
            for (let i = 0; i < lines.length; i++) {
                const n = normAr(lines[i]);
                if (/الميلاد/.test(n) && /مكان/.test(n)) {
                    const chunk = normAr(lines[i] + ' ' + (lines[i + 1] || ''));
                    const tokens = chunk.split(/[^\u0600-\u06FF]+/).filter(Boolean).map(t => t.replace(/^ال/, ''));
                    for (const [city, kws] of CITY_KEYWORDS) {
                        if (kws.some(k => tokens.includes(k))) return city;
                    }
                }
            }
            return '';
        }

        /* ---------- مكوّن Alpine ---------- */
        function registerForm(init) {
            return {
                name: init.name || '',
                national_id: init.national_id || '',
                city: init.city || '',
                gender: init.gender || '',
                birthType: init.birthType || 'gregorian',
                gDay: init.gDay || '',
                gMonth: init.gMonth || '',
                gYear: init.gYear || '',
                hDay: init.hDay || '',
                hMonth: init.hMonth || '',
                hYear: init.hYear || '',

                idImagePreview: null,
                isScanning: false,
                scanSuccess: false,
                scanError: '',
                scanWarnings: [],
                ocrFilled: { name: false, national_id: false, birth: false, gender: false, city: false },
                formError: '',

                get birthDate() {
                    if (this.birthType === 'gregorian' && this.gYear && this.gMonth && this.gDay) {
                        return `${this.gYear}-${String(this.gMonth).padStart(2, '0')}-${String(this.gDay).padStart(2, '0')}`;
                    }
                    return '';
                },

                get birthComplete() {
                    if (this.birthType === 'gregorian') return !!(this.gYear && this.gMonth && this.gDay);
                    return !!(this.hYear && this.hMonth && this.hDay);
                },

                validateBeforeSubmit(e) {
                    this.formError = '';
                    if (this.isScanning) {
                        e.preventDefault();
                        this.formError = 'يرجى الانتظار حتى انتهاء قراءة الهوية.';
                    } else if (!this.city) {
                        e.preventDefault();
                        this.formError = 'يرجى اختيار المدينة الحالية.';
                    } else if (!this.birthComplete) {
                        e.preventDefault();
                        this.formError = 'يرجى إكمال تاريخ الميلاد (اليوم والشهر والسنة).';
                    }
                },

                async processIDCard(event) {
                    const input = event.target;
                    const file = input.files && input.files[0];

                    this.scanSuccess = false;
                    this.scanError = '';
                    this.scanWarnings = [];
                    this.ocrFilled = { name: false, national_id: false, birth: false, gender: false, city: false };

                    if (!file) { this.idImagePreview = null; return; }

                    if (!/^image\/(jpeg|png)$/.test(file.type)) {
                        this.scanError = 'صيغة الصورة غير مدعومة، يرجى استخدام JPG أو PNG.';
                        input.value = '';
                        this.idImagePreview = null;
                        return;
                    }
                    if (file.size > 4 * 1024 * 1024) {
                        this.scanError = 'حجم الصورة كبير، الحد الأقصى 4 ميجابايت.';
                        input.value = '';
                        this.idImagePreview = null;
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => { this.idImagePreview = e.target.result; };
                    reader.readAsDataURL(file);

                    this.isScanning = true;

                    try {
                        const canvas = await prepareCanvas(file);
                        const ocr = await runOcr(canvas);
                        const texts = [ocr.num, ocr.ara];

                        let filled = 0;

                        // 1) رقم الهوية (يُقبل فقط إذا اجتاز التحقق)
                        const id = extractNationalId(texts);
                        if (id) {
                            this.national_id = id;
                            this.ocrFilled.national_id = true;
                            filled++;
                        } else {
                            this.scanWarnings.push('لم نستطع قراءة رقم الهوية بدقة، يرجى إدخاله يدوياً.');
                        }

                        // 2) تاريخ الميلاد
                        const birth = extractBirthDates(texts);
                        if (birth) {
                            if (birth.g) {
                                this.gYear = String(birth.g.y);
                                this.gMonth = String(birth.g.m);
                                this.gDay = String(birth.g.d);
                            }
                            if (birth.h) {
                                this.hYear = String(birth.h.y);
                                this.hMonth = String(birth.h.m);
                                this.hDay = String(birth.h.d);
                            }
                            if (!birth.g && birth.h) this.birthType = 'hijri';
                            this.ocrFilled.birth = true;
                            filled++;
                            if (!birth.confirmed) {
                                this.scanWarnings.push('تاريخ الميلاد لم يُؤكَّد من التقويمين، يرجى مراجعته.');
                            }
                        } else {
                            this.scanWarnings.push('لم نستطع قراءة تاريخ الميلاد، يرجى اختياره يدوياً.');
                        }

                        // 3) الاسم
                        const fullName = extractName(ocr);
                        if (fullName) {
                            this.name = fullName;
                            this.ocrFilled.name = true;
                            filled++;
                            this.scanWarnings.push('تأكد من الاسم الكامل وأنه مطابق للهوية تماماً.');
                        } else {
                            this.scanWarnings.push('لم نستطع قراءة الاسم، يرجى كتابته يدوياً.');
                        }

                        // 4) الجنس
                        const gender = extractGender(ocr.ara);
                        if (gender) {
                            this.gender = gender;
                            this.ocrFilled.gender = true;
                            filled++;
                        } else {
                            this.scanWarnings.push('لم نستطع قراءة الجنس، يرجى اختياره يدوياً.');
                        }

                        // 5) المدينة (من مكان الميلاد فقط، وقد تختلف عن المدينة الحالية)
                        const cityFound = extractCity(ocr.ara);
                        if (cityFound) {
                            this.city = cityFound;
                            this.ocrFilled.city = true;
                            filled++;
                            this.scanWarnings.push('المدينة أُخذت من مكان الميلاد في الهوية، عدّلها إن كانت مدينتك الحالية مختلفة.');
                        }

                        if (filled === 0) {
                            this.scanError = 'لم نتمكن من التعرف على البيانات، جرّب صورة أوضح أو عبّئ البيانات يدوياً (الصورة ستُرفع مع طلبك).';
                        } else {
                            this.scanSuccess = true;
                        }
                    } catch (err) {
                        console.error('OCR Error:', err);
                        this.scanError = 'تعذرت قراءة الصورة تلقائياً، عبّئ البيانات يدوياً (الصورة ستُرفع مع طلبك).';
                    } finally {
                        this.isScanning = false;
                    }
                }
            };
        }
    </script>
    @endverbatim
</head>

<body
    class="bg-slate-50 text-slate-900 antialiased relative min-h-screen w-screen overflow-x-hidden flex items-center justify-center p-4 sm:p-8 md:p-12">

    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div
            class="absolute top-1/4 -right-20 w-80 h-80 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob">
        </div>
        <div
            class="absolute bottom-1/4 -left-20 w-80 h-80 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute top-1/2 left-1/3 w-60 h-60 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-4000">
        </div>
    </div>

    <div class="w-full max-w-2xl z-10">

        <div
            class="bg-white/90 backdrop-blur-md p-6 sm:p-10 md:p-12 rounded-3xl shadow-2xl shadow-indigo-950/5 border border-white/60 relative animate-slide-up">

            <div class="text-center mb-8 relative">
                <div
                    class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-lg shadow-indigo-500/20 mb-3 transform hover:rotate-6 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                        </path>
                    </svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-900 text-slate-900 tracking-tight">انضم لمنصة عُـبـور كمسافر<span
                        class="text-indigo-600">.</span></h2>
                <p class="text-sm font-medium text-slate-500 mt-1">أنشئ حسابك لتبدأ حجز وتتبع رحلاتك بكل سهولة</p>
            </div>

            {{-- كل أخطاء التحقق --}}
            @if ($errors->any())
                <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 text-sm font-bold px-4 py-3.5">
                    <ul class="space-y-1.5">
                        @foreach ($errors->all() as $error)
                            <li class="flex items-start gap-2">
                                <span class="w-2 h-2 mt-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                <span>{{ $error }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ==================== فورم المسافر (يشمل قراءة الهوية ورفعها) ==================== --}}
            <form action="{{ route('client.auth.register') }}" method="POST" enctype="multipart/form-data"
                class="grid grid-cols-1 md:grid-cols-2 gap-5"
                x-data="registerForm(@js([
                    'name' => old('name'),
                    'national_id' => old('national_id'),
                    'city' => old('city'),
                    'gender' => old('gender'),
                    'birthType' => old('birth_type', 'gregorian'),
                    'gDay' => old('gregorian_day'),
                    'gMonth' => old('gregorian_month'),
                    'gYear' => old('gregorian_year'),
                    'hDay' => old('hijri_day'),
                    'hMonth' => old('hijri_month'),
                    'hYear' => old('hijri_year'),
                ]))"
                @submit="validateBeforeSubmit($event)">
                @csrf

                {{-- ===== صورة الهوية: تُقرأ لتعبئة البيانات وتُرفع مع التسجيل ===== --}}
                <div class="md:col-span-2 bg-indigo-50/50 border border-indigo-100 rounded-2xl p-4 sm:p-5 text-right transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🪪</span>
                            <h3 class="text-sm font-bold text-slate-800">صورة الهوية (تعبئة تلقائية + توثيق الحساب)</h3>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <label class="w-full sm:w-auto flex-1 cursor-pointer">
                            <div
                                class="flex items-center justify-center gap-2 px-4 py-3 bg-white border-2 border-dashed border-indigo-200 hover:border-indigo-500 rounded-xl transition-all shadow-sm group">
                                <svg class="w-5 h-5 text-indigo-500 group-hover:scale-110 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h0.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-xs font-bold text-slate-700">اختر صورة الهوية أو صوّرها</span>
                                <input type="file" name="id_image" accept="image/jpeg,image/png" class="hidden"
                                    @change="processIDCard($event)">
                            </div>
                        </label>

                        <template x-if="idImagePreview">
                            <div class="w-12 h-12 rounded-xl border border-slate-200 overflow-hidden shrink-0 shadow-sm">
                                <img :src="idImagePreview" class="w-full h-full object-cover">
                            </div>
                        </template>
                    </div>

                    <p class="mt-3 text-[11px] font-medium text-slate-500 leading-relaxed">
                        صوّر الهوية بإضاءة جيدة وبشكل مستقيم. سنحاول تعبئة الاسم ورقم الهوية وتاريخ الميلاد والجنس والمدينة، وسيتم حفظ الصورة
                        لتوثيق حسابك. راجع البيانات دائماً قبل الإرسال.
                    </p>

                    <div x-show="isScanning" x-cloak class="mt-3 flex items-center gap-2 text-xs text-indigo-600 font-bold">
                        <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        جاري تحليل واستخراج البيانات... (قد يستغرق بضع ثوانٍ)
                    </div>
                    <div x-show="scanSuccess" x-cloak class="mt-3 text-xs text-emerald-600 font-bold flex items-center gap-1.5">
                        <span>✅</span> تمت قراءة البيانات، يرجى مراجعتها قبل الإرسال.
                    </div>
                    <template x-for="w in scanWarnings" :key="w">
                        <div class="mt-2 text-xs text-amber-600 font-bold flex items-start gap-1.5">
                            <span>⚠️</span><span x-text="w"></span>
                        </div>
                    </template>
                    <div x-show="scanError" x-cloak class="mt-3 text-xs text-rose-500 font-bold flex items-center gap-1.5"
                        x-text="scanError"></div>
                </div>

                {{-- الصورة الشخصية --}}
                <div class="md:col-span-2 flex flex-col items-center justify-center bg-slate-50/60 p-5 rounded-2xl border border-slate-200/50 shadow-inner mb-2"
                    x-data="{ avatarPreview: null }">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-3">الصورة الشخصية
                        (اختياري)</label>
                    <div
                        class="relative w-20 h-20 rounded-full bg-white border-2 border-slate-200 shadow-sm overflow-hidden group flex items-center justify-center transition-all hover:border-indigo-500">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarPreview">
                            <span class="text-2xl opacity-40 group-hover:opacity-60 transition-opacity">👤</span>
                        </template>
                        <label
                            class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold cursor-pointer transition-all backdrop-blur-[1px]">
                            رفع 📸
                            <input type="file" name="avatar" accept="image/jpeg,image/png" class="hidden"
                                @change="const file = $el.files[0]; if (file) { const reader = new FileReader(); reader.onload = (e) => { avatarPreview = e.target.result; }; reader.readAsDataURL(file); }">
                        </label>
                    </div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">الاسم الكامل</label>
                    <input type="text" name="name" x-model="name" placeholder="الاسم الثلاثي كما في الهوية"
                        required :class="ocrFilled.name ? 'ring-2 ring-emerald-200' : ''"
                        class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="mail@example.com"
                        required
                        class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-bold mb-1.5 text-indigo-600">رقم الهوية / الإقامة</label>
                    <input type="text" name="national_id" x-model="national_id" placeholder="1XXXXXXXXX" required
                        maxlength="10" minlength="10" pattern="\d{10}" inputmode="numeric"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        :class="ocrFilled.national_id ? 'ring-2 ring-emerald-200' : ''"
                        class="w-full bg-slate-50/50 border border-indigo-100 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-bold mb-1.5 text-indigo-600">رقم الهاتف الجوال</label>
                    <div class="flex items-center w-full bg-slate-50/50 border border-indigo-100 rounded-2xl focus-within:bg-white focus-within:border-indigo-500 transition-all overflow-hidden"
                        dir="ltr">
                        <select name="country_code"
                            class="bg-transparent border-none py-3 pl-4 pr-6 text-sm font-bold text-slate-700 focus:ring-0 outline-none cursor-pointer">
                            <option value="+966" {{ old('country_code') == '+966' ? 'selected' : '' }}>+966</option>
                            <option value="+971" {{ old('country_code') == '+971' ? 'selected' : '' }}>+971</option>
                        </select>
                        <div class="w-px h-6 bg-indigo-200 mx-1"></div>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="05XXXXXXXX" required
                            maxlength="10" minlength="9" pattern="\d{9,10}" inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="w-full bg-transparent border-none px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:ring-0 outline-none text-left">
                    </div>
                </div>

                {{-- تاريخ الميلاد --}}
                <div class="md:col-span-2 bg-slate-50/30 border border-slate-200/50 p-5 rounded-2xl space-y-4"
                    :class="ocrFilled.birth ? 'ring-2 ring-emerald-200' : ''">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                        <label class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
                            📅 <span>تاريخ الميلاد</span>
                        </label>
                        <div class="flex bg-slate-100 p-0.5 rounded-xl w-full sm:w-48 border border-slate-200/30">
                            <button type="button" @click="birthType = 'gregorian'"
                                :class="birthType === 'gregorian' ? 'bg-white text-indigo-600 shadow-sm' :
                                    'text-slate-500 hover:text-slate-800'"
                                class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all">ميلادي</button>
                            <button type="button" @click="birthType = 'hijri'"
                                :class="birthType === 'hijri' ? 'bg-white text-indigo-600 shadow-sm' :
                                    'text-slate-500 hover:text-slate-800'"
                                class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all">هجري</button>
                        </div>
                    </div>

                    <input type="hidden" name="birth_type" :value="birthType">
                    <input type="hidden" name="birth_date" :value="birthDate">

                    <!-- التاريخ الميلادي -->
                    <div x-show="birthType === 'gregorian'" x-transition class="grid grid-cols-3 gap-3">
                        <select name="gregorian_day" x-model="gDay"
                            class="w-full bg-white border border-slate-200/80 rounded-xl px-3 py-3 text-xs text-slate-700 focus:border-indigo-500 outline-none">
                            <option value="">اليوم</option>
                            @for ($i = 1; $i <= 31; $i++)
                                <option value="{{ $i }}">{{ sprintf('%02d', $i) }}</option>
                            @endfor
                        </select>
                        <select name="gregorian_month" x-model="gMonth"
                            class="w-full bg-white border border-slate-200/80 rounded-xl px-3 py-3 text-xs text-slate-700 focus:border-indigo-500 outline-none">
                            <option value="">الشهر</option>
                            @foreach ([1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'] as $num => $monthName)
                                <option value="{{ $num }}">{{ sprintf('%02d', $num) }} - {{ $monthName }}</option>
                            @endforeach
                        </select>
                        <select name="gregorian_year" x-model="gYear"
                            class="w-full bg-white border border-slate-200/80 rounded-xl px-3 py-3 text-xs text-slate-700 focus:border-indigo-500 outline-none">
                            <option value="">السنة</option>
                            @for ($i = date('Y'); $i >= 1930; $i--)
                                <option value="{{ $i }}">{{ $i }} م</option>
                            @endfor
                        </select>
                    </div>

                    <!-- التاريخ الهجري -->
                    <div x-show="birthType === 'hijri'" x-transition class="grid grid-cols-3 gap-3">
                        <select name="hijri_day" x-model="hDay"
                            class="w-full bg-white border border-slate-200/80 rounded-xl px-3 py-3 text-xs text-slate-700 focus:border-indigo-500 outline-none">
                            <option value="">اليوم</option>
                            @for ($i = 1; $i <= 30; $i++)
                                <option value="{{ $i }}">{{ sprintf('%02d', $i) }}</option>
                            @endfor
                        </select>
                        <select name="hijri_month" x-model="hMonth"
                            class="w-full bg-white border border-slate-200/80 rounded-xl px-3 py-3 text-xs text-slate-700 focus:border-indigo-500 outline-none">
                            <option value="">الشهر</option>
                            @foreach ([1 => 'محرم', 2 => 'صفر', 3 => 'ربيع الأول', 4 => 'ربيع الثاني', 5 => 'جمادى الأولى', 6 => 'جمادى الآخرة', 7 => 'رجب', 8 => 'شعبان', 9 => 'رمضان', 10 => 'شوال', 11 => 'ذو القعدة', 12 => 'ذو الحجة'] as $num => $monthName)
                                <option value="{{ $num }}">{{ $num }} - {{ $monthName }}</option>
                            @endforeach
                        </select>
                        <select name="hijri_year" x-model="hYear"
                            class="w-full bg-white border border-slate-200/80 rounded-xl px-3 py-3 text-xs text-slate-700 focus:border-indigo-500 outline-none">
                            <option value="">السنة</option>
                            @for ($i = 1450; $i >= 1350; $i--)
                                <option value="{{ $i }}">{{ $i }} هـ</option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- المدينة --}}
                <div class="md:col-span-1">
                    @php
                        $saudiCities = [
                            'الرياض', 'جدة', 'مكة المكرمة', 'المدينة المنورة', 'الدمام', 'الخبر', 'الظهران',
                            'الهفوف', 'الطائف', 'تبوك', 'أبها', 'خميس مشيط', 'جازان', 'نجران', 'حائل',
                            'بريدة', 'عنيزة', 'سكاكا', 'عرعر', 'ينبع', 'الجبيل', 'القطيف', 'بيشة', 'الباحة',
                            'القريات', 'رابغ', 'الخرج', 'الدوادمي', 'المجمعة',
                        ];
                    @endphp
                    <div x-data="{ open: false, search: '', cities: @js($saudiCities) }" @click.outside="open = false" class="relative z-50">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">المدينة الحاليّة</label>
                        <div class="relative">
                            <input type="hidden" name="city" :value="city">
                            <button type="button" @click="open = !open"
                                :class="ocrFilled.city ? 'ring-2 ring-emerald-200' : ''"
                                class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-right flex justify-between items-center font-medium transition-all hover:border-indigo-300 focus:outline-none">
                                <span x-text="city ? city : 'اختر المدينة الحاليّة...'"
                                    :class="!city ? 'text-slate-400' : 'text-slate-900 font-bold'"></span>
                            </button>
                            <div x-show="open"
                                class="absolute z-50 mt-2 w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-2 max-h-64 overflow-y-auto top-full right-0"
                                style="display: none;">
                                <div class="sticky top-0 bg-white pb-2 z-10">
                                    <input type="text" x-model="search" placeholder="ابحث عن مدينة..."
                                        class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none">
                                </div>
                                <div class="space-y-0.5">
                                    <template x-for="c in cities.filter(item => item.includes(search))"
                                        :key="c">
                                        <button type="button" @click="city = c; open = false; search = ''"
                                            class="w-full text-right px-3 py-2 text-xs font-bold rounded-xl transition-colors hover:bg-slate-50"
                                            :class="city === c ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600'"
                                            x-text="c"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- الجنس --}}
                <div class="md:col-span-1 flex flex-col">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">الجنس</label>
                    <div
                        class="flex gap-3 bg-slate-50/50 p-1.5 rounded-2xl border border-slate-200/80 h-[48px] items-center"
                        :class="ocrFilled.gender ? 'ring-2 ring-emerald-200' : ''">
                        <label
                            class="flex-1 flex items-center justify-center gap-2 bg-white h-full rounded-xl border border-slate-200/60 cursor-pointer shadow-sm"><input
                                type="radio" name="gender" value="male" required
                                x-model="gender"
                                class="w-4 h-4 text-indigo-600"><span class="text-sm font-bold text-slate-700">ذكر
                                👨</span></label>
                        <label
                            class="flex-1 flex items-center justify-center gap-2 bg-white h-full rounded-xl border border-slate-200/60 cursor-pointer shadow-sm"><input
                                type="radio" name="gender" value="female" required
                                x-model="gender"
                                class="w-4 h-4 text-pink-600"><span class="text-sm font-bold text-slate-700">أنثى
                                👩</span></label>
                    </div>
                </div>

                {{-- كلمة المرور --}}
                <div x-data="{ show: false }" class="md:col-span-1">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">كلمة المرور</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required minlength="8" maxlength="8"
                            pattern="\d{8}" inputmode="numeric" autocomplete="new-password"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            placeholder="8 أرقام"
                            class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl pl-12 pr-4 py-3 text-sm focus:outline-none">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">👁️</button>
                    </div>
                </div>

                <div x-data="{ show: false }" class="md:col-span-1">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">تأكيد كلمة المرور</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password_confirmation" required minlength="8" maxlength="8"
                            pattern="\d{8}" inputmode="numeric" autocomplete="new-password"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            placeholder="••••••••"
                            class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl pl-12 pr-4 py-3 text-sm focus:outline-none">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">👁️</button>
                    </div>
                </div>

                <div x-show="formError" x-cloak
                    class="md:col-span-2 rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 text-sm font-bold px-4 py-3 text-center"
                    x-text="formError"></div>

                <button type="submit" :disabled="isScanning" :class="isScanning ? 'opacity-60 cursor-wait' : ''"
                    class="md:col-span-2 py-4 bg-slate-950 text-white rounded-2xl font-bold text-base hover:bg-indigo-600 transition-all duration-200 mt-2">
                    تأكيد إنشاء حساب مسافر ✨
                </button>
            </form>

            <div class="text-center mt-6 border-t border-slate-100 pt-5">
                <a href="{{ route('client.auth.login') }}"
                    class="text-sm font-bold text-slate-400 hover:text-indigo-600 transition-all">لديك حساب بالفعل؟ سجل
                    دخولك من هنا</a>
            </div>

        </div>
    </div>
</body>

</html>