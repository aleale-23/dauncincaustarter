
(() => {
    "use strict";

    const CART_KEY = "dauncincau_cart";
    const ORDERS_KEY = "dauncincau_orders";
    const ACTIVE_ORDER_KEY = "dauncincau_active_order";

    // Nomor WhatsApp Owner Daun Cincau ID
    const OWNER_WHATSAPP = "6281336544622";

    const checkoutFormSection =
        document.getElementById("checkoutFormSection");

    const paymentSection =
        document.getElementById("paymentSection");

    const customerForm =
        document.getElementById("customerForm");

    const checkoutItems =
        document.getElementById("checkoutItems");

    const checkoutSubtotal =
        document.getElementById("checkoutSubtotal");

    const checkoutTotal =
        document.getElementById("checkoutTotal");

    const formError =
        document.getElementById("formError");

    const pageTitle =
        document.getElementById("pageTitle");

    const pageSubtitle =
        document.getElementById("pageSubtitle");


    // MEMBACA DATA LOCAL STORAGE
    function readJSON(value, fallback) {
        try {
            return value ? JSON.parse(value) : fallback;
        } catch {
            return fallback;
        }
    }

    let cart = readJSON(
        localStorage.getItem(CART_KEY),
        []
    );

    let activeOrder = readJSON(
        localStorage.getItem(ACTIVE_ORDER_KEY),
        null
    );


    // FORMAT RUPIAH
    function rupiah(value) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(Number(value) || 0);
    }


    // MENCEGAH HTML INJECTION
    function escapeHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, char => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        })[char]);
    }


    // HITUNG TOTAL
    function getTotal(items) {
        return items.reduce((sum, item) => {
            return sum +
                (Number(item.price) * Number(item.quantity));
        }, 0);
    }


    // TAMPILKAN ITEM PESANAN
    function renderItems(target, items) {

        target.innerHTML = items.map(item => `

            <div class="checkout-line-item">

                <div>
                    <h3>${escapeHTML(item.name)}</h3>

                    <p>
                        ${Number(item.quantity)}
                        × ${rupiah(item.price)}
                    </p>
                </div>

                <strong>
                    ${rupiah(
                        Number(item.price) *
                        Number(item.quantity)
                    )}
                </strong>

            </div>

        `).join("");

    }


    // TAMPILKAN HALAMAN PEMBAYARAN
    function showPayment(order) {

        activeOrder = order;

        localStorage.setItem(
            ACTIVE_ORDER_KEY,
            JSON.stringify(order)
        );

        checkoutFormSection.hidden = true;
        paymentSection.hidden = false;

        pageTitle.textContent = "Pembayaran QRIS";

        pageSubtitle.textContent =
            "Pesanan berhasil dibuat. Silakan lakukan pembayaran.";

        document.getElementById(
            "paymentOrderNumber"
        ).textContent = order.orderNumber;

        document.getElementById(
            "paymentTrackingToken"
        ).textContent = order.trackingToken || "-";

        document.getElementById(
            "paymentTotal"
        ).textContent = rupiah(order.total);

        document.getElementById(
            "paymentSummaryTotal"
        ).textContent = rupiah(order.total);


        // INFORMASI PELANGGAN
        document.getElementById(
            "paymentCustomerDetails"
        ).innerHTML = `

            <div>
                <span>Nama</span>
                <strong>${escapeHTML(order.customer.name)}</strong>
            </div>

            <div>
                <span>WhatsApp</span>
                <strong>${escapeHTML(order.customer.phone)}</strong>
            </div>

            <div>
                <span>Alamat</span>
                <strong>${escapeHTML(order.customer.address)}</strong>
            </div>

            <div>
                <span>Status</span>
                <strong>PENDING</strong>
            </div>

        `;


        renderItems(
            document.getElementById("paymentItems"),
            order.items
        );

        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });

    }


    // JIKA KERANJANG KOSONG
    if (!Array.isArray(cart) || cart.length === 0) {

        if (
            activeOrder &&
            activeOrder.paymentStatus === "PENDING"
        ) {

            showPayment(activeOrder);

        } else {

            checkoutFormSection.innerHTML = `

                <div class="checkout-card"
                     style="grid-column:1/-1;text-align:center;padding:45px 20px">

                    <h2>Keranjang kamu masih kosong</h2>

                    <p>
                        Silakan pilih paket terlebih dahulu
                        sebelum melanjutkan checkout.
                    </p>

                    <a href="produk.html"
                       class="primary-checkout-btn"
                       style="max-width:260px;margin:20px auto 0;text-decoration:none">

                        Kembali ke Produk →

                    </a>

                </div>

            `;

        }

    } else {

        renderItems(checkoutItems, cart);

        const total = getTotal(cart);

        checkoutSubtotal.textContent = rupiah(total);
        checkoutTotal.textContent = rupiah(total);

    }



    /* =====================================
    PROSES CHECKOUT KE DATABASE MYSQL
    ===================================== */

    customerForm?.addEventListener("submit", async event => {

        event.preventDefault();

        formError.textContent = "";

        if (!cart.length) {
            formError.textContent = "Keranjang masih kosong.";
            return;
        }

        const name = document.getElementById("customerName").value.trim();
        const phone = document.getElementById("customerPhone").value.trim();
        const address = document.getElementById("customerAddress").value.trim();
        const note = document.getElementById("customerNote").value.trim();

        const submitButton = customerForm.querySelector(
            'button[type="submit"]'
        );

        if (!name || !phone || !address) {
            formError.textContent = "Lengkapi data pelanggan terlebih dahulu.";
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = "Menyimpan Pesanan...";

        try {

            const response = await fetch("api/create-order.php", {

                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify({

                    customer: {
                        name: name,
                        phone: phone,
                        address: address,
                        note: note
                    },

                    items: cart.map(item => ({
                        id: item.id,
                        quantity: item.quantity
                    }))

                })

            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || "Pesanan gagal disimpan.");
            }

            // DATA DARI DATABASE
            const savedOrder = {

                orderNumber: result.data.order_code,

                trackingToken: result.data.tracking_token,

                customer: {
                    name: name,
                    phone: phone,
                    address: address,
                    note: note
                },

                items: result.data.items.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: Number(item.price),
                    quantity: Number(item.quantity)
                })),

                total: Number(result.data.total),

                paymentMethod: "QRIS_STATIC",

                paymentStatus: "PENDING",

                orderStatus: "WAITING_PAYMENT"

            };

            // SIMPAN REFERENSI PESANAN TERAKHIR DI BROWSER
            localStorage.setItem(
                "dauncincau_active_order",
                JSON.stringify(savedOrder)
            );

            // KOSONGKAN KERANJANG
            localStorage.removeItem("dauncincau_cart");

            // TAMPILKAN PEMBAYARAN
            showPayment(savedOrder);

        } catch (error) {

            console.error("Checkout error:", error);

            formError.textContent =
                error.message || "Terjadi kesalahan. Silakan coba kembali.";

        } finally {

            submitButton.disabled = false;

            submitButton.textContent =
                "Buat Pesanan & Lanjut Bayar →";

        }

    });



    // NOTIFIKASI WHATSAPP OWNER
    document.getElementById(
        "notifyOwnerBtn"
    )?.addEventListener("click", () => {

        if (!activeOrder) return;

        const message = [

            "Halo Daun Cincau ID, saya ingin menginformasikan bahwa saya sudah melakukan pembayaran QRIS.",

            "",

            `Nomor Pesanan: ${activeOrder.orderNumber}`,

            `Nama: ${activeOrder.customer.name}`,

            `Total: ${rupiah(activeOrder.total)}`,

            "Metode Pembayaran: QRIS",

            "Status: Menunggu Verifikasi Owner",

            "",

            "Mohon bantu periksa transaksi saya. Terima kasih."

        ].join("\n");


        const whatsappURL =
            `https://wa.me/${OWNER_WHATSAPP}?text=${encodeURIComponent(message)}`;

        window.open(
            whatsappURL,
            "_blank",
            "noopener,noreferrer"
        );

    });


    // KEMBALI KE PRODUK
    document.getElementById(
        "backToProductsBtn"
    )?.addEventListener("click", () => {

        window.location.href = "produk.html";

    });


    // SALIN TOKEN PELACAKAN
    document.getElementById(
        "copyTrackingToken"
    )?.addEventListener("click", async () => {

        if (!activeOrder?.trackingToken) return;

        try {

            await navigator.clipboard.writeText(
                activeOrder.trackingToken
            );

            const button = document.getElementById(
                "copyTrackingToken"
            );

            button.textContent = "Berhasil Disalin!";

            setTimeout(() => {
                button.textContent = "Salin Kode";
            }, 2000);

        } catch (error) {

            alert("Gagal menyalin kode. Silakan salin secara manual.");

        }

    });

    // TAHUN FOOTER
    document.getElementById(
        "currentYear"
    ).textContent = new Date().getFullYear();

})();
