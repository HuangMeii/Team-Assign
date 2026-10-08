# L10 — Admin/GV xoa mem nhom + nha de tai + SV tao/tham gia nhom moi

> Thu muc: `docs/Final-Bug-Fixes/` — **TRANG THAI: DA TRIEN KHAI (2026-10-08).**
> Chot: admin/GV duoc xoa (GV chi lop minh); **xoa mem** (`deleted_at` + `SoftDeletes`); SV duoc tao/tham gia nhom moi; **de tai ve lai chua dang ky**.

## 1. Hien trang (da khao sat code)

- `GroupController` chi co `index()/show()` (doc); comment dong 16-23 ghi "Khong the huy nhom sau khi tao → khong co chuc nang xoa nhom"; route `groups.*` chi co 2 GET (`groups.index`, `groups.show`) → thuc te khong xoa duoc tren web.
- `GroupService::destroy()` (dong 396-417, cho admin/lecturer, chan nhom da gan de tai) la **code chet** — khong controller/route/view/test nao goi; va la **xoa cung** (`$group->delete()` + xoa invites/joinRequests/topicRequests + `members()->detach()`).
- Model `Groups` chi `use HasFactory` (**khong** `SoftDeletes`); bang `groups` khong co `deleted_at`/co xoa mem (khac `users` co `is_deleted + deleted_at`).
- FK `chat_messages.group_id`, `topic_requests.group_id`, `class_posts.group_id`... da so `CASCADE` → xoa cung keo theo mat chat/bai bang tin (ly do yeu cau de tai cam xoa).
- Logic "1 nhom / 1 lop" (`hasGroupInClass`, `availableUsersForGroup`, `createGroupByStudent`, `InvitationService`, `UserDashboardController`) query qua `group_members + groups.leader_id`, chua loai truong hop nhom xoa.

## 2. Quyet dinh da chot voi user

- Admin + GV duoc xoa (GV chi nhom thuoc lop minh phu trach); SV khong co nut xoa.
- Xoa mem: them `deleted_at`, an khoi danh sach mac dinh, giu du lieu (thanh vien, de tai lich su, chat, bang tin) de khoi phuc/doi chieu.
- Sau xoa: SV cua nhom da xoa duoc coi la "chua co nhom" trong lop → duoc tao nhom moi hoac tham gia nhom khac.
- De tai cua nhom bi xoa **tro lai chua dang ky** (nhom khac dang ky lai duoc); nhom khoi phuc ve sau o trang thai **chua co de tai** (khong tu lay lai de tai cu).

## 3. Ke hoach trien khai

### 3.1 DB + Model

- Migration `add_deleted_at_to_groups_table` (+ index `deleted_at`); `Groups` them `use SoftDeletes`.
- Eloquent mac dinh loai `trashed`; trang admin them tab "Da xoa / Khoi phuc" (`onlyTrashed`).

### 3.2 destroy() → xoa mem kem nha de tai (1 transaction)

- `GroupService::destroy()` doi thanh soft-delete: `$group->delete()` (luc nay la mem) + giu invites/joinRequests/topicRequests/group_members/chat de doi chieu (khong xoa cung nhu cu).
- Nha de tai trong cung transaction: `groups.topic_id → NULL`; `topic_requests` cua nhom (Pending/Accepted) chuyen sang dong (`Rejected`/`Cancelled` + ghi chu "nhom da xoa"); `topics.assigned_group_id` (neu dung) go; nhom khac dang ky lai binh thuong.
- Them `restore()` (khoi phuc ve chua co de tai) + `forceDelete()` (xoa cung that, chi admin, chi khi nhom khong de tai/chat quan trong).
- Quyet dinh bo sung (khi trien khai): nhom **da co de tai duoc duyet** cho phep xoa mem theo chot "de tai ve lai chua dang ky" (khac chan cung cu).

### 3.3 Route + View + phan quyen

- Route: `DELETE groups/{id}` (xoa mem) + `POST groups/{id}/restore` + `DELETE groups/{id}/force` (middleware `admin`/`lecturer` + check lop phu trach voi GV).
- View `groups.index/show`: nut Xoa/Khoi phuc theo quyen; GV khac lop → 403.

### 3.4 Logic "1 nhom / 1 lop" sau xoa mem

- `hasGroupInClass()`, `availableUsersForGroup()`, `createGroupByStudent()`, `InvitationService`, `UserDashboardController::createGroupForm/myGroups/getGroupTopics` phai loai nhom `trashed` (`withoutTrashed` / `whereNull(deleted_at)` trong `whereHas`) — neu khong SV van bi tinh "da co nhom".
- `memberCount/isFull/isEligibleForRegistration`, goi y de tai, chat nhom, bang tin: nhom da xoa → chan gui tin/dang ky moi, nhung van doc duoc lich su.

## 4. Test + Docs + Verify (khi trien khai)

- Test moi `tests/Feature/GroupSoftDeleteTest.php`:
  1. Admin/GV xoa mem (GV khac lop → 403); nhom bien mat khoi danh sach nhung DB con.
  2. De tai ve lai chua dang ky (nhom khac dang ky duoc).
  3. SV tao/tham gia nhom moi duoc.
  4. Khoi phuc ve chua de tai; force-delete chi admin.
  5. Danh sach an trashed + tab Da xoa.
- Docs: sua comment "khong xoa nhom" trong `GroupController`, `2.2.2.12/2.2.2.21`, `FEATURE_STATUS #8`, test-case `05` (TC-STU-06 + them TC xoa mem/khoi phuc), ERD `groups.deleted_at`.
- Verify: `php artisan test` nhom group + topic registration.

## 5. Rui ro / ghi chu

- FK CASCADE hien tai chi anh huong khi force-delete; xoa mem khong mat chat/bai bang tin.
- Lich su `topic_requests` cu giu lai (khong xoa cung) de doi chieu tranh chap de tai.
- Khoi phuc khong tu lay lai de tai cu (tranh tranh chap neu de tai da co nhom moi).

## 6. Ket qua trien khai (2026-10-08)

### 6.1 DB + Model

- Migration `2026_10_08_000003_add_deleted_at_to_groups_table` (đã chạy `php artisan migrate --force`): `groups.deleted_at` (SoftDeletes).
- `App\Models\Groups`: thêm `use SoftDeletes` ⇒ MỌI truy vấn `Groups::…` và các relation
  (`group_members.group`, `chat_messages.group`, `topic_requests.group`…) **tự động loại nhóm đã xóa**,
  nên logic “1 nhóm / 1 lớp” (`hasGroupInClass`, `availableUsersForGroup`, `createGroupByStudent`, `InvitationService`,
  `UserDashboardController`) đúng ngay mà không phải sửa từng chỗ.

### 6.2 Service (`GroupService`)

| Hàm | Nội dung |
|-----|---------|
| `destroy()` | XÓA MỀM + `releaseTopic()` + chuyển lời mời/yêu cầu `Pending` → `Expired` (GIỮ dòng). **Bỏ chặn cứng “nhóm đã gán đề tài”** theo chốt (đề tài được nhả về chưa đăng ký). GV chỉ xóa được nhóm thuộc lớp mình phụ trách |
| `releaseTopic()` *(mới, private)* | `groups.topic_id → NULL`; `topics.assigned_group_id` của nhóm → NULL; `topic_requests` (`Pending`/`Accepted`) → `Cancelled` + `rejection_reason = 'Nhóm đã bị xóa…'` |
| `restore()` *(mới)* | Khôi phục nhóm + `topic_id → NULL` (KHÔNG tự lấy lại đề tài cũ) + tính lại `status` theo số thành viên |
| `forceDelete()` *(mới)* | Chỉ Admin: xóa cứng + dọn invites/join_requests/topic_requests + detach thành viên (chat/bảng tin xóa theo FK CASCADE) |
| `denyGroupManagement()` *(mới, private)* | Admin hoặc GV phụ trách lớp ⇒ cho phép; ngược lại trả lỗi (GV khác lớp / sinh viên) |

### 6.3 Route + View

- `routes/web.php`: **thêm `middleware(['auth'])`** cho nhóm route `groups.*` (**lỗi cũ**: thiếu middleware ⇒ khách
  vào `/groups` gây lỗi 500 vì `Auth::user()` là null) + 3 route mới: `DELETE groups/{id}` (`groups.destroy`),
  `POST groups/{id}/restore` (`groups.restore`), `DELETE groups/{id}/force` (`groups.force-delete`).
- `GroupController`: thêm `destroy()/restore()/forceDestroy()`; `index()` hỗ trợ tab `?trashed=1` (chỉ Admin/GV).
- `resources/views/groups/index.blade.php`: tab “Đang hoạt động | Đã xóa” + nút Xóa/Khôi phục/Xóa vĩnh viễn (ẩn với sinh viên).
- `resources/views/groups/show.blade.php`: nút “Xóa nhóm” cho Admin/GV.

### 6.4 Kiem chung

```powershell
php artisan test tests/Feature/GroupSoftDeleteTest.php   # 8 passed
php artisan test                                         # 400 passed / 0 failed
```

- Test mới `tests/Feature/GroupSoftDeleteTest.php` (8 case): xóa mềm ẩn khỏi danh sách nhưng giữ dữ liệu; GV phụ trách
  xóa được / GV khác lớp bị chặn / sinh viên bị chặn; nhả đề tài + hủy lời mời-yêu cầu treo (giữ lịch sử); SV nhóm đã xóa
  tạo được nhóm mới; khôi phục không tự lấy lại đề tài; xóa vĩnh viễn chỉ Admin; `/groups` yêu cầu đăng nhập.
- Test cũ `GroupServiceTest > không thể xóa nhóm đã được gán đề tài` được **cập nhật** theo chốt mới
  (giờ xóa được — đổi hành vi có chủ đích, không phải regression).
