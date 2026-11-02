# HUNI AI Microservice

Service FastAPI mandiri untuk kapabilitas kecerdasan buatan pada aplikasi HUNI:
1. **AI-1:** Prediksi Risiko Keterlambatan Pembayaran Penghuni (Payment Delinquency Risk Prediction).
2. **AI-2:** Retrieval-Augmented Generation (RAG) untuk FAQ & Tata Tertib Kos.

## Status Fase (Phase 11C)
- **Vector Store:** In-Memory adapter (`InMemoryVectorStore`). Non-persisten, ditujukan untuk pengujian kontrak dan isolasi properti.
- **Semantic Retrieval:** Belum aktif pada fase ini (tidak ada model embedding atau LLM berat yang dipasang).
- **Autentikasi Service:** Wajib menyertakan header `X-HUNI-AI-SECRET`.

## Menjalankan Service di Development
```bash
# 1. Masuk ke direktori service
cd ai

# 2. Sinkronkan virtual environment & dependencies
uv sync

# 3. Jalankan server FastAPI
uv run uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

## Menjalankan Automated Tests
```bash
cd ai
uv run pytest -v
```
