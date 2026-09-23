# Nhóm 08 — Gợi ý đề tài theo ngữ nghĩa

> **Mã nhóm**: `TC-REC` · **Chức năng**: FEATURE_STATUS #18 (semantic recommendation): TopicRecommendationController + TopicRecommendationService + TopicEmbeddingService + bảng topic_embeddings + service AI :8891
> **Số test case**: 20 — Pass: **20** · Fail: **0** · Chưa chạy tay: **0**
> **Môi trường**: MySQL team_assign_test cho phần Feature; phần Unit chạy offline (Http::fake). Cần `AI-Services/topic-recommender` (:8891) khi kiểm tra thật; bật bằng TOPIC_RECOMMENDER_ENABLED=true.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/08-goi-y-de-tai-ngu-nghia.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử tính năng gợi ý đề tài theo NGỮ NGHĨA: người dùng CHỌN MÔN HỌC rồi nhập mô tả → hệ thống trả Top K đề tài gần nghĩa nhất (cosine similarity trên vector 768 chiều của model vietnamese-sbert). Trọng tâm: bắt buộc chọn môn, phạm vi lớp theo vai trò (SV/GV/Admin), cache vector theo `content_hash` (không embed lại đề tài), và fail-open khi service AI tắt/lỗi.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-REC-01` | Yêu cầu đăng nhập mới gọi được API gợi ý (case âm) | Sinh viên | API | Cao | Pass |
| `TC-REC-02` | Mô tả quá ngắn bị từ chối kèm thông báo rõ ràng (case âm) | Sinh viên | API | Trung bình | Pass |
| `TC-REC-03` | Gợi ý đề tài trong lớp của sinh viên và LƯU vector vào DB | Sinh viên | API | Cao | Pass |
| `TC-REC-04` | Lần gợi ý thứ 2 KHÔNG gọi lại /embed cho đề tài đã có vector (tiết kiệm tài nguyên) | Sinh viên | DB | Cao | Pass |
| `TC-REC-05` | only_available = true thì bỏ đề tài đã có nhóm khỏi danh sách ứng viên | Sinh viên | API | Trung bình | Pass |
| `TC-REC-06` | Service AI lỗi ⇒ ok = false + thông báo, KHÔNG trả 500 | Sinh viên | API | Cao | Pass |
| `TC-REC-07` | Tắt tính năng (TOPIC_RECOMMENDER_ENABLED = false) ⇒ API trả ok = false và không gọi service AI | Sinh viên | API | Trung bình | Pass |
| `TC-REC-08` | Không cho hỏi đề tài của lớp mà sinh viên không tham gia (case âm) | Sinh viên | API | Cao | Pass |
| `TC-REC-09` | Panel gợi ý render ở trang danh sách đề tài kèm script gọi API | Sinh viên | UI | Trung bình | Pass |
| `TC-REC-10` | Gộp name + description + goal + requirements thành text đem đi embedding; bỏ qua field rỗng | Hệ thống | API | Trung bình | Pass |
| `TC-REC-11` | content_hash (sha1) đổi khi nội dung đề tài đổi ⇒ chỉ đề tài đó được embed lại | Hệ thống | DB | Cao | Pass |
| `TC-REC-12` | Mã hoá/giải mã vector base64(float32) giữ nguyên giá trị; dữ liệu hỏng trả mảng rỗng (fail-open) | Hệ thống | API | Trung bình | Pass |
| `TC-REC-13` | Gọi POST /embed đúng URL + payload và trả về vector; service lỗi thì ném RuntimeException để caller fail-open | Hệ thống | API | Cao | Pass |
| `TC-REC-14` | Thiếu URL cấu hình ⇒ coi như tính năng tắt (không gọi mạng, không lỗi) | Hệ thống | API | Trung bình | Pass |
| `TC-REC-15` | Thông báo rõ ràng: cần nhập mô tả (và cần chọn môn học) trước khi gợi ý | Sinh viên | API | Trung bình | Pass |
| `TC-REC-16` | BẮT BUỘC chọn môn học trước khi gợi ý; thiếu môn thì JS chặn và API trả 422 (case âm) | Sinh viên | UI | Cao | Pass |
| `TC-REC-17` | Panel gợi ý chỉ cho chọn môn học thuộc lớp học phần mình tham gia | Sinh viên | UI | Cao | Pass |
| `TC-REC-18` | Chọn môn học không có lớp nào của mình ⇒ báo nhẹ (ok=false), không gọi service AI (case âm) | Sinh viên | API | Cao | Pass |
| `TC-REC-19` | Nhóm đã có đề tài được duyệt: panel VẪN dùng được, chỉ hiện cảnh báo tham khảo (không chặn) | Sinh viên | UI | Trung bình | Pass |
| `TC-REC-20` | Giảng viên và Admin cũng dùng được trợ lý gợi ý đề tài (chỉ cần chọn môn, không cần class_id) | Giảng viên · Admin | API | Cao | Pass |

## 3. Chi tiết test case

### TC-REC-01 — Yêu cầu đăng nhập mới gọi được API gợi ý (case âm)

- **Chức năng**: Gọi API gợi ý (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Không đăng nhập (tab ẩn danh)
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend với body mô tả bất kỳ
  2. Quan sát phản hồi
- **Dữ liệu đầu vào**: POST /api/recommend {"description":"..."}
- **Kết quả mong đợi**: Bị chặn: chuyển hướng /login (route web) — không trả kết quả gợi ý
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-02 — Mô tả quá ngắn bị từ chối kèm thông báo rõ ràng (case âm)

- **Chức năng**: Validate đầu vào (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập sinh viên đã tham gia lớp học phần
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend với mô tả 1-2 ký tự
  2. Kiểm tra JSON trả về
- **Dữ liệu đầu vào**: {"description":"a"}
- **Kết quả mong đợi**: ok = false + thông báo cần nhập mô tả dài hơn; KHÔNG gọi service AI
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/TopicRecommendationTest.php`

### TC-REC-03 — Gợi ý đề tài trong lớp của sinh viên và LƯU vector vào DB

- **Chức năng**: Gợi ý theo lớp (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Service AI giả lập (Http::fake) trả vector 768 chiều; lớp có đề tài chưa có vector
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend với mô tả hợp lệ
  2. Đọc danh sách Top K trả về
  3. Kiểm tra bảng topic_embeddings
- **Dữ liệu đầu vào**: {"description":"Xây dựng website quản lý thư viện cho trường học"}
- **Kết quả mong đợi**: ok = true, danh sách đề tài kèm điểm tương đồng; vector được ghi lại cho đề tài
- **Kiểm tra thêm (DB / log / API)**: topic_embeddings có hàng mới: model=vietnamese-sbert, dim=768, content_hash=sha1(text)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-04 — Lần gợi ý thứ 2 KHÔNG gọi lại /embed cho đề tài đã có vector (tiết kiệm tài nguyên)

- **Chức năng**: Cache vector (#18) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đề tài đã có hàng trong `topic_embeddings` với `content_hash` khớp nội dung hiện tại
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend lần 1 (ghi vector)
  2. Gọi lại lần 2 với mô tả khác
  3. Đếm số request tới /embed
- **Dữ liệu đầu vào**: Nhấn mạnh: chỉ gửi text của CÂU TRUY VẤN, không gửi lại đề tài
- **Kết quả mong đợi**: Lần 2 chỉ có 1 request /embed cho câu truy vấn; đề tài không được embed lại
- **Kiểm tra thêm (DB / log / API)**: topic_embeddings giữ nguyên `embedded_at` của đề tài
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-05 — only_available = true thì bỏ đề tài đã có nhóm khỏi danh sách ứng viên

- **Chức năng**: Lọc đề tài còn trống (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Lớp có 2 đề tài: 1 đã gán nhóm (`topics.assigned_group_id`), 1 còn trống
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend với `only_available` = true
  2. Kiểm tra danh sách trả về
- **Dữ liệu đầu vào**: {"description":"...", "only_available":true}
- **Kết quả mong đợi**: Chỉ còn đề tài chưa gán nhóm trong kết quả gợi ý
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-06 — Service AI lỗi ⇒ ok = false + thông báo, KHÔNG trả 500

- **Chức năng**: Fail-open service AI (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Http::fake() trả lỗi/timeout từ service :8891
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend
  2. Kiểm tra mã HTTP và nội dung
- **Dữ liệu đầu vào**: {"description":"Xây dựng website quản lý thư viện"}
- **Kết quả mong đợi**: HTTP 200 + ok = false + thông báo “gợi ý tạm thời không khả dụng”; trang đề tài vẫn hoạt động bình thường
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php + tests/Unit/TopicRecommendationTest.php`
- **Ghi chú**: Bắt buộc: tính năng phụ không được làm hỏng luồng chính (tìm kiếm/đăng ký đề tài).

### TC-REC-07 — Tắt tính năng (TOPIC_RECOMMENDER_ENABLED = false) ⇒ API trả ok = false và không gọi service AI

- **Chức năng**: Tắt tính năng (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đặt `services.topic_recommender.enabled` = false
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend
  2. Kiểm tra phản hồi
  3. Kiểm tra không có request ra ngoài
- **Dữ liệu đầu vào**: {"description":"Xây dựng website quản lý thư viện"}
- **Kết quả mong đợi**: ok = false kèm thông báo tính năng đang tắt; Http::assertNothingSent; panel gợi ý tự ẩn khỏi giao diện
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php + tests/Unit/TopicRecommendationTest.php`

### TC-REC-08 — Không cho hỏi đề tài của lớp mà sinh viên không tham gia (case âm)

- **Chức năng**: Giới hạn theo lớp (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 thuộc lớp A; lớp B có đề tài nhưng sv1 không tham gia
- **Các bước thực hiện**:
  1. Gọi POST /api/recommend với `class_id` = lớp B
- **Dữ liệu đầu vào**: {"description":"...", "class_id":{lớp B}}
- **Kết quả mong đợi**: Bị từ chối (403 hoặc ok = false kèm thông báo không có quyền) — không lộ đề tài của lớp khác
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-09 — Panel gợi ý render ở trang danh sách đề tài kèm script gọi API

- **Chức năng**: Panel gợi ý trên UI (#18) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập sinh viên đã tham gia lớp
- **Các bước thực hiện**:
  1. Mở /user/topics (và /user/groups/{id}/topics)
  2. Kiểm tra khối gợi ý
  3. Xem nguồn HTML tìm URL /api/recommend
- **Dữ liệu đầu vào**: URL /user/topics
- **Kết quả mong đợi**: Trang 200 có panel nhập mô tả + script fetch tới route `api.recommend`; nút gợi ý gọi API bằng CSRF header
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-10 — Gộp name + description + goal + requirements thành text đem đi embedding; bỏ qua field rỗng

- **Chức năng**: Tạo text embedding (#18) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Không cần DB (unit test)
- **Các bước thực hiện**:
  1. Gọi hàm tạo text với đề tài đủ 4 field
  2. Gọi lại với đề tài thiếu field / field rỗng
- **Dữ liệu đầu vào**: name, description, goal, requirements
- **Kết quả mong đợi**: Text gộp theo thứ tự cố định, ngăn cách rõ ràng; field rỗng/whitespace bị bỏ qua (không sinh khoảng trắng thừa)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/TopicRecommendationTest.php`

### TC-REC-11 — content_hash (sha1) đổi khi nội dung đề tài đổi ⇒ chỉ đề tài đó được embed lại

- **Chức năng**: Phát hiện nội dung đổi (#18) · **Role**: Hệ thống · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đề tài đã có vector + content_hash
- **Các bước thực hiện**:
  1. Tính hash từ nội dung hiện tại (khớp ⇒ dùng cache)
  2. Sửa `description` của đề tài
  3. Tính hash lại và so sánh
- **Dữ liệu đầu vào**: topic_embeddings.content_hash
- **Kết quả mong đợi**: Nội dung không đổi ⇒ hash bằng nhau (không embed lại); nội dung đổi ⇒ hash khác và đề tài được đánh dấu cần embed lại
- **Kiểm tra thêm (DB / log / API)**: Chỉ hàng của đề tài bị sửa có hash khác
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/TopicRecommendationTest.php + tests/Feature/TopicRecommendationTest.php`

### TC-REC-12 — Mã hoá/giải mã vector base64(float32) giữ nguyên giá trị; dữ liệu hỏng trả mảng rỗng (fail-open)

- **Chức năng**: Mã hoá vector (#18) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Không cần DB (unit test)
- **Các bước thực hiện**:
  1. Encode vector 768 chiều rồi decode lại
  2. Thử decode chuỗi rỗng / chuỗi hỏng
- **Dữ liệu đầu vào**: float32 little-endian, base64
- **Kết quả mong đợi**: Round-trip sai số trong ngưỡng cho phép; dữ liệu rỗng/hỏng ⇒ trả [] và KHÔNG ném exception
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/TopicRecommendationTest.php`

### TC-REC-13 — Gọi POST /embed đúng URL + payload và trả về vector; service lỗi thì ném RuntimeException để caller fail-open

- **Chức năng**: Gọi service AI (#18) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Http::fake() ghi lại request; cấu hình URL service :8891
- **Các bước thực hiện**:
  1. Gọi service với danh sách text
  2. Kiểm tra request (URL, payload)
  3. Cho service trả lỗi rồi gọi lại
  4. Gọi với danh sách rỗng
- **Dữ liệu đầu vào**: POST {TOPIC_RECOMMENDER_URL}/embed
- **Kết quả mong đợi**: Request đúng URL + payload danh sách text; trả vector đúng; lỗi ⇒ RuntimeException (caller tự fail-open); danh sách rỗng ⇒ KHÔNG gọi mạng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/TopicRecommendationTest.php`

### TC-REC-14 — Thiếu URL cấu hình ⇒ coi như tính năng tắt (không gọi mạng, không lỗi)

- **Chức năng**: Cấu hình service (#18) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đặt `services.topic_recommender.url` = rỗng
- **Các bước thực hiện**:
  1. Kiểm tra cờ “enabled” của service
  2. Gọi API gợi ý
- **Dữ liệu đầu vào**: TOPIC_RECOMMENDER_URL=
- **Kết quả mong đợi**: Service tự coi là tắt; API trả ok = false + thông báo; không phát sinh request ra ngoài
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/TopicRecommendationTest.php`

### TC-REC-15 — Thông báo rõ ràng: cần nhập mô tả (và cần chọn môn học) trước khi gợi ý

- **Chức năng**: Thông báo cho người dùng (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập sinh viên bất kỳ
- **Các bước thực hiện**:
  1. Gọi API gợi ý với mô tả rỗng
  2. Gọi lại khi tài khoản không thuộc lớp nào của môn đã chọn
- **Dữ liệu đầu vào**: POST /api/recommend {"query":"","subject_id":<môn>} · {"query":"...","subject_id":<môn không có lớp của mình>}
- **Kết quả mong đợi**: Mô tả rỗng ⇒ “Bạn hãy nhập mô tả đề tài mà nhóm mình muốn làm.”; không có lớp thuộc môn ⇒ ok=false + “Môn học này chưa có lớp học phần nào bạn được phép xem đề tài.” (KHÔNG ném lỗi 500)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/TopicRecommendationTest.php`
- **Ghi chú**: Thông báo “chưa tham gia lớp học phần nào” vẫn còn ở tầng service (`TopicRecommendationService::recommend()`) cho trường hợp danh sách lớp rỗng.

### TC-REC-16 — BẮT BUỘC chọn môn học trước khi gợi ý; thiếu môn thì JS chặn và API trả 422 (case âm)

- **Chức năng**: Chọn môn học cần gợi ý (#18) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 tham gia ít nhất 1 lớp học phần; panel gợi ý đang hiển thị
- **Các bước thực hiện**:
  1. Mở /user/topics
  2. Bấm “Gợi ý” khi combo box “Môn học cần gợi ý” còn trống
  3. Gọi thẳng API không kèm subject_id
  4. Gọi API với subject_id không tồn tại
- **Dữ liệu đầu vào**: POST /api/recommend {"query":"...","subject_id":""} · {"subject_id":999999}
- **Kết quả mong đợi**: Ở UI: báo “Bạn hãy chọn môn học cần gợi ý” và KHÔNG gọi API. Ở API: 422 + lỗi validate `subject_id`; không gọi service AI
- **Kiểm tra thêm (DB / log / API)**: Không phát sinh request ra service AI (không có log/HTTP call)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-17 — Panel gợi ý chỉ cho chọn môn học thuộc lớp học phần mình tham gia

- **Chức năng**: Phạm vi môn học (#18) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 tham gia môn A; hệ thống có thêm môn B (lớp của môn B sv1 không học)
- **Các bước thực hiện**:
  1. Mở /user/topics
  2. Mở combo box “Môn học cần gợi ý”
- **Dữ liệu đầu vào**: URL /user/topics
- **Kết quả mong đợi**: Chỉ thấy môn A; KHÔNG thấy môn B (không lộ môn của lớp mình không học)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-18 — Chọn môn học không có lớp nào của mình ⇒ báo nhẹ (ok=false), không gọi service AI (case âm)

- **Chức năng**: Phạm vi môn học (#18) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 không học lớp nào của môn B
- **Các bước thực hiện**:
  1. Gọi API với subject_id của môn B
  2. Quan sát phản hồi JSON
- **Dữ liệu đầu vào**: POST /api/recommend {"query":"...","subject_id":<môn B>}
- **Kết quả mong đợi**: HTTP 200 + ok=false + thông báo “Môn học này chưa có lớp học phần nào bạn được phép xem đề tài.”; KHÔNG có request ra service AI
- **Kiểm tra thêm (DB / log / API)**: Meta trả về `user_role = student` và `class_ids = []`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-19 — Nhóm đã có đề tài được duyệt: panel VẪN dùng được, chỉ hiện cảnh báo tham khảo (không chặn)

- **Chức năng**: Cảnh báo nhóm đã có đề tài (#18) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhóm của sv1 đã được duyệt đề tài (groups.topic_id khác null)
- **Các bước thực hiện**:
  1. Mở /user/topics
  2. Quan sát cảnh báo phía trên panel
  3. Nhập mô tả + chọn môn rồi bấm “Gợi ý”
- **Dữ liệu đầu vào**: URL /user/topics · POST /api/recommend
- **Kết quả mong đợi**: Hiện cảnh báo “Nhóm của bạn đã có đề tài được duyệt — kết quả gợi ý dưới đây chỉ mang tính tham khảo.”; API vẫn trả ok=true (KHÔNG bị chặn)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`

### TC-REC-20 — Giảng viên và Admin cũng dùng được trợ lý gợi ý đề tài (chỉ cần chọn môn, không cần class_id)

- **Chức năng**: Dùng trợ lý đề tài (#18) · **Role**: Giảng viên · Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Giảng viên phụ trách 2 lớp của môn A; Admin không gán lớp nào
- **Các bước thực hiện**:
  1. Giảng viên gọi API với subject_id của môn mình phụ trách
  2. Admin gọi API với subject_id của môn bất kỳ
  3. Mở /topics (trang quản lý đề tài) và quan sát panel
- **Dữ liệu đầu vào**: POST /api/recommend {query, subject_id} bằng token/session của giảng viên và admin
- **Kết quả mong đợi**: Cả 2 đều ok=true; giảng viên: class_ids = các lớp mình phụ trách của môn đó; admin: class_ids = mọi lớp của môn. Panel gợi ý cũng render ở trang /topics với đúng danh sách môn theo vai trò
- **Kiểm tra thêm (DB / log / API)**: logs không có cảnh báo 403/500
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicRecommendationTest.php`
- **Ghi chú**: Sinh viên vẫn chỉ trong lớp mình tham gia; truyền `class_id` của lớp không thuộc quyền ⇒ 403 (case cũ giữ nguyên).

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Unit/TopicRecommendationTest.php tests/Feature/TopicRecommendationTest.php
```

## 5. Ghi chú & rủi ro

- Kiến trúc: UI (Blade + fetch, `components/topic-recommender.blade.php`) → `POST /api/recommend` (khai báo ở `routes/web.php` để có session + CSRF) → controller → service → service AI :8891 → `POST /embed` + cosine.
- BẮT BUỘC chọn MÔN HỌC: combo box “Môn học cần gợi ý” nằm trong panel; JS chặn khi chưa chọn, API validate `subject_id` (`required|exists:subjects,subject_id` ⇒ 422). Danh sách môn đưa vào panel đã lọc theo vai trò: sinh viên = môn của lớp mình tham gia; giảng viên = môn của lớp mình phụ trách; admin = mọi môn.
- Phạm vi tra cứu = (lớp theo vai trò) ∩ (lớp của môn đã chọn); rỗng ⇒ HTTP 200 + ok=false (không gọi service AI) chứ không phải 500. Sinh viên truyền `class_id` của lớp mình không tham gia ⇒ 403.
- Panel cũng được nhúng ở trang quản lý đề tài `/topics` (giảng viên + admin) ngoài `user/topics` và `user/group_topics`.
- Đã có đề tài được duyệt (nhóm có `topic_id`): panel VẪN dùng bình thường, chỉ thêm cảnh báo “chỉ mang tính tham khảo” — không chặn.
- BẮT BUỘC chọn môn học: request phải có `subject_id` (`required|exists:subjects,subject_id` — thiếu/sai ⇒ 422). Phạm vi tìm kiếm = lớp theo vai trò (sinh viên: `user_classes`; giảng viên: lớp mình phụ trách; admin: tất cả) GIAO với lớp thuộc môn đã chọn. Rỗng ⇒ `ok=false` kèm thông báo (không 403/500).
- Giảng viên/Admin dùng được trợ lý ở cả `/topics` (trang quản lý đề tài) — không cần truyền `class_id`. Sinh viên đã có đề tài được duyệt VẪN dùng được, panel chỉ hiện thêm cảnh báo tham khảo (không chặn).
- Vector đề tài lưu ở `topic_embeddings` (1 hàng/đề tài, PK = topic_id): `model` (vietnamese-sbert), `dim` (768), `content_hash` (sha1 text đã embed), `embedding` (base64 float32 LE ~4KB). MySQL 8.4 chưa có kiểu VECTOR.
- Đổi nội dung đề tài ⇒ `content_hash` đổi ⇒ CHỈ đề tài đó embed lại; đổi model thì phải đổi `TOPIC_RECOMMENDER_MODEL_TAG` rồi chạy `php artisan topics:embed --force`.
- Giới hạn quyền: chỉ gợi ý trong lớp mình tham gia (`user_classes`); truyền `class_id` của lớp khác ⇒ 403. Admin/Giảng viên: xem theo lớp mình phụ trách (GV) hoặc mọi lớp thuộc môn đã chọn (Admin).
- Fail-open 2 tầng: `TOPIC_RECOMMENDER_ENABLED=false` ⇒ panel tự ẩn + API trả `ok:false`; service AI tắt/lỗi ⇒ HTTP 200 + thông báo “tạm thời không khả dụng”, KHÔNG bao giờ 500 và không ảnh hưởng trang tìm kiếm/đăng ký đề tài.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/08-goi-y-de-tai-ngu-nghia.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/08-goi-y-de-tai-ngu-nghia.php`</sub>
