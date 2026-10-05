# 07 — Thông báo & hạ tầng Laravel

[← Mục lục](README.md) · Bảng: `notifications` + 7 bảng hạ tầng
(`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`)

---

## 1. `notifications` — thông báo trong app

Model: `App\Models\Notifications` · PK: `notification_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `notification_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `user_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người nhận |
| `type` | varchar(255) | NN | | `topic_request`, `join_request`, `invite`, … |
| `title` | varchar(255) | NN | | tiêu đề |
| `message` | text | NN | | nội dung |
| `data` | text | NULL | | dữ liệu phụ dạng **JSON** (lưu text, không phải kiểu `json`) |
| `url` | varchar(255) | NULL | | link tới hành động |
| `is_read` | tinyint(1) | NN | default `0` | đã đọc |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| | | | **IX** `notifications_user_id_is_read_index (user_id, is_read)` | badge chuông |

**Cơ chế badge:** `NotificationService::create()` tăng `users.unread_notifications`;
`markAsRead()` / `markAllAsRead()` reset về 0.

---

## 2. Bảng hạ tầng Laravel

### 2.1. `sessions` — ⚠ không được dùng

| Cột | Kiểu dữ liệu | Null | Ràng buộc |
|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment |
| `created_at` | timestamp | NULL | |
| `updated_at` | timestamp | NULL | |

`.env` đặt `SESSION_DRIVER=file` ⇒ bảng này **không được đọc/ghi**. Bảng hiện tại **không phải**
bảng session chuẩn của Laravel (thiếu `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`);
nếu chuyển sang `SESSION_DRIVER=database` phải tạo lại bảng đúng chuẩn.

### 2.2. `cache` — cache store (đang dùng: `CACHE_STORE=database`)

| Cột | Kiểu dữ liệu | Null | Ràng buộc |
|---|---|---|---|
| `key` | varchar(255) | NN | **PK** |
| `value` | mediumtext | NN | |
| `expiration` | int | NN | UNIX timestamp hết hạn |

### 2.3. `cache_locks` — khóa cache nguyên tử

| Cột | Kiểu dữ liệu | Null | Ràng buộc |
|---|---|---|---|
| `key` | varchar(255) | NN | **PK** |
| `owner` | varchar(255) | NN | |
| `expiration` | int | NN | |

### 2.4. `jobs` — hàng đợi (đang dùng: `QUEUE_CONNECTION=database`)

| Cột | Kiểu dữ liệu | Null | Ràng buộc |
|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment |
| `queue` | varchar(255) | NN | **IX** `jobs_queue_index` |
| `payload` | longtext | NN | |
| `attempts` | tinyint unsigned | NN | |
| `reserved_at` | int unsigned | NULL | |
| `available_at` | int unsigned | NN | |
| `created_at` | int unsigned | NN | |

### 2.5. `job_batches` — lô job

| Cột | Kiểu dữ liệu | Null | Ràng buộc |
|---|---|---|---|
| `id` | varchar(255) | NN | **PK** |
| `name` | varchar(255) | NN | |
| `total_jobs` | int | NN | |
| `pending_jobs` | int | NN | |
| `failed_jobs` | int | NN | |
| `failed_job_ids` | longtext | NN | |
| `options` | mediumtext | NULL | |
| `cancelled_at` | int | NULL | |
| `created_at` | int | NN | |
| `finished_at` | int | NULL | |

### 2.6. `failed_jobs` — job thất bại

| Cột | Kiểu dữ liệu | Null | Ràng buộc |
|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment |
| `uuid` | varchar(255) | NN | **UQ** `failed_jobs_uuid_unique` |
| `connection` | text | NN | |
| `queue` | text | NN | |
| `payload` | longtext | NN | |
| `exception` | longtext | NN | |
| `failed_at` | timestamp | NN | default `CURRENT_TIMESTAMP` |

### 2.7. `migrations` — sổ migration của Laravel

| Cột | Kiểu dữ liệu | Null | Ràng buộc |
|---|---|---|---|
| `id` | int unsigned | NN | **PK**, auto_increment |
| `migration` | varchar(255) | NN | tên file migration |
| `batch` | int | NN | lần chạy `migrate` thứ mấy |

---

Quay lại: [Mục lục](README.md) · [08 — Khóa ngoại](08-foreign-keys.md) ·
[09 — Liên kết](09-relations.md)
