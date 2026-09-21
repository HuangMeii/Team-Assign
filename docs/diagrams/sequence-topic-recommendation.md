# Sequence — Gợi ý đề tài theo ngữ nghĩa (semantic recommendation)

> Chức năng #18 · `POST /api/recommend` · service AI `AI-Services/topic-recommender` (:8891)

```mermaid
sequenceDiagram
    autonumber
    actor SV as Sinh viên
    participant UI as Blade + fetch<br/>(topic-recommender panel)
    participant CT as TopicRecommendationController
    participant RS as TopicRecommendationService
    participant ES as TopicEmbeddingService
    participant DB as MySQL<br/>(topics + topic_embeddings)
    participant PY as FastAPI :8891<br/>vietnamese-sbert

    SV->>UI: Nhập mô tả ("làm web quản lý thư viện...")
    UI->>CT: POST /api/recommend {query, class_id?, only_available, top_k}
    Note over CT: middleware auth + validate<br/>(query 3..1000 ký tự)
    CT->>CT: allowedClassIds() — SV chỉ lớp mình tham gia<br/>lớp khác ⇒ 403

    CT->>RS: recommend(classIds, query, topK, onlyAvailable)
    RS->>DB: Topics whereIn class_id (+ only_available)<br/>limit max_candidates (500)
    DB-->>RS: danh sách đề tài ứng viên

    RS->>ES: ensureFor(topics)
    ES->>DB: đọc topic_embeddings theo topic_id + model
    DB-->>ES: vector đã lưu (nếu còn khớp content_hash)
    Note over ES: CHỈ đề tài thiếu vector /<br/>lệch content_hash mới cần embed
    ES->>PY: POST /embed {texts} (batch 32)
    PY-->>ES: vector 768d (L2-normalized)
    ES->>DB: upsert topic_embeddings<br/>(model, dim, content_hash, embedding base64)

    RS->>PY: POST /recommend {query, topics:[{id, embedding|text}]}
    Note over PY: embed QUERY (cache sha1)<br/>cosine = dot product<br/>sort desc → top_k
    PY-->>RS: {results:[{id, score, rank}], embedded:{...}}

    RS->>DB: lưu vector server vừa embed hộ (nếu có)
    RS->>RS: map topic_id → Topics + similarity/percent
    RS-->>CT: {ok:true, results}
    CT-->>UI: JSON {ok, results, meta}
    UI-->>SV: Top 5 card + "% phù hợp" + badge Còn trống/Đã có nhóm
```

## Ghi chú thiết kế

| Điểm | Chi tiết |
|---|---|
| **Không embedding lại đề tài** | Vector nằm ở `topic_embeddings`; lần gợi ý thứ 2 trở đi chỉ embed câu truy vấn (bước 12–13 bị bỏ qua) |
| Đổi nội dung đề tài | `content_hash` đổi ⇒ chỉ đề tài đó được embed lại (bước 10–13) |
| Đổi model | Đổi `TOPIC_RECOMMENDER_MODEL_TAG` ⇒ mọi đề tài được embed lại 1 lần (`php artisan topics:embed --force`) |
| Fail-open | `:8891` tắt/lỗi ⇒ service trả `ok:false` + message, HTTP **200** (UI hiện cảnh báo, không vỡ trang) |
| Giới hạn phạm vi | Sinh viên/giảng viên: chỉ lớp trong `user_classes`; admin: mọi lớp (hoặc 1 lớp nếu truyền `class_id`) |
| Ngưỡng & top_k | `config/recommender.json` (Python đọc) + `TOPIC_RECOMMENDER_TOP_K/MIN_SCORE` (Laravel) |
