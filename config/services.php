<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Server kiểm duyệt ảnh Cloud Vision (project ImageCommentClassification, node server.js).
    'vision' => [
        'url' => env('VISION_MODERATION_URL', 'http://localhost:8888'),
    ],

    // Server phân loại text PhoBERT (violation-detection/python/app.py, port 8889).
    // mode: rules (mặc định, không gọi mạng) | model | hybrid (rule OR model).
    // Mọi lỗi/timeout đều fallback về rules => chat không bao giờ bị chặn/treo.
    'text_moderation' => [
        'url' => env('TEXT_MODERATION_URL'),
        'mode' => env('TEXT_MODERATION_MODE', 'rules'),
    ],

    // Server PhoBERT v2 phân loại XÚC PHẠM / NỘI DUNG NHẠY CẢM
    // (violation-detection/python/app_moderation.py, port 8890, model 5 nhãn multi-label).
    // Độc lập với text_moderation ở trên; cùng nguyên tắc fail-open về rules.
    'content_moderation' => [
        'url' => env('MODERATION_URL'),
        'mode' => env('MODERATION_MODE', 'rules'),
    ],

    // Chatbot trợ lý đề tài (Gemini API). Thiếu key => controller trả thông báo
    // thân thiện và component tự ẩn, KHÔNG lỗi 500.
    // LƯU Ý: key mới (dạng AQ...) chỉ gọi được model mới — gemini-2.5-* trả 404
    // "no longer available to new users". Mặc định: gemini-3.6-flash.
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent'),
    ],

    // Service AI GỢI Ý ĐỀ TÀI THEO NGỮ NGHĨA (embedding + cosine similarity).
    // Code serve: AI-Services/topic-recommender/app.py · port 8891 (xem AI-Services/README.md).
    // Vector của đề tài được LƯU vào bảng `topic_embeddings` ⇒ mỗi đề tài chỉ embedding MỘT LẦN;
    // các lần gợi ý sau chỉ sinh vector cho câu truy vấn.
    // Service tắt/lỗi ⇒ tính năng gợi ý báo "tạm thời không khả dụng", app vẫn chạy bình thường (fail-open).
    'topic_recommender' => [
        'url' => env('TOPIC_RECOMMENDER_URL', 'http://127.0.0.1:8891'),
        'enabled' => env('TOPIC_RECOMMENDER_ENABLED', false),
        // Nhãn model lưu ở cột topic_embeddings.model — ĐỔI model phải đổi tag rồi embed lại.
        'model_tag' => env('TOPIC_RECOMMENDER_MODEL_TAG', 'vietnamese-sbert'),
        'top_k' => env('TOPIC_RECOMMENDER_TOP_K', 5),
        'min_score' => env('TOPIC_RECOMMENDER_MIN_SCORE', 0.0),
        'max_candidates' => env('TOPIC_RECOMMENDER_MAX_CANDIDATES', 500),
        'timeout' => env('TOPIC_RECOMMENDER_TIMEOUT', 15),
        // Lần đầu của 1 lớp phải sinh vector cho đề tài chưa có ⇒ cho timeout dài hơn.
        'embed_timeout' => env('TOPIC_RECOMMENDER_EMBED_TIMEOUT', 120),
    ],

];
