document.addEventListener('DOMContentLoaded', function () {
    var now = new Date();
    var currentYear = now.getFullYear();
    var currentMonth = now.getMonth();
    var selectedDate = null;

    var monthNames = [
        'Януари', 'Февруари', 'Март', 'Април', 'Май', 'Юни',
        'Юли', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'
    ];

    var grid = document.getElementById('calGrid');
    var title = document.getElementById('calTitle');
    var prevBtn = document.getElementById('calPrev');
    var nextBtn = document.getElementById('calNext');
    var formWrapper = document.getElementById('bookingFormWrapper');
    var dateDisplay = document.getElementById('selectedDateDisplay');
    var dateInput = document.getElementById('eventDateInput');
    var timeSlotsWrapper = document.getElementById('timeSlotsWrapper');
    var startTimeInput = document.getElementById('startTimeInput');
    var endTimeInput = document.getElementById('endTimeInput');
    var timeDisplay = document.getElementById('selectedTimeDisplay');

    prevBtn.addEventListener('click', function () {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        loadMonth();
    });

    nextBtn.addEventListener('click', function () {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        loadMonth();
    });

    function loadMonth() {
        title.textContent = monthNames[currentMonth] + ' ' + currentYear;
        var isCurrentMonth = (currentYear === now.getFullYear() && currentMonth === now.getMonth());
        var isPast = (currentYear < now.getFullYear()) || (currentYear === now.getFullYear() && currentMonth < now.getMonth());
        prevBtn.disabled = isCurrentMonth || isPast;

        fetch('/api/booking-availability?year=' + currentYear + '&month=' + (currentMonth + 1))
            .then(function (r) { return r.json(); })
            .then(function (data) { renderCalendar(data); })
            .catch(function () { renderCalendar({}); });
    }

    function renderCalendar(data) {
        grid.innerHTML = '';
        var firstDay = new Date(currentYear, currentMonth, 1);
        var lastDay = new Date(currentYear, currentMonth + 1, 0);
        var startDow = (firstDay.getDay() + 6) % 7;

        for (var i = 0; i < startDow; i++) {
            var empty = document.createElement('div');
            empty.className = 'cal-day empty';
            grid.appendChild(empty);
        }

        for (var d = 1; d <= lastDay.getDate(); d++) {
            var dateStr = currentYear + '-' + pad(currentMonth + 1) + '-' + pad(d);
            var status = data[dateStr] || 'available';

            var cell = document.createElement('div');
            cell.className = 'cal-day ' + status;
            cell.textContent = d;
            cell.dataset.date = dateStr;

            if (selectedDate === dateStr) {
                cell.classList.add('selected');
            }

            if (status === 'available' || status === 'partial') {
                cell.addEventListener('click', onDayClick);
            }

            grid.appendChild(cell);
        }
    }

    function onDayClick(e) {
        var dateStr = e.currentTarget.dataset.date;

        var prev = grid.querySelector('.selected');
        if (prev) prev.classList.remove('selected');
        e.currentTarget.classList.add('selected');
        selectedDate = dateStr;

        var parts = dateStr.split('-');
        dateDisplay.textContent = parts[2] + '.' + parts[1] + '.' + parts[0];
        dateInput.value = dateStr;

        // Reset time selection
        startTimeInput.value = '';
        endTimeInput.value = '';
        timeDisplay.style.display = 'none';

        // Fetch available hours for this date
        loadAvailableHours(dateStr);

        formWrapper.style.display = 'block';
        formWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function loadAvailableHours(dateStr) {
        timeSlotsWrapper.innerHTML = '<p class="text-muted text-center">Зареждане на часове...</p>';

        fetch('/api/booking-hours?date=' + dateStr)
            .then(function (r) { return r.json(); })
            .then(function (hours) {
                renderTimeSlots(hours);
            })
            .catch(function () {
                timeSlotsWrapper.innerHTML = '<p class="text-danger text-center">Грешка при зареждане.</p>';
            });
    }

    var selectedStartHour = null;
    var selectedEndHour = null;
    var availableHoursData = [];

    function renderTimeSlots(hours) {
        availableHoursData = hours;
        selectedStartHour = null;
        selectedEndHour = null;
        timeSlotsWrapper.innerHTML = '';

        if (hours.length === 0) {
            timeSlotsWrapper.innerHTML = '<p class="text-muted text-center">Няма свободни часове за тази дата.</p>';
            return;
        }

        var instruction = document.createElement('p');
        instruction.className = 'time-instruction text-muted text-center mb-3';
        instruction.textContent = 'Изберете начален час:';
        timeSlotsWrapper.appendChild(instruction);

        var slotsDiv = document.createElement('div');
        slotsDiv.className = 'time-slots-grid';
        slotsDiv.id = 'timeSlotsGrid';

        for (var i = 0; i < hours.length; i++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'time-slot-btn';
            btn.textContent = hours[i];
            btn.dataset.hour = hours[i];
            btn.addEventListener('click', onTimeSlotClick);
            slotsDiv.appendChild(btn);
        }

        timeSlotsWrapper.appendChild(slotsDiv);
    }

    function onTimeSlotClick(e) {
        var hour = e.currentTarget.dataset.hour;

        if (!selectedStartHour) {
            // Selecting start time
            selectedStartHour = hour;
            startTimeInput.value = hour;

            // Highlight and update instruction
            var instruction = timeSlotsWrapper.querySelector('.time-instruction');
            instruction.textContent = 'Изберете краен час:';

            // Re-render slots showing valid end times
            var slotsGrid = document.getElementById('timeSlotsGrid');
            var buttons = slotsGrid.querySelectorAll('.time-slot-btn');
            var startH = parseInt(hour.substring(0, 2));

            for (var i = 0; i < buttons.length; i++) {
                var btnH = parseInt(buttons[i].dataset.hour.substring(0, 2));
                buttons[i].classList.remove('selected', 'in-range', 'disabled');

                if (btnH === startH) {
                    buttons[i].classList.add('selected');
                } else if (btnH < startH) {
                    buttons[i].classList.add('disabled');
                    buttons[i].disabled = true;
                } else {
                    // Check if hours are consecutive from start
                    var consecutive = true;
                    for (var h = startH; h <= btnH; h++) {
                        var hStr = pad(h) + ':00';
                        if (availableHoursData.indexOf(hStr) === -1) {
                            consecutive = false;
                            break;
                        }
                    }
                    if (!consecutive) {
                        buttons[i].classList.add('disabled');
                        buttons[i].disabled = true;
                    }
                }
            }
        } else {
            // Selecting end time
            var endH = parseInt(hour.substring(0, 2)) + 1; // end time is exclusive (e.g. selecting 14:00 means until 15:00)
            selectedEndHour = pad(endH) + ':00';
            endTimeInput.value = selectedEndHour;

            // Show selection summary
            timeDisplay.style.display = 'block';
            timeDisplay.textContent = selectedStartHour + ' – ' + selectedEndHour;

            // Highlight range
            var slotsGrid = document.getElementById('timeSlotsGrid');
            var buttons = slotsGrid.querySelectorAll('.time-slot-btn');
            var startH = parseInt(selectedStartHour.substring(0, 2));

            for (var i = 0; i < buttons.length; i++) {
                var btnH = parseInt(buttons[i].dataset.hour.substring(0, 2));
                buttons[i].classList.remove('in-range');
                if (btnH >= startH && btnH <= endH - 1) {
                    buttons[i].classList.add('in-range');
                }
            }
        }
    }

    // Allow resetting time selection by clicking the time display
    if (timeDisplay) {
        timeDisplay.addEventListener('click', function () {
            if (selectedDate) {
                loadAvailableHours(selectedDate);
            }
        });
    }

    function pad(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    loadMonth();
});
