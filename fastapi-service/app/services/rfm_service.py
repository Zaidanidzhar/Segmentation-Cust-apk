from datetime import date
from typing import Any

import pandas as pd
import numpy as np


class RFMService:
    """
    Service untuk membentuk dataset RFM berdasarkan
    periode analisis yang dipilih.

    RFM:
    - Recency   = jarak hari dari transaksi terakhir
                   terhadap akhir periode
    - Frequency = jumlah order unik
    - Monetary  = total pembayaran order unik
    """

    # =========================================================
    # KOLOM DATA VISCOTTON
    # =========================================================

    ORDER_COLUMN = "No. Pesanan"
    STATUS_COLUMN = "Status Pesanan"
    CUSTOMER_COLUMN = "Username (Pembeli)"
    DATE_COLUMN = "Waktu Pesanan Selesai"
    MONETARY_COLUMN = "Total Pembayaran"

    VALID_STATUS = "Selesai"

    # =========================================================
    # LOAD DATAFRAME
    # =========================================================

    @classmethod
    def prepare_dataframe(
        cls,
        dataframe: pd.DataFrame,
    ) -> pd.DataFrame:

        required_columns = [
            cls.ORDER_COLUMN,
            cls.STATUS_COLUMN,
            cls.CUSTOMER_COLUMN,
            cls.DATE_COLUMN,
            cls.MONETARY_COLUMN,
        ]

        missing_columns = [
            column
            for column in required_columns
            if column not in dataframe.columns
        ]

        if missing_columns:
            raise ValueError(
                "Kolom dataset tidak lengkap: "
                + ", ".join(missing_columns)
            )

        df = dataframe.copy()

        # -----------------------------------------------------
        # DATE
        # -----------------------------------------------------

        df[cls.DATE_COLUMN] = pd.to_datetime(
            df[cls.DATE_COLUMN],
            errors="coerce",
        )

        # -----------------------------------------------------
        # MONETARY
        # -----------------------------------------------------

        df[cls.MONETARY_COLUMN] = (
            df[cls.MONETARY_COLUMN]
            .astype(str)
            .str.replace(".", "", regex=False)
            .str.replace(",", ".", regex=False)
        )

        df[cls.MONETARY_COLUMN] = pd.to_numeric(
            df[cls.MONETARY_COLUMN],
            errors="coerce",
        )

        # -----------------------------------------------------
        # CLEAN CUSTOMER
        # -----------------------------------------------------

        df[cls.CUSTOMER_COLUMN] = (
            df[cls.CUSTOMER_COLUMN]
            .astype(str)
            .str.strip()
        )

        # -----------------------------------------------------
        # CLEAN ORDER
        # -----------------------------------------------------

        df[cls.ORDER_COLUMN] = (
            df[cls.ORDER_COLUMN]
            .astype(str)
            .str.strip()
        )

        # -----------------------------------------------------
        # FILTER TRANSAKSI VALID
        # -----------------------------------------------------

        df = df[
            df[cls.STATUS_COLUMN]
            .astype(str)
            .str.strip()
            .eq(cls.VALID_STATUS)
        ]

        # Buang data yang tidak memiliki
        # customer / order / tanggal / monetary
        df = df.dropna(
            subset=[
                cls.CUSTOMER_COLUMN,
                cls.ORDER_COLUMN,
                cls.DATE_COLUMN,
                cls.MONETARY_COLUMN,
            ]
        )

        df = df[
            (df[cls.CUSTOMER_COLUMN] != "")
            & (df[cls.ORDER_COLUMN] != "")
        ]

        return df.reset_index(drop=True)

    # =========================================================
    # PERIODE
    # =========================================================

    @staticmethod
    def get_period_range(
        period_type: str,
        start_date: date,
        end_date: date | None = None,
    ) -> tuple[pd.Timestamp, pd.Timestamp]:

        start = pd.Timestamp(start_date)

        if period_type == "custom":

            if end_date is None:
                raise ValueError(
                    "end_date wajib diisi untuk periode custom."
                )

            end = pd.Timestamp(end_date)

        elif period_type == "1_month":

            end = start + pd.DateOffset(months=1) - pd.Timedelta(days=1)

        elif period_type == "3_months":

            end = start + pd.DateOffset(months=3) - pd.Timedelta(days=1)

        elif period_type == "6_months":

            end = start + pd.DateOffset(months=6) - pd.Timedelta(days=1)

        elif period_type == "12_months":

            end = start + pd.DateOffset(months=12) - pd.Timedelta(days=1)

        else:

            raise ValueError(
                "period_type harus salah satu dari: "
                "1_month, 3_months, 6_months, "
                "12_months, custom."
            )

        if end < start:
            raise ValueError(
                "Tanggal akhir tidak boleh lebih kecil "
                "dari tanggal mulai."
            )

        return start, end

    # =========================================================
    # RFM SATU PERIODE
    # =========================================================

    @classmethod
    def calculate_rfm(
        cls,
        dataframe: pd.DataFrame,
        start_date: date,
        end_date: date,
    ) -> pd.DataFrame:

        df = cls.prepare_dataframe(dataframe)

        start = pd.Timestamp(start_date).normalize()

        end = (
            pd.Timestamp(end_date)
            .normalize()
            + pd.Timedelta(days=1)
            - pd.Timedelta(microseconds=1)
        )

        # -----------------------------------------------------
        # FILTER PERIODE
        # -----------------------------------------------------

        period_df = df[
            (df[cls.DATE_COLUMN] >= start)
            & (df[cls.DATE_COLUMN] <= end)
        ].copy()

        if period_df.empty:

            return pd.DataFrame(
                columns=[
                    "Periode",
                    "Username",
                    "Recency",
                    "Frequency",
                    "Monetary",
                    "LastPurchase",
                    "Monetary_log",
                ]
            )

        # =====================================================
        # SATU ORDER BISA MEMILIKI BANYAK BARIS PRODUK
        # =====================================================
        #
        # Kita deduplicate order terlebih dahulu.
        #
        # Frequency = jumlah order unik
        # Monetary  = nilai Total Pembayaran satu kali/order
        #

        order_df = (
            period_df
            .sort_values(
                [
                    cls.ORDER_COLUMN,
                    cls.DATE_COLUMN,
                ]
            )
            .groupby(
                cls.ORDER_COLUMN,
                as_index=False,
            )
            .agg(
                {
                    cls.CUSTOMER_COLUMN: "first",
                    cls.DATE_COLUMN: "max",
                    cls.MONETARY_COLUMN: "first",
                }
            )
        )

        # -----------------------------------------------------
        # RFM PER CUSTOMER
        # -----------------------------------------------------

        rfm = (
            order_df
            .groupby(
                cls.CUSTOMER_COLUMN,
                as_index=False,
            )
            .agg(
                LastPurchase=(
                    cls.DATE_COLUMN,
                    "max",
                ),
                Frequency=(
                    cls.ORDER_COLUMN,
                    "nunique",
                ),
                Monetary=(
                    cls.MONETARY_COLUMN,
                    "sum",
                ),
            )
        )

        # -----------------------------------------------------
        # RECENCY
        # -----------------------------------------------------

        reference_date = pd.Timestamp(
            end_date
        ).normalize()

        rfm["Recency"] = (
            reference_date
            - pd.to_datetime(
                rfm["LastPurchase"]
            ).dt.normalize()
        ).dt.days

        # -----------------------------------------------------
        # MONETARY LOG
        # -----------------------------------------------------

        rfm["Monetary_log"] = np.log1p(
            rfm["Monetary"].clip(lower=0)
        )

        # -----------------------------------------------------
        # PERIODE
        # -----------------------------------------------------

        rfm.insert(
            0,
            "Periode",
            (
                pd.Timestamp(start_date)
                .strftime("%Y-%m-%d")
                + " s/d "
                + pd.Timestamp(end_date)
                .strftime("%Y-%m-%d")
            ),
        )

        rfm = rfm.rename(
            columns={
                cls.CUSTOMER_COLUMN: "Username",
            }
        )

        # -----------------------------------------------------
        # URUTAN KOLOM
        # -----------------------------------------------------

        rfm = rfm[
            [
                "Periode",
                "Username",
                "Recency",
                "Frequency",
                "Monetary",
                "LastPurchase",
                "Monetary_log",
            ]
        ]

        return rfm.sort_values(
            "Monetary",
            ascending=False,
        ).reset_index(drop=True)

    # =========================================================
    # GENERATE PERIODE BERULANG
    # =========================================================

    @classmethod
    def calculate_periods(
        cls,
        dataframe: pd.DataFrame,
        period_type: str,
        start_date: date,
        end_date: date,
    ) -> list[dict[str, Any]]:

        df = cls.prepare_dataframe(dataframe)

        overall_start = pd.Timestamp(
            start_date
        ).normalize()

        overall_end = pd.Timestamp(
            end_date
        ).normalize()

        periods = []

        if period_type == "1_month":
            months = 1

        elif period_type == "3_months":
            months = 3

        elif period_type == "6_months":
            months = 6

        elif period_type == "12_months":
            months = 12

        else:
            raise ValueError(
                "calculate_periods hanya mendukung "
                "1_month, 3_months, 6_months, "
                "dan 12_months."
            )

        current_start = overall_start

        while current_start <= overall_end:

            current_end = (
                current_start
                + pd.DateOffset(months=months)
                - pd.Timedelta(days=1)
            )

            # Jangan melewati batas periode utama
            if current_end > overall_end:
                current_end = overall_end

            rfm = cls.calculate_rfm(
                dataframe=df,
                start_date=current_start.date(),
                end_date=current_end.date(),
            )

            periods.append(
                {
                    "start_date": (
                        current_start.strftime(
                            "%Y-%m-%d"
                        )
                    ),
                    "end_date": (
                        current_end.strftime(
                            "%Y-%m-%d"
                        )
                    ),
                    "customer_count": int(
                        len(rfm)
                    ),
                    "rfm": rfm,
                }
            )

            current_start = (
                current_start
                + pd.DateOffset(
                    months=months
                )
            )

        return periods