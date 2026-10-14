import joblib
import pandas as pd
import numpy as np

from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans
from sklearn.metrics import silhouette_score, davies_bouldin_score

from pathlib import Path


MODEL_DIR = Path(__file__).resolve().parent.parent / "models"


class ClusterPredictor:

    def __init__(self):
        try:
            self.default_scaler = joblib.load(
                MODEL_DIR / "scaler.pkl"
            )

            self.default_kmeans = joblib.load(
                MODEL_DIR / "kmeans.pkl"
            )

            n_clusters = (
                self.default_kmeans
                .cluster_centers_
                .shape[0]
            )

            print(
                f"[ClusterPredictor] "
                f"Loaded trained model: {n_clusters} clusters"
            )

        except Exception as e:
            print(
                f"[ClusterPredictor] "
                f"Could not load trained model: {e}"
            )

            self.default_scaler = None
            self.default_kmeans = None

    # ============================================================
    # EUCLIDEAN DISTANCE PER K
    # ============================================================

    def _compute_euclidean_distance_by_k(
        self,
        X: np.ndarray,
        k_min: int = 2,
        k_max: int = 8,
        random_state: int = 42,
    ) -> list[dict]:

        results = []

        for k in range(k_min, k_max + 1):

            kmeans = KMeans(
                n_clusters=k,
                random_state=random_state,
                n_init=10,
            )
            labels = kmeans.fit_predict(X)

            distances = []

            for cluster_id in range(k):

                centroid = (
                    kmeans.cluster_centers_[cluster_id]
                )

                member_mask = labels == cluster_id

                cluster_points = X[member_mask]

                if cluster_points.size == 0:
                    continue

                cluster_distances = np.linalg.norm(
                    cluster_points - centroid,
                    axis=1,
                )

                distances.extend(
                    cluster_distances.tolist()
                )

            average_distance = (
                float(np.mean(distances))
                if distances
                else 0.0
            )

            max_distance = (
                float(np.max(distances))
                if distances
                else 0.0
            )

            results.append(
                {
                    "k": k,
                    "average_euclidean_distance": round(
                        average_distance,
                        6,
                    ),
                    "max_euclidean_distance": round(
                        max_distance,
                        6,
                    ),
                }
            )

        return results

    # ============================================================
    # EXPERIMENT K-MEANS
    # ============================================================

    def experiment_kmeans(
        self,
        customers: list[dict],
        k_min: int = 2,
        k_max: int = 8,
        random_state: int = 42,
    ) -> dict:
        """
        Menjalankan eksperimen K-Means untuk beberapa nilai K.

        Metrik yang dihitung:
        - Inertia
        - Silhouette Score
        - Davies-Bouldin Index
        - Average Euclidean Distance
        - Maximum Euclidean Distance

        Eksperimen TIDAK mengubah model default.
        """

        # --------------------------------------------------------
        # VALIDASI
        # --------------------------------------------------------

        if not customers:
            raise ValueError(
                "Data customer kosong."
            )

        if k_min < 2:
            raise ValueError(
                "k_min minimal adalah 2."
            )

        if k_max < k_min:
            raise ValueError(
                "k_max harus lebih besar atau sama dengan k_min."
            )

        if len(customers) < k_max:
            raise ValueError(
                f"Jumlah pelanggan ({len(customers)}) "
                f"tidak cukup untuk eksperimen sampai K={k_max}."
            )

        # --------------------------------------------------------
        # DATAFRAME
        # --------------------------------------------------------

        df = pd.DataFrame(customers)

        required_columns = [
            "recency",
            "frequency",
            "monetary",
        ]

        missing_columns = [
            column
            for column in required_columns
            if column not in df.columns
        ]

        if missing_columns:
            raise ValueError(
                "Kolom RFM tidak lengkap: "
                + ", ".join(missing_columns)
            )

        # --------------------------------------------------------
        # MONETARY TRANSFORMATION
        # --------------------------------------------------------

        df["monetary_log"] = np.log1p(
            df["monetary"]
        )

        # --------------------------------------------------------
        # FEATURE RFM
        # --------------------------------------------------------

        features = df[
            [
                "recency",
                "frequency",
                "monetary_log",
            ]
        ]

        # --------------------------------------------------------
        # STANDARDISASI
        # --------------------------------------------------------

        scaler = StandardScaler()

        scaled = scaler.fit_transform(
            features
        )

        results = []

        # --------------------------------------------------------
        # EKSPERIMEN K
        # --------------------------------------------------------

        for k in range(
            k_min,
            k_max + 1
        ):

            kmeans = KMeans(
                n_clusters=k,
                random_state=random_state,
                n_init=10,
            )

            labels = kmeans.fit_predict(
                scaled
            )

            # ----------------------------------------------------
            # INERTIA
            # ----------------------------------------------------

            inertia = float(
                kmeans.inertia_
            )

            # ----------------------------------------------------
            # SILHOUETTE SCORE
            # ----------------------------------------------------

            silhouette = None

            unique_labels = len(
                set(labels)
            )

            if (
                unique_labels > 1
                and unique_labels < len(labels)
            ):
                silhouette = float(
                    silhouette_score(
                        scaled,
                        labels,
                    )
                )

            # ----------------------------------------------------
            # DAVIES-BOULDIN INDEX
            # ----------------------------------------------------

            dbi = None

            if unique_labels > 1:

                dbi = float(
                    davies_bouldin_score(
                        scaled,
                        labels,
                    )
                )

            # ----------------------------------------------------
            # EUCLIDEAN DISTANCE
            # ----------------------------------------------------

            distances = []

            for cluster_id in range(k):

                centroid = (
                    kmeans.cluster_centers_[
                        cluster_id
                    ]
                )

                member_mask = (
                    labels == cluster_id
                )

                cluster_points = (
                    scaled[member_mask]
                )

                if cluster_points.size == 0:
                    continue

                cluster_distances = np.linalg.norm(
                    cluster_points - centroid,
                    axis=1,
                )

                distances.extend(
                    cluster_distances.tolist()
                )

            average_euclidean = (
                float(np.mean(distances))
                if distances
                else 0.0
            )

            max_euclidean = (
                float(np.max(distances))
                if distances
                else 0.0
            )

            # ----------------------------------------------------
            # SIMPAN HASIL
            # ----------------------------------------------------

            results.append(
                {
                    "k": k,

                    "inertia": round(
                        inertia,
                        6,
                    ),

                    "silhouette_score": (
                        round(
                            silhouette,
                            6,
                        )
                        if silhouette is not None
                        else None
                    ),

                    "davies_bouldin_index": (
                        round(
                            dbi,
                            6,
                        )
                        if dbi is not None
                        else None
                    ),

                    "average_euclidean_distance": round(
                        average_euclidean,
                        6,
                    ),

                    "max_euclidean_distance": round(
                        max_euclidean,
                        6,
                    ),
                }
            )

        optimal_k = self.select_optimal_k(
            results
        )

        return {
            "k_min": k_min,
            "k_max": k_max,
            "random_state": random_state,
            "results": results,
            "optimal_k": optimal_k,
        }

    def select_optimal_k(
        self,
        experiment_results: list[dict],
    ) -> dict:
        """
        Menentukan kandidat K berdasarkan:
        1. Silhouette Score tertinggi
        2. Davies-Bouldin Index terendah
        3. Elbow/Inertia sebagai informasi pendukung

        Pemilihan akhir tetap harus mempertimbangkan
        interpretasi hasil clustering.
        """

        if not experiment_results:
            raise ValueError(
                "Hasil eksperimen kosong."
            )

        # --------------------------------------------------------
        # VALIDASI
        # --------------------------------------------------------

        valid_results = [
            result
            for result in experiment_results
            if result.get("silhouette_score") is not None
            and result.get("davies_bouldin_index") is not None
        ]

        if not valid_results:
            raise ValueError(
                "Tidak ada hasil eksperimen yang valid."
            )

        # --------------------------------------------------------
        # K TERBAIK BERDASARKAN SILHOUETTE
        # --------------------------------------------------------

        best_silhouette = max(
            valid_results,
            key=lambda x: x["silhouette_score"]
        )

        silhouette_k = best_silhouette["k"]

        # --------------------------------------------------------
        # K TERBAIK BERDASARKAN DBI
        # --------------------------------------------------------

        best_dbi = min(
            valid_results,
            key=lambda x: x["davies_bouldin_index"]
        )

        dbi_k = best_dbi["k"]

        # --------------------------------------------------------
        # K DENGAN INERTIA TERENDAH
        # --------------------------------------------------------
        #
        # HANYA sebagai informasi.
        # Bukan dasar tunggal pemilihan K.
        #

        lowest_inertia = min(
            valid_results,
            key=lambda x: x["inertia"]
        )

        lowest_inertia_k = (
            lowest_inertia["k"]
        )

        # --------------------------------------------------------
        # SISTEM SCORING
        # --------------------------------------------------------
        #
        # Silhouette dan DBI diberi bobot yang sama.
        #
        # Jika kedua metrik memilih K yang sama,
        # K tersebut menjadi rekomendasi utama.
        #

        scores = {
            result["k"]: 0
            for result in valid_results
        }

        # Silhouette terbaik
        scores[silhouette_k] += 1

        # DBI terbaik
        scores[dbi_k] += 1

        # --------------------------------------------------------
        # REKOMENDASI
        # --------------------------------------------------------

        max_score = max(
            scores.values()
        )

        candidates = [
            k
            for k, score in scores.items()
            if score == max_score
        ]

        # Jika terdapat satu kandidat dengan
        # skor tertinggi
        if len(candidates) == 1:

            recommended_k = candidates[0]

        else:
            # Tie-break menggunakan Silhouette
            recommended_k = max(
                candidates,
                key=lambda k: next(
                    result["silhouette_score"]
                    for result in valid_results
                    if result["k"] == k
                )
            )

        return {
            "recommended_k": recommended_k,

            "silhouette_best_k": silhouette_k,

            "silhouette_best_score": (
                best_silhouette[
                    "silhouette_score"
                ]
            ),

            "davies_bouldin_best_k": dbi_k,

            "davies_bouldin_best_score": (
                best_dbi[
                    "davies_bouldin_index"
                ]
            ),

            "lowest_inertia_k": (
                lowest_inertia_k
            ),

            "selection_method": (
                "K dipilih berdasarkan "
                "kesesuaian Silhouette Score "
                "dan Davies-Bouldin Index. "
                "Inertia digunakan sebagai "
                "pendukung analisis Elbow."
            ),
        }

    # ============================================================
    # FIT AND PREDICT
    # ============================================================

    def fit_and_predict(
        self,
        customers: list[dict],
        n_clusters: int = 4,
    ) -> tuple[
        list[dict],
        list[dict],
        list[dict],
        float | None,
        float | None,
    ]:

        """
        Melatih K-Means baru dari nol.

        Digunakan untuk kebutuhan retraining/offline.
        """

        if len(customers) < n_clusters:
            raise ValueError(
                f"Jumlah klaster ({n_clusters}) "
                f"harus lebih kecil atau sama dengan "
                f"jumlah pelanggan ({len(customers)})."
            )

        df = pd.DataFrame(customers)

        df["monetary_log"] = np.log1p(
            df["monetary"]
        )

        features = df[
            [
                "recency",
                "frequency",
                "monetary_log",
            ]
        ]

        scaler = StandardScaler()

        scaled = scaler.fit_transform(
            features
        )

        kmeans = KMeans(
            n_clusters=n_clusters,
            random_state=42,
            n_init=10,
        )

        raw_labels = kmeans.fit_predict(
            scaled
        )

        # --------------------------------------------------------
        # RANKING CLUSTER BERDASARKAN MONETARY
        # --------------------------------------------------------

        centroids = kmeans.cluster_centers_

        centroids_original = (
            scaler.inverse_transform(
                centroids
            )
        )

        monetary_col_index = 2

        order = np.argsort(
            -centroids_original[
                :,
                monetary_col_index
            ]
        )

        cluster_rank_map = {
            int(original_label): int(rank)
            for rank, original_label
            in enumerate(order)
        }

        df["cluster_label"] = [
            cluster_rank_map[label]
            for label in raw_labels
        ]

        results = df[
            [
                "customer_id",
                "cluster_label",
            ]
        ].to_dict(
            orient="records"
        )

        # --------------------------------------------------------
        # ELBOW DATA
        # --------------------------------------------------------

        elbow_data = []

        max_k = min(
            8,
            len(customers)
        )

        for k in range(
            2,
            max_k + 1
        ):

            km = KMeans(
                n_clusters=k,
                random_state=42,
                n_init=10,
            )

            km.fit(scaled)

            elbow_data.append(
                {
                    "k": k,
                    "inertia": float(
                        km.inertia_
                    ),
                }
            )

        # --------------------------------------------------------
        # EUCLIDEAN DISTANCE
        # --------------------------------------------------------

        euclidean_distance_by_k = (
            self._compute_euclidean_distance_by_k(
                scaled,
                k_min=2,
                k_max=max_k,
                random_state=42,
            )
        )

        # --------------------------------------------------------
        # METRICS FINAL
        # --------------------------------------------------------

        silhouette = None
        dbi = None

        if (
            len(set(raw_labels)) > 1
            and len(raw_labels) > 1
        ):

            silhouette = float(
                silhouette_score(
                    scaled,
                    raw_labels,
                )
            )

            dbi = float(
                davies_bouldin_score(
                    scaled,
                    raw_labels,
                )
            )

        # --------------------------------------------------------
        # DATA UNTUK 3D PLOT
        # --------------------------------------------------------

        plot_3d_data = []

        for _, row in df.iterrows():

            plot_3d_data.append(
                {
                    "x": float(
                        row["recency"]
                    ),

                    "y": float(
                        row["frequency"]
                    ),

                    "z": float(
                        row["monetary_log"]
                    ),

                    "cluster": (
                        f"Cluster "
                        f"{int(row['cluster_label'])}"
                    ),

                    "name": str(
                        row["customer_id"]
                    ),
                }
            )

        return (
            results,
            elbow_data,
            plot_3d_data,
            silhouette,
            dbi,
        )

    # ============================================================
    # PREDICT
    # ============================================================

    def predict(
        self,
        customers: list[dict],
        n_clusters: int = 4,
    ) -> dict:

        """
        Prediksi/segmentasi pelanggan.

        Jika K sama dengan model terlatih,
        gunakan model default.

        Jika K berbeda,
        lakukan fit sementara pada batch data.

        Model default TIDAK ditimpa.
        """

        if (
            self.default_scaler is None
            or self.default_kmeans is None
        ):
            raise ValueError(
                "Model belum dilatih/tersedia. "
                "Pastikan scaler.pkl dan kmeans.pkl "
                "ada di folder models/."
            )

        if len(customers) < n_clusters:
            raise ValueError(
                f"Jumlah klaster ({n_clusters}) "
                f"harus lebih kecil atau sama dengan "
                f"jumlah pelanggan ({len(customers)})."
            )

        trained_k = int(
            self.default_kmeans
            .cluster_centers_
            .shape[0]
        )

        scaler = self.default_scaler

        kmeans = self.default_kmeans

        # ========================================================
        # JIKA K BERBEDA DARI MODEL DEFAULT
        # ========================================================

        if n_clusters != trained_k:

            df = pd.DataFrame(
                customers
            )

            df = df.rename(
                columns={
                    "recency": "Recency",
                    "frequency": "Frequency",
                    "monetary": "Monetary",
                    "monetary_log": "Monetary_log",
                }
            )

            if (
                "Monetary_log" not in df.columns
                and "Monetary" in df.columns
            ):
                df["Monetary_log"] = np.log1p(
                    df["Monetary"]
                )

            features = df[
                [
                    "Recency",
                    "Frequency",
                    "Monetary_log",
                ]
            ]

            # Model sementara
            experimental_scaler = (
                StandardScaler()
            )

            scaled = (
                experimental_scaler.fit_transform(
                    features
                )
            )

            experimental_kmeans = KMeans(
                n_clusters=n_clusters,
                random_state=42,
                n_init=10,
            )

            raw_labels = (
                experimental_kmeans.fit_predict(
                    scaled
                )
            )

            scaler = experimental_scaler
            kmeans = experimental_kmeans

            # ----------------------------------------------------
            # RANKING CLUSTER
            # ----------------------------------------------------

            centroids = (
                kmeans.cluster_centers_
            )

            centroids_original = (
                scaler.inverse_transform(
                    centroids
                )
            )

            monetary_col_index = 2

            order = np.argsort(
                -centroids_original[
                    :,
                    monetary_col_index
                ]
            )

            rank_map = {
                int(original_label): int(rank)
                for rank, original_label
                in enumerate(order)
            }

            df["cluster_label"] = [
                rank_map[label]
                for label in raw_labels
            ]

            # ----------------------------------------------------
            # METRICS
            # ----------------------------------------------------

            silhouette = None
            dbi = None

            unique_labels = len(
                set(raw_labels)
            )

            if (
                unique_labels > 1
                and unique_labels < len(raw_labels)
            ):

                silhouette = float(
                    silhouette_score(
                        scaled,
                        raw_labels,
                    )
                )

                dbi = float(
                    davies_bouldin_score(
                        scaled,
                        raw_labels,
                    )
                )

            # ----------------------------------------------------
            # EUCLIDEAN
            # ----------------------------------------------------

            max_k = min(
                8,
                len(customers)
            )

            euclidean_distance_by_k = (
                self._compute_euclidean_distance_by_k(
                    scaled,
                    k_min=2,
                    k_max=max_k,
                    random_state=42,
                )
            )

            return {
                "results": df[
                    [
                        "customer_id",
                        "cluster_label",
                    ]
                ].to_dict(
                    orient="records"
                ),

                "chosen_k": n_clusters,

                "silhouette_score": silhouette,

                "davies_bouldin_index": dbi,

                "elbow_data": [],

                "euclidean_distance_by_k":
                    euclidean_distance_by_k,
            }

        # ========================================================
        # JIKA K SAMA DENGAN MODEL DEFAULT
        # ========================================================

        df = pd.DataFrame(
            customers
        )

        df = df.rename(
            columns={
                "recency": "Recency",
                "frequency": "Frequency",
                "monetary": "Monetary",
                "monetary_log": "Monetary_log",
            }
        )

        if (
            "Monetary_log" not in df.columns
            and "Monetary" in df.columns
        ):
            df["Monetary_log"] = np.log1p(
                df["Monetary"]
            )

        features = df[
            [
                "Recency",
                "Frequency",
                "Monetary_log",
            ]
        ]

        scaled = scaler.transform(
            features
        )

        raw_labels = kmeans.predict(
            scaled
        )

        # --------------------------------------------------------
        # RANKING CLUSTER
        # --------------------------------------------------------

        centroids = (
            kmeans.cluster_centers_
        )

        centroids_original = (
            scaler.inverse_transform(
                centroids
            )
        )

        monetary_col_index = 2

        order = np.argsort(
            -centroids_original[
                :,
                monetary_col_index
            ]
        )

        rank_map = {
            int(original_label): int(rank)
            for rank, original_label
            in enumerate(order)
        }

        df["cluster_label"] = [
            rank_map[label]
            for label in raw_labels
        ]

        # --------------------------------------------------------
        # METRICS
        # --------------------------------------------------------

        silhouette = None
        dbi = None

        unique_labels = len(
            set(raw_labels)
        )

        if (
            unique_labels > 1
            and unique_labels < len(raw_labels)
        ):

            silhouette = float(
                silhouette_score(
                    scaled,
                    raw_labels,
                )
            )

            dbi = float(
                davies_bouldin_score(
                    scaled,
                    raw_labels,
                )
            )

        # --------------------------------------------------------
        # EUCLIDEAN DISTANCE
        # --------------------------------------------------------

        max_k = min(
            8,
            len(customers)
        )

        euclidean_distance_by_k = (
            self._compute_euclidean_distance_by_k(
                scaled,
                k_min=2,
                k_max=max_k,
                random_state=42,
            )
        )

        return {
            "results": df[
                [
                    "customer_id",
                    "cluster_label",
                ]
            ].to_dict(
                orient="records"
            ),

            "chosen_k": n_clusters,

            "silhouette_score": silhouette,

            "davies_bouldin_index": dbi,

            "elbow_data": [],

            "euclidean_distance_by_k":
                euclidean_distance_by_k,
        }


# ================================================================
# SINGLETON PREDICTOR
# ================================================================

predictor = ClusterPredictor()