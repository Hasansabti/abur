function bookingManager(config) {
    return {
        openPassengerModal: false,
        activeModalSeat: null,

        showAlertModal: false,
        alertMessage: '',

        selectedSeats: [],
        passengerNames: {},
        passengerIds: {},
        passengerGenders: {},
        seatRoomAssignments: {},
        passengerMeals: {},
        isEmptySeat: {}, // تتبع المقاعد الإضافية الفارغة بدون مسافر

        roomQuantities: {
            single: 0,
            double: 0,
            triple: 0,
            quad: 0,
        },

        triggerAlert(message) {
            this.alertMessage = message;
            this.showAlertModal = true;
        },

        // فرز ذكي لوضع المقاعد أولاً ثم سكن فقط H1, H2.. بالترتيب التصاعدي
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

        // إضافة مسافر "سكن فقط" (بدون حجز مقعد حافلة)
        addAccommodationOnlyPassenger() {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            let i = 1;
            while (this.selectedSeats.includes(`H${i}`)) {
                i++;
            }
            const id = `H${i}`;
            this.selectedSeats.push(id);

            this.passengerMeals[id] = {
                breakfast: false,
                lunch: false,
                dinner: false,
                wants_meal: false
            };
            this.passengerNames[id] = '';
            this.passengerIds[id] = '';
            this.passengerGenders[id] = '';
            this.seatRoomAssignments[id] = '';

            this.activeModalSeat = id;
            this.activeModalSeatZone = 'free';
            this.openPassengerModal = true;
            this.syncRoomAssignments();
        },

        // التحقق من إدراج المقعد النشط ضمن السعة الشاملة للغرف الخاصة بناءً على ترتيب الاختيار
        checkIncluded() {
            if (!this.activeModalSeat) return false;
            let totalCapacity = ((this.roomQuantities.single || 0) * 1) +
                ((this.roomQuantities.double || 0) * 2) +
                ((this.roomQuantities.triple || 0) * 3) +
                ((this.roomQuantities.quad || 0) * 4);

            return this.sortedSeats().indexOf(this.activeModalSeat) < totalCapacity;
        },

        roomOptions: (() => {
            let options = config.roomOptionsRaw;
            if (typeof options === 'string') {
                try {
                    options = JSON.parse(options);
                } catch (e) {
                    options = [];
                }
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
                try {
                    meals = JSON.parse(meals);
                } catch (e) {
                    meals = {};
                }
            }
            return {
                breakfast: meals?.individual?.breakfast?.active ? (parseFloat(meals.individual.breakfast.price) || 0) : 0,
                lunch: meals?.individual?.lunch?.active ? (parseFloat(meals.individual.lunch.price) || 0) : 0,
                dinner: meals?.individual?.dinner?.active ? (parseFloat(meals.individual.dinner.price) || 0) : 0,
                breakfast_active: !!meals?.individual?.breakfast?.active,
                lunch_active: !!meals?.individual?.lunch?.active,
                dinner_active: !!meals?.individual?.dinner?.active,
            };
        })(),

        mealPackages: (() => {
            let meals = config.mealOptionsRaw;
            if (typeof meals === 'string') {
                try {
                    meals = JSON.parse(meals);
                } catch (e) {
                    meals = {};
                }
            }
            return {
                bf_ln: meals?.packages?.bf_ln !== undefined && meals?.packages?.bf_ln !== null ? parseFloat(meals.packages.bf_ln) : null,
                ln_dn: meals?.packages?.ln_dn !== undefined && meals?.packages?.ln_dn !== null ? parseFloat(meals.packages.ln_dn) : null,
                bf_dn: meals?.packages?.bf_dn !== undefined && meals?.packages?.bf_dn !== null ? parseFloat(meals.packages.bf_dn) : null,
                all: meals?.packages?.all !== undefined && meals?.packages?.all !== null ? parseFloat(meals.packages.all) : null,
            };
        })(),

        getMealPrice(seatId, mealType) {
            if (!seatId) return 0;
            let assignment = this.seatRoomAssignments[seatId] || 'shared';

            if (assignment === 'shared' || assignment === 'additional') {
                return this.baseMealPrices[mealType] || 0;
            }

            let roomType = assignment.split('_')[0];
            if (this.roomOptions[roomType]?.meals && this.roomOptions[roomType].meals[mealType] !== undefined && this.roomOptions[roomType].meals[mealType] !== 0) {
                return parseFloat(this.roomOptions[roomType].meals[mealType]) || 0;
            }
            return this.baseMealPrices[mealType] || 0;
        },

        getActiveSeatPackages() {
            if (!this.activeModalSeat) return [];
            let assignment = this.seatRoomAssignments[this.activeModalSeat] || 'shared';
            if (assignment === 'shared' || assignment === 'additional') return [];
            let roomType = assignment.split('_')[0];
            return this.roomOptions[roomType]?.packages || [];
        },

        get roomPrices() {
            return config.roomPrices;
        },

        seatPrice: config.seatPrice,
        isLoggedIn: config.isLoggedIn,
        showLoginModal: false,

        toggleSeat(id) {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            if (this.selectedSeats.includes(id)) {
                this.selectedSeats = this.selectedSeats.filter(s => s !== id);
                delete this.passengerNames[id];
                delete this.passengerIds[id];
                delete this.passengerGenders[id];
                delete this.seatRoomAssignments[id];
                delete this.passengerMeals[id];
                delete this.isEmptySeat[id];

                if (this.activeModalSeat === id) {
                    this.openPassengerModal = false;
                    this.activeModalSeat = null;
                }
            } else {
                this.selectedSeats.push(id);

                this.passengerMeals[id] = {
                    breakfast: false,
                    lunch: false,
                    dinner: false,
                    wants_meal: false
                };

                if (this.selectedSeats.length === 1) {
                    this.passengerNames[id] = config.authUserName;
                    this.passengerIds[id] = config.authUserNationalId;
                    this.passengerGenders[id] = config.authUserGender;
                }

                this.activeModalSeat = id;
                this.openPassengerModal = true;
            }
            this.syncRoomAssignments();
        },

        toggleMeal(seatId, mealType) {
            if (this.passengerMeals[seatId]) {
                this.passengerMeals[seatId][mealType] = !this.passengerMeals[seatId][mealType];
                this.passengerMeals[seatId].wants_meal =
                    this.passengerMeals[seatId].breakfast ||
                    this.passengerMeals[seatId].lunch ||
                    this.passengerMeals[seatId].dinner ||
                    !!this.passengerMeals[seatId].package_name;
            }
        },

        increaseRoom(type) {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
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
                        name: `غرفة ${this.getTypeName(type)} #${i}`
                    });
                }
            }
            return list;
        },

        syncRoomAssignments() {
            const roomCapacities = { single: 1, double: 2, triple: 3, quad: 4 };
            let rooms = this.bookedRoomsList;
            let seatIndex = 0;
            let updatedAssignments = {};

            this.selectedSeats.forEach(seat => {
                updatedAssignments[seat] = 'shared';
            });

            for (let room of rooms) {
                let roomType = room.uid.split('_')[0];
                let capacity = roomCapacities[roomType] || 0;

                for (let i = 0; i < capacity; i++) {
                    if (seatIndex < this.selectedSeats.length) {
                        let seatId = this.selectedSeats[seatIndex];
                        updatedAssignments[seatId] = room.uid;
                        seatIndex++;
                    }
                }
            }

            this.seatRoomAssignments = updatedAssignments;
        },

        isSelected(id) {
            return this.selectedSeats.includes(id);
        },

        getTypeName(type) {
            return { single: 'فردية', double: 'ثنائية', triple: 'ثلاثية', quad: 'رباعية' }[type];
        },

        totalSavings() {
            let savings = 0;
            this.selectedSeats.forEach(seat => {
                const meals = this.passengerMeals[seat];
                if (!meals || !meals.wants_meal) return;

                const hasBf = !!meals.breakfast;
                const hasLn = !!meals.lunch;
                const hasDn = !!meals.dinner;

                let breakfastPrice = this.getMealPrice(seat, 'breakfast');
                let lunchPrice = this.getMealPrice(seat, 'lunch');
                let dinnerPrice = this.getMealPrice(seat, 'dinner');

                let regularPrice = (hasBf ? breakfastPrice : 0) + (hasLn ? lunchPrice : 0) + (hasDn ? dinnerPrice : 0);
                let actualPrice = regularPrice;

                if (meals.package_name) {
                    actualPrice = parseFloat(meals.package_price) || 0;
                } else {
                    if (hasBf && hasLn && hasDn && this.mealPackages.all !== null) {
                        actualPrice = this.mealPackages.all;
                    } else if (hasBf && hasLn && this.mealPackages.bf_ln !== null) {
                        actualPrice = this.mealPackages.bf_ln;
                    } else if (hasLn && hasDn && this.mealPackages.ln_dn !== null) {
                        actualPrice = this.mealPackages.ln_dn;
                    } else if (hasBf && hasDn && this.mealPackages.bf_dn !== null) {
                        actualPrice = this.mealPackages.bf_dn;
                    }
                }
                savings += Math.max(0, regularPrice - actualPrice);
            });
            return savings;
        },

        get totalPrice() {
            let totalRoomCost = 0;
            let allowedFreeSeats = 0;
            let totalMealsCost = 0;
            const roomCapacities = { single: 1, double: 2, triple: 3, quad: 4 };

            for (let type in this.roomQuantities) {
                let qty = this.roomQuantities[type] || 0;
                let price = parseFloat(this.roomPrices[type]) || 0;
                totalRoomCost += qty * price;
                if (roomCapacities[type]) allowedFreeSeats += qty * roomCapacities[type];
            }

            const numericSeatsSelected = this.selectedSeats.filter(seat => !String(seat).startsWith('H'));
            const extraSeatsCount = Math.max(0, numericSeatsSelected.length - allowedFreeSeats);
            const totalSeatsCost = extraSeatsCount * this.seatPrice;

            this.selectedSeats.forEach(seat => {
                const meals = this.passengerMeals[seat];
                if (!meals || !meals.wants_meal) return;

                const hasBf = !!meals.breakfast;
                const hasLn = !!meals.lunch;
                const hasDn = !!meals.dinner;

                let bfP = this.getMealPrice(seat, 'breakfast');
                let lnP = this.getMealPrice(seat, 'lunch');
                let dnP = this.getMealPrice(seat, 'dinner');

                if (meals.package_name) {
                    totalMealsCost += parseFloat(meals.package_price) || 0;
                } else {
                    if (hasBf && hasLn && hasDn && this.mealPackages.all !== null) {
                        totalMealsCost += this.mealPackages.all;
                    } else if (hasBf && hasLn && this.mealPackages.bf_ln !== null) {
                        totalMealsCost += this.mealPackages.bf_ln;
                    } else if (hasLn && hasDn && this.mealPackages.ln_dn !== null) {
                        totalMealsCost += this.mealPackages.ln_dn;
                    } else if (hasBf && hasDn && this.mealPackages.bf_dn !== null) {
                        totalMealsCost += this.mealPackages.bf_dn;
                    } else {
                        totalMealsCost += (hasBf ? bfP : 0) + (hasLn ? lnP : 0) + (hasDn ? dnP : 0);
                    }
                }
            });

            return totalRoomCost + totalSeatsCost + totalMealsCost;
        },

        init() {
            this.$watch('activeModalSeat', (value) => {
                if (value) {
                    if (!this.passengerMeals[value]) this.passengerMeals[value] = { breakfast: false, lunch: false, dinner: false, wants_meal: false };
                    if (!this.passengerNames[value]) this.passengerNames[value] = '';
                    if (!this.passengerIds[value]) this.passengerIds[value] = '';
                    if (!this.passengerGenders[value]) this.passengerGenders[value] = '';
                    if (!this.seatRoomAssignments[value]) this.seatRoomAssignments[value] = '';
                }
            });
        },

        proceed() {
            if (!this.isLoggedIn) {
                this.showLoginModal = true;
                return;
            }

            for (let seat of this.selectedSeats) {
                if (this.isEmptySeat[seat]) continue;

                if (!this.passengerNames[seat] || this.passengerNames[seat].trim() === '') {
                    this.triggerAlert(`يرجى كتابة الاسم الكامل للمسافر أو تحديد خيار المقعد الفارغ.`);
                    return;
                }
                if (!this.passengerIds[seat] || this.passengerIds[seat].trim() === '') {
                    this.triggerAlert(`يرجى كتابة رقم الهوية للمسافر.`);
                    return;
                }
                if (!this.passengerGenders[seat] || this.passengerGenders[seat] === '') {
                    this.triggerAlert(`يرجى تحديد جنس الراكب.`);
                    return;
                }
            }

            const roomCapacities = { single: 1, double: 2, triple: 3, quad: 4 };

            for (let room of this.bookedRoomsList) {
                let roomType = room.uid.split('_')[0];
                let requiredCapacity = roomCapacities[roomType] || 0;
                let assignedSeatsCount = this.selectedSeats.filter(seat => this.seatRoomAssignments[seat] === room.uid).length;

                if (assignedSeatsCount < requiredCapacity) {
                    this.triggerAlert(`تنبيه: (${room.name}) غير ممتلئة بالكامل! سعة هذه الغرفة تتطلب وجود ${requiredCapacity} ركاب، بينما تم تعيين ${assignedSeatsCount} فقط من المسافرين المحددين.`);
                    return;
                }
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = config.checkoutRoute;

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = config.csrfToken;
            form.appendChild(csrfInput);

            const sorted = this.sortedSeats();
            const fields = {
                trip_id: config.tripId,
                seats: sorted.join(','),
                passenger_names: sorted.map(seat => (this.passengerNames[seat] || '').trim()).join(','),
                passenger_ids: sorted.map(seat => (this.passengerIds[seat] || '').trim()).join(','),
                passenger_genders: sorted.map(seat => this.passengerGenders[seat] || '').join(','),
                room_quantities: JSON.stringify(this.roomQuantities),
                room_assignments: JSON.stringify(this.seatRoomAssignments),
                passenger_meals: JSON.stringify(this.passengerMeals),
            };

            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }
    }
}