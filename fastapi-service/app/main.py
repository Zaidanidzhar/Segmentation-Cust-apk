from fastapi import FastAPI, HTTPException
import pandas as pd
import json
from pathlib import Path

from app.schemas import (
    PredictRequest,
    PredictResponse,
    ExperimentRequest,
    ExperimentResponse,
    PeriodRfmRequest,
)
from app.services.rfm_service import RFMService
from app.ml.predictor import predictor


# ============================================================
# FASTAPI APPLICATION
# ============================================================

app = FastAPI(
    title="RFM K-Means Clustering API",
    description=(
        "API untuk segmentasi pelanggan menggunakan "
        "K-Means berbasis model RFM."
    ),
    version="1.0.0",
)


# ============================================================
# ROOT
# ============================================================

@app.get("/")
def root():
    return {
        "message": "RFM K-Means Clustering API",
        "status": "running",
    }


# ============================================================
# HEALTH CHECK
# ============================================================

@app.get("/health")
def health_check():

    return {
        "status": "healthy",
        "service": "rfm-kmeans-api",
    }


# ============================================================
# MODEL METRICS
# ============================================================

@app.get("/model-metrics")
def model_metrics():
    metrics_path = Path(__file__).resolve().parent.parent / "model_metrics.json"

    if not metrics_path.exists() or metrics_path.stat().st_size == 0:
        return {}

    try:
        with metrics_path.open("r", encoding="utf-8") as metrics_file:
            metrics = json.load(metrics_file)
    except (OSError, json.JSONDecodeError):
        return {}

    return metrics if isinstance(metrics, dict) else {}


# ============================================================
# PREDICT / FINAL CLUSTERING
# ============================================================

@app.post(
    "/predict-cluster",
    response_model=PredictResponse,
)
def predict_cluster(
    payload: PredictRequest,
):

    if not payload.customers:
        raise HTTPException(
            status_code=400,
            detail="Data customer kosong.",
        )

    if payload.n_clusters < 2:
        raise HTTPException(
            status_code=400,
            detail="Jumlah cluster minimal adalah 2.",
        )

    if payload.n_clusters > len(
        payload.customers
    ):
        raise HTTPException(
            status_code=400,
            detail=(
                f"Jumlah cluster ({payload.n_clusters}) "
                f"tidak boleh lebih besar dari "
                f"jumlah customer "
                f"({len(payload.customers)})."
            ),
        )

    try:

        customers_data = [
            customer.model_dump()
            for customer in payload.customers
        ]

        result = predictor.predict(
            customers=customers_data,
            n_clusters=payload.n_clusters,
        )

        return result

    except ValueError as e:

        raise HTTPException(
            status_code=400,
            detail=str(e),
        )

    except Exception as e:

        raise HTTPException(
            status_code=500,
            detail=(
                "Gagal melakukan clustering: "
                f"{str(e)}"
            ),
        )


# ============================================================
# K-MEANS EXPERIMENT
# ============================================================

@app.post(
    "/experiment-kmeans",
    response_model=ExperimentResponse,
)
def experiment_kmeans(
    payload: ExperimentRequest,
):

    # --------------------------------------------------------
    # VALIDASI DATA
    # --------------------------------------------------------

    if not payload.customers:

        raise HTTPException(
            status_code=400,
            detail="Data customer kosong.",
        )

    # --------------------------------------------------------
    # VALIDASI K MINIMUM
    # --------------------------------------------------------

    if payload.k_min < 2:

        raise HTTPException(
            status_code=400,
            detail="K minimum harus minimal 2.",
        )

    # --------------------------------------------------------
    # VALIDASI K MAXIMUM
    # --------------------------------------------------------

    if payload.k_max < payload.k_min:

        raise HTTPException(
            status_code=400,
            detail=(
                "K maksimum harus lebih besar "
                "atau sama dengan K minimum."
            ),
        )

    # --------------------------------------------------------
    # VALIDASI JUMLAH CUSTOMER
    # --------------------------------------------------------

    if payload.k_max > len(
        payload.customers
    ):

        raise HTTPException(
            status_code=400,
            detail=(
                f"K maksimum ({payload.k_max}) "
                f"tidak boleh lebih besar dari "
                f"jumlah customer "
                f"({len(payload.customers)})."
            ),
        )

    try:

        customers_data = [
            customer.model_dump()
            for customer in payload.customers
        ]

        # ----------------------------------------------------
        # JALANKAN EKSPERIMEN
        # ----------------------------------------------------

        result = predictor.experiment_kmeans(
            customers=customers_data,
            k_min=payload.k_min,
            k_max=payload.k_max,
            random_state=payload.random_state,
        )

        return result

    except ValueError as e:

        raise HTTPException(
            status_code=400,
            detail=str(e),
        )

    except Exception as e:

        raise HTTPException(
            status_code=500,
            detail=(
                "Gagal menjalankan eksperimen "
                f"K-Means: {str(e)}"
            ),
        )
    # ============================================================
# GENERATE RFM BY PERIOD
# ============================================================

@app.post(
    "/generate-rfm",
)
def generate_rfm(
    payload: PeriodRfmRequest,
):

    try:

        # ----------------------------------------------------
        # DATASET
        # ----------------------------------------------------

        dataset_path = (
            "data/Visscotton.xlsx"
        )

        dataframe = pd.read_excel(
            dataset_path
        )

        # ----------------------------------------------------
        # CUSTOM
        # ----------------------------------------------------

        if payload.period_type == "custom":

            if payload.end_date is None:

                raise HTTPException(
                    status_code=400,
                    detail=(
                        "end_date wajib diisi "
                        "untuk periode custom."
                    ),
                )

            start_date = payload.start_date
            end_date = payload.end_date

            rfm = RFMService.calculate_rfm(
                dataframe=dataframe,
                start_date=start_date,
                end_date=end_date,
            )

            return {
                "period_type": "custom",
                "start_date": start_date.isoformat(),
                "end_date": end_date.isoformat(),
                "customer_count": len(rfm),
                "data": rfm.to_dict(
                    orient="records"
                ),
            }

        # ----------------------------------------------------
        # PERIODIC
        # ----------------------------------------------------

        if payload.end_date is None:

            raise HTTPException(
                status_code=400,
                detail=(
                    "end_date wajib diisi "
                    "untuk analisis periodik."
                ),
            )

        periods = (
            RFMService.calculate_periods(
                dataframe=dataframe,
                period_type=payload.period_type,
                start_date=payload.start_date,
                end_date=payload.end_date,
            )
        )

        return {
            "period_type": payload.period_type,
            "start_date": payload.start_date.isoformat(),
            "end_date": payload.end_date.isoformat(),
            "periods": [
                {
                    "start_date": item[
                        "start_date"
                    ],
                    "end_date": item[
                        "end_date"
                    ],
                    "customer_count": item[
                        "customer_count"
                    ],
                    "data": item[
                        "rfm"
                    ].to_dict(
                        orient="records"
                    ),
                }
                for item in periods
            ],
        }

    except ValueError as e:

        raise HTTPException(
            status_code=400,
            detail=str(e),
        )

    except Exception as e:

        raise HTTPException(
            status_code=500,
            detail=(
                "Gagal membentuk RFM: "
                f"{str(e)}"
            ),
        )