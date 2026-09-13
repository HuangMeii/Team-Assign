# 🐞 KẾ HOẠCH SỬA LỖI — Team-Assign

> Nguồn: `bug-report.xlsx` (74 dòng chức năng: Admin 2–37, Giáo viên 38–56, Sinh viên 57–74)
> Cập nhật: 13/09/2026 — Các lỗi đã được xác minh trong code trước khi đưa vào kế hoạch.
> Ghi chú: Cột G "Quyết định sửa" trong Excel hiện đang trống → cần duyệt trước khi bắt đầu từng nhóm lỗi.

## Mục lục

1. [Tổng quan trạng thái](#1-tổng-quan-trạng-thái)
2. [Danh sách lỗi chi tiết](#2-danh-sách-lỗi-chi-tiết)
   - Nhóm A — Lỗi chặn nghiệp vụ (Critical)
   - Nhóm B — Nghiệp vụ dữ liệu & vai trò (High)
   - Nhóm C — Trải nghiệm Admin (Medium)
   - Nhóm D — Chat, Nhóm & dữ liệu test
3. [Lịch thực hiện 5 giai đoạn](#3-lịch-thực-hiện-theo-5-giai-đoạn)
4. [Điểm cần quyết định trước khi bắt đầu](#4-điểm-cần-quyết-định-trước-khi-bắt-đầu)
5. [Bảng theo dõi tiến độ](#5-bảng-theo-dõi-tiến-độ)
6. [Nhật ký cập nhật](#6-nhật-ký-cập-nhật)

---

## 1. Tổng quan trạng thái

| Trạng thái (cột D Excel) | Số dòng | Xử lý |
|---|---|---|
| Hoàn thành | ~30 | Không động vào (trừ R73 có note "bể giao diện") |
| Lỗi | 8 | Ưu tiên sửa — Giai đoạn 1, 2, 4 |
| Không ổn nghiệp vụ | 4 | Sửa theo nghiệp vụ đề xuất — Giai đoạn 2, 4 |
| Cải tiến / Chỉnh sửa nhẹ | 6 | Giai đoạn 3 |
| Chưa có tài nguyên test | 3 | Tạo Seeder/Factory — Giai đoạn 4 |
| Giáo viên (dòng 38–56) | 19 | Chưa đánh trạng thái — cần kiểm thử song song với Admin sau khi sửa |

**Tổng: ~17 lỗi/cải tiến cần xử lý.**

---

## 2. Danh sách lỗi chi tiết

### 🔴 Nhóm A — Lỗi chặn nghiệp vụ (Critical) → Giai đoạn 1

#### A1. [R17] Admin không duyệt/từ chối được đăng ký đề tài
- **Excel:** Quản lý Duyệt đăng ký → Duyệt/Từ chối → "Không duyệt cũng như không từ chối đc"
- **Nguyên nhân gốc (đã xác minh):** `app/Services/TopicRegistrationService.php` — cả `approve()` (dòng ~128) và `reject()` (dòng ~174) đều có check `if ($lecturer->role !== 'lecturer') return error` → **admin gọi luôn bị từ chối**.
- **Cách sửa:** Cho phép cả `admin` duyệt/từ chối; giảng viên chỉ duyệt được yêu cầu thuộc lớp mình phụ trách (check theo `topic.class_id` ∈ `$user->classes`).
- **File ảnh hưởng:** `app/Services/TopicRegistrationService.php` (có thể thêm phần mềm trung gian/policy `canApprove`).
- **Trạng thái:** ✅ **Đã sửa ngày 13/09/2026** — chi tiết tại [mục 6. Nhật ký cập nhật](#6-nhật-ký-cập-nhật).

#### A2. [R6] Admin không tạo được Đề tài (dropdown lớp trống)
- **Excel:** Quản lý Đề tài (Admin) → Thêm Đề tài → "Không hiện thị danh sách lớp học phần => Không tạo đc"
- **Nguyên nhân gốc:** `app/Http/Controllers/TopicController.php` — `create()`, `store()`, `update()`, `edit()` đều dùng `$user->classes`; admin không có quan hệ classes → rỗng. `store()`/`update()` còn chặn bằng `$classIds->contains()`.
- **Cách sửa:** Khi role = admin → lấy toàn bộ `ClassSection::with('subject')->get()` (giống `index()` đã làm), bỏ qua check quyền theo class cho admin.
- **File ảnh hưởng:** `app/Http/Controllers/TopicController.php`.
- **Trạng thái:** ✅ **Đã sửa ngày 13/09/2026** — chi tiết tại [mục 6. Nhật ký cập nhật](#6-nhật-ký-cập-nhật). Phát hiện thêm: `topics/edit.blade.php` **thiếu select lớp học phần** (nguyên nhân thật của "Lỗi id" trong R4) → đã bổ sung.

#### A3. [R4] Chỉnh sửa Đề tài: sai tên giảng viên + lỗi id
- **Excel:** Quản lý Đề tài (Admin) → Chỉnh sửa Đề tài → "-Lỗi id -Lỗi hiển thị tên giảng viên"
- **Nguyên nhân gốc:** `resources/views/topics/edit.blade.php` dòng 52 hiển thị `{{ Auth::user()->name }}` thay vì `$topic->lecturer` → ai mở form cũng thấy tên chính mình.
- **Cách sửa:** Hiển thị `$topic->lecturer` (tên giảng viên thực sự của đề tài). Phần "Lỗi id": verify lại khi sửa (nghi vấn liên quan dropdown lớp rỗng của A2 làm form thiếu `class_id`); kiểm tra binding `Topics $topic` với khóa chính `topic_id` đã đúng.
- **File ảnh hưởng:** `resources/views/topics/edit.blade.php`, `app/Http/Controllers/TopicController.php`.

#### A4. [R57] Sinh viên thấy đề tài của lớp KHÔNG phải của mình
- **Excel:** Sinh viên → Tìm kiếm/Lọc Đề tài → "Hiển thị đề tài của lớp học phần không phải của mình"
- **Nguyên nhân gốc:** `app/Http/Controllers/UserDashboardController.php` → `topics()` lọc `whereIn('subject_id', $subjectIds)`; cùng 1 môn có nhiều lớp → sinh viên thấy đề tài của các lớp khác cùng môn.
- **Cách sửa:** Đổi lọc theo `class_id` thuộc `userClasses` của sinh viên (`whereIn('class_id', $userClasses->pluck('class_id'))`); giữ filter tìm kiếm theo môn/lớp như bổ trợ.
- **File ảnh hưởng:** `app/Http/Controllers/UserDashboardController.php`.

---

### 🟠 Nhóm B — Nghiệp vụ dữ liệu & vai trò (High) → Giai đoạn 2

#### B1. [R71] Hiển thị sai vai trò "leader" trong hồ sơ / quản lý SV / mời SV
- **Excel:** Sinh viên → Hồ sơ → "Sinh viên là nhóm trưởng 1 nhóm thui mà vai trò hiện trong hồ sơ là leader => gây hiểu lầm về role, lỗi hiển thị trong quản lý sinh viên, lỗi mời sinh viên"
- **Nguyên nhân gốc:** DB `users.role` là ENUM có giá trị riêng `'leader'` (migration `2026_08_24_050724_add_role_leader_to_users_table.php`) → sinh viên làm trưởng nhóm bị set role='leader', gây nhầm với role hệ thống.
- **Cách sửa (đề xuất):**
  1. Migration mới: `UPDATE users SET role='student' WHERE role='leader'` + tạo lại ENUM chỉ còn `('student','lecturer','admin')`.
  2. Trạng thái "Nhóm trưởng" derive động từ `groups.leader_id` (accessor `is_leader` / quan hệ `groupsLed`).
  3. Dọn các check `role === 'leader'` và validate `in:student,leader,lecturer,admin` trong controllers & views.
- **File ảnh hưởng:** migration mới, `app/Models/User.php`, `GroupService`, `InvitationService`, `InviteController`, `UserDashboardController`, `AdminController`, `UserController`, các view hồ sơ/quản lý SV/mời thành viên.

#### B2. [R71] Cờ "Đã có nhóm" (`is_have_group`) chặn SV vào nhiều nhóm/nhiều môn
- **Excel:** Sinh viên → Hồ sơ → "Trạng thái 'Đã có nhóm' làm sinh viên chỉ có thể vào đc 1 nhóm duy nhất mặc dù có nhiều môn học"
- **Nguyên nhân gốc:** `users.is_have_group` là boolean duy nhất trên user, trong khi nghiệp vụ cho phép mỗi lớp/môn 1 nhóm.
- **Cách sửa:** Bỏ tin vào cờ tĩnh; tính "đã có nhóm trong lớp X" bằng query `group_members` join `groups` theo `groups.class_id`. Cập nhật cờ khi accept/reject/leave (hoặc loại bỏ hoàn toàn và query động). Áp dụng cho: mời thành viên (chỉ mời SV chưa có nhóm trong lớp đó), đăng ký đề tài, hiển thị hồ sơ.
- **File ảnh hưởng:** `GroupService`, `InvitationService`, `InviteController`, `UserDashboardController`, `app/Models/User.php`, views liên quan.

#### B3. [R13] Sửa SV rời lớp nhưng vẫn còn trong nhóm lớp đó
- **Excel:** Quản lý Sinh viên → Chỉnh sửa Sinh viên → "khi sinh viên đã join nhóm và cho sinh viên rời lớp thì nhóm vẫn còn sinh viên đó"
- **Nguyên nhân gốc:** `StudentController::update()` chỉ `classes()->sync($class_ids)` — không dọn dữ liệu nhóm khi SV bị bỏ khỏi lớp.
- **Cách sửa:**
  1. Khi sync lớp: với các lớp bị bỏ → xóa SV khỏi `group_members` của nhóm thuộc lớp đó; nếu SV là leader → chuyển leader hoặc giải tán nhóm (theo policy); hủy `invites`/`join_requests` còn pending của SV trong lớp đó.
  2. Cải tiến chọn lớp: thay multi-select dài bằng ô tìm kiếm (Alpine.js/datalist — project có sẵn Alpine + Tailwind).
- **File ảnh hưởng:** `app/Http/Controllers/StudentController.php`, `resources/views/students/edit.blade.php`.

#### B4. [R30] Thêm Môn học: mã tự sinh + thiếu cột tín chỉ
- **Excel:** Quản lý môn học → Thêm Môn học → "Mã môn học cần đc tự sinh, Số tín chỉ cần được điền vào ngay khi tạo"
- **Nguyên nhân gốc:** Migration `2025_10_22_151331_create_subjects_table.php` **không có cột `credits`**; `SubjectController::store()` bắt nhập tay `subject_code`.
- **Cách sửa:**
  1. Migration thêm cột `credits` (nullable hoặc default — tùy quyết định bắt buộc hay không).
  2. Tự sinh `subject_code` duy nhất (format đề xuất: mã rút gọn tên môn + số thứ tự), user vẫn có thể ghi đè.
  3. Form thêm: ô mã để trống = tự sinh; validate `credits` bắt buộc nhập ngay khi tạo.
- **File ảnh hưởng:** migration mới, `app/Http/Controllers/SubjectController.php`, `resources/views/admin/subjects/create.blade.php`, `app/Models/Subject.php`.

#### B5. [R28] Thêm Lớp học phần: mã lớp cần tự sinh
- **Excel:** Quản lý Lớp học phần → Thêm Lớp học phần → "Mã lớp học phần cần đc tự sinh"
- **Nguyên nhân gốc:** `ClassSectionController::store()` (admin) **không set `class_code`** (cột nullable theo migration `2026_08_24_050726`) → lớp tạo từ admin không có mã → join-by-code hỏng. `lecturerStore()` bắt GV nhập tay.
- **Cách sửa:** Tự sinh `class_code` duy nhất cho cả 2 luồng (format đề xuất: mã môn + số thứ tự, ví dụ `WEB101-01`), hiển thị mã sau khi tạo để GV gửi cho SV.
- **File ảnh hưởng:** `app/Http/Controllers/ClassSectionController.php`, `resources/views/admin/classes/create.blade.php`, `resources/views/lecturer/classes/create.blade.php`.

#### B6. [R31] Không được phép sửa mã môn
- **Excel:** Quản lý môn học → Chỉnh sửa Môn học → "Không đc phép sửa mã môn"
- **Cách sửa:** View `admin/subjects/edit.blade.php`: input `subject_code` readonly; server: `SubjectController::update()` bỏ `subject_code` khỏi validate/update.
- **File ảnh hưởng:** `app/Http/Controllers/SubjectController.php`, `resources/views/admin/subjects/edit.blade.php`.

#### B7. [R32] Xóa Môn học: hiện thông báo khi không xóa được
- **Excel:** Quản lý môn học → Xóa Môn học → "Thông báo khi không xóa đc"
- **Nguyên nhân gốc:** `SubjectController::destroy()` đã trả về `back()->with('error')` khi môn đang có lớp; cần xác minh view có hiển thị flash error không.
- **Cách sửa:** Bổ sung/đồng bộ khối hiển thị `session('error')`/`session('success')` trong `admin/subjects/index.blade.php`.
- **File ảnh hưởng:** `resources/views/admin/subjects/index.blade.php`.

---

### 🟡 Nhóm C — Trải nghiệm Admin (Medium) → Giai đoạn 3

#### C1. [R19] Không cho Admin khóa Admin khác
- **Excel:** Quản lý Người dùng → Khóa Người dùng → "Không cho Admin khóa nhau" (Chỉnh sửa nhẹ)
- **Nguyên nhân gốc:** `AdminController::toggleActive()` chỉ chặn tự khóa (`$user->user_id === Auth::id()`).
- **Cách sửa:** Thêm check: nếu target `role === 'admin'` và không phải chính mình → từ chối với thông báo phù hợp.
- **File:** `app/Http/Controllers/AdminController.php`.

#### C2. [R22] Đổi mật khẩu (view admin): không hiện lỗi, thiếu xác nhận lần 2
- **Excel:** Quản lý Người dùng → Đổi mật khẩu → "Không hiện và không cho người dùng xác nhận lần 2"
- **Cách sửa:** Đồng bộ `users/profile-admin-password.blade.php` với `users/profile-password.blade.php` (view chuẩn đã có sẵn: toggle hiện/ẩn mật khẩu + ô `new_password_confirmation` + hiển thị `@error`).
- **File:** `resources/views/users/profile-admin-password.blade.php`.

#### C3. [R34] Hồ sơ: hiện thông báo khi đổi mật khẩu không thành
- **Excel:** Admin → Khác → Hồ sơ → Đổi mật khẩu → "Thông báo vấn đề khi đổi không thành"
- **Cách sửa:** Đảm bảo cả 2 view password hiển thị `@error('current_password')` và lỗi validate chung; giữ tab "Đổi mật khẩu" đang mở sau redirect.
- **File:** `resources/views/users/profile-password.blade.php`, `resources/views/users/profile-admin-password.blade.php`, `UserController::changePassword()`.

#### C4. [R13-CT] Chỉnh sửa SV: chọn lớp kiểu tìm kiếm (list lớp sẽ phồng)
- **Excel:** Ghi chú trong "Chỉnh sửa Sinh viên": "Khi thêm join lớp cho sinh viên nên cho tìm lớp, tại tương lai dữ liệu phồng bự dễ bể giao diện"
- **Cách sửa:** Ô tìm kiếm lớp (Alpine.js combobox / datalist) trong `students/edit.blade.php` + `students/create.blade.php`.
- **File:** `resources/views/students/edit.blade.php`, `resources/views/students/create.blade.php`.

#### C5. [R16] Danh sách đăng ký: thêm lọc/tìm kiếm + cột Môn/Lớp
- **Excel:** Quản lý Duyệt đăng ký → Hiển thị danh sách đăng ký → "Chưa có lọc, tìm kiếm; Thiếu thông tin về môn, lớp nên khó nhìn"
- **Cách sửa:** `TopicRequestController::index()` nhận query `search`, `status`, `class_id`; view thêm hàng filter + cột Môn học/Lớp (qua `$req->topic->class->subject`).
- **File:** `app/Http/Controllers/TopicRequestController.php`, `resources/views/topic_requests/index.blade.php`.

#### C6. [R24] Lọc Lớp học phần: multi-select môn/GV/trạng thái
- **Excel:** Quản lý Lớp học phần → Tìm kiếm/Lọc → "Chọn nhiều môn/giáo viên/ trạng thái"
- **Cách sửa:** Filter nhiều giá trị: `whereIn('subject_id', ...)`, `whereHas('lecturers', ...)` cho nhiều GV, trạng thái nhiều lựa chọn (`is_active` in 0,1).
- **File:** `app/Http/Controllers/ClassSectionController.php`, `resources/views/admin/classes/index.blade.php`.

#### C7. [R14] Quick action "Gửi email" ghi "đang phát triển"
- **Excel:** Quản lý Sinh viên → (Thao tác nhanh) Gửi email → "Nó ghi đang phát triển"
- **Cách sửa (sau khi chốt policy + SMTP):** Implement gửi mail cho SV (Laravel Mail + Mailable), hoặc ẩn nút nếu chưa dùng.
- **File:** `StudentController` (hoặc controller mới), view danh sách SV, `.env` (MAIL_*).

#### C8. [R15] Quick action "Reset mật khẩu" chỉ reset về mật khẩu chung
- **Excel:** Quản lý Sinh viên → (Thao tác nhanh) Reset mật khẩu → "Nó ghi đang phát triển, thật ra chỉ là đổi về mật khẩu chung thôi mà"
- **Cách sửa (sau khi chốt policy):** Sinh mật khẩu tạm ngẫu nhiên, hash lưu, hiển thị/email cho SV, đánh dấu buộc đổi mật khẩu ở lần đăng nhập tiếp theo (cần cột mới nếu làm).
- **File:** `StudentController`, view, có thể migration thêm cột `must_change_password`.

---

### 🟢 Nhóm D — Chat, Nhóm & dữ liệu test → Giai đoạn 4

#### D1. [R67] Chat nhóm không gửi được tin nhắn
- **Excel:** Sinh viên → Chức năng Nhóm → Chat nhóm → "Không gửi tin nhắn lên đoạn chat đc, lỗi"
- **Trạng thái code (đã kiểm tra):**
  - Route `POST /groups/{groupId}/chat/send` → `GroupsChatController::sendMessage()` tồn tại (routes/web.php dòng 157-158).
  - View `groups/chat.blade.php` dùng `fetch()` + CSRF token từ meta; có `chat_listener.js` (Laravel Echo) + channel `chat.group.{groupId}` (routes/channels.php) + event `NewChatMessage`.
- **Việc cần làm (điều tra khi sửa):**
  1. Chạy `php artisan reverb:start` + kiểm tra `.env` (REVERB_APP_*, BROADCAST_CONNECTION, VITE_REVERB_*).
  2. Kiểm tra `bootstrap.js`/cấu hình Echo, xem lỗi console/F12 khi gửi.
  3. Xác minh mã lỗi HTTP trả về (403/419/500) để khoanh vùng: CSRF, auth, hay broadcast fail.
  4. Fallback: nếu Reverb chưa chạy, tin nhắn vẫn phải lưu DB + hiện sau khi reload.
- **File:** `GroupsChatController`, `resources/views/groups/chat.blade.php`, JS listener, `.env`.

#### D2. [R70] 2 nút "Tìm nhóm": tìm riêng theo lớp phải chỉ hiện nhóm lớp đó
- **Excel:** Sinh viên → Chức năng Nhóm → Tìm nhóm → "Hiện đang có 2 nút tìm nhóm: 1 ở phía trên (tìm chung), 1 ở các lớp tìm riêng => tìm riêng chỉ nên hiện nhóm của lớp đó thui"
- **Cách sửa:** `availableGroups()` nhận tham số `class_id` khi gọi từ trang lớp; filter `groups.class_id = class_id`; phân biệt 2 luồng trong view (`user/available_groups.blade.php` + `user/class_detail.blade.php`).
- **File:** `UserDashboardController::availableGroups()`, `resources/views/user/available_groups.blade.php`, `resources/views/user/class_detail.blade.php`.

#### D3. [R10] Import/Export Sinh viên: chưa có tài nguyên test
- **Cách sửa:** Tạo Factory/Seeder sinh user + class + enrollment; file Excel mẫu trong `public/templates/` (hiện `students_template.xlsx` fallback đang generate động — xác minh lại).
- **File:** `database/factories/`, `database/seeders/`, `app/Imports/StudentsImport.php`, `app/Exports/*`.

#### D4. [R25] Khóa Lớp học phần: chưa rõ nghiệp vụ + chưa test
- **Excel:** Quản lý Lớp học phần → Khóa Lớp học phần → "Chưa hiểu nghiệp vụ có tác dụng gì" (Chưa có tài nguyên test, Lỗi)
- **Nghiệp vụ đề xuất:** Lớp bị khóa ⇒ sinh viên KHÔNG join bằng mã lớp được, KHÔNG tạo nhóm/đăng ký đề tài trong lớp đó; hiện trạng thái "Đã khóa" trong UI các trang liên quan.
- **Việc cần làm:** Enforce check `is_active` ở `ClassJoinController::joinByCode()`, `UserDashboardController::storeGroup()`, `TopicRegistrationService::register()`; tạo seeder để test.
- **File:** `ClassJoinController`, `UserDashboardController`, `TopicRegistrationService`, views.

#### D5. [R35] Thông báo: chưa có dữ liệu test
- **Cách sửa:** Seeder tạo notifications mẫu cho từng loại (invite, join request, topic approved/rejected, admin broadcast).
- **File:** `database/seeders/`, `NotificationService`.

#### D6. [R36] Cài đặt: chưa có nghiệp vụ
- **Excel:** Admin → Khác → Cài đặt → "Chưa có nghiệp vụ" (Không ổn nghiệp vụ)
- **Việc cần quyết định:** phạm vi cài đặt (kích thước trang, bật/tắt thông báo, ngôn ngữ...) hoặc tạm ẩn mục menu. Chờ quyết định → nếu ẩn menu: sửa `layouts/admin.blade.php`.

#### D7. [R73] Giao diện Thông báo bị vỡ (note dù đã "Hoàn thành")
- **Excel:** Sinh viên → Khác → Thông báo → "Nhưng mà bị bể giao diện nha"
- **Cách sửa:** Rà soát CSS/HTML trong `layouts/user.blade.php` (dropdown thông báo) và `NotificationController` + view liên quan.

---

## 3. Lịch thực hiện theo 5 giai đoạn

> Ước lượng theo buổi làm việc (mỗi buổi ~3-4h). Có thể triển khai song song nếu chia nhiều người.

### Giai đoạn 1 — Sửa lỗi chặn nghiệp vụ (Critical): 3–4 buổi
| Thứ tự | Mục | File chính |
|---|---|---|
| 1.1 | A1: mở quyền duyệt/từ chối cho admin, check quyền theo lớp cho lecturer | `TopicRegistrationService.php` |
| 1.2 | A2 + A3: fix tạo/sửa đề tài cho admin + hiển thị đúng giảng viên | `TopicController.php`, `topics/*.blade.php` |
| 1.3 | A4: lọc đề tài SV theo `class_id` thay vì `subject_id` | `UserDashboardController.php` |

### Giai đoạn 2 — Dữ liệu & vai trò (High): 4–5 buổi
| Thứ tự | Mục | Ghi chú |
|---|---|---|
| 2.1 | B1 + B2: bỏ role 'leader', derive leader động; "đã có nhóm" tính theo lớp | Ảnh hưởng rộng nhất — làm sớm vì chặn nhiều luồng (mời, hiển thị quản lý SV) |
| 2.2 | B4 + B6 + B7: môn học (credits, tự sinh mã, khóa sửa mã, flash xóa) | Có migration mới |
| 2.3 | B5: tự sinh `class_code` cho lớp học phần | Cả luồng admin + lecturer |
| 2.4 | B3: sync lớp + dọn dữ liệu nhóm khi SV rời lớp | Kèm C4 (searchable class picker) |

### Giai đoạn 3 — Trải nghiệm Admin (Medium): 3–4 buổi
| Thứ tự | Mục |
|---|---|
| 3.1 | C1 (khóa admin) + C2 + C3 (form mật khẩu, hiển thị lỗi) |
| 3.2 | C5 (filter danh sách đăng ký) + C6 (multi-filter lớp học phần) |
| 3.3 | C7 + C8 (gửi email / reset mật khẩu) — sau khi chốt policy + SMTP |

### Giai đoạn 4 — Chat, Nhóm & dữ liệu test: 3–4 buổi
| Thứ tự | Mục |
|---|---|
| 4.1 | D1: debug chat (Reverb/Echo) — chạy `php artisan reverb:start`, kiểm tra `.env`, console |
| 4.2 | D2: tìm nhóm theo lớp |
| 4.3 | D3 + D4 + D5: seeders/factories dữ liệu test; chốt nghiệp vụ khóa lớp + enforce |
| 4.4 | D6 + D7: cài đặt (tùy quyết định) + fix giao diện thông báo |

### Giai đoạn 5 — Kiểm thử & đóng bug: 1–2 buổi
1. `php artisan migrate` (các migration mới), `php artisan test` (Pest có sẵn trong project).
2. Test thủ công các luồng chính: login 3 role → tạo đề tài → SV join lớp/nhóm → nhóm đăng ký đề tài → duyệt/từ chối (cả admin & GV) → chat → thông báo → import/export.
3. Cập nhật lại `bug-report.xlsx`: cột G "Quyết định sửa" + cột F "Check task" + đánh dấu dòng Giáo viên (38–56).
4. Kiểm thử song song role Giáo viên (Excel chưa đánh giá role này).

---

## 4. Điểm cần quyết định trước khi bắt đầu

| # | Câu hỏi | Đề xuất mặc định nếu không phản hồi |
|---|---|---|
| 1 | Cột G "Quyết định sửa" trong Excel — duyệt "Sửa/Không sửa" từng dòng? | Sửa hết các mục A→D |
| 2 | C7 (gửi email): SMTP nào? | `MAIL_MAILER=log` trước, up SMTP thật sau |
| 3 | C8 (reset mật khẩu): chính sách nào — mật khẩu tạm qua email hay mật khẩu mặc định? | Mật khẩu tạm + bắt buộc đổi lần đầu |
| 4 | D6 (Cài đặt): làm nghiệp vụ gì hay tạm ẩn menu? | Tạm ẩn mục menu |
| 5 | B1 (role leader): bỏ hẳn role 'leader' trong DB, derive từ `groups.leader_id`? | Đồng ý — đơn giản hóa, đúng nghiệp vụ |
| 6 | D4 (khóa lớp): nghiệp vụ khóa = chặn join-by-code + chặn đăng ký đề tài? | Đồng ý — đề xuất trên |

---

## 5. Bảng theo dõi tiến độ

Cập nhật trạng thái tại đây sau mỗi mục hoàn thành (`⬜ Chưa → 🔧 Đang → ✅ Xong`).

| Mã | Lỗi | Ưu tiên | Giai đoạn | Trạng thái | Ghi chú |
|---|---|---|---|---|---|
| A1 | R17 — Admin không duyệt/từ chối được đăng ký | Critical | 1 | ✅ 13/09 | Role check trong `TopicRegistrationService` — đã thêm `canReview()` |
| A2 | R6 — Admin không tạo được đề tài | Critical | 1 | ✅ 13/09 | Admin lấy toàn bộ lớp; bỏ chặn contains(); thêm select lớp vào view edit |
| A3 | R4 — Edit đề tài: sai tên GV + lỗi id | Critical | 1 | ⬜ | `topics/edit.blade.php` dòng 52 |
| A4 | R57 — Đề tài SV sai lớp | Critical | 1 | ⬜ | Lọc `subject_id` → đổi `class_id` |
| B1 | R71 — Role 'leader' gây nhầm | High | 2 | ⬜ | Cần migration + dọn check |
| B2 | R71 — `is_have_group` chặn đa nhóm | High | 2 | ⬜ | Tính theo lớp |
| B3 | R13 — Rời lớp còn trong nhóm | High | 2 | ⬜ | Sync + cleanup nhóm |
| B4 | R30 — Môn học: mã tự sinh + credits | High | 2 | ⬜ | Cần migration `credits` |
| B5 | R28 — Lớp: tự sinh mã | High | 2 | ⬜ | Admin store thiếu `class_code` |
| B6 | R31 — Khóa sửa mã môn | High | 2 | ⬜ | |
| B7 | R32 — Flash khi xóa môn thất bại | High | 2 | ⬜ | |
| C1 | R19 — Chặn khóa admin | Medium | 3 | ⬜ | |
| C2 | R22 — Form mật khẩu admin | Medium | 3 | ⬜ | |
| C3 | R34 — Hiện lỗi đổi mật khẩu | Medium | 3 | ⬜ | |
| C4 | R13-CT — Searchable class picker | Medium | 2/3 | ⬜ | Làm cùng B3 |
| C5 | R16 — Filter danh sách đăng ký | Medium | 3 | ⬜ | |
| C6 | R24 — Multi-filter lớp học phần | Medium | 3 | ⬜ | |
| C7 | R14 — Gửi email | Medium | 3 | ⬜ | Chờ quyết định #2 |
| C8 | R15 — Reset mật khẩu | Medium | 3 | ⬜ | Chờ quyết định #3 |
| D1 | R67 — Chat không gửi được | High | 4 | ⬜ | Điều tra Reverb/Echo |
| D2 | R70 — Tìm nhóm theo lớp | Medium | 4 | ⬜ | |
| D3 | R10 — Seeder Import/Export | Medium | 4 | ⬜ | |
| D4 | R25 — Nghiệp vụ khóa lớp | Medium | 4 | ⬜ | Chờ quyết định #6 |
| D5 | R35 — Seeder thông báo | Medium | 4 | ⬜ | |
| D6 | R36 — Cài đặt | Low | 4 | ⬜ | Chờ quyết định #4 |
| D7 | R73 — Vỡ giao diện thông báo | Low | 4 | ⬜ | |

---

*Kế hoạch này dựa trên dữ liệu `bug-report.xlsx` + thẩm định code thực tế ngày 13/09/2026. Mọi thay đổi phạm vi cần cập nhật lại cả file này và Excel.*

---

## 6. Nhật ký cập nhật

### 13/09/2026 — ✅ A1 [R17] Admin không duyệt/từ chối được đăng ký đề tài

**File sửa:** `app/Services/TopicRegistrationService.php`

**Nội dung chỉnh sửa:**
1. **Thêm method private `canReview(Topic_requests $request, User $user): bool`** — hàm kiểm quyền dùng chung cho cả duyệt lẫn từ chối:
   - `admin`: được xử lý **mọi** yêu cầu (nguyên nhân gốc của bug là check cũ `role !== 'lecturer'` chặn cả admin).
   - `lecturer`: chỉ được xử lý yêu cầu thuộc lớp mình phụ trách (`topic.class_id` ∈ `user->classes`), tăng chặt quyền so với trước.
   - Role khác (student...): không có quyền.
2. **`approve()`**: thay `if ($lecturer->role !== 'lecturer')` bằng `if (!$this->canReview(...))` → **admin duyệt được**; bổ sung guard trả lỗi thân thiện khi đề tài của yêu cầu không còn tồn tại; cập nhật docblock.
3. **`reject()`**: thay check tương tự bằng `canReview()` → **admin từ chối được**; cập nhật docblock.

**Không đổi:** toàn bộ luồng transaction khi duyệt (gán đề tài cho nhóm, tự động từ chối yêu cầu khác cùng đề tài/cùng nhóm, gửi notification), controller `TopicRequestController` và view `topic_requests/index.blade.php` (đã cho phép cả lecturer + admin từ trước).

**Test bổ sung:** `tests/Feature/Services/TopicRegistrationServiceTest.php` — thêm 3 test:
1. `admin duyệt yêu cầu đăng ký đề tài thành công`
2. `admin từ chối yêu cầu đăng ký đề tài kèm lý do`
3. `giảng viên khác lớp không được duyệt/từ chối yêu cầu` (siết quyền theo lớp)

**Kết quả kiểm thử (13/09/2026):**
- `php -l`: không lỗi cú pháp.
- `TopicRegistrationServiceTest`: **19/19 pass (40 assertions)** — gồm 16 test cũ (không hồi quy) + 3 test mới.
- Toàn bộ suite: **77 test pass (152 assertions)**.

**Ghi chú môi trường:** `phpunit.xml` cấu hình test trỏ tới MySQL local `127.0.0.1:3306`/`team_assign_test` (khác `.env` đang dùng MySQL cloud Aiven). Đã khởi động MySQL 8.4.3 portable của Laragon (`bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe --defaults-file=...\my.ini`, data dir `laragon\data\mysql-8.4`) để chạy test. **Tuyệt đối không chạy `migrate:fresh`/test trỏ vào DB cloud trong `.env`.**

**Hướng kiểm thử thủ công:** đăng nhập admin → Trang "Duyệt yêu cầu đăng ký đề tài" → nút **Duyệt** và **Từ chối** (modal nhập lý do) phải hoạt động; giảng viên chỉ thấy duyệt được yêu cầu thuộc lớp mình, báo lỗi quyền với lớp khác.

### 13/09/2026 — ✅ A2 [R6] Admin không tạo được Đề tài (dropdown lớp trống)

**File sửa:**
- `app/Http/Controllers/TopicController.php`
- `resources/views/topics/edit.blade.php`

**Nội dung chỉnh sửa:**
1. **`create()`**: admin → lấy `ClassSection::with('subject')->get()` (toàn bộ lớp); lecturer giữ nguyên `$user->classes->load('subject')` (thêm eager-load tránh N+1 khi render dropdown).
2. **`store()`**: thay check `$classIds->contains()` bằng điều kiện `role !== 'admin' && !contains(...)` → **admin được tạo đề tài cho mọi lớp**; phân tách nguồn `lecturer`: lecturer = tên đang đăng nhập, **admin = giảng viên phụ trách lớp** (`$class->lecturer?->name`) — tránh ghi nhầm tên admin làm giảng viên (một phần liên quan A3); eager-load `with('lecturers')`.
3. **`edit()`**: giống `create()` — admin lấy toàn bộ lớp (đã eager-load subject).
4. **`update()`**: bỏ chặn `contains()` cho admin → admin được chuyển đề tài sang lớp bất kỳ; subject_id tự cập nhật theo lớp mới, lecturer giữ nguyên.
5. **`getByClass()`** (AJAX): lecturer chỉ xem lớp mình phụ trách; admin xem mọi đề tài của lớp; role khác trả 403 (trước đây admin bị 403 do `$user->classes` rỗng).
6. **Phát hiện quan trọng:** `topics/edit.blade.php` **không có trường select lớp học phần** trong khi `update()` validate `class_id` bắt buộc → mọi submit sửa đề tài đều fail validation ("The class id field is required") — đây chính là **"Lỗi id"** ghi trong R4. Đã bổ sung dropdown lớp vào form sửa (option selected theo `$topic->class_id`, hỗ trợ `old()`), đồng bộ style với form thêm.

**Test bổ sung:** `tests/Feature/TopicControllerTest.php` (mới) — 5 test:
1. `admin thấy danh sách lớp học phần trong form thêm đề tài`
2. `admin tạo được đề tài cho lớp bất kỳ và ghi đúng giảng viên phụ trách lớp`
3. `admin sửa được đề tài và chuyển đề tài sang lớp khác`
4. `admin thấy danh sách lớp học phần trong form sửa đề tài`
5. `giảng viên khác lớp không tạo được đề tài cho lớp không phụ trách`

**Kết quả kiểm thử (13/09/2026):**
- `php -l`: không lỗi cú pháp.
- `TopicControllerTest`: **5/5 pass (17 assertions)**.
- Toàn bộ suite: **82 test pass (169 assertions)** — không hồi quy.

**Còn lại liên quan A3 [R4]:** view `topics/edit.blade.php` vẫn hiển thị `{{ Auth::user()->name }}` làm "Giảng viên hướng dẫn" (sai với `$topic->lecturer`) — sẽ sửa trong A3.
