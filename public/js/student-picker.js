(function () {
    function initPicker(form) {
        var modal = form.closest('.modal');
        if (!modal) return;
        var search = modal.querySelector('[data-student-picker-search]');
        var rows = Array.prototype.slice.call(modal.querySelectorAll('[data-student-picker-row]'));
        var checks = Array.prototype.slice.call(modal.querySelectorAll('[data-student-picker-check]'));
        var checkAll = modal.querySelector('[data-student-picker-check-all]');
        var selectVisible = modal.querySelector('[data-student-picker-select-visible]');
        var clearBtn = modal.querySelector('[data-student-picker-clear]');
        var countBadge = modal.querySelector('[data-student-picker-count]');
        var submitBtn = modal.querySelector('[data-student-picker-submit]');

        function visibleRows() {
            return rows.filter(function (row) { return row.style.display !== 'none'; });
        }

        function checkedCount() {
            return checks.filter(function (c) { return c.checked; }).length;
        }

        function refresh() {
            var n = checkedCount();
            if (countBadge) countBadge.textContent = 'Da chon: ' + n;
            if (submitBtn) {
                submitBtn.disabled = n === 0;
                submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Them ' + n + ' sinh vien';
            }
            if (checkAll) {
                var vis = visibleRows().map(function (row) {
                    return row.querySelector('[data-student-picker-check]');
                }).filter(Boolean);
                var checkedVis = vis.filter(function (c) { return c.checked; }).length;
                checkAll.checked = vis.length > 0 && checkedVis === vis.length;
                checkAll.indeterminate = checkedVis > 0 && checkedVis < vis.length;
            }
        }

        if (search) {
            search.addEventListener('input', function () {
                var keyword = this.value.toLowerCase().trim();
                rows.forEach(function (row) {
                    var hay = (row.getAttribute('data-search') || '').toLowerCase();
                    row.style.display = hay.indexOf(keyword) !== -1 ? '' : 'none';
                });
                refresh();
            });
        }

        checks.forEach(function (c) { c.addEventListener('change', refresh); });

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                visibleRows().forEach(function (row) {
                    var c = row.querySelector('[data-student-picker-check]');
                    if (c) c.checked = checkAll.checked;
                });
                refresh();
            });
        }

        if (selectVisible) {
            selectVisible.addEventListener('click', function () {
                visibleRows().forEach(function (row) {
                    var c = row.querySelector('[data-student-picker-check]');
                    if (c) c.checked = true;
                });
                refresh();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                checks.forEach(function (c) { c.checked = false; });
                refresh();
            });
        }

        form.addEventListener('submit', function (e) {
            var n = checkedCount();
            if (n === 0) {
                e.preventDefault();
                alert('Vui long chon it nhat mot sinh vien.');
                return;
            }
            if (!confirm('Them ' + n + ' sinh vien da chon vao lop?')) {
                e.preventDefault();
            }
        });

        refresh();
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(document.querySelectorAll('[data-student-picker-form]'), initPicker);
    });
})();
