/* =========================================
   JAVASCRIPT HALAMAN KONTAK
   DAUN CINCAU ID
========================================= */

document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("contact-page-loaded");

  /* =====================================
     1. FORMULIR KIRIM PESAN WHATSAPP
  ===================================== */

  const contactForm = document.getElementById("contactForm");

  if (contactForm) {
    contactForm.addEventListener("submit", (event) => {
      event.preventDefault();

      if (!contactForm.reportValidity()) return;

      const nama = document.getElementById("nama").value.trim();
      const kontak = document.getElementById("kontak").value.trim();
      const subjek = document.getElementById("subjek").value;
      const pesan = document.getElementById("pesan").value.trim();

      const nomorTujuan = "6289516817752";

      const isiPesan =
        "Halo Daun Cincau ID!\n\n" +
        "Saya ingin menghubungi Daun Cincau ID.\n\n" +
        "Nama: " +
        nama +
        "\n" +
        "Kontak: " +
        kontak +
        "\n" +
        "Topik: " +
        subjek +
        "\n\n" +
        "Pesan:\n" +
        pesan;

      const url =
        "https://wa.me/" +
        nomorTujuan +
        "?text=" +
        encodeURIComponent(isiPesan);

      const whatsappWindow = window.open(url, "_blank");

      if (whatsappWindow) {
        whatsappWindow.opener = null;
      } else {
        window.location.href = url;
      }
    });
  }

  /* =====================================
     2. SCROLL REVEAL ANIMATION
  ===================================== */

  const revealElements = document.querySelectorAll(`
    .contact-info,
    .contact-form-card,
    .contact-closing,
    .site-footer .footer-brand,
    .site-footer .footer-column,
    .footer-bottom
  `);

  const reduceMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  ).matches;

  if (reduceMotion || !("IntersectionObserver" in window)) {
    revealElements.forEach((element) => {
      element.classList.add("is-visible");
    });
  } else {
    const observer = new IntersectionObserver(
      (entries, observerInstance) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");

            observerInstance.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.12,
        rootMargin: "0px 0px -40px 0px",
      },
    );

    revealElements.forEach((element) => {
      element.classList.add("reveal-on-scroll");
      observer.observe(element);
    });
  }

  /* =====================================
     3. KERANJANG BELANJA
     MENGGUNAKAN LOCAL STORAGE
  ===================================== */

  const CART_STORAGE_KEY = "daunCincauCart";

  const cartPanel = document.getElementById("cartPanel");
  const cartOverlay = document.getElementById("overlay");
  const cartItemsContainer = document.getElementById("cartItems");

  const cartCountElement = document.getElementById("cartCount");
  const cartTotalElement = document.getElementById("cartTotal");

  const openCartButton = document.getElementById("openCart");
  const closeCartButton = document.getElementById("closeCart");

  const checkoutButton = document.getElementById("checkoutButton");

  const toast = document.getElementById("toast");

  let cart = loadCart();

  let toastTimer;

  /* MEMBACA DATA KERANJANG */

  function loadCart() {
    try {
      const savedCart = localStorage.getItem(CART_STORAGE_KEY);

      const parsedCart = savedCart ? JSON.parse(savedCart) : [];

      return Array.isArray(parsedCart) ? parsedCart : [];
    } catch (error) {
      console.error("Keranjang tidak dapat dibaca:", error);

      return [];
    }
  }

  /* MENYIMPAN DATA KERANJANG */

  function saveCart() {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    } catch (error) {
      console.error("Keranjang tidak dapat disimpan:", error);
    }
  }

  /* MENGAMBIL INFORMASI PRODUK */

  function getItemName(item) {
    return item.name ?? item.nama ?? item.title ?? item.productName ?? "Produk";
  }

  function getItemPrice(item) {
    const value = item.price ?? item.harga ?? item.productPrice ?? 0;

    if (typeof value === "number") {
      return value;
    }

    return Number(String(value).replace(/[^\d]/g, "")) || 0;
  }

  function getItemQuantity(item) {
    return Math.max(0, Number(item.qty ?? item.quantity ?? item.jumlah ?? 1));
  }

  function setItemQuantity(item, quantity) {
    if (
      Object.prototype.hasOwnProperty.call(item, "quantity") &&
      !Object.prototype.hasOwnProperty.call(item, "qty")
    ) {
      item.quantity = quantity;
    } else if (
      Object.prototype.hasOwnProperty.call(item, "jumlah") &&
      !Object.prototype.hasOwnProperty.call(item, "qty")
    ) {
      item.jumlah = quantity;
    } else {
      item.qty = quantity;
    }
  }

  /* FORMAT HARGA RUPIAH */

  function formatRupiah(value) {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(Number(value) || 0);
  }

  /* MENCEGAH TEKS PRODUK MEMASUKKAN HTML */

  function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, (character) => {
      const entities = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      };

      return entities[character];
    });
  }

  /* =====================================
     4. MENAMPILKAN ISI KERANJANG
  ===================================== */

  function renderCart() {
    if (!cartCountElement || !cartItemsContainer || !cartTotalElement) return;

    cart = cart.filter((item) => item && typeof item === "object");

    const totalQuantity = cart.reduce(
      (sum, item) => sum + getItemQuantity(item),
      0,
    );

    const totalPrice = cart.reduce(
      (sum, item) => sum + getItemPrice(item) * getItemQuantity(item),
      0,
    );

    // Update jumlah produk

    cartCountElement.textContent = totalQuantity;

    // Update total harga

    cartTotalElement.textContent = formatRupiah(totalPrice);

    // Jika keranjang kosong

    if (cart.length === 0 || totalQuantity === 0) {
      cartItemsContainer.innerHTML = `

        <div class="empty-cart">

          <span class="empty-cart-icon">🛒</span>

          <h3>Keranjang masih kosong</h3>

          <p>
            Yuk, pilih produk favoritmu di halaman Produk.
          </p>

          <a href="../produk.html" class="cart-browse-link">
            Lihat Produk
          </a>

        </div>

      `;

      return;
    }

    // Menampilkan produk

    cartItemsContainer.innerHTML = cart
      .map((item, index) => {
        const itemName = escapeHTML(getItemName(item));

        const itemPrice = getItemPrice(item);

        const itemQuantity = getItemQuantity(item);

        const itemSubtotal = itemPrice * itemQuantity;

        return `

        <article class="cart-item">

          <div class="cart-item-info">

            <h4>${itemName}</h4>

            <p>
              ${formatRupiah(itemPrice)} × ${itemQuantity}
            </p>

            <strong>
              ${formatRupiah(itemSubtotal)}
            </strong>

          </div>


          <div class="cart-quantity">

            <button
              type="button"
              class="quantity-button"
              data-cart-index="${index}"
              data-cart-change="-1"
              aria-label="Kurangi jumlah produk"
            >
              −
            </button>

            <span>${itemQuantity}</span>

            <button
              type="button"
              class="quantity-button"
              data-cart-index="${index}"
              data-cart-change="1"
              aria-label="Tambah jumlah produk"
            >
              +
            </button>

          </div>

        </article>

      `;
      })
      .join("");

    // Event tombol tambah dan kurang

    cartItemsContainer
      .querySelectorAll("[data-cart-change]")
      .forEach((button) => {
        button.addEventListener("click", () => {
          const index = Number(button.dataset.cartIndex);

          const change = Number(button.dataset.cartChange);

          changeCartQuantity(index, change);
        });
      });
  }

  /* =====================================
     5. MENGUBAH JUMLAH PRODUK
  ===================================== */

  function changeCartQuantity(index, change) {
    const item = cart[index];

    if (!item) return;

    const nextQuantity = getItemQuantity(item) + change;

    if (nextQuantity <= 0) {
      cart.splice(index, 1);
    } else {
      setItemQuantity(item, nextQuantity);
    }

    saveCart();

    renderCart();
  }

  /* =====================================
     6. MEMBUKA KERANJANG
  ===================================== */

  function openCart() {
    if (!cartPanel || !cartOverlay) return;

    renderCart();

    cartPanel.classList.add("open");

    cartOverlay.classList.add("show");

    document.body.classList.add("cart-open");

    cartPanel.setAttribute("aria-hidden", "false");

    cartOverlay.setAttribute("aria-hidden", "false");

    openCartButton?.setAttribute("aria-expanded", "true");

    closeCartButton?.focus();
  }

  /* =====================================
     7. MENUTUP KERANJANG
  ===================================== */

  function closeCart() {
    if (!cartPanel || !cartOverlay) return;

    cartPanel.classList.remove("open");

    cartOverlay.classList.remove("show");

    document.body.classList.remove("cart-open");

    cartPanel.setAttribute("aria-hidden", "true");

    cartOverlay.setAttribute("aria-hidden", "true");

    openCartButton?.setAttribute("aria-expanded", "false");
  }

  /* =====================================
     8. NOTIFIKASI
  ===================================== */

  function showToast(message) {
    if (!toast) return;

    toast.textContent = message;

    toast.classList.add("show");

    clearTimeout(toastTimer);

    toastTimer = setTimeout(() => {
      toast.classList.remove("show");
    }, 2200);
  }

  /* EVENT KERANJANG */

  openCartButton?.addEventListener("click", openCart);

  closeCartButton?.addEventListener("click", closeCart);

  cartOverlay?.addEventListener("click", closeCart);

  // Tutup dengan tombol ESC

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeCart();
    }
  });

  /* =====================================
     9. CHECKOUT VIA WHATSAPP
  ===================================== */

  checkoutButton?.addEventListener("click", () => {
    renderCart();

    if (cart.length === 0) {
      alert("Keranjang masih kosong. Silakan pilih produk terlebih dahulu.");

      return;
    }

    const customerName =
      document.getElementById("customerName")?.value.trim() || "";

    const orderNotes =
      document.getElementById("orderNotes")?.value.trim() || "-";

    if (!customerName) {
      alert("Masukkan nama pemesan terlebih dahulu.");

      document.getElementById("customerName")?.focus();

      return;
    }

    const total = cart.reduce(
      (sum, item) => sum + getItemPrice(item) * getItemQuantity(item),
      0,
    );

    const orderDetails = cart
      .map((item) => {
        const name = getItemName(item);

        const quantity = getItemQuantity(item);

        const subtotal = getItemPrice(item) * quantity;

        return "- " + name + " x" + quantity + " = " + formatRupiah(subtotal);
      })
      .join("\n");

    const message =
      "Halo Daun Cincau ID, saya ingin melakukan pemesanan.\n\n" +
      "Nama: " +
      customerName +
      "\n\n" +
      "Daftar Pesanan:\n" +
      orderDetails +
      "\n\n" +
      "Total: " +
      formatRupiah(total) +
      "\n" +
      "Catatan: " +
      orderNotes;

    const whatsappNumber = "6289516817752";

    const whatsappURL =
      "https://wa.me/" +
      whatsappNumber +
      "?text=" +
      encodeURIComponent(message);

    const whatsappWindow = window.open(whatsappURL, "_blank");

    if (whatsappWindow) {
      whatsappWindow.opener = null;

      showToast("Detail pesanan siap dikirim melalui WhatsApp.");
    } else {
      window.location.href = whatsappURL;
    }
  });

  /* =====================================
     10. SINKRONISASI LOCAL STORAGE
  ===================================== */

  window.addEventListener("storage", (event) => {
    if (event.key === CART_STORAGE_KEY) {
      cart = loadCart();

      renderCart();
    }
  });

  // Tampilkan isi keranjang saat halaman dibuka

  renderCart();
});

/* =========================================
   11. SMART NAVBAR
   HIDE SAAT SCROLL DOWN
   SHOW SAAT SCROLL UP
========================================= */

(() => {
  const header = document.querySelector("body > header");

  if (!header) return;

  let lastScrollY = window.scrollY;

  const scrollThreshold = 6;

  if (lastScrollY > 80) {
    header.classList.add("header-hidden");
  }

  window.addEventListener(
    "scroll",

    () => {
      const currentScrollY = window.scrollY;

      // Tampilkan ketika di bagian paling atas

      if (currentScrollY <= 15) {
        header.classList.remove("header-hidden");

        lastScrollY = currentScrollY;

        return;
      }

      // Scroll ke bawah

      if (currentScrollY > lastScrollY + scrollThreshold) {
        header.classList.add("header-hidden");

        lastScrollY = currentScrollY;
      }

      // Scroll ke atas
      else if (currentScrollY < lastScrollY - scrollThreshold) {
        header.classList.remove("header-hidden");

        lastScrollY = currentScrollY;
      }
    },

    { passive: true },
  );
})();
