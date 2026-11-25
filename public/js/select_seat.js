const DEFAULT_BED_TYPE_OPTIONS = {
    single: [{ id: 'single_bed', label: 'سرير فردي' }],
    double: [{ id: 'double_bed', label: 'سرير مزدوج' }, { id: 'twin_beds', label: 'سريران منفردان' }],
    triple: [{ id: 'three_singles', label: 'ثلاثة أسرّة فردية' }, { id: 'one_double_one_single', label: 'سرير مزدوج + سرير فردي' }],
    quad: [
        { id: 'four_singles', label: 'أربعة أسرّة فردية' },
        { id: 'two_doubles', label: 'سريران مزدوجان' },
        { id: 'one_double_two_singles', label: 'سرير مزدوج + سريران فرديان' },
    ],
};
function bookingManager(config) {
    return {
        // --- 1. الخصائص والبيانات الأساسية ---
        openPassengerModal: false,
        activeModalSeat: null,
        activeModalSeatZone: 'free',
        companions: config.companions || [],
        sharedRoomTypes: {},

        draftExpiresAt: null,
        timeLeftFormatted: '',
        draftTimer: null,

        existingBookingId: config.existingBookingId || null,
        prefilledSeats: config.prefilledSeats || [],
        prefilledPassengers: config.prefilledPassengers || {},

        serviceOptionsRaw: config.extra_services,
        passengerServices: {},

        showAlertModal: false,
        alertMessage: '',
        step: 1,
        showBookedError: false,

        selectedUnassigned: null,
        isAdditionalSeatModal: false,

        passengerSeats: {},
        showAddSeatSelect: {},

        addedCompanionKeys: [],
        companionUidLookup: {},
        selfAdded: false,
        selfUid: null,
        allowExtraSeat: !!config.allowExtraSeat,

        // 🔹 إعداد الحد الأقصى للمقاعد بآمان
        maxSeats: (config.maxSeats !== undefined && config.maxSeats !== null && !isNaN(parseInt(config.maxSeats)))
            ? parseInt(config.maxSeats)
            : null,

        // 🔹 خصائص الرحلة المشروطة والمهلة الزمانية
        isConditional: !!config.isConditional,
        conditionalDeadline: config.conditionalDeadline || null,
        timeRemaining: '',
        isExpired: false,
        deadlineTimer: null,

        selectedSeats: [],
        passengerNames: {},
        passengerIds: {},
        passengerGenders: {},
        seatRoomAssignments: {},
        passengerMeals: {},
        isEmptySeat: {},
        isAdditionalSeat: {},

        accommodationOnlyDiscount: parseFloat(config.accommodationOnlyDiscount) || 0,
        seatPrice: parseFloat(config.seatPrice) || 0,
        isLoggedIn: !!config.isLoggedIn,
        showLoginModal: false,

        roomQuantities: {
            single: 0,
            double: 0,
            triple: 0,
            quad: 0,
        },
        // ① الحالة (بجانب roomQuantities)
        roomBedTypes: {},
        agencyNote: '',
        bedTypeOptions: (config.bedTypeOptions && Object.keys(config.bedTypeOptions).length)
            ? config.bedTypeOptions
            : DEFAULT_BED_TYPE_OPTIONS,

        getBedOptions(uid) {
            const type = String(uid).split('_')[0];
            return this.bedTypeOptions?.[type] || [];
        },

        setBedType(uid, bedId) {
            this.roomBedTypes = { ...this.roomBedTypes, [uid]: bedId };
        },

        getBedLabel(uid) {
            const opt = this.getBedOptions(uid).find(o => o.id === this.roomBedTypes?.[uid]);
            return opt ? opt.label : '';
        },

        // يضبط الأسرّة على الغرف الموجودة فقط ويعطي افتراضي لأي غرفة جديدة
        syncBedTypes() {
            const next = {};
            this.bookedRoomsList.forEach(rm => {
                const opts = this.getBedOptions(rm.uid);
                const cur = this.roomBedTypes?.[rm.uid];
                next[rm.uid] = opts.some(o => o.id === cur) ? cur : (opts[0]?.id || '');
            });
            this.roomBedTypes = next;
        },

        // --- دوال المقاعد الأساسية ---
        assignSeatToSelectedPassenger(seatNum) {
            if (!this.selectedUnassigned) {
                this.alertMessage = '⚠️ يرجى تحديد المسافر من القائمة أولاً لتسكينه.';
                this.showAlertModal = true;
                return;
            }

            let uid = String(this.selectedUnassigned);

            if (!this.passengerSeats[uid]) {
                this.passengerSeats[uid] = [];
            }

            if (this.isSeatTaken(seatNum)) {
                this.alertMessage = '⚠️ هذا المقعد محجوز بالفعل.';
                this.showAlertModal = true;
                return;
            }

            // ✅ إصلاح: منع تسكين المسافر في أكثر من مقعد واحد إذا كانت ميزة "المقعد الإضافي" غير مفعّلة لهذه الرحلة
            if (this.passengerSeats[uid].length >= 1 && !this.allowExtraSeat) {
                this.alertMessage = '⚠️ هذا المسافر لديه مقعد بالفعل، ولا يمكن إضافة مقعد إضافي لأن هذه الميزة غير مفعّلة في هذه الرحلة.';
                this.showAlertModal = true;
                return;
            }

            // ✅ إصلاح: حتى مع تفعيل الميزة، لا يُسمح بأكثر من مقعدين (أساسي + إضافي واحد) لكل مسافر
            if (this.passengerSeats[uid].length >= 2) {
                this.alertMessage = '⚠️ هذا المسافر لديه مقعد أساسي ومقعد إضافي بالفعل، لا يمكن إضافة المزيد.';
                this.showAlertModal = true;
                return;
            }

            if (!this.passengerSeats[uid].includes(seatNum)) {
                this.passengerSeats[uid].push(seatNum);
            }

            if (!this.selectedSeats.includes(seatNum)) {
                this.selectedSeats.push(seatNum);
            }
        },

        // دالة موحدة وآمنة لإلغاء المقعد (تم حل التكرار وتأمين الحذف)
        unseatPassenger(seatNum) {
            const targetSeat = Number(seatNum);
            const strSeat = String(seatNum);

            // 1. إزالة من مصفوفات المسافرين
            for (let uid in this.passengerSeats) {
                if (this.passengerSeats[uid]) {
                    this.passengerSeats[uid] = this.passengerSeats[uid].filter(s => Number(s) !== targetSeat);
                }
            }

            // 2. إزالة من المصفوفة الكلية
            this.selectedSeats = this.selectedSeats.filter(s => String(s) !== strSeat);

            // 3. تنظيف آمن للبيانات المرتبطة لضمان التفاعلية (Reactivity)
            let newNames = { ...this.passengerNames }; delete newNames[strSeat]; this.passengerNames = newNames;
            let newIds = { ...this.passengerIds }; delete newIds[strSeat]; this.passengerIds = newIds;
            let newGenders = { ...this.passengerGenders }; delete newGenders[strSeat]; this.passengerGenders = newGenders;
            let newMeals = { ...this.passengerMeals }; delete newMeals[strSeat]; this.passengerMeals = newMeals;
            let newServices = { ...this.passengerServices }; delete newServices[strSeat]; this.passengerServices = newServices;
            let newAssignments = { ...this.seatRoomAssignments }; delete newAssignments[strSeat]; this.seatRoomAssignments = newAssignments;
            let newEmpty = { ...this.isEmptySeat }; delete newEmpty[strSeat]; this.isEmptySeat = newEmpty;
            let newShared = { ...this.sharedRoomTypes }; delete newShared[strSeat]; this.sharedRoomTypes = newShared;

            if (this.isAdditionalSeat) {
                let newAdd = { ...this.isAdditionalSeat };
                delete newAdd[strSeat];
                this.isAdditionalSeat = newAdd;
            }
        },
        getAvailableSharedRoomTypes() {
            // 1. التصفية بناءً على الغرف المحجوزة في السلة (إن وجدت)
            if (Array.isArray(this.bookedRoomsList) && this.bookedRoomsList.length > 0) {
                const foundMap = new Map();
                const capToType = { 1: 'single', 2: 'double', 3: 'triple', 4: 'quad' };

                this.bookedRoomsList.forEach(rm => {
                    const uid = rm.uid || '';
                    let cap = rm.capacity ? parseInt(rm.capacity, 10) : 0;

                    // تحديد معرّف النوع بناءً على السعة أو المعرف uid
                    let typeId = capToType[cap];
                    if (!typeId) {
                        if (uid.includes('single')) typeId = 'single';
                        else if (uid.includes('triple')) typeId = 'triple';
                        else if (uid.includes('quad')) typeId = 'quad';
                        else typeId = 'double';
                    }

                    if (!foundMap.has(typeId)) {
                        foundMap.set(typeId, {
                            id: typeId,
                            name: this.getTypeName?.(typeId) || this.getDefaultTypeName(typeId)
                        });
                    }
                });

                // إرجاع النتيجة إذا تم العثور على أنواع مطابقة، وإلا يتم الانتقال للخطوة التالية
                if (foundMap.size > 0) {
                    return Array.from(foundMap.values());
                }
            }

            // 2. الجلب من إعدادات الرحلة الأصلية (roomOptions)
            if (this.roomOptions && typeof this.roomOptions === 'object') {
                const available = Object.entries(this.roomOptions)
                    .filter(([_, opt]) => opt && (Number(opt.count) > 0 || Number(opt.price) > 0))
                    .map(([typeKey]) => ({
                        id: typeKey,
                        name: this.getTypeName?.(typeKey) || this.getDefaultTypeName(typeKey)
                    }));

                if (available.length > 0) {
                    return available;
                }
            }

            // 3. الخيار الافتراضي في حال عدم وجود أي تقييد في الإعدادات
            return [
                { id: 'single', name: 'فردية' },
                { id: 'double', name: 'ثنائية' },
                { id: 'triple', name: 'ثلاثية' },
                { id: 'quad', name: 'رباعية' }
            ];
        },

        // دالة مساعدة لضمان إرجاع الاسم العربي في حال لم تكن getTypeName متوفرة
        getDefaultTypeName(typeId) {
            const names = { single: 'فردية', double: 'ثنائية', triple: 'ثلاثية', quad: 'رباعية' };
            return names[typeId] || 'غرفة';
        },
        getPassengerRoomLabel(seat) {
            let room = this.seatRoomAssignments?.[seat];
            if (!room) return 'لم يتم تحديد سكن';

            if (room === 'shared') {
                let typeId = this.sharedRoomTypes?.[seat];
                let typeName = this.getTypeName(typeId);
                return typeName ? `غرفة مشتركة (${typeName})` : 'غرفة مشتركة';
            }

            if (room === 'additional') {
                return 'مقعد إضافي (بدون سكن)';
            }

            let bookedRoom = this.bookedRoomsList.find(r => r.uid === room);
            return bookedRoom ? bookedRoom.name : room;
        },


        // *** إصلاح: دالة مفقودة كانت تُستدعى في صفحة_الحجز.txt (قسم "المقاعد المخصصة")
        // ولم تكن معرّفة إطلاقاً، ما يسبب خطأ JS عند عرض اسم المسافر بجانب رقم المقعد ***
        getPassengerNameBySeat(seat) {
            const strSeat = String(seat);

            if (this.passengerNames?.[seat] !== undefined && this.passengerNames[seat] !== '') {
                return this.passengerNames[seat];
            }
            if (this.passengerNames?.[strSeat] !== undefined && this.passengerNames[strSeat] !== '') {
                return this.passengerNames[strSeat];
            }

            for (let uid in this.passengerSeats) {
                const seats = this.passengerSeats[uid];
                if (seats && seats.map(Number).includes(Number(seat))) {
                    if (this.passengerNames?.[uid]) {
                        return this.passengerNames[uid];
                    }
                }
            }

            return 'بدون اسم';
        },

        isSeatTaken(seatNum) {
            return Object.values(this.passengerSeats || {}).some(
                seats => seats && seats.map(s => String(s)).includes(String(seatNum))
            );
        },

        getPassengerUids() {
            return Object.keys(this.passengerNames || {}).filter(uid => {
                return isNaN(Number(uid)) || Number(uid) < 0;
            });
        },

        getAvailableSeats() {
            let total = parseInt(config.totalSeats) || 49;
            const toArr = v => Array.isArray(v) ? v : Object.values(v || {});

            let unavailable = (config.unavailableSeats || []).map(s => parseInt(s));
            let booked = (config.bookedSeats || config.booked_seats || []).map(s => parseInt(s));
            let takenSeats = Object.values(this.passengerSeats || {}).flat().map(s => parseInt(s));

            let available = [];
            for (let i = 1; i <= total; i++) {
                if (!takenSeats.includes(i) && !unavailable.includes(i) && !booked.includes(i)) {
                    available.push(i);
                }
            }
            return available;
        },


        addExtraSeatToPassenger(uid, seatNumber) {
            if (!this.allowExtraSeat) {
                this.triggerAlert('⚠️ ميزة إضافة مقعد إضافي غير مفعّلة لهذه الرحلة.');
                return;
            }
            if (!seatNumber) return;
            seatNumber = parseInt(seatNumber);
            if (!seatNumber) return;
            seatNumber = parseInt(seatNumber);

            if (!this.passengerSeats[uid]) {
                this.passengerSeats[uid] = [];
            }

            if (!this.passengerSeats[uid].includes(seatNumber)) {
                this.passengerSeats[uid].push(seatNumber);
                if (!this.selectedSeats.includes(seatNumber)) {
                    this.selectedSeats.push(seatNumber);
                }

                if (!this.passengerMeals[seatNumber]) {
                    this.passengerMeals[seatNumber] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
                }
                if (!this.passengerServices[seatNumber]) {
                    this.passengerServices[seatNumber] = [];
                }
            }
        },

        removeExtraSeatFromPassenger(uid, seatNumber) {
            if (this.passengerSeats[uid]) {
                this.passengerSeats[uid] = this.passengerSeats[uid].filter(s => Number(s) !== Number(seatNumber));
            }
            this.selectedSeats = this.selectedSeats.filter(s => String(s) !== String(seatNumber));

            let newMeals = { ...this.passengerMeals }; delete newMeals[seatNumber]; this.passengerMeals = newMeals;
            let newServices = { ...this.passengerServices }; delete newServices[seatNumber]; this.passengerServices = newServices;
        },

        // --- 2. دوال التسكين وإدارة المقاعد غير المسكنة ---
        placeOnSeat(seatNumber, zone = 'free') {
            if (!this.selectedUnassigned) return;
            let oldUid = String(this.selectedUnassigned);
            let strSeat = String(seatNumber);

            // 1. تحديث مصفوفة المقاعد
            let index = this.selectedSeats.indexOf(oldUid);
            if (index !== -1) {
                this.selectedSeats.splice(index, 1, strSeat);
            }

            // 2. تحديد الجنس
            let currentGender = this.passengerGenders[oldUid] || '';
            if (!currentGender) {
                if (zone === 'female') currentGender = 'female';
                else if (zone === 'male') currentGender = 'male';
            }

            // 3. نقل حالة "المقعد الإضافي"
            let isAdd = !!(this.isAdditionalSeat && this.isAdditionalSeat[oldUid]) ||
                (Number(oldUid) < 0) ||
                (this.seatRoomAssignments[oldUid] === 'additional');

            if (isAdd) {
                this.isAdditionalSeat = { ...(this.isAdditionalSeat || {}), [strSeat]: true };
                if (this.isAdditionalSeat && this.isAdditionalSeat[oldUid]) {
                    let newAdd = { ...this.isAdditionalSeat };
                    delete newAdd[oldUid];
                    this.isAdditionalSeat = newAdd;
                }
            }

            // 4. نقل البيانات بطريقة آمنة لـ Alpine (Reactivity)
            let newNames = { ...this.passengerNames, [strSeat]: this.passengerNames[oldUid] || 'مقعد إضافي' }; delete newNames[oldUid]; this.passengerNames = newNames;
            let newIds = { ...this.passengerIds, [strSeat]: this.passengerIds[oldUid] || '' }; delete newIds[oldUid]; this.passengerIds = newIds;
            let newGenders = { ...this.passengerGenders, [strSeat]: currentGender }; delete newGenders[oldUid]; this.passengerGenders = newGenders;
            let newAssignments = { ...this.seatRoomAssignments, [strSeat]: this.seatRoomAssignments[oldUid] || 'additional' }; delete newAssignments[oldUid]; this.seatRoomAssignments = newAssignments;

            if (this.passengerMeals && this.passengerMeals[oldUid]) {
                let newMeals = { ...this.passengerMeals, [strSeat]: this.passengerMeals[oldUid] }; delete newMeals[oldUid]; this.passengerMeals = newMeals;
            }

            if (this.passengerServices && this.passengerServices[oldUid]) {
                let newServices = { ...this.passengerServices, [strSeat]: this.passengerServices[oldUid] }; delete newServices[oldUid]; this.passengerServices = newServices;
            }

            this.selectedUnassigned = null;
        },

        getUnassignedPassengers() {
            let pUids = this.getPassengerUids();
            return pUids.filter(uid => {
                if (String(uid).startsWith('H')) return false;
                let seats = this.passengerSeats[uid] || [];
                return seats.length === 0;
            });
        },

        // --- 3. إضافة وتعيين المسافرين وفحص الحدود ---
        addExtraSeatPassenger() {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            if (this.maxSeats !== null && this.selectedSeats.length >= this.maxSeats) {
                this.triggerAlert(`⚠️ عذراً، لا يمكنك إضافة أكثر من ${this.maxSeats} مسافرين في الحجز الواحد.`);
                return;
            }

            let tempId = -1;
            while (this.selectedSeats.some(s => String(s) === String(tempId))) tempId--;

            const strTempId = String(tempId);
            this.selectedSeats.push(strTempId);

            if (!this.isAdditionalSeat) this.isAdditionalSeat = {};
            this.passengerMeals[strTempId] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
            this.passengerServices[strTempId] = [];
            this.passengerNames[strTempId] = '';
            this.passengerIds[strTempId] = '';
            this.passengerGenders[strTempId] = '';
            this.seatRoomAssignments[strTempId] = 'additional';
            this.isAdditionalSeat[strTempId] = true;

            this.activeModalSeat = strTempId;
            this.activeModalSeatZone = 'free';
            this.openPassengerModal = true;
            this.syncRoomAssignments();
        },

        addTransportPassenger() {
            if (this.maxSeats !== null && this.selectedSeats.length >= this.maxSeats) {
                this.triggerAlert(`عذراً، لا يمكنك إضافة أكثر من ${this.maxSeats} مسافرين.`);
                return;
            }
            this.addExtraSeatPassenger();
        },

        addAccommodationOnlyPassenger() {
            if (this.maxSeats !== null && this.selectedSeats.length >= this.maxSeats) {
                this.triggerAlert(`عذراً، لا يمكنك إضافة أكثر من ${this.maxSeats} مسافرين.`);
                return;
            }
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            let i = 1;
            while (this.selectedSeats.some(s => String(s) === `H${i}`)) i++;

            const id = `H${i}`;
            this.selectedSeats.push(id);
            this.passengerMeals[id] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
            this.passengerServices[id] = [];
            this.passengerNames[id] = '';
            this.passengerIds[id] = '';
            this.passengerGenders[id] = '';
            this.seatRoomAssignments[id] = '';

            this.activeModalSeat = id;
            this.activeModalSeatZone = 'free';
            this.openPassengerModal = true;
            this.syncRoomAssignments();
        },
        getCompanionKey(companion, index) {
            return companion?.id
                ?? companion?.national_id
                ?? companion?.id_number
                ?? companion?.identity
                ?? ('idx_' + index);
        },

        isCompanionAdded(companion, index) {
            const key = this.getCompanionKey(companion, index);
            if (this.addedCompanionKeys.includes(key)) return true;

            // فحص احتياطي: يمنع التكرار حتى لو كان الكاش فارغاً (مسودات قديمة)
            const compId = String(companion.national_id || companion.id_number || companion.identity || '').trim();
            if (compId) {
                return Object.values(this.passengerIds || {}).some(id => String(id).trim() === compId);
            }
            return false;
        },

        addCompanionPassenger(companion, index) {
            if (this.isCompanionAdded(companion, index)) return; // 🔧 يمنع التكرار حتى مع مسودة قديمة

            if (this.maxSeats !== null && this.selectedSeats.length >= this.maxSeats) {
                this.triggerAlert(`عذراً، وصلت للحد الأقصى (${this.maxSeats} مسافرين).`);
                return;
            }
            const key = this.getCompanionKey(companion, index);

            let uid = -Date.now() - Math.floor(Math.random() * 1000);
            const strUid = String(uid);

            this.selectedSeats.push(strUid);
            this.passengerNames[strUid] = companion.name || companion.full_name || '';
            this.passengerIds[strUid] = companion.national_id || companion.id_number || companion.identity || '';
            this.passengerGenders[strUid] = companion.gender === 'female' ? 'female' : 'male';
            this.passengerMeals[strUid] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
            this.seatRoomAssignments[strUid] = '';

            this.addedCompanionKeys.push(key);
            this.companionUidLookup[strUid] = key;

            this.syncRoomAssignments();
        },

        addSelfPassenger() {
            const myId = String(config.authUserNationalId || '').trim();
            const alreadyExists = this.selfAdded ||
                (myId && Object.values(this.passengerIds || {}).some(id => String(id).trim() === myId));

            if (alreadyExists) {
                this.selfAdded = true; // مزامنة الحالة دفاعياً
                return;
            }

            if (this.maxSeats !== null && this.selectedSeats.length >= this.maxSeats) {
                this.triggerAlert(`عذراً، وصلت للحد الأقصى (${this.maxSeats} مسافرين).`);
                return;
            }

            let uid = -Date.now() - Math.floor(Math.random() * 1000);
            const strUid = String(uid);

            this.selectedSeats.push(strUid);
            this.passengerNames[strUid] = config.authUserName || '';
            this.passengerIds[strUid] = config.authUserNationalId || '';
            this.passengerGenders[strUid] = config.authUserGender === 'female' ? 'female' : 'male';
            this.passengerMeals[strUid] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
            this.seatRoomAssignments[strUid] = '';

            this.selfAdded = true;
            this.selfUid = strUid;

            this.syncRoomAssignments();
        },

        isAdditionalPassenger(seat) {
            if (seat === null || seat === undefined) return false;
            const strSeat = String(seat);

            if (this.isAdditionalSeat && !!this.isAdditionalSeat[strSeat]) return true;
            if (this.seatRoomAssignments && this.seatRoomAssignments[strSeat] === 'additional') return true;
            return false;
        },

        canDeletePassenger(seat) {
            return this.isAdditionalPassenger(seat);
        },

        savePassengerModal() {
            if (this.activeModalSeat == null) {
                this.openPassengerModal = false;
                return;
            }

            const seatId = String(this.activeModalSeat);
            const numericSeat = parseInt(seatId, 10);

            const name = String(this.passengerNames?.[seatId] ?? '').trim();
            const id = String(this.passengerIds?.[seatId] ?? '').trim();
            const gender = String(this.passengerGenders?.[seatId] ?? '').trim();
            const room = this.seatRoomAssignments?.[seatId] ?? null;

            // جلب نوع الغرفة المشتركة
            const sharedType = this.sharedRoomTypes?.[seatId] ?? null;

            const isAdditional = this.isAdditionalSeatModal || this.isAdditionalPassenger(seatId);
            const nameValid = name.length > 0;
            const idValid = isAdditional ? true : /^\d{10}$/.test(id);
            const genderValid = gender.length > 0;
            const roomValid = room !== null && room !== '';

            // التحقق من صحة الغرفة المشتركة (يجب أن تحتوي على قيمة إذا كان السكن 'shared')
            const sharedTypeValid = room === 'shared' ? (sharedType !== null && sharedType !== '') : true;

            // إضافة sharedTypeValid لشرط المنع
            if (!nameValid || !idValid || !genderValid || !roomValid || !sharedTypeValid) {
                this.showValidation = true;
                if (!nameValid) this.triggerAlert('⚠️ يرجى إدخال اسم المسافر.');
                else if (!idValid) this.triggerAlert('⚠️ يرجى إدخال رقم هوية صحيح مكون من 10 أرقام.');
                else if (!genderValid) this.triggerAlert('⚠️ يرجى تحديد جنس المسافر (ذكر / أنثى).');
                else if (!roomValid) this.triggerAlert('⚠️ يجب اختيار سكن للمسافر قبل الحفظ.');
                else if (!sharedTypeValid) this.triggerAlert('⚠️ يرجى تحديد نوع الغرفة المشتركة.'); // التنبيه المخصص
                return;
            }

            if (typeof config !== 'undefined' && config.isAutoSplit && !isNaN(numericSeat) && numericSeat > 0) {
                const boundary = parseInt(config.splitBoundary, 10) || 28;
                const isFemaleZone = numericSeat > boundary;

                if (gender === 'male' && isFemaleZone) {
                    this.showValidation = true;
                    this.triggerAlert('⚠️ عذراً، هذا المقعد في القسم المخصص للسيدات.');
                    return;
                }
                if (gender === 'female' && !isFemaleZone) {
                    this.showValidation = true;
                    this.triggerAlert('⚠️ عذراً، هذا المقعد في القسم المخصص للرجال.');
                    return;
                }
            }

            this.showValidation = false;
            this.openPassengerModal = false;
        },
        // تحويل المسافر بين (نقل وسكن) و (سكن فقط) مع الحفاظ على كل بياناته
        convertPassengerType(oldUid, toAccommodationOnly) {
            oldUid = String(oldUid);
            const isH = oldUid.startsWith('H');
            if (toAccommodationOnly === isH) return; // نفس النوع الحالي

            // مقعد إضافي مرتبط بمسافر نقل لا يتحول لسكن فقط
            if (toAccommodationOnly && this.isAdditionalPassenger(oldUid)) {
                this.triggerAlert('⚠️ لا يمكن تحويل مقعد إضافي إلى سكن فقط.');
                return;
            }

            // توليد المعرّف الجديد
            let newUid;
            if (toAccommodationOnly) {
                let i = 1;
                while (this.selectedSeats.some(s => String(s) === `H${i}`)) i++;
                newUid = `H${i}`;
            } else {
                newUid = String(-Date.now() - Math.floor(Math.random() * 1000));
            }

            // السكن فقط ما له مقاعد حافلة: نحرر مقاعده الحالية
            const oldSeatNums = (this.passengerSeats[oldUid] || []).map(Number);
            if (toAccommodationOnly && oldSeatNums.length > 0) {
                if (!confirm('سيتم إلغاء مقعد/مقاعد هذا المسافر في الحافلة لأنه سيصبح سكن فقط. هل تريد المتابعة؟')) return;
            }

            // نقل مفتاح واحد داخل كائن مع الحفاظ على التفاعلية
            const move = (obj, transform) => {
                const n = { ...(obj || {}) };
                if (oldUid in n) {
                    const val = n[oldUid];
                    delete n[oldUid];
                    const out = transform ? transform(val) : val;
                    if (out !== undefined) n[newUid] = out;
                }
                return n;
            };

            this.passengerNames = move(this.passengerNames);
            this.passengerIds = move(this.passengerIds);
            this.passengerGenders = move(this.passengerGenders);
            this.passengerMeals = move(this.passengerMeals);
            this.passengerServices = move(this.passengerServices);
            this.isEmptySeat = move(this.isEmptySeat);
            this.isAdditionalSeat = move(this.isAdditionalSeat);

            // السكن: "مشترك" و"إضافي" غير مسموحين لمسافر السكن فقط
            this.seatRoomAssignments = move(this.seatRoomAssignments, v =>
                (toAccommodationOnly && (v === 'shared' || v === 'additional')) ? '' : v);
            this.sharedRoomTypes = move(this.sharedRoomTypes, v => toAccommodationOnly ? undefined : v);

            // المقاعد: السكن فقط بدون مقاعد، والعكس يبدأ بدون مقعد
            this.passengerSeats = move(this.passengerSeats, () => toAccommodationOnly ? undefined : []);
            if (!toAccommodationOnly) this.passengerSeats = { ...this.passengerSeats, [newUid]: [] };

            // المصفوفة الكلية: استبدال المعرّف وحذف مقاعد الحافلة المحررة
            this.selectedSeats = this.selectedSeats
                .filter(s => !(toAccommodationOnly && oldSeatNums.includes(Number(s)) && String(s) !== oldUid))
                .map(s => String(s) === oldUid ? newUid : s);

            // تتبع المرافق / الحساب الشخصي
            if (this.companionUidLookup && this.companionUidLookup[oldUid] !== undefined) {
                const lookup = { ...this.companionUidLookup };
                lookup[newUid] = lookup[oldUid];
                delete lookup[oldUid];
                this.companionUidLookup = lookup;
            }
            if (this.selfUid === oldUid) this.selfUid = newUid;
            if (this.selectedUnassigned && String(this.selectedUnassigned) === oldUid) this.selectedUnassigned = null;

            // إبقاء النافذة مفتوحة على نفس المسافر بمعرّفه الجديد
            this.activeModalSeat = newUid;
            this.isAdditionalSeatModal = false;
            this.syncRoomAssignments();
        },

        triggerAlert(message) {
            this.alertMessage = message;
            this.showAlertModal = true;
        },

        removePassenger(seat) {
            if (seat === null || seat === undefined) return;
            const targetSeat = String(seat);

            if (this.companionUidLookup && this.companionUidLookup[targetSeat] !== undefined) {
                const key = this.companionUidLookup[targetSeat];
                this.addedCompanionKeys = this.addedCompanionKeys.filter(k => k !== key);
                let newLookup = { ...this.companionUidLookup };
                delete newLookup[targetSeat];
                this.companionUidLookup = newLookup;
            }
            if (this.selfAdded && this.selfUid === targetSeat) {
                this.selfAdded = false;
                this.selfUid = null;
            }

            this.selectedSeats = this.selectedSeats.filter(s => String(s) !== targetSeat);

            // عمليات حذف آمنة لدعم Alpine.js Reactivity
            let newNames = { ...this.passengerNames };
            let newIds = { ...this.passengerIds };
            let newGenders = { ...this.passengerGenders };
            let newMeals = { ...this.passengerMeals };
            let newServices = { ...this.passengerServices };
            let newAssignments = { ...this.seatRoomAssignments };
            let newEmpty = { ...this.isEmptySeat };
            let newAdd = { ...this.isAdditionalSeat };
            let newShared = { ...this.sharedRoomTypes }; delete newShared[seat]; delete newShared[targetSeat]; this.sharedRoomTypes = newShared;

            [seat, targetSeat].forEach(k => {
                delete newNames[k];
                delete newIds[k];
                delete newGenders[k];
                delete newMeals[k];
                delete newServices[k];
                delete newAssignments[k];
                delete newEmpty[k];
                if (newAdd) delete newAdd[k];
            });

            this.passengerNames = newNames;
            this.passengerIds = newIds;
            this.passengerGenders = newGenders;
            this.passengerMeals = newMeals;
            this.passengerServices = newServices;
            this.seatRoomAssignments = newAssignments;
            this.isEmptySeat = newEmpty;
            if (this.isAdditionalSeat) this.isAdditionalSeat = newAdd;

            if (this.selectedUnassigned && String(this.selectedUnassigned) === targetSeat) {
                this.selectedUnassigned = null;
            }

            this.syncRoomAssignments();
            this.openPassengerModal = false;
            this.activeModalSeat = null;
        },

        isSelected(id) {
            return this.selectedSeats.some(s => String(s) === String(id));
        },

        toggleSeat(id) {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            id = String(id);
            if (this.selectedSeats.includes(id)) {
                this.removePassenger(id);
            } else {
                if (this.maxSeats !== null && this.selectedSeats.length >= this.maxSeats) {
                    this.triggerAlert(`عذراً، لا يمكنك اختيار أكثر من ${this.maxSeats} مقاعد في الحجز الواحد.`);
                    return;
                }

                this.selectedSeats.push(id);
                this.passengerMeals[id] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
                this.passengerServices[id] = [];

                if (this.selectedSeats.length === 1) {
                    this.passengerNames[id] = config.authUserName || '';
                    this.passengerIds[id] = config.authUserNationalId || '';
                    this.passengerGenders[id] = config.authUserGender || '';
                }

                this.activeModalSeat = id;
                this.openPassengerModal = true;
            }
            this.syncRoomAssignments();
        },

        // --- 5. دوال الترتيب والحسابات ---
        sortedSeats() {
            return [...this.selectedSeats].sort((a, b) => {
                const aIsH = String(a).startsWith('H');
                const bIsH = String(b).startsWith('H');
                if (aIsH && !bIsH) return 1;
                if (!aIsH && bIsH) return -1;
                if (aIsH && bIsH) {
                    return parseInt(String(a).substring(1)) - parseInt(String(b).substring(1));
                }
                return parseInt(a) - parseInt(b);
            });
        },

        autoSelectGroup(groupSize, zone) {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            let maxAllowedToAssign = groupSize;
            if (this.maxSeats !== null) {
                maxAllowedToAssign = this.maxSeats - this.selectedSeats.length;
                if (maxAllowedToAssign <= 0) {
                    this.triggerAlert(`⚠️ لقد وصلت بالفعل للحد الأقصى (${this.maxSeats} مقاعد).`);
                    return;
                }
            }

            let effectiveGroupSize = Math.min(groupSize, maxAllowedToAssign);
            let availableSeats = [];
            let total = parseInt(config.totalSeats) || 49;
            let boundary = parseInt(config.splitBoundary) || 28;
            let unavailable = (config.unavailableSeats || []).map(s => String(s));

            for (let i = 1; i <= total; i++) {
                let seatStr = String(i);
                if (!unavailable.includes(seatStr)) {
                    let isFemaleZone = config.isAutoSplit && i > boundary;
                    let currentZone = isFemaleZone ? 'female' : 'male';

                    if (!config.isAutoSplit || currentZone === zone) {
                        availableSeats.push(i);
                    }
                }
            }

            availableSeats.sort((a, b) => {
                let colA = (a - 1) % 5;
                let rowA = Math.floor((a - 1) / 5);
                let colB = (b - 1) % 5;
                let rowB = Math.floor((b - 1) / 5);

                if (colA !== colB) return colA - colB;
                return rowA - rowB;
            });

            let assignedCount = 0;
            for (let i = 0; i < availableSeats.length && assignedCount < effectiveGroupSize; i++) {
                let seatId = String(availableSeats[i]);
                if (!this.selectedSeats.includes(seatId)) {
                    this.toggleSeat(seatId);
                    assignedCount++;
                }
            }

            if (assignedCount < groupSize) {
                this.triggerAlert(`تنبيه: تم تحديد ${assignedCount} مقاعد فقط.`);
            }
        },

        getTotalCapacity() {
            return ((this.roomQuantities.single || 0) * 1) +
                ((this.roomQuantities.double || 0) * 2) +
                ((this.roomQuantities.triple || 0) * 3) +
                ((this.roomQuantities.quad || 0) * 4);
        },


        checkIncluded() {
            const a = this.seatRoomAssignments[this.activeModalSeat];
            return !!a && a !== 'additional';
        },

        getSeatStatusText() {
            const seat = this.activeModalSeat;
            if (!seat) return '';
            if (String(seat).startsWith('H')) return 'مسافر (سكن فقط) مستقل، لا يتم تخصيص مقعد له في الحافلة.';

            const a = this.seatRoomAssignments[seat];
            if (a === 'shared') {
                const t = this.sharedRoomTypes[seat];
                return t ? `غرفة مشتركة (${this.getTypeName(t)}): ${this.getRoomPrice(t)} ر.س للفرد`
                    : 'اختر نوع الغرفة المشتركة';
            }
            if (a && a !== 'additional') {
                return `غرفة خاصة: ${this.getRoomPrice(a.split('_')[0])} ر.س للفرد`;
            }
            return `لم يتم تحديد سكن (سيُحتسب سعر المقعد ${this.seatPrice} ر.س)`;
        },

        // --- 6. خيارات الغرف والوجبات والخدمات الإضافية ---
        roomOptions: (() => {
            let options = config.roomOptionsRaw;
            if (typeof options === 'string') {
                try { options = JSON.parse(options); } catch (e) { options = []; }
            }

            let defOptions = {
                single: { price: 0, count: 0, meals: { breakfast: 0, lunch: 0, dinner: 0 }, packages: [] },
                double: { price: 0, count: 0, meals: { breakfast: 0, lunch: 0, dinner: 0 }, packages: [] },
                triple: { price: 0, count: 0, meals: { breakfast: 0, lunch: 0, dinner: 0 }, packages: [] },
                quad: { price: 0, count: 0, meals: { breakfast: 0, lunch: 0, dinner: 0 }, packages: [] },
            };

            if (Array.isArray(options)) {
                options.forEach(opt => {
                    let key = opt.key || opt.type || opt.room_type;
                    if (key && defOptions.hasOwnProperty(key)) {
                        defOptions[key].price = parseFloat(opt.price) || 0;
                        defOptions[key].count = parseInt(opt.count || opt.qty || opt.available) || 0;
                        defOptions[key].packages = opt.packages || [];
                        if (opt.meals) {
                            defOptions[key].meals.breakfast = parseFloat(opt.meals.breakfast) || 0;
                            defOptions[key].meals.lunch = parseFloat(opt.meals.lunch) || 0;
                            defOptions[key].meals.dinner = parseFloat(opt.meals.dinner) || 0;
                        }
                    }
                });
            } else if (options && typeof options === 'object') {
                for (let key in options) {
                    if (defOptions.hasOwnProperty(key)) {
                        defOptions[key].price = parseFloat(options[key].price) || 0;
                        defOptions[key].count = parseInt(options[key].count || 0) || 0;
                        defOptions[key].packages = options[key].packages || [];
                        if (options[key].meals) {
                            defOptions[key].meals.breakfast = parseFloat(options[key].meals.breakfast) || 0;
                            defOptions[key].meals.lunch = parseFloat(options[key].meals.lunch) || 0;
                            defOptions[key].meals.dinner = parseFloat(options[key].meals.dinner) || 0;
                        }
                    }
                }
            }
            return defOptions;
        })(),

        baseMealPrices: (() => {
            let meals = config.mealOptionsRaw;
            if (typeof meals === 'string') {
                try { meals = JSON.parse(meals); } catch (e) { meals = {}; }
            }

            const bf = meals?.individual?.breakfast?.price ?? meals?.breakfast ?? meals?.breakfast_price ?? 0;
            const ln = meals?.individual?.lunch?.price ?? meals?.lunch ?? meals?.lunch_price ?? 0;
            const dn = meals?.individual?.dinner?.price ?? meals?.dinner ?? meals?.dinner_price ?? 0;

            return {
                breakfast: parseFloat(bf) || 0,
                lunch: parseFloat(ln) || 0,
                dinner: parseFloat(dn) || 0,
                breakfast_active: meals?.individual?.breakfast?.active !== undefined ? !!meals.individual.breakfast.active : (parseFloat(bf) > 0),
                lunch_active: meals?.individual?.lunch?.active !== undefined ? !!meals.individual.lunch.active : (parseFloat(ln) > 0),
                dinner_active: meals?.individual?.dinner?.active !== undefined ? !!meals.individual.dinner.active : (parseFloat(dn) > 0),
            };
        })(),

        mealPackages: (() => {
            let meals = config.mealOptionsRaw;
            if (typeof meals === 'string') {
                try { meals = JSON.parse(meals); } catch (e) { meals = {}; }
            }
            return {
                bf_ln: meals?.packages?.bf_ln !== undefined && meals?.packages?.bf_ln !== null ? parseFloat(meals.packages.bf_ln) : null,
                ln_dn: meals?.packages?.ln_dn !== undefined && meals?.packages?.ln_dn !== null ? parseFloat(meals.packages.ln_dn) : null,
                bf_dn: meals?.packages?.bf_dn !== undefined && meals?.packages?.bf_dn !== null ? parseFloat(meals.packages.bf_dn) : null,
                all: meals?.packages?.all !== undefined && meals?.packages?.all !== null ? parseFloat(meals.packages.all) : null,
            };
        })(),

        getMealPrice(seat, mealType) {
            return parseFloat(
                this.baseMealPrices?.[mealType] ||
                this.mealOptionsRaw?.[mealType] ||
                this.mealOptionsRaw?.[mealType + '_price'] ||
                0
            );
        },

        getActiveSeatPackages() {
            if (!this.activeModalSeat) return [];
            let assignment = this.seatRoomAssignments[this.activeModalSeat] || 'shared';
            if (assignment === 'shared' || assignment === 'additional') return [];
            let roomType = assignment.split('_')[0];
            return this.roomOptions[roomType]?.packages || [];
        },

        get roomPrices() {
            let prices = config.roomPrices;
            if (typeof prices === 'string') {
                try { prices = JSON.parse(prices); } catch (e) { prices = {}; }
            }
            return prices || {};
        },

        selectPackage(seatId, pkg) {
            if (!this.passengerMeals[seatId]) {
                this.passengerMeals[seatId] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
            }

            let pm = this.passengerMeals[seatId];
            if (pm.package_name === pkg.name) {
                pm.package_name = null;
                pm.package_price = 0;
                pm.breakfast = false;
                pm.lunch = false;
                pm.dinner = false;
                pm.wants_meal = false;
            } else {
                pm.package_name = pkg.name;
                pm.package_price = parseFloat(pkg.price) || 0;
                pm.breakfast = !!pkg.includes_breakfast;
                pm.lunch = !!pkg.includes_lunch;
                pm.dinner = !!pkg.includes_dinner;
                pm.wants_meal = true;
            }
        },

        toggleIndividualMeal(seatId, mealType) {
            if (!this.passengerMeals[seatId]) {
                this.passengerMeals[seatId] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
            }

            let pm = this.passengerMeals[seatId];
            pm[mealType] = !pm[mealType];
            pm.package_name = null;
            pm.package_price = 0;
            pm.wants_meal = !!(pm.breakfast || pm.lunch || pm.dinner);
        },

        onMealChange(seatId) {
            if (!this.passengerMeals[seatId]) return;
            let pm = this.passengerMeals[seatId];
            pm.package_name = null;
            pm.package_price = 0;
            pm.wants_meal = !!(pm.breakfast || pm.lunch || pm.dinner);
        },

        toggleService(seat, serviceKey) {
            if (!seat) return;
            if (!this.passengerServices[seat]) {
                this.passengerServices[seat] = [];
            }
            let index = this.passengerServices[seat].indexOf(serviceKey);
            if (index > -1) {
                this.passengerServices[seat].splice(index, 1);
            } else {
                this.passengerServices[seat].push(serviceKey);
            }
        },

        hasService(seat, serviceKey) {
            return !!(this.passengerServices[seat] && this.passengerServices[seat].includes(serviceKey));
        },

        getPassengerServicesDetails(seat) {
            if (!seat || !this.passengerServices?.[seat] || !this.serviceOptionsRaw) return [];
            const selectedKeys = this.passengerServices[seat];
            return this.serviceOptionsRaw.filter(service => {
                let key = service.id !== undefined ? service.id : service.name;
                return selectedKeys.some(k => String(k) === String(key));
            });
        },

        increaseRoom(type) {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            const roomCapacities = { single: 1, double: 2, triple: 3, quad: 4 };
            const addedCap = roomCapacities[type] || 1;

            if (this.maxSeats !== null && (this.getTotalCapacity() + addedCap) > this.maxSeats) {
                this.triggerAlert(`⚠️ لا يمكنك زيادة الغرف، سعة الغرف الكلية ستتجاوز الحد الأقصى المسموح به للرحلة (${this.maxSeats} مسافرين).`);
                return;
            }

            this.roomQuantities[type] = (this.roomQuantities[type] || 0) + 1;
            this.syncRoomAssignments();
        },

        decreaseRoom(type) {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }
            if (this.roomQuantities[type] > 0) {
                this.roomQuantities[type]--;
                this.syncRoomAssignments();
            }
        },

        get bookedRoomsList() {
            let list = [];
            for (let type in this.roomQuantities) {
                let qty = this.roomQuantities[type];
                for (let i = 1; i <= qty; i++) {
                    list.push({
                        uid: `${type}_${i}`,
                        name: `غرفة ${this.getTypeName(type)} #${i}`,
                        meals: this.roomOptions[type]?.meals || { breakfast: 0, lunch: 0, dinner: 0 }
                    });
                }
            }
            return list;
        },

        syncRoomAssignments() {
            const rooms = this.bookedRoomsList;
            const validUids = new Set(rooms.map(r => r.uid));
            const capacities = { single: 1, double: 2, triple: 3, quad: 4 };
            const getCap = uid => capacities[String(uid).split('_')[0]] || 2;

            const used = {};
            const updated = {};

            // نحافظ على اختيارات المستخدم الصالحة فقط، وبدون أي قيمة افتراضية
            Object.keys(this.seatRoomAssignments || {}).forEach(key => {
                const val = this.seatRoomAssignments[key];

                if (val === 'additional' || val === 'shared') {
                    updated[key] = val;
                } else if (val && validUids.has(val) && (used[val] || 0) < getCap(val)) {
                    updated[key] = val;
                    used[val] = (used[val] || 0) + 1;
                } else {
                    // الغرفة انحذفت أو امتلأت أو ما اختار شي
                    updated[key] = '';
                }
            });

            // أي مسافر جديد بدون مفتاح يبدأ فارغ (إجباري يختار)
            this.getPassengerUids().forEach(uid => {
                if (!(uid in updated)) updated[uid] = '';
            });

            this.seatRoomAssignments = updated;

            // تنظيف الباقات المرتبطة بغرفة ما عاد لها وجود
            Object.keys(updated).forEach(key => {
                const a = updated[key];
                const pm = this.passengerMeals[key];
                if ((!a || a === 'shared' || a === 'additional') && pm && pm.package_name) {
                    pm.package_name = null;
                    pm.package_price = 0;
                    pm.wants_meal = !!(pm.breakfast || pm.lunch || pm.dinner);
                }
            });
            this.syncBedTypes();

        },

        getTypeName(type) {
            return { single: 'فردية', double: 'ثنائية', triple: 'ثلاثية', quad: 'رباعية' }[type];
        },

        getSingleMealPrice(type) {
            return this.baseMealPrices[type] || 0;
        },

        calcMeals(seat, m) {
            if (!m || !m.wants_meal) return { regular: 0, actual: 0 };
            const bf = !!m.breakfast, ln = !!m.lunch, dn = !!m.dinner;
            const pBf = bf ? this.getMealPrice(seat, 'breakfast') : 0;
            const pLn = ln ? this.getMealPrice(seat, 'lunch') : 0;
            const pDn = dn ? this.getMealPrice(seat, 'dinner') : 0;
            const regular = pBf + pLn + pDn;

            if (m.package_name) return { regular, actual: parseFloat(m.package_price) || 0 };

            const pk = this.mealPackages;
            const options = [regular];
            if (bf && ln && dn && pk.all !== null) options.push(pk.all);
            if (bf && ln && pk.bf_ln !== null) options.push(pk.bf_ln + pDn);
            if (ln && dn && pk.ln_dn !== null) options.push(pk.ln_dn + pBf);
            if (bf && dn && pk.bf_dn !== null) options.push(pk.bf_dn + pLn);
            return { regular, actual: Math.min(...options) };
        },

        get totalMealsPrice() {
            let t = 0;
            for (const seat in this.passengerMeals) t += this.calcMeals(seat, this.passengerMeals[seat]).actual;
            return t;
        },

        totalSavings() {
            let s = 0;
            for (const seat in this.passengerMeals) {
                const r = this.calcMeals(seat, this.passengerMeals[seat]);
                s += Math.max(0, r.regular - r.actual);
            }
            return s;
        },
        // --- 7. حساب المبالغ والإجمالي ---
        // totalSavings() {
        //     let savings = 0;
        //     this.selectedSeats.forEach(seat => {
        //         const meals = this.passengerMeals[seat];
        //         if (!meals || !meals.wants_meal) return;

        //         const hasBf = !!meals.breakfast;
        //         const hasLn = !!meals.lunch;
        //         const hasDn = !!meals.dinner;

        //         let breakfastPrice = this.getMealPrice(seat, 'breakfast');
        //         let lunchPrice = this.getMealPrice(seat, 'lunch');
        //         let dinnerPrice = this.getMealPrice(seat, 'dinner');

        //         let regularPrice = (hasBf ? breakfastPrice : 0) + (hasLn ? lunchPrice : 0) + (hasDn ? dinnerPrice : 0);
        //         let actualPrice = regularPrice;

        //         if (meals.package_name) {
        //             actualPrice = parseFloat(meals.package_price) || 0;
        //         } else {
        //             if (hasBf && hasLn && hasDn && this.mealPackages.all !== null) {
        //                 actualPrice = this.mealPackages.all;
        //             } else if (hasBf && hasLn && this.mealPackages.bf_ln !== null) {
        //                 actualPrice = this.mealPackages.bf_ln;
        //             } else if (hasLn && hasDn && this.mealPackages.ln_dn !== null) {
        //                 actualPrice = this.mealPackages.ln_dn;
        //             } else if (hasBf && hasDn && this.mealPackages.bf_dn !== null) {
        //                 actualPrice = this.mealPackages.bf_dn;
        //             }
        //         }
        //         savings += Math.max(0, regularPrice - actualPrice);
        //     });
        //     return savings;
        // },

        // get totalMealsPrice() {
        //     let total = 0;
        //     for (let seat in this.passengerMeals) {
        //         let mealData = this.passengerMeals[seat];
        //         if (!mealData || !mealData.wants_meal) continue;

        //         if (mealData.package_name) {
        //             total += parseFloat(mealData.package_price) || 0;
        //         } else {
        //             let hasBf = !!mealData.breakfast;
        //             let hasLn = !!mealData.lunch;
        //             let hasDn = !!mealData.dinner;

        //             if (hasBf && hasLn && hasDn && this.mealPackages.all !== null) {
        //                 total += this.mealPackages.all;
        //             } else if (hasBf && hasLn && this.mealPackages.bf_ln !== null) {
        //                 total += this.mealPackages.bf_ln;
        //             } else if (hasLn && hasDn && this.mealPackages.ln_dn !== null) {
        //                 total += this.mealPackages.ln_dn;
        //             } else if (hasBf && hasDn && this.mealPackages.bf_dn !== null) {
        //                 total += this.mealPackages.bf_dn;
        //             } else {
        //                 if (hasBf) total += this.getMealPrice(seat, 'breakfast');
        //                 if (hasLn) total += this.getMealPrice(seat, 'lunch');
        //                 if (hasDn) total += this.getMealPrice(seat, 'dinner');
        //             }
        //         }
        //     }
        //     return total;
        // },

        get totalServicesPrice() {
            let total = 0;
            if (!this.serviceOptionsRaw || !this.passengerServices) return 0;

            for (let seat in this.passengerServices) {
                let details = this.getPassengerServicesDetails(seat);
                details.forEach(service => {
                    total += parseFloat(service.price) || 0;
                });
            }
            return total;
        },

        getRoomPrice(type) {
            return parseFloat(this.roomPrices?.[type]) || parseFloat(this.roomOptions?.[type]?.price) || 0;
        },
        get priceBreakdown() {
            // دالة مساعدة لجلب قيمة مرتبطة بالمسافر سواء كانت مخزّنة تحت uid المجموعة أو رقم المقعد نفسه
            const getPassengerAttr = (seat, attrObject) => {
                if (attrObject[seat] !== undefined && attrObject[seat] !== '') {
                    return attrObject[seat];
                }
                for (let uid in this.passengerSeats) {
                    if (this.passengerSeats[uid] && this.passengerSeats[uid].map(Number).includes(Number(seat))) {
                        if (attrObject[uid] !== undefined && attrObject[uid] !== '') {
                            return attrObject[uid];
                        }
                    }
                }
                return '';
            };

            let uids = this.getPassengerUids ? this.getPassengerUids() : [];

            let roomsTotal = 0;
            let extraSeatCount = 0;


            uids.forEach(uid => {
                const isAccommodationOnly = String(uid).startsWith('H');
                const room = getPassengerAttr(uid, this.seatRoomAssignments) || '';

                if (room && room !== 'shared' && room !== 'additional' && this.bookedRoomsList.some(r => r.uid === room)) {
                    // مُسكَّن في غرفة خاصة محجوزة فعلياً من الخطوة 1
                    const type = room.split('_')[0];
                    roomsTotal += this.getRoomPrice(type);
                } else if (room === 'shared') {
                    // مُسكَّن في غرفة مشتركة: احتساب السعر حسب النوع الذي اختاره فعلياً
                    const sharedType = getPassengerAttr(uid, this.sharedRoomTypes) || 'double';
                    roomsTotal += this.getRoomPrice(sharedType);

                } else if (!isAccommodationOnly) {
                    // مسافر نقل بدون سكن محدد (مقعد إضافي فعلي)
                    extraSeatCount++;
                }
                // ملاحظة: مسافر "سكن فقط" (H) بدون تسكين غير محتسب هنا لأن savePassengerModal يفرض اختيار سكن إجبارياً

                // بغض النظر عن كون المسافر مُسكَّناً في غرفة أم لا، أي مقعد فعلي زائد عن مقعده الأساسي يُحتسب كمقعد إضافي مستقل.
                if (!isAccommodationOnly) {
                    const seatsForThisUid = this.passengerSeats[uid] || [];
                    if (seatsForThisUid.length > 1) {
                        extraSeatCount += (seatsForThisUid.length - 1);
                    }
                }
            });

            // خصم حجز السكن فقط والنتيجة النهائية
            let hotelDiscount = this.selectedSeats.filter(s => String(s).startsWith('H')).length * (this.accommodationOnlyDiscount || 0);
            return { roomsTotal, extraSeatCount, hotelDiscount };
        },
        get totalPrice() {
            const b = this.priceBreakdown;
            return Math.max(0, b.roomsTotal + b.extraSeatCount * this.seatPrice + this.totalMealsPrice + this.totalServicesPrice - b.hotelDiscount);
        },

        startDeadlineTimer() {
            if (!this.conditionalDeadline) return;
            if (this.deadlineTimer) clearInterval(this.deadlineTimer);
            const updateTimer = () => {
                const formattedDate = String(this.conditionalDeadline).replace(' ', 'T');
                const deadline = new Date(formattedDate).getTime();
                const now = Date.now();
                const diff = deadline - now;

                if (diff <= 0) {
                    this.timeRemaining = 'انتهت مهلة التأكيد لهذه الرحلة المشروطة.';
                    this.isExpired = true;
                    if (this.deadlineTimer) clearInterval(this.deadlineTimer);
                    return true;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                this.timeRemaining = `${hours} ساعة و ${minutes} دقيقة و ${seconds} ثانية`;
                return false;
            };

            if (!updateTimer()) {
                this.deadlineTimer = setInterval(updateTimer, 1000);
            }
        },

        // 🧹 دالة موحّدة لمسح المسودة بالكامل من الذاكرة والـ sessionStorage معاً
        clearBookingDraft() {
            sessionStorage.removeItem('booking_draft_' + config.tripId);

            this.selectedSeats = [];
            this.passengerNames = {};
            this.passengerIds = {};
            this.passengerGenders = {};
            this.seatRoomAssignments = {};
            this.passengerMeals = {};
            this.passengerServices = {};
            this.passengerSeats = {};
            this.isEmptySeat = {};
            this.addedCompanionKeys = [];
            this.companionUidLookup = {};
            this.selfAdded = false;
            this.selfUid = null;
            this.isAdditionalSeat = {};
            this.sharedRoomTypes = {};
            this.roomQuantities = { single: 0, double: 0, triple: 0, quad: 0 };

            this.roomBedTypes = {};
            this.agencyNote = '';

            this.draftExpiresAt = null;
            this.timeLeftFormatted = '';
            if (this.draftTimer) {
                clearInterval(this.draftTimer);
                this.draftTimer = null;
            }
        },

        startDraftTimer() {
            if (this.draftTimer) clearInterval(this.draftTimer);

            const updateTimer = () => {
                const now = Date.now();
                const diff = this.draftExpiresAt - now;

                if (diff <= 0) {
                    this.clearBookingDraft();
                    return true;
                }

                const totalSeconds = Math.floor(diff / 1000);
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;

                this.timeLeftFormatted = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                return false;
            };

            if (!updateTimer()) {
                this.draftTimer = setInterval(updateTimer, 1000);
            }
        },

        // ⏱️ يقرأ المسودة من التخزين ويشغّل العداد بغض النظر عن نجاح استرجاع البيانات
        resumeDraftTimer() {
            try {
                const raw = sessionStorage.getItem('booking_draft_' + config.tripId);
                if (!raw) return;

                const d = JSON.parse(raw);
                if (!d.expiresAt || Date.now() >= d.expiresAt) {
                    this.clearBookingDraft();
                    return;
                }

                this.draftExpiresAt = d.expiresAt;
                this.startDraftTimer();
            } catch (e) {
                console.error('تعذر تشغيل مؤقت المسودة:', e);
            }
        },

        init() {
            const draftKey = 'booking_draft_' + config.tripId;
            let restoredFromDraft = false;

            // =====================================================
            // 1. المسودة أولاً (أحدث تعديل للمستخدم، وتبقى بعد أي خطأ من السيرفر)
            // =====================================================
            this.roomBedTypes = { ...(config.prefilledRoomBedTypes || {}) };
            this.agencyNote = config.prefilledAgencyNote || '';
            const savedDraft = sessionStorage.getItem(draftKey);

            if (savedDraft) {
                try {
                    const draft = JSON.parse(savedDraft);
                    const now = Date.now();

                    if (draft.expiresAt && now > draft.expiresAt) {
                        // انتهت صلاحية المسودة فقط → نحذفها
                        sessionStorage.removeItem(draftKey);
                    } else {
                        this.selectedSeats = draft.selectedSeats || [];
                        this.passengerNames = draft.passengerNames || {};
                        this.passengerIds = draft.passengerIds || {};
                        this.passengerGenders = draft.passengerGenders || {};
                        this.roomQuantities = draft.roomQuantities || { single: 0, double: 0, triple: 0, quad: 0 };


                        this.syncBedTypes();

                        this.seatRoomAssignments = draft.seatRoomAssignments || {};
                        this.passengerMeals = draft.passengerMeals || {};
                        this.passengerSeats = draft.passengerSeats || {};
                        this.passengerServices = draft.passengerServices || {};
                        this.isEmptySeat = draft.isEmptySeat || {};
                        this.sharedRoomTypes = draft.sharedRoomTypes || {};
                        this.isAdditionalSeat = draft.isAdditionalSeat || {};

                        this.roomBedTypes = draft.roomBedTypes || this.roomBedTypes;
                        this.agencyNote = draft.agencyNote ?? this.agencyNote;

                        this.addedCompanionKeys = draft.addedCompanionKeys || [];
                        this.companionUidLookup = draft.companionUidLookup || {};
                        this.selfAdded = draft.selfAdded || false;
                        this.selfUid = draft.selfUid || null;
                        restoredFromDraft = true;
                        this.syncBedTypes();
                        // المقاعد المحجوزة فعلاً من السيرفر (مع استثناء مقاعد حجزه الحالي نفسه)

                        const toArr = v => Array.isArray(v) ? v : Object.values(v || {});
                        const serverBookedSeats = toArr(config.bookedSeats || config.booked_seats).map(s => parseInt(s));
                        const ownSeats = (config.prefilledSeats || []).map(s => parseInt(String(s).replace(/"/g, '')));

                        const conflictingSeats = (this.selectedSeats || []).filter(seat => {
                            const n = parseInt(seat);
                            return !isNaN(n) && n > 0 && serverBookedSeats.includes(n) && !ownSeats.includes(n);
                        });

                        // بدل حذف المسودة كاملة: نفك المقاعد المتعارضة فقط ونبقي بقية البيانات
                        if (conflictingSeats.length > 0) {
                            conflictingSeats.forEach(seat => this.unseatPassenger(seat));
                            this.triggerAlert('⚠️ بعض المقاعد أصبحت محجوزة، يرجى اختيار مقاعد بديلة. تم الاحتفاظ ببقية بياناتك.');
                            this.step = 3;
                        }

                        // ملاحظة: تشغيل العداد يتم لاحقاً عبر resumeDraftTimer() في آخر init()
                        // والمدة ثابتة 10 دقائق من لحظة الضغط على "تأكيد الحجز"
                    }
                } catch (e) {
                    console.error('خطأ في استرجاع مسودة الحجز:', e);
                }
            }

            // =====================================================
            // 2. إذا ما فيه مسودة → نستخدم بيانات الحجز السابق من السيرفر
            //    (ولا نحذف المسودة)
            // =====================================================
            if (!restoredFromDraft && config.existingBookingId && config.prefilledSeats && config.prefilledSeats.length > 0) {
                this.existingBookingId = config.existingBookingId;

                const seats = config.prefilledSeats.map(s => {
                    const cleaned = String(s).replace(/"/g, '').trim();
                    return /^\d+$/.test(cleaned) ? parseInt(cleaned, 10) : cleaned;
                });
                this.selectedSeats = seats;

                // المصدر الموثوق لعدد الغرف: بيانات الحجز نفسها
                const savedRoomQuantities = config.prefilledRoomQuantities || {};
                this.roomQuantities = {
                    single: parseInt(savedRoomQuantities.single) || 0,
                    double: parseInt(savedRoomQuantities.double) || 0,
                    triple: parseInt(savedRoomQuantities.triple) || 0,
                    quad: parseInt(savedRoomQuantities.quad) || 0,
                };

                const savedRoomAssignments = config.prefilledRoomAssignments || {};
                const prefilled = config.prefilledPassengers || {};

                // تطبيع كل مقاعد الحجز السابق + تحديد أيها "إضافي"
                const normalizedEntries = Object.keys(prefilled).map(rawSeat => {
                    const cleanedSeat = String(rawSeat).replace(/"/g, '').trim();
                    const seatNum = /^\d+$/.test(cleanedSeat) ? parseInt(cleanedSeat, 10) : cleanedSeat;
                    const passenger = prefilled[rawSeat];
                    const officialRoom =
                        savedRoomAssignments[seatNum] ||
                        savedRoomAssignments[rawSeat] ||
                        (passenger ? passenger.room_type : '') || '';
                    const isAdditional = officialRoom === 'additional' || (passenger && passenger.room_type === 'additional');
                    return { rawSeat, seatNum, passenger, officialRoom, isAdditional };
                }).filter(e => e.passenger);

                // المرحلة 1: إنشاء المسافرين الأساسيين فقط (غير الإضافيين)
                const nationalIdToUid = {};
                normalizedEntries
                    .filter(e => !e.isAdditional)
                    .forEach(({ seatNum, passenger, officialRoom }) => {
                        let uid = -Date.now() - Math.floor(Math.random() * 100000);
                        uid = String(uid);

                        const isSelf = passenger.passenger_national_id &&
                            String(passenger.passenger_national_id) === String(config.authUserNationalId);

                        if (isSelf) {
                            this.selfAdded = true;
                            this.selfUid = uid;
                        } else if (passenger.passenger_national_id) {
                            if (!this.addedCompanionKeys.includes(passenger.passenger_national_id)) {
                                this.addedCompanionKeys.push(passenger.passenger_national_id);
                                this.companionUidLookup[uid] = passenger.passenger_national_id;
                            }
                        }

                        this.passengerNames[uid] = passenger.passenger_name || '';
                        this.passengerIds[uid] = passenger.passenger_national_id || '';
                        this.passengerGenders[uid] = passenger.gender || 'male';
                        this.seatRoomAssignments[uid] = officialRoom;

                        this.passengerMeals[uid] = passenger.meal_options || {
                            breakfast: false, lunch: false, dinner: false,
                            wants_meal: false, package_name: null, package_price: 0
                        };
                        this.passengerServices[uid] = (passenger.extra_services || []).map(s => {
                            if (s !== null && typeof s === 'object') {
                                return s.id !== undefined ? s.id : (s.name !== undefined ? s.name : s);
                            }
                            return s;
                        });

                        if (!this.passengerSeats[uid]) this.passengerSeats[uid] = [];
                        if (!this.passengerSeats[uid].includes(seatNum)) {
                            this.passengerSeats[uid].push(seatNum);
                        }

                        if (passenger.passenger_national_id) {
                            nationalIdToUid[String(passenger.passenger_national_id).trim()] = uid;
                        }
                    });

                // المرحلة 2: إلحاق أي مقعد إضافي بنفس المسافر الأساسي (بدل إنشاء مسافر مكرر)
                normalizedEntries
                    .filter(e => e.isAdditional)
                    .forEach(({ seatNum, passenger, officialRoom }) => {
                        const nationalId = passenger.passenger_national_id
                            ? String(passenger.passenger_national_id).trim()
                            : '';
                        const existingUid = nationalId ? nationalIdToUid[nationalId] : null;

                        if (existingUid) {
                            if (!this.passengerSeats[existingUid]) this.passengerSeats[existingUid] = [];
                            if (!this.passengerSeats[existingUid].includes(seatNum)) {
                                this.passengerSeats[existingUid].push(seatNum);
                            }
                            if (!this.isAdditionalSeat) this.isAdditionalSeat = {};
                            this.isAdditionalSeat[seatNum] = true;
                        } else {
                            // حالة نادرة: لا يوجد مسافر أساسي مطابق → ننشئه ككيان مستقل بدل فقدان البيانات
                            let uid = -Date.now() - Math.floor(Math.random() * 100000);
                            uid = String(uid);

                            this.passengerNames[uid] = passenger.passenger_name || '';
                            this.passengerIds[uid] = passenger.passenger_national_id || '';
                            this.passengerGenders[uid] = passenger.gender || 'male';
                            this.seatRoomAssignments[uid] = officialRoom;
                            this.passengerMeals[uid] = passenger.meal_options || {
                                breakfast: false, lunch: false, dinner: false,
                                wants_meal: false, package_name: null, package_price: 0
                            };
                            this.passengerServices[uid] = (passenger.extra_services || []).map(s => {
                                if (s !== null && typeof s === 'object') {
                                    return s.id !== undefined ? s.id : (s.name !== undefined ? s.name : s);
                                }
                                return s;
                            });
                            if (!this.passengerSeats[uid]) this.passengerSeats[uid] = [];
                            this.passengerSeats[uid].push(seatNum);

                            if (!this.isAdditionalSeat) this.isAdditionalSeat = {};
                            this.isAdditionalSeat[seatNum] = true;
                        }
                    });

                // ملاحظة: لا نحذف المسودة هنا. تُحذف فقط عند انتهاء صلاحيتها أو بعد نجاح الحجز.
            }

            // =====================================================
            // 3. المراقبات والمؤقتات
            // =====================================================
            if (this.isConditional && this.conditionalDeadline) {
                this.startDeadlineTimer();
            }

            this.$watch('selectedSeats', (value) => {
                if (this.maxSeats !== null && value && value.length > this.maxSeats) {
                    this.selectedSeats = value.slice(0, this.maxSeats);
                    this.triggerAlert(`⚠️ عذراً، لا يمكنك حجز أكثر من ${this.maxSeats} مقاعد في العملية الواحدة.`);
                }
            });

            this.$watch('activeModalSeat', (value) => {
                if (value) {
                    if (!this.passengerMeals[value]) this.passengerMeals[value] = { breakfast: false, lunch: false, dinner: false, wants_meal: false, package_name: null, package_price: 0 };
                    if (!this.passengerServices[value]) this.passengerServices[value] = [];
                    if (!this.passengerNames[value]) this.passengerNames[value] = '';
                    if (!this.passengerIds[value]) this.passengerIds[value] = '';
                    if (!this.passengerGenders[value]) this.passengerGenders[value] = '';
                    if (!this.seatRoomAssignments[value]) this.seatRoomAssignments[value] = '';
                }
            });

            // =====================================================
            // 4. مؤقت المسودة (مستقل عن نجاح استرجاع البيانات)
            // =====================================================
            this.resumeDraftTimer();

            // الرجوع بزر Back من الذاكرة (bfcache)
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) this.resumeDraftTimer();
            });
        },

        proceed() {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                this.syncBedTypes();

                return;
            }

            if (this.isConditional && this.isExpired) {
                this.triggerAlert('⚠️ عذراً، انتهت المهلة المحددة لتأكيد هذه الرحلة المشروطة ولا يمكن إكمال الحجز.');
                return;
            }

            const getPassengerAttr = (seat, attrObject) => {
                if (attrObject[seat] !== undefined && attrObject[seat] !== '') {
                    return attrObject[seat];
                }
                for (let uid in this.passengerSeats) {
                    if (this.passengerSeats[uid] && this.passengerSeats[uid].map(Number).includes(Number(seat))) {
                        if (attrObject[uid] !== undefined && attrObject[uid] !== '') {
                            return attrObject[uid];
                        }
                    }
                }
                return '';
            };

            // *** إصلاح جوهري: دالة موحّدة لتحويل قيمة الغرفة إلى النص الصحيح للعرض ***
            // تُستخدم في كل مكان نحسب فيه اسم الغرفة، بدل تكرار نفس المنطق مرتين
            // بشكل غير متطابق (وهذا كان سبب اختفاء "غرفة مشتركة").
            const resolveRoomLabel = (key) => {
                const room = getPassengerAttr(key, this.seatRoomAssignments);
                if (room === 'shared') {
                    const sharedType = getPassengerAttr(key, this.sharedRoomTypes);
                    const typeName = this.getTypeName(sharedType);
                    return typeName ? `غرفة مشتركة (${typeName})` : 'غرفة مشتركة';
                }
                return room || '';
            };

            for (let seat of this.selectedSeats) {
                if (this.isEmptySeat[seat]) {
                    this.passengerNames[seat] = "مقعد فارغ";
                    this.passengerIds[seat] = "0000000000";
                    this.passengerGenders[seat] = "male";
                    continue;
                }

                let pName = String(getPassengerAttr(seat, this.passengerNames)).trim();
                let pId = String(getPassengerAttr(seat, this.passengerIds)).trim();
                let pGender = String(getPassengerAttr(seat, this.passengerGenders)).trim();

                const isAdditional = this.isAdditionalPassenger(seat);

                if (!pName) {
                    this.triggerAlert(`يرجى كتابة الاسم الكامل للمسافر على المقعد ${seat} أو تحديد خيار المقعد الفارغ.`);
                    return;
                }

                if (!isAdditional) {
                    if (!pId) {
                        this.triggerAlert(`يرجى كتابة رقم الهوية للمسافر (${pName}).`);
                        return;
                    }
                    if (pId.length !== 10) {
                        this.triggerAlert(`يجب أن يتكون رقم الهوية أو الإقامة للمسافر (${pName}) من 10 أرقام بالضبط.`);
                        return;
                    }
                    if (!pGender) {
                        this.triggerAlert(`يرجى تحديد جنس الراكب (${pName}).`);
                        return;
                    }
                }

                // *** إصلاح: استخدام getPassengerAttr بدل القراءة المباشرة ***
                // الغرفة قد تكون مخزّنة تحت uid المجموعة وليس رقم المقعد نفسه،
                // فالقراءة المباشرة this.seatRoomAssignments[seat] كانت تتجاهل هذه الحالة
                // وتُطلق تنبيه "لم يتم تسكينه" حتى لو كان المسافر متسكنًا فعليًا.
                const seatRoom = getPassengerAttr(seat, this.seatRoomAssignments);
                if (String(seat).startsWith('H') && (seatRoom === 'shared' || !seatRoom)) {
                    this.triggerAlert(`خطأ: المسافر المخصص للسكن فقط (${seat}) لم يتم تسكينه في غرفة.`);
                    return;
                }
            }

            if (!config.checkoutRoute) {
                console.error('Checkout route is missing from config!');
                this.triggerAlert('خطأ في إعدادات النظام: رابط التشيك أوت غير معرف.');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = config.checkoutRoute;

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = config.csrfToken || '';
            form.appendChild(csrfInput);

            const sorted = this.sortedSeats().filter(seat => {
                const isSynthetic = isNaN(seat) || Number(seat) < 0;
                if (isSynthetic && this.passengerSeats[seat] && this.passengerSeats[seat].length > 0) {
                    return false;
                }
                return true;
            });

            const normalizedRoomAssignments = {};

            // *** إصلاح: استخدام resolveRoomLabel بدل القيمة الخام مباشرة ***
            // سابقًا كان الكود يخزن القيمة الخام "shared" هنا مباشرة بدون تحويلها
            // إلى "غرفة مشتركة (النوع)"، وبما أن الحلقة الثانية تتخطى أي مقعد
            // له قيمة مسبقة، كانت هذه القيمة الخام تصل للسيرفر كما هي فيظهر
            // "غرفة خاصة" كقيمة افتراضية لأن السيرفر لا يتعرف على "shared".
            for (let uid in this.passengerSeats) {
                const passengerSeatList = this.passengerSeats[uid];
                if (passengerSeatList && passengerSeatList.length > 0) {
                    const primarySeat = passengerSeatList[0];
                    const label = resolveRoomLabel(uid) || resolveRoomLabel(primarySeat);
                    if (label) {
                        normalizedRoomAssignments[primarySeat] = label;
                    }
                }
            }

            sorted.forEach(seat => {
                if (!normalizedRoomAssignments[seat]) {
                    const isExtraSeat = Object.values(this.passengerSeats).some(
                        seats => seats.map(Number).includes(Number(seat)) && Number(seats[0]) !== Number(seat)
                    );
                    if (!isExtraSeat) {
                        const label = resolveRoomLabel(seat);
                        if (label) {
                            normalizedRoomAssignments[seat] = label;
                        }
                    }
                }
            });

            const normalizedMeals = {};
            const fullPassengerServices = {};

            sorted.forEach(seat => {
                let passengerUid = seat;
                let isPrimarySeat = true;

                for (let uid in this.passengerSeats) {
                    const seatsList = this.passengerSeats[uid];
                    if (seatsList && seatsList.map(Number).includes(Number(seat))) {
                        passengerUid = uid;
                        if (Number(seatsList[0]) !== Number(seat)) {
                            isPrimarySeat = false;
                        }
                        break;
                    }
                }

                if (isPrimarySeat) {
                    const meal = getPassengerAttr(seat, this.passengerMeals);
                    if (meal) {
                        normalizedMeals[seat] = meal;
                    }
                    fullPassengerServices[seat] = this.getPassengerServicesDetails(passengerUid)
                        || this.getPassengerServicesDetails(seat)
                        || [];
                } else {
                    fullPassengerServices[seat] = [];
                }
            });

            const fields = {
                trip_id: config.tripId,
                booking_id: config.existingBookingId || '',

                room_bed_types: JSON.stringify(this.roomBedTypes),
                agency_note: (this.agencyNote || '').trim(),

                seats: sorted.join(','),
                passenger_names: sorted.map(seat => String(getPassengerAttr(seat, this.passengerNames)).replace(/,/g, ' ').trim()).join(','),
                passenger_ids: sorted.map(seat => {
                    let id = String(getPassengerAttr(seat, this.passengerIds)).trim();
                    if (id !== '') return id;
                    return '99' + Math.floor(10000000 + Math.random() * 90000000);
                }).join(','),
                passenger_genders: sorted.map(seat => getPassengerAttr(seat, this.passengerGenders)).join(','),
                room_quantities: JSON.stringify(this.roomQuantities),
                room_assignments: JSON.stringify(normalizedRoomAssignments),
                passenger_meals: JSON.stringify(normalizedMeals),
                passenger_seats: JSON.stringify(this.passengerSeats),
                passenger_services: JSON.stringify(fullPassengerServices),
            };

            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            const draftData = {
                selectedSeats: this.selectedSeats,
                passengerNames: this.passengerNames,
                passengerIds: this.passengerIds,

                roomBedTypes: this.roomBedTypes,
                agencyNote: this.agencyNote,

                passengerGenders: this.passengerGenders,
                roomQuantities: this.roomQuantities,
                seatRoomAssignments: this.seatRoomAssignments,
                passengerMeals: this.passengerMeals,
                passengerServices: this.passengerServices,
                passengerSeats: this.passengerSeats,
                isEmptySeat: this.isEmptySeat,
                sharedRoomTypes: this.sharedRoomTypes,
                isAdditionalSeat: this.isAdditionalSeat,
                addedCompanionKeys: this.addedCompanionKeys,
                companionUidLookup: this.companionUidLookup,
                selfAdded: this.selfAdded,
                selfUid: this.selfUid,
                expiresAt: Date.now() + (10 * 60 * 1000)
            };

            sessionStorage.setItem('booking_draft_' + config.tripId, JSON.stringify(draftData));

            // ✅ تشغيل المؤقت فوراً
            this.draftExpiresAt = draftData.expiresAt;
            this.startDraftTimer();

            document.body.appendChild(form);
            form.submit();
        },

    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('tripBookingApp', (initialData) => bookingManager(initialData));
});