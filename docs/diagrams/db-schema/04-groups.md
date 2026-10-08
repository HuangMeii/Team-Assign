# 04 — Nhóm

[← Mục lục](README.md) · Bảng: `groups` · `group_members` · `invites` · `join_requests`

Luồng nghiệp vụ: sinh viên **tạo nhóm** (`groups` + dòng leader trong `group_members`) →
**mời** bạn cùng lớp (`invites`) hoặc **xin vào nhóm** (`join_requests`) → khi đủ thành viên
`groups.status = 'complete'` → nhóm **gửi yêu cầu nhận đề tài** (`topic_requests`) →
được duyệt thì ghi `groups.topic_id`.

---

## 1. `groups` — nhóm sinh viên

Model: `App\Models\Groups` · PK: `group_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `group_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `group_name` | varchar(255) | NN | | tên nhóm |
| `leader_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | nhóm trưởng (derive, **không** là role hệ thống) |
| `topic_id` | bigint unsigned | NULL | **FK** → `topics.topic_id` ON DELETE **SET NULL** | đề tài nhóm đã được duyệt |
| `class_id` | bigint unsigned | NULL | **FK** → `class_sections.class_id` ON DELETE **SET NULL** | lớp học phần của nhóm |
| `status` | enum('incomplete','complete') | NN | default `'incomplete'` | chưa đủ / đã đủ thành viên |
| `deleted_at` | timestamp | NULL | | **L10** — xóa MỀM (`SoftDeletes`); truy vấn mặc định loại nhóm đã xóa |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |

**Lưu ý:**

- Nhóm thuộc **lớp học phần** (`class_id`) ⇒ mỗi lớp/môn sinh viên có thể có 1 nhóm riêng
  (cờ `users.isHaveGroup` đã bị bỏ để cho phép điều này).
- Nhóm trưởng được xác định bằng `leader_id` (và dòng `group_members.role = 'leader'`).
- ⚠ DB **không** ràng buộc UNIQUE `(class_id, group_name)` — kiểm tra trùng tên nhóm nằm ở tầng code.

Quan hệ code: `Groups::leader()` (belongsTo users), `topic()`, `class()` (belongsTo),
`members()` (belongsToMany qua `group_members`, `withPivot('role','id')`, `withTimestamps()`),
`chatMessages()`, `invites()`, `joinRequests()`, `topicRequests()` (hasMany).

---

## 2. `group_members` — bảng nối Nhóm ↔ User (N–N) + vai trò trong nhóm

Model: `App\Models\Group_Members` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `group_id` | bigint unsigned | NN | **FK** → `groups.group_id` ON DELETE **CASCADE** | |
| `user_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | |
| `role` | enum('member','leader') | NN | default `'member'` | vai trò **trong nhóm** (khác `users.role`) |
| `created_at` | timestamp | NULL | | mốc tham gia nhóm (dùng cho bảng tin lớp + backfill) |
| `updated_at` | timestamp | NULL | | |
| | | | **UQ** `group_members_group_id_user_id_unique` `(group_id, user_id)` | |

> Đây là bảng quyết định "sinh viên đã có nhóm chưa" — `User::getHasGroupAttribute()` =
> `is_leader || Group_Members::where('user_id', ...)->exists()`.

---

## 3. `invites` — lời mời tham gia nhóm

Model: `App\Models\Invites` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `group_id` | bigint unsigned | NN | **FK** → `groups.group_id` ON DELETE **CASCADE** | nhóm mời |
| `invitedBy` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người gửi lời mời (tên cột camelCase) |
| `member_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người được mời |
| `status` | enum('Pending','Accepted','Rejected','Expired') | NN | default `'Pending'` | `Expired` = hết hiệu lực khi nhóm đủ thành viên |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |

⚠ DB **không** có UNIQUE `(group_id, member_id)` cho bảng này (khác `join_requests`).

---

## 4. `join_requests` — yêu cầu xin vào nhóm

Model: `App\Models\Join_requests` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `group_id` | bigint unsigned | NN | **FK** → `groups.group_id` ON DELETE **CASCADE** | nhóm được xin vào |
| `member_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người xin vào |
| `status` | enum('Pending','Accepted','Rejected','Expired') | NN | default `'Pending'` | |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| | | | **UQ** `join_requests_group_id_member_id_unique` `(group_id, member_id)` | |

> Badge "Yêu cầu" của trưởng nhóm đếm `join_requests` có `status = 'Pending'` **và**
> `created_at > users.join_requests_seen_at` (xem `User::getPendingJoinRequestsCountAttribute()`).

---

Xem tiếp: [05 — Bảng tin lớp](05-class-stream.md) · [08 — Khóa ngoại](08-foreign-keys.md) ·
[09 — Liên kết](09-relations.md)
