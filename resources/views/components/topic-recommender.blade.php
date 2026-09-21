@props([
    'classId' => null,
    'title' => 'Gợi ý đề tài theo mô tả',
    'hint' => null,
])

@php
    // Panel tự ẩn khi tính năng chưa bật (giống components/chatbot.blade.php tự ẩn khi thiếu API key).
    $enabled = (bool) config('services.topic_recommender.enabled', false);
    $topK = (int) config('services.topic_recommender.top_k', 5);
    $uid = 'topic-recommender-' . substr(md5(($classId ?? 'all') . microtime()), 0, 8);
@endphp

@if($enabled)
<div class="card border-0 shadow-sm mb-4" id="{{ $uid }}">
    <div class="card-body p-4">
        <div class="d-flex align-items-start mb-3">
            <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                <i class="fas fa-wand-magic-sparkles text-primary fa-lg"></i>
            </div>
            <div>
                <h5 class="mb-1 fw-bold">{{ $title }}</h5>
                <small class="text-muted">
                    Mô tả điều nhóm bạn muốn làm — hệ thống so sánh <strong>ngữ nghĩa</strong> (không chỉ khớp từ khoá)
                    và trả về {{ $topK }} đề tài gần nhất trong lớp của bạn.
                </small>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-12">
                <textarea class="form-control" rows="3" maxlength="1000" data-role="query"
                          placeholder="{{ $hint ?? 'Ví dụ: Làm website quản lý thư viện cho trường học, có mượn/trả sách, thẻ bạn đọc, thông báo quá hạn...' }}"></textarea>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" data-role="only-available" id="{{ $uid }}-available">
                    <label class="form-check-label" for="{{ $uid }}-available">Chỉ đề tài còn trống</label>
                </div>
                <button type="button" class="btn btn-primary ms-auto" data-role="submit">
                    <i class="fas fa-magic me-2"></i>Gợi ý đề tài
                </button>
            </div>
        </div>

        <div class="mt-3 d-none" data-role="status"></div>
        <div class="mt-3" data-role="results"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const root = document.getElementById(@json($uid));
    if (!root) return;

    const queryEl = root.querySelector('[data-role="query"]');
    const availableEl = root.querySelector('[data-role="only-available"]');
    const submitEl = root.querySelector('[data-role="submit"]');
    const statusEl = root.querySelector('[data-role="status"]');
    const resultsEl = root.querySelector('[data-role="results"]');

    const API_URL = @json(route('api.recommend'));
    const CSRF = '{{ csrf_token() }}';
    const CLASS_ID = @json($classId);
    const TOP_K = {{ $topK }};

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function showStatus(html, type) {
        statusEl.className = 'mt-3 alert alert-' + type + ' mb-0';
        statusEl.innerHTML = html;
        statusEl.classList.remove('d-none');
    }

    function hideStatus() {
        statusEl.classList.add('d-none');
        statusEl.innerHTML = '';
    }

    function renderCard(topic) {
        const percent = Math.max(0, Math.min(100, parseInt(topic.similarity_percent, 10) || 0));
        const badge = topic.is_available
            ? '<span class="badge bg-success rounded-pill">Còn trống</span>'
            : '<span class="badge bg-danger rounded-pill">Đã có nhóm</span>';

        const facts = [];
        if (topic.lecturer) facts.push('<span><i class="fas fa-user-tie me-1"></i>' + escapeHtml(topic.lecturer) + '</span>');
        if (topic.subject_name) facts.push('<span><i class="fas fa-book me-1"></i>' + escapeHtml(topic.subject_name) + '</span>');
        if (topic.class_name) facts.push('<span><i class="fas fa-chalkboard me-1"></i>' + escapeHtml(topic.class_name) + '</span>');
        facts.push('<span><i class="fas fa-users me-1"></i>' + (topic.min_members || 1) + ' – ' + (topic.max_members || 5) + ' TV</span>');

        return '' +
            '<div class="card border-0 shadow-sm mb-3">' +
                '<div class="card-body">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2">' +
                        '<h6 class="fw-bold mb-0 me-2">' + escapeHtml(topic.name) + '</h6>' + badge +
                    '</div>' +
                    '<div class="d-flex flex-wrap gap-3 mb-2 small text-muted">' + facts.join('') + '</div>' +
                    (topic.description ? '<p class="text-muted small mb-2">' + escapeHtml(topic.description) + '</p>' : '') +
                    '<div class="d-flex align-items-center gap-2">' +
                        '<div class="progress flex-grow-1" style="height:8px" title="cosine = ' + escapeHtml(topic.similarity) + '">' +
                            '<div class="progress-bar bg-primary" style="width:' + percent + '%"></div>' +
                        '</div>' +
                        '<small class="text-primary fw-semibold text-nowrap">' + percent + '% phù hợp</small>' +
                        '<a href="' + escapeHtml(topic.url) + '" class="btn btn-sm btn-outline-primary text-nowrap">Chi tiết</a>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    async function recommend() {
        const query = (queryEl.value || '').trim();

        if (query.length < 3) {
            showStatus('<i class="fas fa-triangle-exclamation me-1"></i>Bạn hãy nhập mô tả ít nhất 3 ký tự.', 'warning');
            queryEl.focus();
            return;
        }

        const originalLabel = submitEl.innerHTML;
        submitEl.disabled = true;
        submitEl.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang phân tích...';
        resultsEl.innerHTML = '';
        showStatus('<i class="fas fa-circle-info me-1"></i>Đang so sánh ngữ nghĩa với đề tài trong lớp của bạn...', 'info');

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    query: query,
                    class_id: CLASS_ID,
                    only_available: availableEl.checked,
                    top_k: TOP_K
                })
            });

            const data = await response.json().catch(function () { return null; });

            if (!response.ok || !data) {
                showStatus('<i class="fas fa-triangle-exclamation me-1"></i>Không gọi được API gợi ý (HTTP ' + response.status + ').', 'danger');
                return;
            }

            if (!data.ok) {
                showStatus('<i class="fas fa-circle-info me-1"></i>' + escapeHtml(data.message || 'Tạm thời chưa gợi ý được, vui lòng thử lại sau.'), 'warning');
                return;
            }

            if (!data.results || !data.results.length) {
                showStatus('<i class="fas fa-circle-info me-1"></i>' + escapeHtml(data.message || 'Không tìm thấy đề tài phù hợp.'), 'warning');
                return;
            }

            hideStatus();
            resultsEl.innerHTML =
                '<h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning me-2"></i>' +
                'Top ' + data.results.length + ' đề tài gần nhất với mô tả của bạn</h6>' +
                data.results.map(renderCard).join('');
        } catch (error) {
            showStatus('<i class="fas fa-triangle-exclamation me-1"></i>Lỗi kết nối tới máy chủ, vui lòng thử lại.', 'danger');
        } finally {
            submitEl.disabled = false;
            submitEl.innerHTML = originalLabel;
        }
    }

    submitEl.addEventListener('click', recommend);

    queryEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            recommend();
        }
    });
})();
</script>
@endpush
@endif
