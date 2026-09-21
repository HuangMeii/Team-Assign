# Sơ đồ kiến trúc triển khai

```mermaid
graph LR
    subgraph Browser
        UI[Blade + Vanilla JS<br/>Bootstrap 5]
    end

    subgraph "Laravel app (PHP 8.2, :8000)"
        R[routes/web.php] --> C[Controllers]
        C --> S1[TextModerationService]
        C --> S2[SensitiveModerationService]
        C --> S3[ImageModerationService]
        C --> S4[TopicRecommendationService<br/>+ TopicEmbeddingService]
        C --> CH[ChatbotController]
        C --> EV[Events: DirectMessageSent,<br/>NewChatMessage]
        Q[Queue/Broadcast driver reverb]
    end

    subgraph "AI servers (code: violation-detection/python · topic-recommender · weights: AI-Services/)"
        P1[FastAPI :8889<br/>app.py<br/>PhoBERT gian lận 2 nhãn<br/>softmax]
        P2[FastAPI :8890<br/>app_moderation.py<br/>PhoBERT moderation 5 nhãn<br/>multi-label sigmoid]
        P3[FastAPI :8891<br/>topic-recommender/app.py<br/>Vietnamese SBERT 768d<br/>cosine similarity]
        M1[(AI-Services/<br/>phobert-negative-classifier)]
        M2[(AI-Services/<br/>chat_moderation_model)]
        M3[(AI-Services/<br/>topic-recommender/model)]
    end

    subgraph "Node servers"
        N1[Node :8888<br/>Cloud Vision<br/>AI-Services/ImageCommentClassification]
        N2[Reverb :8080<br/>WebSocket real-time]
    end

    DB[(MySQL 8.4<br/>team_assign / team_assign_test<br/>+ topic_embeddings)]
    GM[Gemini API<br/>chatbot]

    UI -->|HTTP| R
    EV -->|WebSocket| N2 --> UI
    S1 -.timeout 3s, fail-open.-> P1
    S2 -.timeout 3s, fail-open.-> P2
    S3 -.fail-open.-> N1
    S4 -.timeout, fail-open.-> P3
    CH --> GM
    C --> DB
    P1 --> M1
    P2 --> M2
    P3 --> M3
    S1 --> TH[config/thresholds.json<br/>PHP + Python đọc chung]
    S2 --> TH
    P2 --> TH
    S4 --> RC[config/recommender.json<br/>PHP + Python đọc chung]
    P3 --> RC
```

## Nguyên tắc fail-open
- Mọi server phụ (8889, 8890, 8891, 8888, Reverb, Gemini) đều **không bắt buộc** để hệ thống hoạt động:
  chết/treo/timeout → tự fallback (kiểm duyệt về rules, gợi ý đề tài ẩn panel) hoặc bỏ qua,
  **không bao giờ chặn người dùng**.
- Model chạy CPU (không cần GPU); bật/tắt qua `.env` (`TEXT_MODERATION_MODE`, `MODERATION_MODE`,
  `VISION_MODERATION_URL`, `TOPIC_RECOMMENDER_ENABLED`).

## Nơi đặt code vs weights

| Phần | Vị trí |
|---|---|
| Code serve (FastAPI, PHP, Node) | trong repo: `violation-detection/python/`, `violation-detection/src/`, `app/Services/` |
| **Weights model kiểm duyệt** (2 × ~542 MB) | **ngoài repo**: `G:\MyApp\laragon\www\AI-Services\phobert-negative-classifier`, `...\chat_moderation_model` |
| **Service gợi ý đề tài** (code + docs + weights 515 MB + config) | **ngoài repo**: `G:\MyApp\laragon\www\AI-Services\topic-recommender\` (`app.py`, `README.md`, `MODEL_NOTES.md`, `config/recommender.json`, `model/`) |
| Node app Cloud Vision | **ngoài repo**: `G:\MyApp\laragon\www\AI-Services\ImageCommentClassification` |
| Bật / dừng cả 4 | `powershell -File G:\MyApp\laragon\www\AI-Services\start-servers.ps1` · `stop-servers.ps1` |

> Đường dẫn weights do env quyết định (`TEXT_MODEL_DIR`, `MODERATION_MODEL_DIR` trong `.env`)
> nên đổi chỗ model **không cần sửa code**. Chi tiết model + FAQ: `AI-Services/MODEL_INFO.md`.
