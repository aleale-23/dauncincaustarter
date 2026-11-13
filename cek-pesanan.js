
(() => {
    "use strict";

    const form = document.getElementById("trackingForm");

    const orderCodeInput =
        document.getElementById("trackingOrderCode");

    const phoneInput =
        document.getElementById("trackingPhone");

    const tokenInput =
        document.getElementById("trackingToken");

    const errorBox =
        document.getElementById("trackingError");

    const submitButton =
        document.getElementById("trackingSubmit");

    const resultSection =
        document.getElementById("trackingResult");

    const refreshButton =
        document.getElementById("refreshTracking");

    let lastSearch = null;


    // =====================================
    // FORMAT RUPIAH
    // =====================================

    function rupiah(value) {

        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(Number(value) || 0);

    }


    // =====================================
    // FORMAT TANGGAL
    // =====================================

    function formatDate(value) {

        const date = new Date(value.replace(" ", "T"));

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString("id-ID", {
            dateStyle: "long",
            timeStyle: "short"
        });

    }


    // =====================================
    // STATUS PEMBAYARAN
    // =====================================

    function paymentStatusText(status) {

        const statuses = {
            PENDING: "Menunggu Verifikasi",
            PAID: "Pembayaran Berhasil",
            FAILED: "Pembayaran Gagal",
            EXPIRED: "Pembayaran Kedaluwarsa",
            REFUNDED: "Pembayaran Dikembalikan"
        };

        return statuses[status] || status;

    }


    // =====================================
    // STATUS PESANAN
    // =====================================

    function orderStatusText(status) {

        const statuses = {
            WAITING_PAYMENT: "Menunggu Pembayaran",
            PROCESSING: "Sedang Diproses",
            READY: "Siap Diambil / Dikirim",
            COMPLETED: "Pesanan Selesai",
            CANCELLED: "Pesanan Dibatalkan"
        };

        return statuses[status] || status;

    }


    // =====================================
    // TAMPILKAN TIMELINE
    // =====================================

    function renderTimeline(order) {

        const container =
            document.getElementById("resultTimeline");

        const paymentStatus = order.payment_status;

        const orderStatus = order.order_status;

        const stages = [

            {
                title: "Pesanan Dibuat",
                description: "Pesanan berhasil tercatat di sistem.",
                done: true
            },

            {
                title: "Pembayaran QRIS",
                description: paymentStatus === "PAID"
                    ? "Pembayaran telah diverifikasi owner."
                    : "Menunggu pembayaran dan pemeriksaan owner.",
                done: paymentStatus === "PAID"
            },

            {
                title: "Pesanan Diproses",
                description: "Owner sedang menyiapkan pesanan.",
                done: ["PROCESSING", "READY", "COMPLETED"].includes(orderStatus)
            },

            {
                title: "Siap Diambil / Dikirim",
                description: "Pesanan sudah siap untuk pelanggan.",
                done: ["READY", "COMPLETED"].includes(orderStatus)
            },

            {
                title: "Pesanan Selesai",
                description: "Pesanan telah selesai.",
                done: orderStatus === "COMPLETED"
            }

        ];


        if (orderStatus === "CANCELLED") {

            stages.push({
                title: "Pesanan Dibatalkan",
                description: "Pesanan telah dibatalkan.",
                done: true
            });

        }

        if (paymentStatus === "FAILED" || paymentStatus === "EXPIRED") {

            stages.push({
                title: paymentStatus === "FAILED"
                    ? "Pembayaran Gagal"
                    : "Pembayaran Kedaluwarsa",
                description: paymentStatusText(paymentStatus),
                done: true
            });

        }

        container.innerHTML = stages.map(stage => `

            <div class="timeline-item ${stage.done ? "done" : ""}">

                <div class="timeline-marker"></div>

                <div class="timeline-content">

                    <strong>${stage.title}</strong>

                    <p>${stage.description}</p>

                </div>

            </div>

        `).join("");

    }


    // =====================================
    // TAMPILKAN HASIL PESANAN
    // =====================================

    function renderOrder(order) {

        document.getElementById(
            "resultOrderCode"
        ).textContent = order.order_code;

        document.getElementById(
            "resultTotal"
        ).textContent = rupiah(order.total_amount);

        document.getElementById(
            "resultCustomer"
        ).textContent = order.customer_name;

        document.getElementById(
            "resultDate"
        ).textContent = formatDate(order.created_at);


        // BADGE PEMBAYARAN
        const badge = document.getElementById(
            "resultPaymentBadge"
        );

        badge.textContent = order.payment_status;

        badge.className = "tracking-badge";

        if (order.payment_status === "PAID") {
            badge.classList.add("paid");
        } else if (
            ["FAILED", "EXPIRED", "REFUNDED"].includes(order.payment_status)
        ) {
            badge.classList.add("failed");
        }


        // KOTAK STATUS
        const statusBox = document.getElementById(
            "resultStatusBox"
        );

        const statusTitle = document.getElementById(
            "resultStatusTitle"
        );

        const statusDescription = document.getElementById(
            "resultStatusDescription"
        );

        statusTitle.textContent =
            paymentStatusText(order.payment_status);

        statusBox.className = "result-status-box";

        if (order.payment_status === "PAID") {

            statusBox.classList.add("paid");

            statusDescription.textContent =
                "Pembayaran telah diverifikasi owner. Status pesanan: " +
                orderStatusText(order.order_status) + ".";

        } else if (
            ["FAILED", "EXPIRED", "REFUNDED"].includes(order.payment_status)
        ) {

            statusBox.classList.add("failed");

            statusDescription.textContent =
                "Silakan hubungi owner untuk mendapatkan informasi lebih lanjut.";

        } else {

            statusDescription.textContent =
                "Pembayaran kamu masih menunggu pemeriksaan owner. Pesanan belum diproses.";

        }


        // ITEM PESANAN
        document.getElementById(
            "resultItems"
        ).innerHTML = order.items.map(item => {

            const row = document.createElement("div");

            row.className = "result-item";

            const name = document.createElement("span");

            name.textContent =
                item.product_name + " × " + item.quantity;

            const price = document.createElement("strong");

            price.textContent = rupiah(item.subtotal);

            row.append(name, price);

            return row.outerHTML;

        }).join("");


        renderTimeline(order);

        resultSection.hidden = false;

        resultSection.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });

    }


    // =====================================
    // CARI PESANAN
    // =====================================

    async function searchOrder() {

        if (!lastSearch) return;

        errorBox.textContent = "";

        submitButton.disabled = true;

        submitButton.textContent = "Mencari Pesanan...";

        try {

            const response = await fetch("api/track-order.php", {

                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify(lastSearch)

            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || "Pesanan tidak ditemukan.");
            }

            renderOrder(result.data);

        } catch (error) {

            resultSection.hidden = true;

            errorBox.textContent = error.message;

        } finally {

            submitButton.disabled = false;

            submitButton.textContent = "Cari Pesanan →";

        }

    }


    // =====================================
    // SUBMIT FORM
    // =====================================

    form.addEventListener("submit", event => {

        event.preventDefault();

        lastSearch = {

            order_code: orderCodeInput.value.trim().toUpperCase(),

            phone: phoneInput.value.trim(),

            tracking_token: tokenInput.value.trim()

        };

        searchOrder();

    });


    // =====================================
    // PERBARUI STATUS
    // =====================================

    refreshButton.addEventListener("click", () => {

        searchOrder();

    });


    // =====================================
    // ISI OTOMATIS DARI CHECKOUT
    // =====================================

    try {

        const savedOrder = JSON.parse(
            localStorage.getItem("dauncincau_active_order")
        );

        if (savedOrder) {

            orderCodeInput.value =
                savedOrder.orderNumber || "";

            phoneInput.value =
                savedOrder.customer?.phone || "";

            tokenInput.value =
                savedOrder.trackingToken || "";

        }

    } catch (error) {

        console.warn("Data pesanan lokal tidak tersedia.");

    }


    // FOOTER
    document.getElementById(
        "trackingYear"
    ).textContent = new Date().getFullYear();

})();
