{{--
    Auto-fills SST, SST(CF), M&A Fee and Total from the price, cleaning fee,
    dates and source. Mirrors App\Support\EzeePricing so what the form previews
    is what gets stored.

    Expects:
      $formId   — id of the <form> to wire up
      $bookedOn — Y-m-d the booking was made; several rates changed over time,
                  so editing an old booking must use its original date
--}}
<script>
(function () {
    var RATES = {
        DEFAULT: 0.20, BOOKING_1: 0.18, BOOKING_2: 0.028,
        AIRBNB: 0.159, AIRBNB_SEP: 0.15, TRAVELOKA: 0.17,
        WALK_IN: 0.12, WALK_IN8: 0.08, EXPEDIA: 0.15, CTRIP: 0.15
    };

    // ISO dates compare correctly as strings, so no Date parsing needed.
    var CHECK_DATE = '2022-11-30', CHECK_DATE_15 = '2023-02-01',
        CHECK_DATE_NEW = '2023-06-17', CHECK_DATE_NEW8 = '2023-07-01',
        SEP_DATE = '2024-09-01', SST_DATE = '2024-03-01';

    var BOOKED_ON = @json($bookedOn);

    var form = document.getElementById(@json($formId));
    if (!form) return;

    function field(n) { return form.querySelector('[name="' + n + '"]'); }

    var elCheckIn  = field('check_in'),
        elCheckOut = field('check_out'),
        elNight    = field('price_night'),
        elClean    = field('cleaning_fee'),
        elDiscount = field('discount_fee'),
        elSource   = field('source'),
        elSst      = field('sst'),
        elSstCf    = field('sst_cf'),
        elOta      = field('ota_fee'),
        elTotal    = field('price');

    function floor2(v) { return Math.floor(v * 100) / 100; }
    function num(el)   { var v = parseFloat(el && el.value); return isFinite(v) ? v : 0; }

    function nights() {
        if (!elCheckIn || !elCheckOut || !elCheckIn.value || !elCheckOut.value) return 0;
        var a = new Date(elCheckIn.value + 'T00:00:00'),
            b = new Date(elCheckOut.value + 'T00:00:00');
        if (isNaN(a) || isNaN(b) || b <= a) return 0;
        return Math.round((b - a) / 86400000);
    }

    // EZEE appends references to some source names ("Booking.com-13707539").
    function normalise(s) {
        return String(s || '').replace(/[^A-Za-z. ]/g, '').trim();
    }

    function otaFee(source, base, baseTaxed) {
        var afterCheck = BOOKED_ON > CHECK_DATE,
            afterNew   = BOOKED_ON > CHECK_DATE_NEW,
            beforeNew  = BOOKED_ON < CHECK_DATE_NEW;

        if (['Walk-in', 'Walk In', 'PMS', 'Website'].indexOf(source) !== -1) {
            if (BOOKED_ON >= CHECK_DATE_NEW8) return floor2(RATES.WALK_IN8 * base);
            if (BOOKED_ON > CHECK_DATE_15)    return floor2(RATES.WALK_IN * base);
            if (afterCheck)                   return floor2(RATES.DEFAULT * base);
            return floor2(0.20 * base);
        }

        if (source === 'Airbnb') {
            if (BOOKED_ON >= SEP_DATE)     return floor2(RATES.AIRBNB_SEP * baseTaxed);
            if (afterCheck && beforeNew)   return floor2(RATES.DEFAULT * base);
            return floor2(RATES.AIRBNB * base);
        }

        if (['Booking.com', 'Booking'].indexOf(source) !== -1) {
            if (afterCheck && beforeNew) return floor2(RATES.DEFAULT * base);
            if (afterNew) {
                return floor2(floor2(RATES.BOOKING_2 * baseTaxed)
                            + floor2(RATES.BOOKING_1 * base));
            }
            return floor2(0.205 * base);
        }

        if (source === 'Traveloka') {
            if (afterCheck && beforeNew) return floor2(RATES.DEFAULT * base);
            if (afterNew)                return floor2(RATES.TRAVELOKA * baseTaxed);
            return floor2(0.18 * base);
        }

        if (['Trip.com', 'CTrip.com', 'Ctrip.com', 'CTrip', 'Ctrip'].indexOf(source) !== -1) {
            if (afterCheck && beforeNew) return floor2(RATES.DEFAULT * base);
            if (afterNew)                return 0;
            return floor2(RATES.CTRIP * base);
        }

        if (source === 'Expedia') {
            if (afterNew)   return floor2(RATES.DEFAULT * baseTaxed);
            if (afterCheck) return floor2(RATES.DEFAULT * base);
            return floor2(RATES.EXPEDIA * base);
        }

        // Sources that never carry a marketing & administration fee.
        if (['Agoda', 'Long Term Rental', 'Tiket.com', 'owner', 'Owner'].indexOf(source) !== -1) {
            return 0;
        }

        if (afterCheck && beforeNew) return floor2(RATES.DEFAULT * base);
        if (afterNew)                return floor2(RATES.DEFAULT * baseTaxed);
        return floor2(0.1 * base);
    }

    // A field the user has typed into is left alone from then on, so a manual
    // override is never silently recalculated away.
    var overridden = {};
    [elSst, elSstCf, elOta, elTotal].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', function () { overridden[el.name] = true; });
    });

    function set(el, value) {
        if (!el || overridden[el.name]) return;
        el.value = value.toFixed(2);
    }

    function recalc() {
        var n         = nights(),
            roomTotal = num(elNight) * n,
            cleaning  = num(elClean),
            discount  = num(elDiscount),
            sstRate   = BOOKED_ON < SST_DATE ? 0.06 : 0.08;

        var sst   = floor2(roomTotal * sstRate),
            sstCf = floor2(cleaning * sstRate);

        var base      = roomTotal + cleaning,
            baseTaxed = base + sst + sstCf;

        set(elSst, sst);
        set(elSstCf, sstCf);
        set(elOta, otaFee(normalise(elSource && elSource.value), base, baseTaxed));
        set(elTotal, roomTotal + cleaning + sst + sstCf - discount);
    }

    // Only recalculate in response to a real edit — opening an existing booking
    // must not rewrite its stored figures.
    [elCheckIn, elCheckOut, elNight, elClean, elDiscount, elSource].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', recalc);
        el.addEventListener('change', recalc);
    });

    form.addEventListener('reset', function () {
        overridden = {};
        setTimeout(recalc, 0);
    });
})();
</script>
