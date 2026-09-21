"""
FastAPI server phân loại text GIAN LẬN (flag-only moderation) bằng PhoBERT.

Model: RobertaForSequenceClassification, 2 nhãn (fine-tune trên Colab)
  - 0 = bình thường
  - 1 = gian lận            (đổi bằng env TEXT_MODEL_POSITIVE_INDEX)
  - Thư mục model: env TEXT_MODEL_DIR
    (mặc định G:\\MyApp\\laragon\\www\\AI-Services\\phobert-negative-classifier)

Contract trả về GIỐNG bản PHP rule-based nên Laravel/Controller/trang admin
không phải sửa:
  {is_violation, score, reasons, needs_review, skipped, model}

Ngưỡng đọc từ violation-detection/config/thresholds.json
(text_model_threshold, text_review_threshold) => tune không cần build lại.

Cài đặt + chạy:
  conda create -n phobert python=3.11 -y
  conda run -n phobert pip install torch --index-url https://download.pytorch.org/whl/cpu
  conda run -n phobert pip install -r violation-detection/python/requirements.txt
  cd violation-detection/python
  conda run -n phobert uvicorn app:app --host 127.0.0.1 --port 8889

Nguyên tắc: server này CHỈ cho điểm để gắn cờ — không chặn gửi, không xoá gì.
Server tắt/lỗi thì Laravel tự fallback về rules (fail-open).
"""

from __future__ import annotations

import json
import os
import time
from pathlib import Path
from typing import Any, Dict, Optional

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

HERE = Path(__file__).resolve().parent
THRESHOLDS_PATH = HERE.parent / "config" / "thresholds.json"

DEFAULT_MODEL_DIR = r"G:\MyApp\laragon\www\AI-Services\phobert-negative-classifier"

MODEL_DIR = Path(os.getenv("TEXT_MODEL_DIR", DEFAULT_MODEL_DIR))
POSITIVE_INDEX = int(os.getenv("TEXT_MODEL_POSITIVE_INDEX", "1"))
MAX_LEN = int(os.getenv("TEXT_MODEL_MAX_LEN", "256"))
SEGMENT = os.getenv("TEXT_MODEL_SEGMENT", "raw").strip().lower()  # raw | pyvi
MODEL_TAG = os.getenv("TEXT_MODEL_TAG", f"phobert-fraud@{MODEL_DIR.name}")

app = FastAPI(title="Text violation moderation (PhoBERT)", version="1.0.0")

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


def _threshold() -> float:
    return float(_thresholds().get("text_model_threshold", 0.5))


def _review_threshold() -> float:
    return float(_thresholds().get("text_review_threshold", 0.35))


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

        threads = int(os.getenv("TEXT_MODEL_THREADS", str(os.cpu_count() or 4)))
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
        "positive_index": POSITIVE_INDEX,
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
        }

    import torch

    started = time.perf_counter()

    model_input = _segmenter.tokenize(text) if _segmenter is not None else text

    inputs = _tokenizer(model_input, return_tensors="pt", truncation=True, max_length=MAX_LEN)
    with torch.no_grad():
        logits = _model(**inputs).logits

    probs = torch.softmax(logits, dim=-1)[0]
    index = POSITIVE_INDEX if 0 <= POSITIVE_INDEX < probs.shape[0] else probs.shape[0] - 1
    score = round(float(probs[index]), 3)

    is_violation = score >= _threshold()
    needs_review = (not is_violation) and score >= _review_threshold()

    return {
        "is_violation": is_violation,
        "score": score,
        "reasons": ["phobert-fraud"] if is_violation else [],
        "needs_review": needs_review,
        "skipped": False,
        "model": MODEL_TAG,
        "latency_ms": round((time.perf_counter() - started) * 1000, 1),
    }
