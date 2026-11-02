import os
import tempfile
from services.vector_store import PersistentSqliteVectorStore, StoredDocument


def test_persistence_across_service_restart():
    """
    Verify that SQLite vector store persists documents to disk and reloads them intact
    after the process or service restarts (simulated by re-instantiating the class).
    """
    with tempfile.TemporaryDirectory() as tmpdir:
        db_path = os.path.join(tmpdir, "test_kb.db")

        # 1. First service instance
        store1 = PersistentSqliteVectorStore(db_path=db_path)
        doc1 = StoredDocument(
            source_id="1",
            source_type="rule",
            property_id=10,
            title="Aturan Jam Malam",
            content="Pintu gerbang ditutup pukul 23:00 WIB.",
            is_active=True,
            version=1,
        )
        doc2 = StoredDocument(
            source_id="2",
            source_type="faq",
            property_id=10,
            title="Parkir Kendaraan",
            content="Parkir motor gratis untuk penghuni resmi.",
            is_active=True,
            version=1,
        )
        store1.sync_snapshot(property_id=10, incoming_docs=[doc1, doc2])

        assert store1.count(property_id=10) == 2
        status1 = store1.get_property_status(10)
        assert status1["is_indexed"] is True
        assert status1["active_documents_count"] == 2

        # 2. Simulate complete restart: re-instantiate store on the same database file
        store2 = PersistentSqliteVectorStore(db_path=db_path)

        assert store2.count(property_id=10) == 2
        status2 = store2.get_property_status(10)
        assert status2["is_indexed"] is True
        assert status2["active_documents_count"] == 2

        # 3. Retrieve documents via the new instance
        results = store2.retrieve_relevant_documents(property_id=10, query="kapan gerbang malam tutup?", threshold=0.20)
        assert len(results) >= 1
        assert results[0]["document"].title == "Aturan Jam Malam"
        assert results[0]["score"] >= 0.20


def test_pruning_and_deactivation_reflected_immediately():
    """
    Verify that deactivated or pruned documents are removed from subsequent retrieval immediately.
    """
    with tempfile.TemporaryDirectory() as tmpdir:
        db_path = os.path.join(tmpdir, "test_kb.db")
        store = PersistentSqliteVectorStore(db_path=db_path)

        doc1 = StoredDocument(
            source_id="1",
            source_type="rule",
            property_id=20,
            title="Hewan Peliharaan",
            content="Dilarang membawa kucing atau anjing.",
            is_active=True,
        )
        store.sync_snapshot(property_id=20, incoming_docs=[doc1])

        # Active query succeeds
        res = store.retrieve_relevant_documents(property_id=20, query="Boleh bawa kucing?", threshold=0.20)
        assert len(res) == 1

        # Now deactivate or prune
        doc1_deactivated = StoredDocument(
            source_id="1",
            source_type="rule",
            property_id=20,
            title="Hewan Peliharaan",
            content="Dilarang membawa kucing atau anjing.",
            is_active=False,
        )
        store.sync_snapshot(property_id=20, incoming_docs=[doc1_deactivated])

        # Active count is 0
        assert store.count(property_id=20, active_only=True) == 0

        # Query now returns empty
        res_after = store.retrieve_relevant_documents(property_id=20, query="Boleh bawa kucing?", threshold=0.20)
        assert len(res_after) == 0


def test_cross_property_isolation():
    """
    Ensure property A's documents cannot be retrieved when searching property B.
    """
    with tempfile.TemporaryDirectory() as tmpdir:
        db_path = os.path.join(tmpdir, "test_kb.db")
        store = PersistentSqliteVectorStore(db_path=db_path)

        doc_prop_a = StoredDocument(
            source_id="101",
            source_type="faq",
            property_id=1,
            title="Password Wifi Mawar",
            content="Password wifi adalah mawarindah123",
            is_active=True,
        )
        doc_prop_b = StoredDocument(
            source_id="202",
            source_type="faq",
            property_id=2,
            title="Password Wifi Melati",
            content="Password wifi adalah melatisuper456",
            is_active=True,
        )
        store.sync_snapshot(property_id=1, incoming_docs=[doc_prop_a])
        store.sync_snapshot(property_id=2, incoming_docs=[doc_prop_b])

        # Query property 1
        res_1 = store.retrieve_relevant_documents(property_id=1, query="password wifi", threshold=0.20)
        assert len(res_1) == 1
        assert res_1[0]["document"].property_id == 1
        assert "mawarindah123" in res_1[0]["document"].content
        assert "melatisuper456" not in res_1[0]["document"].content

        # Query property 2
        res_2 = store.retrieve_relevant_documents(property_id=2, query="password wifi", threshold=0.20)
        assert len(res_2) == 1
        assert res_2[0]["document"].property_id == 2
        assert "melatisuper456" in res_2[0]["document"].content
        assert "mawarindah123" not in res_2[0]["document"].content


def test_relevance_threshold_insufficient_evidence():
    """
    Verify threshold filtering works and excludes unrelated items.
    """
    with tempfile.TemporaryDirectory() as tmpdir:
        db_path = os.path.join(tmpdir, "test_kb.db")
        store = PersistentSqliteVectorStore(db_path=db_path)

        doc = StoredDocument(
            source_id="1",
            source_type="rule",
            property_id=30,
            title="Ketertiban dan Ketenangan",
            content="Dilarang menyalakan musik keras setelah jam 22:00 WIB demi kenyamanan bersama.",
            is_active=True,
        )
        store.sync_snapshot(property_id=30, incoming_docs=[doc])

        # Relevant query with matching terms
        relevant = store.retrieve_relevant_documents(property_id=30, query="Bolehkah menyalakan musik keras di kos?", threshold=0.20)
        assert len(relevant) >= 1
        assert relevant[0]["score"] >= 0.20

        # Totally unrelated query
        unrelated = store.retrieve_relevant_documents(property_id=30, query="Resep masakan nasi goreng spesial", threshold=0.20)
        assert len(unrelated) == 0
