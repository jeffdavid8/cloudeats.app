(function () {
  'use strict';

  const DAYS = [
    ['monday', 'Monday'],
    ['tuesday', 'Tuesday'],
    ['wednesday', 'Wednesday'],
    ['thursday', 'Thursday'],
    ['friday', 'Friday'],
    ['saturday', 'Saturday'],
    ['sunday', 'Sunday']
  ];

  function emptySchedule() {
    const weekly = {};
    DAYS.forEach(function (day) { weekly[day[0]] = []; });
    return { weekly: weekly, holidays: {} };
  }

  function normalize(value) {
    const schedule = emptySchedule();
    if (!value || typeof value !== 'object') return schedule;
    if (value.weekly && typeof value.weekly === 'object') {
      DAYS.forEach(function (day) {
        const intervals = Array.isArray(value.weekly[day[0]]) ? value.weekly[day[0]] : [];
        schedule.weekly[day[0]] = intervals
          .filter(function (item) { return item && item.open && item.close; })
          .map(function (item) { return { open: item.open, close: item.close }; });
      });
    }
    if (value.holidays && typeof value.holidays === 'object') {
      Object.keys(value.holidays).forEach(function (date) {
        const intervals = Array.isArray(value.holidays[date]) ? value.holidays[date] : [];
        schedule.holidays[date] = intervals
          .filter(function (item) { return item && item.open && item.close; })
          .map(function (item) { return { open: item.open, close: item.close }; });
      });
    }
    return schedule;
  }

  function button(label, className, handler) {
    const element = document.createElement('button');
    element.type = 'button';
    element.className = className || 'store-hours-button';
    element.textContent = label;
    element.addEventListener('click', handler);
    return element;
  }

  function intervalRow(interval, remove) {
    const row = document.createElement('div');
    row.className = 'store-hours-interval';
    const open = document.createElement('input');
    open.type = 'time';
    open.value = interval.open || '09:00';
    open.setAttribute('aria-label', 'Opening time');
    const dash = document.createElement('span');
    dash.textContent = 'to';
    const close = document.createElement('input');
    close.type = 'time';
    close.value = interval.close || '17:00';
    close.setAttribute('aria-label', 'Closing time');
    row.append(open, dash, close, button('Remove', 'store-hours-remove', function () {
      row.remove();
      remove();
    }));
    [open, close].forEach(function (input) {
      input.addEventListener('change', function () {
        interval.open = open.value;
        interval.close = close.value;
        row.dispatchEvent(new CustomEvent('hourschange', { bubbles: true }));
      });
    });
    return row;
  }

  function init(container) {
    const textarea = document.getElementById(container.dataset.textarea);
    if (!textarea) return;
    let schedule;
    try { schedule = normalize(JSON.parse(textarea.value)); } catch (error) { schedule = emptySchedule(); }
    container.innerHTML = '';
    const weeklyPanel = document.createElement('div');
    weeklyPanel.className = 'store-hours-weekly';
    const holidayPanel = document.createElement('div');
    holidayPanel.className = 'store-hours-holidays';
    const advanced = document.createElement('details');
    advanced.className = 'store-hours-advanced';
    const summary = document.createElement('summary');
    summary.textContent = 'Advanced JSON editor';
    advanced.append(summary, textarea);
    textarea.style.minHeight = '180px';
    textarea.hidden = true;
    advanced.addEventListener('toggle', function () {
      textarea.hidden = !advanced.open;
    });
    container.append(weeklyPanel, holidayPanel, advanced);

    function sync() {
      textarea.value = JSON.stringify(schedule, null, 2);
      textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function renderDay(day) {
      const card = document.createElement('div');
      card.className = 'store-hours-day';
      const heading = document.createElement('strong');
      heading.textContent = day[1];
      card.appendChild(heading);
      const intervals = schedule.weekly[day[0]];
      const rows = document.createElement('div');
      intervals.forEach(function (interval, index) {
        rows.appendChild(intervalRow(interval, function () {
          intervals.splice(index, 1);
          sync();
        }));
      });
      card.appendChild(rows);
      card.appendChild(button(intervals.length ? 'Add another shift' : 'Add hours', 'store-hours-add', function () {
        intervals.push({ open: '09:00', close: '17:00' });
        render();
        sync();
      }));
      if (!intervals.length) {
        const closed = document.createElement('span');
        closed.className = 'store-hours-closed';
        closed.textContent = 'Closed';
        card.insertBefore(closed, rows);
      }
      return card;
    }

    function render() {
      weeklyPanel.innerHTML = '<h4>Weekly hours</h4><p class="store-hours-help">Add one or more shifts per day. For a closed day, leave it empty.</p>';
      DAYS.forEach(function (day) { weeklyPanel.appendChild(renderDay(day)); });
      renderHolidays();
    }

    function renderHolidays() {
      holidayPanel.innerHTML = '<h4>Holiday and date overrides</h4><p class="store-hours-help">A date override replaces that day’s weekly hours. Leave it empty to mark the date closed.</p>';
      Object.keys(schedule.holidays).sort().forEach(function (date) {
        const row = document.createElement('div');
        row.className = 'store-hours-holiday';
        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        dateInput.value = date;
        dateInput.addEventListener('change', function () {
          const intervals = schedule.holidays[date];
          delete schedule.holidays[date];
          schedule.holidays[dateInput.value] = intervals;
          render();
          sync();
        });
        const label = document.createElement('span');
        label.textContent = schedule.holidays[date].length ? 'Open with special hours' : 'Closed';
        row.append(dateInput, label);
        schedule.holidays[date].forEach(function (interval, index) {
          row.appendChild(intervalRow(interval, function () {
            schedule.holidays[date].splice(index, 1);
            render();
            sync();
          }));
        });
        row.appendChild(button('Add shift', 'store-hours-add', function () {
          schedule.holidays[date].push({ open: '09:00', close: '17:00' });
          render();
          sync();
        }));
        row.appendChild(button('Remove date', 'store-hours-remove', function () {
          delete schedule.holidays[date];
          render();
          sync();
        }));
        holidayPanel.appendChild(row);
      });
      holidayPanel.appendChild(button('Add holiday or special date', 'store-hours-add', function () {
        const date = new Date().toISOString().slice(0, 10);
        let candidate = date;
        while (Object.prototype.hasOwnProperty.call(schedule.holidays, candidate)) {
          const next = new Date(candidate + 'T00:00:00');
          next.setDate(next.getDate() + 1);
          candidate = next.toISOString().slice(0, 10);
        }
        schedule.holidays[candidate] = [];
        render();
        sync();
      }));
    }

    textarea.addEventListener('change', function () {
      try {
        schedule = normalize(JSON.parse(textarea.value));
        textarea.setCustomValidity('');
        render();
      } catch (error) {
        textarea.setCustomValidity('Store hours must be valid JSON.');
      }
    });
    container.addEventListener('hourschange', sync);
    render();
    sync();
  }

  const style = document.createElement('style');
  style.textContent = [
    '.store-hours-editor{border:1px solid #e5e7eb;border-radius:8px;padding:1rem;}',
    '.store-hours-editor h4{margin:.25rem 0 .35rem;font-size:1rem}',
    '.store-hours-help{margin:.25rem 0 .75rem;color:#6b7280;font-size:.85rem}',
    '.store-hours-day{display:grid;grid-template-columns:110px 1fr auto;gap:.6rem;align-items:center;padding:.55rem 0;border-bottom:1px solid #e5e7eb}',
    '.store-hours-interval{display:flex;align-items:center;gap:.45rem;margin:.25rem 0}',
    '.store-hours-interval input{width:125px;margin:0!important}',
    '.store-hours-button{border:1px solid #c7d2fe;border-radius:4px;background:white;color:#4338ca;padding:.35rem .55rem;cursor:pointer;font-size:.8rem}',
    '.store-hours-remove{border-color:#fecaca;color:#b91c1c}',
    '.store-hours-closed{color:#9ca3af;font-size:.85rem}',
    '.store-hours-holidays{margin-top:1.25rem}',
    '.store-hours-holiday{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;padding:.65rem 0;border-bottom:1px solid #e5e7eb}',
    '.store-hours-holiday input[type=date]{width:160px;margin:0!important}',
    '.store-hours-advanced{margin-top:1rem}.store-hours-advanced summary{cursor:pointer;color:#4f46e5;font-size:.85rem}',
    '.store-hours-advanced textarea{width:100%;font-family:monospace;margin-top:.75rem}'
  ].join('');
  document.head.appendChild(style);

  window.StoreHoursEditor = { init: init };
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-store-hours-editor]').forEach(init);
  });
}());
