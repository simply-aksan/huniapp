import os
import tempfile
import pytest
from services.vector_store import PersistentSqliteVectorStore, StoredDocument


@pytest.fixture
def benchmark_store():
    with tempfile.TemporaryDirectory() as tmpdir:
        db_path = os.path.join(tmpdir, "benchmark_kb.db")
        store = PersistentSqliteVectorStore(db_path=db_path)

        # Populate property 100 with comprehensive rules & FAQs
        docs_p100 = [
            StoredDocument(
                source_id="1",
                source_type="rule",
                property_id=100,
                title="Jam Malam dan Akses Gerbang",
                content="Pintu gerbang utama kos dikunci setiap malam pukul 23:00 WIB demi keamanan. Penghuni lembur wajib konfirmasi ke penjaga.",
                category="keamanan",
                is_active=True,
            ),
            StoredDocument(
                source_id="2",
                source_type="rule",
                property_id=100,
                title="Kebijakan Tamu dan Menginap",
                content="Tamu lawan jenis dilarang masuk ke dalam kamar. Tamu keluarga boleh berkunjung hingga pukul 21:00 WIB dan dilarang menginap tanpa izin tertulis pemilik.",
                category="ketertiban",
                is_active=True,
            ),
            StoredDocument(
                source_id="3",
                source_type="faq",
                property_id=100,
                title="Biaya dan Lokasi Parkir Kendaraan",
                content="Parkir sepeda motor gratis di area dalam berkanopi. Parkir mobil dikenakan biaya tambahan Rp 150.000 per bulan karena kapasitas terbatas.",
                category="fasilitas",
                is_active=True,
            ),
            StoredDocument(
                source_id="4",
                source_type="faq",
                property_id=100,
                title="Jatuh Tempo dan Rekening Pembayaran Sewa",
                content="Uang sewa kamar wajib dibayarkan paling lambat tanggal 5 setiap bulan melalui transfer ke rekening BCA 123456789 atas nama Pemilik Kos.",
                category="pembayaran",
                is_active=True,
            ),
            StoredDocument(
                source_id="5",
                source_type="rule",
                property_id=100,
                title="Penggunaan Alat Elektronik dan Daya Listrik",
                content="Setiap kamar dilengkapi daya listrik 450W. Penggunaan alat elektronik watt besar seperti AC portable, kulkas mini, atau dispenser dikenakan biaya tambahan Rp 50.000 per alat.",
                category="fasilitas",
                is_active=True,
            ),
            StoredDocument(
                source_id="6",
                source_type="faq",
                property_id=100,
                title="Kebersihan Kamar dan Pembuangan Sampah",
                content="Sampah kamar wajib dimasukkan ke dalam kantong plastik dan dibuang ke tempat sampah utama di dekat gerbang depan sebelum pukul 09:00 WIB.",
                category="kebersihan",
                is_active=True,
            ),
        ]

        # Populate property 200 with different rules
        docs_p200 = [
            StoredDocument(
                source_id="201",
                source_type="rule",
                property_id=200,
                title="Jam Malam Kos Khusus Putri",
                content="Gerbang dikunci pukul 22:00 WIB setiap hari tanpa terkecuali.",
                category="keamanan",
                is_active=True,
            ),
            StoredDocument(
                source_id="202",
                source_type="faq",
                property_id=200,
                title="Parkir Khusus Motor",
                content="Hanya menerima parkir motor, tidak menyediakan area parkir mobil sama sekali.",
                category="fasilitas",
                is_active=True,
            ),
        ]

        store.sync_snapshot(property_id=100, incoming_docs=docs_p100)
        store.sync_snapshot(property_id=200, incoming_docs=docs_p200)

        yield store


def test_retrieval_controlled_benchmark_dataset(benchmark_store: PersistentSqliteVectorStore):
    """
    Controlled Evaluation Benchmark across 15 Indonesian inquiries:
    - Direct queries, synonyms, paraphrases, short keywords, light typos.
    - Out-of-domain / unsupported queries.
    - Measures precision, top-1 ranking, and insufficient_evidence triggering.
    """
    test_cases = [
        # (Query, Expected Document ID or None, Description)
        ("jam berapa gerbang malam ditutup?", "1", "Exact query: Jam Malam"),
        ("kapan pintu pagar dikunci malam hari?", "1", "Paraphrase: Jam Malam"),
        ("jamm malam kos tutup jam brapa?", "1", "Typo: Jam Malam"),
        ("apakah boleh membawa tamu menginap?", "2", "Exact query: Tamu Menginap"),
        ("teman saya mau tidur di kamar boleh tidak?", "2", "Paraphrase: Tamu Menginap"),
        ("berapa biaya parkir mobil?", "3", "Exact query: Parkir Mobil"),
        ("parkir motor", "3", "Short query: Parkir Motor"),
        ("kapan batas pembayaran uang sewa kos?", "4", "Exact query: Pembayaran Sewa"),
        ("cara transfer biaya kos bulanan rekening apa?", "4", "Paraphrase: Pembayaran Sewa"),
        ("apakah boleh membawa kulkas mini dan dispenser di kamar?", "5", "Exact query: Elektronik Listrik"),
        ("apakah ada biaya tambahan bawa kulkas?", "5", "Paraphrase: Elektronik Listrik"),
        ("di mana tempat membuang sampah kamar?", "6", "Exact query: Sampah"),
        # Negative / Unrelated Queries (MUST trigger insufficient_evidence / return empty)
        ("resep membuat soto ayam lamongan enak", None, "Unrelated: Cooking recipe"),
        ("cara mengganti oli motor matic honda beat", None, "Unrelated: Motor mechanic"),
        ("harga tiket kereta api bisnis ke bandung", None, "Unrelated: Train ticket"),
        ("bagaimana cara instalasi linux ubuntu di laptop", None, "Unrelated: Tech tutorial"),
    ]

    results_summary = {
        "total": len(test_cases),
        "correct_top1": 0,
        "correct_insufficient_evidence": 0,
        "false_positives": 0,
        "false_negatives": 0,
    }

    for query, expected_id, desc in test_cases:
        retrieved = benchmark_store.retrieve_relevant_documents(
            property_id=100, query=query, top_k=3, threshold=0.20
        )

        if expected_id is not None:
            # Expected a relevant hit
            if not retrieved:
                results_summary["false_negatives"] += 1
                pytest.fail(f"False Negative for query '{query}' ({desc}). Expected doc ID {expected_id}, got empty result.")
            else:
                top_doc = retrieved[0]["document"]
                top_score = retrieved[0]["score"]
                assert top_doc.source_id == expected_id, (
                    f"Top hit mismatch for '{query}' ({desc}): Expected doc {expected_id}, got {top_doc.source_id} (title: {top_doc.title}, score: {top_score})"
                )
                assert top_score >= 0.20, f"Score {top_score} below threshold for '{query}'"
                results_summary["correct_top1"] += 1
        else:
            # Expected NO hits (insufficient evidence)
            if retrieved:
                results_summary["false_positives"] += 1
                top_doc = retrieved[0]["document"]
                top_score = retrieved[0]["score"]
                pytest.fail(
                    f"False Positive for unrelated query '{query}' ({desc}). Retrieved doc ID {top_doc.source_id} ('{top_doc.title}') with score {top_score}."
                )
            else:
                results_summary["correct_insufficient_evidence"] += 1

    # Print evaluation metrics
    print("\n=== RETRIEVAL BENCHMARK EVALUATION RESULTS ===")
    print(f"Total Test Cases: {results_summary['total']}")
    print(f"Correct Top-1 Hits: {results_summary['correct_top1']} / 12 relevant queries")
    print(f"Correct Insufficient Evidence: {results_summary['correct_insufficient_evidence']} / 4 unrelated queries")
    print(f"False Positives: {results_summary['false_positives']}")
    print(f"False Negatives: {results_summary['false_negatives']}")
    print(f"Precision: 100.0%, Recall: 100.0%")


def test_cross_property_retrieval_isolation(benchmark_store: PersistentSqliteVectorStore):
    """
    Ensure Property 100 and Property 200 return their own distinct documents
    and never leak across property boundaries.
    """
    # Property 100 curfew (23:00)
    res_100 = benchmark_store.retrieve_relevant_documents(
        property_id=100, query="kapan jam malam gerbang ditutup?", top_k=1, threshold=0.20
    )
    assert len(res_100) == 1
    assert "23:00" in res_100[0]["document"].content
    assert res_100[0]["document"].property_id == 100

    # Property 200 curfew (22:00)
    res_200 = benchmark_store.retrieve_relevant_documents(
        property_id=200, query="kapan jam malam gerbang ditutup?", top_k=1, threshold=0.20
    )
    assert len(res_200) == 1
    assert "22:00" in res_200[0]["document"].content
    assert res_200[0]["document"].property_id == 200
    assert "23:00" not in res_200[0]["document"].content


def test_property_without_documents_returns_empty(benchmark_store: PersistentSqliteVectorStore):
    """
    Query on unindexed property ID 999 must return empty.
    """
    res = benchmark_store.retrieve_relevant_documents(
        property_id=999, query="jam malam", top_k=3, threshold=0.20
    )
    assert res == []
