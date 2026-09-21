"""
FastAPI server phân loại text XÚC PHẠM / NỘI DUNG NHẠY CẢM (flag-only moderation)
bằng PhoBERT v2 đã fine-tune.

Model: RobertaForSequenceClassification, **5 nhãn, multi_label_classification**
  - 0 = profanity (chửi thề)
  - 1 = insult    (xúc phạm)
  - 2 = threat    (đe doạ)
  - 3 = dangerous (nguy hiểm: vũ khí, ma tuý...)
  - 4 = adult     (nội dung người lớn)
  Model KHÔNG có output "clean": clean = không nhãn nào >= ngưỡng.
  Một câu có thể ra NHIỀU nhãn cùng lúc (vd: ['insult', 'threat']).
  (thứ tự index đã kiểm chứng bằng probe thực tế trên model này;
   tên nhãn + danh sách nhãn tính là vi phạm đọc từ
   violation-detection/config/thresholds.json => đảo/tune nhãn không cần sửa code)

KHÁC model gian lận (2 nhãn -> softmax + POSITIVE_INDEX): model này là multi-label
nên phải dùng **sigmoid từng nhãn**, gắn cờ khi max(điểm các nhãn vi phạm) >= ngưỡng.

Thư mục model: env MODERATION_MODEL_DIR
  (mặc định G:\\MyApp\\laragon\\www\\AI-Services\\chat_moderation_model)

Contract trả về GIỐNG bản PHP rule-based nên Laravel/Controller/trang admin
không phải sửa:
  {is_violation, score, reasons, needs_review, skipped, model, labels}

Cài đặt + chạy (dùng chung conda env với server gian lận):
  cd g:\\MyApp\\laragon\\www\\Team-Assign\\violation-detection\\python
  G:\\MyApp\\miniconda3\\envs\\phobert\\python.exe -m uvicorn app_moderation:app --host 127.0.0.1 --port 8890

Nguyên tắc: server này CHỈ cho điểm để gắn cờ — không chặn gửi, không xoá gì.
Server tắt/lỗi thì Laravel tự fallback về rules (fail-open).
"""

from __future__ import annotations

import json
import os
import time
from pathlib import Path
from typing import Any, Dict, List, Optional

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

HERE = Path(__file__).resolve().parent
THRESHOLDS_PATH = HERE.parent / "config" / "thresholds.json"

DEFAULT_MODEL_DIR = r"G:\MyApp\laragon\www\AI-Services\chat_moderation_model"

MODEL_DIR = Path(os.getenv("MODERATION_MODEL_DIR", DEFAULT_MODEL_DIR))
MAX_LEN = int(os.getenv("MODERATION_MAX_LEN", "256"))
SEGMENT = os.getenv("MODERATION_SEGMENT", "raw").strip().lower()  # raw | pyvi
MODEL_TAG = os.getenv("MODERATION_TAG", f"phobert-moderation@{MODEL_DIR.name}")

DEFAULT_LABELS = {
    "0": "profanity",
    "1": "insult",
    "2": "threat",
    "3": "dangerous",
    "4": "adult",
}

app = FastAPI(title="Text sensitive/offensive moderation (PhoBERT v2)", version="1.0.0")

_tokenizer: Optional[Any] = None
_model: Optional[Any] = None
_segmenter: Optional[Any] = None
_load_error: Optional[str] = None


class PredictIn(BaseModel):
    text: str = ""


def _thresholds() -> Dict[str, Any]:
    """Đọc chung thresholds.json với PHP => một nguồn cấu hình duy nhất."""
    try:
        with THRESHOLDS_PATH.open("r", encoding="utf-8") as fh:
            data = json.load(fh)
            return data if isinstance(data, dict) else {}
    except Exception:
        return {}


def _label_map() -> Dict[int, str]:
    """{index: tên nhãn} — đọc từ thresholds.json, fallback bộ mặc định."""
    raw = _thresholds().get("moderation_labels")
    labels: Dict[int, str] = {}

    if isinstance(raw, dict):
        for key, value in raw.items():
            try:
                labels[int(key)] = str(value)
            except (TypeError, ValueError):
                continue

    if not labels:
        labels = {int(k): v for k, v in DEFAULT_LABELS.items()}

    return labels


def _positive_labels() -> List[int]:
    """Các nhãn được tính là vi phạm (mặc định CẢ 5 nhãn: model không có output 'clean')."""
    raw = _thresholds().get("moderation_positive_labels")

    if isinstance(raw, list) and raw:
        out: List[int] = []
        for item in raw:
            try:
                out.append(int(item))
            except (TypeError, ValueError):
                continue
        if out:
            return out

    return sorted(_label_map().keys())


def _threshold() -> float:
    return float(_thresholds().get("moderation_threshold", 0.5))


def _review_threshold() -> float:
    return float(_thresholds().get("moderation_review_threshold", 0.35))


def _load() -> None:
    """Nạp model 1 lần; lỗi ghi vào _load_error để /health báo rõ."""
    global _tokenizer, _model, _segmenter, _load_error

    if _model is not None or _load_error is not None:
        return

    try:
        import torch
        from transformers import AutoModelForSequenceClassification, AutoTokenizer

        if not MODEL_DIR.is_dir():
            raise FileNotFoundError(f"Không thấy thư mục model: {MODEL_DIR}")

        try:
            _tokenizer = AutoTokenizer.from_pretrained(str(MODEL_DIR))
        except Exception:
            # Một số bản transformers chỉ có tokenizer slow cho PhoBERT.
            _tokenizer = AutoTokenizer.from_pretrained(str(MODEL_DIR), use_fast=False)

        _model = AutoModelForSequenceClassification.from_pretrained(str(MODEL_DIR))
        _model.eval()

        threads = int(os.getenv("MODERATION_THREADS", str(os.cpu_count() or 4)))
        torch.set_num_threads(max(1, threads))

        if SEGMENT == "pyvi":
            from pyvi import ViTokenizer

            _segmenter = ViTokenizer
    except Exception as exc:  # fail-open: PHP sẽ fallback về rules
        _load_error = f"{type(exc).__name__}: {exc}"


@app.get("/health")
def health() -> Dict[str, Any]:
    _load()

    return {
        "status": "ok" if _model is not None else "error",
        "model": MODEL_TAG,
        "model_dir": str(MODEL_DIR),
        "num_labels": None if _model is None else int(_model.config.num_labels),
        "labels": _label_map(),
        "positive_labels": _positive_labels(),
        "threshold": _threshold(),
        "review_threshold": _review_threshold(),
        "segment": SEGMENT,
        "device": "cpu",
        "error": _load_error,
    }


@app.post("/predict")
def predict(payload: PredictIn) -> Dict[str, Any]:
    _load()

    if _model is None:
        # HTTP != 2xx => Laravel coi là lỗi và fallback về rules (fail-open).
        raise HTTPException(status_code=503, detail=f"model chưa sẵn sàng: {_load_error}")

    text = (payload.text or "").strip()
    if text == "":
        return {
            "is_violation": False,
            "score": 0.0,
            "reasons": [],
            "needs_review": False,
            "skipped": False,
            "model": MODEL_TAG,
            "labels": {},
        }

    import torch

    started = time.perf_counter()

    model_input = _segmenter.tokenize(text) if _segmenter is not None else text

    inputs = _tokenizer(model_input, return_tensors="pt", truncation=True, max_length=MAX_LEN)
    with torch.no_grad():
        logits = _model(**inputs).logits

    # multi_label_classification => sigmoid TỪNG nhãn (KHÔNG softmax như model 2 nhãn).
    probs = torch.sigmoid(logits)[0]
    label_map = _label_map()
    positive = _positive_labels()
    threshold = _threshold()

    scores: Dict[str, float] = {}
    for index in sorted(label_map.keys()):
        if index < probs.shape[0]:
            scores[label_map[index]] = round(float(probs[index]), 3)

    hit_labels = [
        (index, float(probs[index]))
        for index in positive
        if index < probs.shape[0]
    ]
    hit_labels.sort(key=lambda item: item[1], reverse=True)

    score = round(hit_labels[0][1], 3) if hit_labels else 0.0
    is_violation = score >= threshold
    needs_review = (not is_violation) and score >= _review_threshold()

    reasons: List[str] = []
    if is_violation:
        # Liệt kê TẤT CẢ các nhãn vượt ngưỡng (multi-label: 1 câu vi phạm nhiều loại).
        # Chỉ tên nhãn thuần — Laravel FlagHelper sẽ tự thêm điểm tổng vào flag_reason.
        for index, value in hit_labels:
            if value >= threshold:
                reasons.append(label_map.get(index, f"LABEL_{index}"))

    return {
        "is_violation": is_violation,
        "score": score,
        "reasons": reasons,
        "needs_review": needs_review,
        "skipped": False,
        "model": MODEL_TAG,
        "labels": scores,
        "latency_ms": round((time.perf_counter() - started) * 1000, 1),
    }
