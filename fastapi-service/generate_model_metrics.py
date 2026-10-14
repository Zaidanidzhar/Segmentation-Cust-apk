# -*- coding: utf-8 -*-
"""
Regenerasi model_metrics.json dari hasil training RESMI di Google Colab
(Hasil_Segmentasi_Visscotton_Final.csv), bukan dari refit on-the-fly.

Jalankan sekali setiap kali model di-retrain ulang di Colab, lalu salin
model_metrics.json yang dihasilkan ke:
rfm-segmentation/storage/app/private/model_metrics.json
"""
import json
import pandas as pd
import numpy as np
from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans
from sklearn.metrics import silhouette_score, davies_bouldin_score

CSV_PATH = "app/models/Hasil_Segmentasi_Visscotton_Final.csv"
OUTPUT_PATH = "model_metrics.json"
CHOSEN_K = 4


def compute_euclidean_metrics_by_k(X: np.ndarray, max_k: int = 8):
    results = []
    for k in range(2, max_k + 1):
        km = KMeans(n_clusters=k, random_state=42, n_init=10)
        labels = km.fit_predict(X)
        distances = []

        for cluster_id in range(k):
            centroid = km.cluster_centers_[cluster_id]
            member_mask = labels == cluster_id
            cluster_points = X[member_mask]
            if len(cluster_points) == 0:
                continue
            diffs = cluster_points - centroid
            distances.extend(np.linalg.norm(diffs, axis=1).tolist())

        avg_dist = float(np.mean(distances)) if distances else 0.0
        max_dist = float(np.max(distances)) if distances else 0.0
        results.append({
            "k": k,
            "average_euclidean_distance": round(avg_dist, 4),
            "max_euclidean_distance": round(max_dist, 4),
        })

    return results


df = pd.read_csv(CSV_PATH)
features = df[["Recency", "Frequency", "Monetary_log"]]

scaler = StandardScaler()
X_scaled = scaler.fit_transform(features)

# --- Elbow method: hitung inertia untuk k=2..8 pada dataset resmi ini ---
elbow_data = []
for k in range(2, 9):
    km = KMeans(n_clusters=k, random_state=42, n_init=10)
    km.fit(X_scaled)
    elbow_data.append({"k": k, "inertia": round(float(km.inertia_), 4)})

# --- Eksperimen Euclidean per K untuk membandingkan jarak rata-rata dan maksimum ke centroid ---
euclidean_distance_by_k = compute_euclidean_metrics_by_k(X_scaled, max_k=8)

# --- Model final K=4, hitung Silhouette Score & Davies-Bouldin Index ---
kmeans_final = KMeans(n_clusters=CHOSEN_K, random_state=42, n_init=10)
labels = kmeans_final.fit_predict(X_scaled)

sil_score = silhouette_score(X_scaled, labels)
dbi_score = davies_bouldin_score(X_scaled, labels)

print(f"Silhouette Score : {sil_score:.4f}")
print(f"Davies-Bouldin   : {dbi_score:.4f}")
print("Euclidean distance experiment per k:")
for item in euclidean_distance_by_k:
    print(f"k={item['k']} | avg={item['average_euclidean_distance']:.4f} | max={item['max_euclidean_distance']:.4f}")

# --- Plot 3D: pakai label Segment ASLI dari CSV (bukan 'Cluster 0/1/2/3' generik) ---
plot_3d_data = []
for _, row in df.iterrows():
    plot_3d_data.append({
        "x": float(row["Recency"]),
        "y": float(row["Frequency"]),
        "z": float(row["Monetary_log"]),
        "cluster": str(row["Segment"]),
        "name": str(row["Username"]),
    })

metrics = {
    "chosen_k": CHOSEN_K,
    "silhouette_score": round(float(sil_score), 4),
    "davies_bouldin_index": round(float(dbi_score), 4),
    "elbow_data": elbow_data,
    "euclidean_distance_by_k": euclidean_distance_by_k,
    "plot_3d_data": plot_3d_data,
}

with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
    json.dump(metrics, f, indent=2)

print(f"\nmodel_metrics.json berhasil dibuat: {OUTPUT_PATH}")
print(f"Total titik plot 3D: {len(plot_3d_data)}")
