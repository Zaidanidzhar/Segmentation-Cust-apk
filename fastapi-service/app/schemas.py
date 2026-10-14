from typing import List, Optional

from pydantic import BaseModel, Field
from datetime import date


# ============================================================
# CUSTOMER RFM
# ============================================================

class CustomerRfm(BaseModel):
    customer_id: int

    recency: float = Field(
        ...,
        description="Nilai Recency pelanggan"
    )

    frequency: float = Field(
        ...,
        description="Nilai Frequency pelanggan"
    )

    monetary: float = Field(
        ...,
        description="Nilai Monetary pelanggan"
    )


# ============================================================
# PREDICTION REQUEST
# ============================================================

class PredictRequest(BaseModel):
    customers: List[CustomerRfm]

    n_clusters: int = Field(
        default=4,
        ge=2,
        description="Jumlah cluster K-Means"
    )


# ============================================================
# PREDICTION RESULT
# ============================================================

class ClusterResult(BaseModel):
    customer_id: int
    cluster_label: int


# ============================================================
# ELBOW RESULT
# ============================================================

class ElbowPoint(BaseModel):
    k: int
    inertia: float


# ============================================================
# EUCLIDEAN DISTANCE RESULT
# ============================================================

class EuclideanDistanceByK(BaseModel):
    k: int

    average_euclidean_distance: float

    max_euclidean_distance: float


# ============================================================
# PREDICTION RESPONSE
# ============================================================

class PredictResponse(BaseModel):
    results: List[ClusterResult]

    chosen_k: int

    silhouette_score: Optional[float] = None

    davies_bouldin_index: Optional[float] = None

    elbow_data: List[ElbowPoint] = []

    euclidean_distance_by_k: List[
        EuclideanDistanceByK
    ] = []


# ============================================================
# K-MEANS EXPERIMENT REQUEST
# ============================================================

class ExperimentRequest(BaseModel):
    customers: List[CustomerRfm]

    k_min: int = Field(
        default=2,
        ge=2,
        description="Nilai K minimum untuk eksperimen"
    )

    k_max: int = Field(
        default=8,
        ge=2,
        description="Nilai K maksimum untuk eksperimen"
    )

    random_state: int = Field(
        default=42,
        description="Random state K-Means"
    )


# ============================================================
# K-MEANS EXPERIMENT RESULT
# ============================================================

class KMeansExperimentPoint(BaseModel):
    k: int

    inertia: float

    silhouette_score: Optional[float] = None

    davies_bouldin_index: Optional[float] = None

    average_euclidean_distance: float

    max_euclidean_distance: float


# ============================================================
# OPTIMAL K RESULT
# ============================================================

class OptimalKResult(BaseModel):
    recommended_k: int

    silhouette_best_k: int

    silhouette_best_score: float

    davies_bouldin_best_k: int

    davies_bouldin_best_score: float

    lowest_inertia_k: int

    selection_method: str


# ============================================================
# K-MEANS EXPERIMENT RESPONSE
# ============================================================

class ExperimentResponse(BaseModel):
    k_min: int

    k_max: int

    random_state: int

    results: List[
        KMeansExperimentPoint
    ]

    optimal_k: OptimalKResult
    # ============================================================
# PERIOD ANALYSIS
# ============================================================

class PeriodRfmRequest(BaseModel):
    period_type: str = Field(
        default="1_month",
        description=(
            "Jenis periode: "
            "1_month, 3_months, 6_months, "
            "12_months, custom"
        ),
    )

    start_date: date

    end_date: Optional[date] = None


class RfmCustomerResult(BaseModel):
    Username: str

    Recency: int

    Frequency: int

    Monetary: float

    LastPurchase: str

    Monetary_log: float


class PeriodRfmResponse(BaseModel):
    period_type: str

    start_date: str

    end_date: str

    customer_count: int

    data: List[RfmCustomerResult]