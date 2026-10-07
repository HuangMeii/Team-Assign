# L05 — Trang thai Dang hoc / Da roi lop (mo rong `user_classes`)

> Thu muc: `docs/Final-Bug-Fixes/` — ke hoach sua sau (chua trien khai code).
> Chot yeu cau: **1a** (mo rong `user_classes`, khong tao bang moi) + **3** (giu hien thi xam phia Admin/GV).
> Muc **2** (quy tac leader/chuyen nhom khi roi lop): **DE DO — chua chot, chua trien khai**.

---

## 1. Hien trang (da khao sat code)

- Bang noi duy nhat `user_classes`: `id, user_id, class_id, created_at, updated_at` + UQ `(user_id, class_id)`.
  Nguon: `docs/diagrams/db-schema/02-subjects-classes.md`, `App\Models\user_class` (rong), `ClassSection::students()/lecturers()/users()`, `User::classes()`.
- **Chua co** cot trang thai cho tung `(user_id, class_id)`.
- Xoa sinh vien hien tai = `detach()` xoa cung dong pivot:
  - `ClassSectionController::removeStudent()` (~dong 258-259).
  - `ClassSectionController::lecturerClassesRemoveStudent()` (~dong 601-602).
- Them sinh vien: `addStudents()` / `lecturerClassesAddStudents()` dung `$class->students()->syncWithoutDetaching()` trong khi `students()` co `->where('role','student')`.
- Sinh vien tu join: `ClassJoinController::joinByCode()` dung `$user->classes()->attach()` (khong loc `role`).
- Don nhom khi roi lop: `GroupService::removeUserFromClassGroups()` + `disbandOrTransferLeadership()`.
- Phia sinh vien (`UserDashboardController`): `classes, classDetail, getUserGroups, getGroupTopics, topics, group_topics, createGroupForm/storeGroup` chi kiem tra dong `user_classes` ton tai — khong co khai niem "da roi".

## 2. Loi da bao

1. **Khong xoa / them sinh vien ra khoi lop duoc.**
   - Nghi ngo chinh: `detach()/syncWithoutDetaching()` goi tren relation `students()` da bi loc `->where('role','student')` -> SQL sai / xoa 0 dong / exception -> roi vao `catch` bao loi.
   - Can kiem chung bang log `storage/logs/laravel.log` + test tay o Act trien khai.
2. **Sinh vien da join nhom, cho roi lop thi nhom van con sinh vien do.**
   - Co the do loi (1) khien transaction do dang, hoac view nhom khong loc trang thai lop.
   - `disbandOrTransferLeadership()` khi promote leader moi khong xoa dong pivot cua leader moi -> dem trung (`memberCount = members + 1`).

- Sinh vien `left`:
  - Phia **sinh vien**: an toan bo nhom, de tai, loi moi, yeu cau, chat nhom thuoc `class_id` do.
  - Phia **Admin/GV**: van hien thi, render **xam + badge "Da roi"** + bo loc trang thai.

## 4. Pham vi sua (khi trien khai)

### 4.1 Migration + Model

- Migration `alter user_classes`: them `status`, `left_at`, index `(class_id, status)`.
- `App\Models\user_class`: `fillable`, `casts`, const `STATUS_STUDYING`, `STATUS_LEFT`, scope `studying()/left()`.
- `ClassSection::students()/users()/lecturers()`, `User::classes()`: them `withPivot('status','left_at')`; tach 2 huong doc:
  - Luong SV (mac dinh): chi `status='studying'`.
  - Luong Admin/GV quan ly: xem tat ca + badge.

### 4.2 Sua loi them/xoa sinh vien

- `ClassSectionController::addStudents / removeStudent / lecturerClassesAddStudents / lecturerClassesRemoveStudent`:
  - Bo `students()->detach()/syncWithoutDetaching()` bi loc `role`.
  - Chuyen sang thao tac tren `users()` khong loc / `DB::table('user_classes')` + `DB::transaction` (gom cap nhat `status` + don nhom).
  - `add`: khoi phuc `left -> studying` truoc, chi insert khi chua co dong.
  - `remove`: `UPDATE -> left` thay vi `detach`.
- `ClassJoinController::joinByCode()`:
  - Neu da co dong `left` -> khoi phuc `studying`, bao "Chao mung quay lai lop".
  - Neu da `studying` -> giu canh bao "da tham gia".

### 4.3 An du lieu lop da roi (phia sinh vien)

Ap dieu kien `user_classes.status = 'studying'` tai:

- `UserDashboardController`: `index/getUserGroups/getGroupTopics/classes/classDetail/topics/group_topics/createGroupForm/storeGroup/applyTopicFilters`.
- `GroupService`: `hasGroupInClass/createGroupByStudent/availableUsersForGroup/isInGroup`.
- `InvitationService`: `sendInvite/acceptInvite` (chan moi/nguoi da roi).
- `TopicRegistrationService`: chan dang ky/duyet de tai cho nhom co thanh vien da roi (quy tac chi tiet cho chot muc 2).
- `ClassStreamService` / `ClassStreamController` / `Presence`: SV `left` khong thay bang tin lop.
- `GroupsChatController` / `DirectChatController`: chan gui/xem chat nhom cua lop da roi.

### 4.4 View

- `resources/views/admin/classes/show.blade.php`:
  - Cot "Trang thai": `Dang hoc` (xanh) / `Da roi` (xam).
  - Nut `Roi lop` <-> `Them lai`; bo loc `Tat ca / Dang hoc / Da roi`.
  - Dong `left`: class `opacity-60 bg-gray-50`.
- `resources/views/lecturer/classes/show.blade.php`: tuong tu ban admin.
- View SV (`user.dashboard`, `user.topics`, `user.group_topics`, `user.classes`, `user.class_detail`): khong render lop `left` va nhom/de tai thuoc lop do.

## 5. Muc 2 — DE DO (chua trien khai)

> Theo yeu cau: "so 2 de do" — phan quy tac leader/nhom khi tat ca roi lop (giu lich su de tai, `leader_id` nullable, dong bang nhom, dem active qua `user_classes`) **tam hoan**, se quay lai sau khi chot.
> Khi trien khai muc 5 phai ra soat null-safe vi hien code gia dinh `groups.leader_id` luon non-null (`isLeader`, chat, dang ky de tai).

## 6. Docs + Test (khi trien khai)

- Cap nhat: `docs/diagrams/db-schema/02-subjects-classes.md`, `docs/FEATURE_STATUS.md`, test-case `02`, `04`.
- Test moi (vi du `tests/Feature/ClassMembershipStatusTest.php`):
  1. Roi lop -> `status='left'`, `left_at` not null; Admin/GV thay dong xam.
  2. Them lai -> `status='studying'`, `left_at` null; khong sinh dong trung.
  3. SV `left` khong thay nhom/de tai/loi moi cua lop do; lop khac khong anh huong.
  4. Join bang ma khi dang `left` -> khoi phuc `studying`.
  5. Xoa SV khong thuoc lop -> warning, khong doi DB.
  6. Them trung -> warning, khong trung dong.
- Lenh kiem chung:
  - `php artisan migrate`
  - `php artisan test tests/Feature/LecturerClassTest.php tests/Feature/StudentClassDetailTest.php tests/Feature/ClassJoinTest.php`

## 7. Rui ro / ghi chu

- Moi query dem si so (`withCount('students')`), moi nhom, tao nhom, goi y de tai deu phai loc `studying` — de sot, can grep toan repo `user_classes|->classes|students()` sau khi sua.
- Backward-compat: dong `user_classes` cu mac dinh `studying` nen khong vo du lieu hien tai.
- Log kiem chung loi (1): `storage/logs/laravel.log` cac dong `Error adding/removing student`.


## 3. De xuat chot (1a)

**Khong tao bang `chi_tiet_lop_hoc` moi** — vi `user_classes` chinh la bang chi tiet `(class_id, user_id)`.
Mo rong `user_classes`:

- `status ENUM('studying','left') DEFAULT 'studying'` — trang thai tung SV trong tung lop.
- `left_at NULL` — moc roi lop; `created_at` giu lam moc tham gia.
- Index them: `(class_id, status)` de loc nhanh danh sach lop.
- "Xoa khoi lop" = `UPDATE status='left', left_at=now()` (xoa mem), **khong** `detach()`.
- "Them lai" = neu da co dong `left` -> `UPDATE status='studying', left_at=NULL`; chi `attach()` khi chua co dong nao (tranh vo UQ).
