
/* =========================================
   DAUN CINCAU ID - PRODUCT PAGE
========================================= */

document.addEventListener("DOMContentLoaded", () => {

  /* =====================================
     FILTER DAN PENCARIAN PRODUK
  ===================================== */

  const filterButtons = document.querySelectorAll(".filter-btn");
  const productCards = document.querySelectorAll(".catalog-card");
  const searchInput = document.getElementById("productSearch");
  const noProduct = document.getElementById("noProduct");

  let activeFilter = "semua";

  function filterProducts() {

    const keyword = searchInput.value.toLowerCase().trim();
    let visibleCount = 0;

    productCards.forEach(card => {

      const category = card.dataset.category;
      const name = card.querySelector("h3").textContent.toLowerCase();

      const matchCategory =
        activeFilter === "semua" || category === activeFilter;

      const matchSearch = name.includes(keyword);

      if (matchCategory && matchSearch) {
        card.classList.remove("hidden");
        visibleCount++;
      } else {
        card.classList.add("hidden");
      }

    });

    noProduct.style.display =
      visibleCount === 0 ? "block" : "none";

  }

  filterButtons.forEach(button => {

    button.addEventListener("click", () => {

      filterButtons.forEach(btn =>
        btn.classList.remove("active")
      );

      button.classList.add("active");

      activeFilter = button.dataset.filter;

      filterProducts();

    });

  });

  searchInput.addEventListener("input", filterProducts);


/* =====================================
   MODAL DETAIL PRODUK
===================================== */

const productModal = document.getElementById("productModal");

const modalName = document.getElementById("modalProductName");
const modalDescription = document.getElementById("modalProductDescription");
const modalComposition = document.getElementById("modalProductComposition");
const modalAdvantages = document.getElementById("modalProductAdvantages");
const modalBadge = document.getElementById("modalProductBadge");
const modalImage = document.getElementById("modalProductImage");

const modalOrderButton = document.getElementById("modalOrderButton");

const closeModal = document.getElementById("closeModal");
const modalOk = document.getElementById("modalOk");


// DATA DETAIL PRODUK
const productDetails = {

  "Cincau Murni": {
    badge: "Cincau Alami",

    description:
      "Cincau hijau alami dengan tekstur lembut dan rasa khas yang menyegarkan. Tersedia dalam beberapa pilihan ukuran.",

    composition:
      "Daun cincau dan air minum kemasan.",

    advantages: [
      "Dibuat dari daun cincau alami.",
      "Tersedia ukuran 1 liter, 500 ml, dan 420 ml.",
      "Dapat dinikmati dengan berbagai pilihan pelengkap."
    ]
  },


  "Cincau Original": {
    badge: "Minuman Siap Minum",

    description:
      "Minuman cincau siap minum dengan perpaduan rasa original yang lembut dan menyegarkan. Praktis dinikmati kapan saja.",

    composition:
      "Cincau, krimer, dan gula jawa.",

    advantages: [
      "Memiliki cita rasa original yang khas.",
      "Praktis karena siap dinikmati.",
      "Tersedia pilihan topping nangka, roti tawar, dan selasih."
    ]
  },


  "Cincau Cokelat": {
    badge: "Minuman Siap Minum",

    description:
      "Minuman cincau dengan perpaduan rasa cokelat yang nikmat untuk menemani aktivitasmu.",

    composition:
      "Cincau, krimer, gula jawa, dan cocoa powder.",

    advantages: [
      "Memiliki perpaduan rasa cokelat dan cincau.",
      "Praktis karena siap dinikmati.",
      "Tersedia pilihan topping nangka, roti tawar, dan selasih."
    ]
  },


  "Cincau Frambozen": {
    badge: "Minuman Siap Minum",

    description:
      "Minuman cincau dengan perpaduan rasa frambozen yang memberikan sensasi segar.",

    composition:
      "Krimer dan sirup frambozen.",

    advantages: [
      "Memiliki pilihan rasa frambozen.",
      "Praktis untuk dinikmati.",
      "Tersedia pilihan topping nangka, roti tawar, dan selasih."
    ]
  },


  "Krimer": {
    badge: "Bahan Pelengkap",

    description:
      "Krimer sebagai pelengkap sajian cincau untuk memberikan sensasi creamy sesuai selera.",

    composition:
      "Krimer.",

    advantages: [
      "Dapat dipadukan dengan cincau murni.",
      "Menambah cita rasa creamy.",
      "Tersedia dalam kemasan 350 ml."
    ]
  },


  "Gula Jawa Cair": {
    badge: "Bahan Pelengkap",

    description:
      "Gula jawa cair sebagai pelengkap sajian cincau dengan cita rasa manis khas gula jawa.",

    composition:
      "Gula jawa cair.",

    advantages: [
      "Memberikan rasa manis khas gula jawa.",
      "Dapat disesuaikan dengan selera.",
      "Tersedia dalam kemasan 350 ml."
    ]
  },


  "Kuah Original": {
    badge: "Bahan Pelengkap",

    description:
      "Kuah original untuk melengkapi sajian cincau agar semakin nikmat dan menyegarkan.",

    composition:
      "Kuah original.",

    advantages: [
      "Cocok dipadukan dengan cincau murni.",
      "Praktis sebagai pelengkap sajian.",
      "Tersedia dalam kemasan 350 ml."
    ]
  },


  "Kuah Cokelat": {
    badge: "Bahan Pelengkap",

    description:
      "Kuah bercita rasa cokelat sebagai pelengkap sajian cincau.",

    composition:
      "Kuah cokelat.",

    advantages: [
      "Memberikan variasi rasa cokelat.",
      "Dapat dipadukan dengan cincau murni.",
      "Tersedia dalam kemasan 350 ml."
    ]
  },


  "Kuah Frambozen": {
    badge: "Bahan Pelengkap",

    description:
      "Kuah dengan sentuhan rasa frambozen untuk variasi sajian cincau.",

    composition:
      "Kuah frambozen.",

    advantages: [
      "Memberikan variasi rasa frambozen.",
      "Dapat dipadukan dengan cincau murni.",
      "Tersedia dalam kemasan 350 ml."
    ]
  }

};


// FUNGSI MEMBUKA MODAL
function showModal(button) {

  const productName = button.dataset.name;

  const product = productDetails[productName];

  if (!product) return;

  // Mengambil gambar dari kartu produk
  const card = button.closest(".catalog-card");

  const cardImage = card.querySelector(".catalog-image img");

  // Mengisi informasi produk
  modalName.textContent = productName;

  modalBadge.textContent = product.badge;

  modalDescription.textContent = product.description;

  modalComposition.textContent = product.composition;

  // Mengisi daftar keunggulan
  modalAdvantages.innerHTML = "";

  product.advantages.forEach(item => {

    const li = document.createElement("li");

    li.textContent = item;

    modalAdvantages.appendChild(li);

  });

  // Mengganti gambar sesuai produk
  modalImage.src = cardImage.getAttribute("src");

  modalImage.alt = productName;

  // Mengarahkan tombol pesan ke WhatsApp
  const message = encodeURIComponent(
    "Halo Daun Cincau ID, saya ingin bertanya mengenai produk " +
    productName + "."
  );

  modalOrderButton.href =
    "https://wa.me/6281336544622?text=" + message;

  // Menampilkan modal
  productModal.classList.add("show");

  productModal.setAttribute("aria-hidden", "false");

  document.body.style.overflow = "hidden";

  closeModal.focus();

}


// FUNGSI MENUTUP MODAL
function hideModal() {

  productModal.classList.remove("show");

  productModal.setAttribute("aria-hidden", "true");

  document.body.style.overflow = "";

}


// EVENT TOMBOL LIHAT DETAIL
document.querySelectorAll(".detail-btn").forEach(button => {

  button.addEventListener("click", () => {

    showModal(button);

  });

});


// EVENT TOMBOL TUTUP
closeModal.addEventListener("click", hideModal);

modalOk.addEventListener("click", hideModal);


// TUTUP MODAL KETIKA KLIK AREA LUAR
productModal.addEventListener("click", event => {

  if (event.target === productModal) {

    hideModal();

  }

});


// TUTUP MODAL DENGAN TOMBOL ESCAPE
document.addEventListener("keydown", event => {

  if (
    event.key === "Escape" &&
    productModal.classList.contains("show")
  ) {

    hideModal();

  }

});


  /* =====================================
     KERANJANG BELANJA
  ===================================== */

  const cart = [];

  const openCart = document.getElementById("openCart");
  const closeCart = document.getElementById("closeCart");

  const cartDrawer = document.getElementById("cartDrawer");
  const cartOverlay = document.getElementById("cartOverlay");

  const cartItems = document.getElementById("cartItems");

  const cartCount = document.getElementById("cartCount");

  const cartSubtotal = document.getElementById("cartSubtotal");

  const checkoutBtn = document.getElementById("checkoutBtn");


  // FORMAT RUPIAH
  function formatRupiah(number) {

    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0
    }).format(number);

  }


  // BUKA KERANJANG
  function showCart() {

    cartDrawer.classList.add("show");
    cartOverlay.classList.add("show");

    document.body.style.overflow = "hidden";

  }


  // TUTUP KERANJANG
  function hideCart() {

    cartDrawer.classList.remove("show");
    cartOverlay.classList.remove("show");

    document.body.style.overflow = "";

  }

  openCart.addEventListener("click", showCart);

  closeCart.addEventListener("click", hideCart);

  cartOverlay.addEventListener("click", hideCart);


  // TAMBAH PRODUK KE KERANJANG
  document.querySelectorAll(".add-cart-btn").forEach(button => {

    button.addEventListener("click", () => {

      const id = button.dataset.id;

      const name = button.dataset.name;

      const price = Number(button.dataset.price);

      const existingItem = cart.find(item => item.id === id);

      if (existingItem) {

        existingItem.quantity++;

      } else {

        cart.push({
          id,
          name,
          price,
          quantity: 1
        });

      }

      renderCart();

      // Feedback tombol
      const originalText = button.textContent;

      button.textContent = "✓ Ditambahkan";

      button.style.background = "#244d2d";

      setTimeout(() => {

        button.textContent = originalText;
        button.style.background = "";

      }, 1200);

    });

  });


  // UPDATE KERANJANG
  function renderCart() {

    let totalQuantity = 0;
    let subtotal = 0;

    cart.forEach(item => {

      totalQuantity += item.quantity;

      subtotal += item.price * item.quantity;

    });


    // UPDATE JUMLAH ITEM
    cartCount.textContent = totalQuantity;


    // KERANJANG KOSONG
    if (cart.length === 0) {

      cartItems.innerHTML = `
        <div class="empty-cart">
          <span>🛒</span>
          <h3>Keranjang masih kosong</h3>
          <p>Yuk, pilih paket favoritmu!</p>
        </div>
      `;

    } else {

      cartItems.innerHTML = cart.map(item => `

        <div class="cart-item">

          <div>

            <h4>${item.name}</h4>

            <p class="item-price">
              ${formatRupiah(item.price)}
            </p>

            <div class="quantity-control">

              <button
                class="quantity-btn"
                data-id="${item.id}"
                data-action="minus">
                −
              </button>

              <span>${item.quantity}</span>

              <button
                class="quantity-btn"
                data-id="${item.id}"
                data-action="plus">
                +
              </button>

            </div>

          </div>

          <button
            class="remove-item"
            data-id="${item.id}">
            Hapus
          </button>

        </div>

      `).join("");

    }


    // UPDATE SUBTOTAL
    cartSubtotal.textContent = formatRupiah(subtotal);

    checkoutBtn.disabled = cart.length === 0;


    // UPDATE TOMBOL JUMLAH
    document.querySelectorAll(".quantity-btn").forEach(button => {

      button.addEventListener("click", () => {

        updateQuantity(
          button.dataset.id,
          button.dataset.action
        );

      });

    });


    // HAPUS ITEM
    document.querySelectorAll(".remove-item").forEach(button => {

      button.addEventListener("click", () => {

        const index = cart.findIndex(
          item => item.id === button.dataset.id
        );

        if (index !== -1) {

          cart.splice(index, 1);

          renderCart();

        }

      });

    });

  }


  // TAMBAH / KURANG JUMLAH
  function updateQuantity(id, action) {

    const item = cart.find(item => item.id === id);

    if (!item) return;

    if (action === "plus") {

      item.quantity++;

    } else if (action === "minus") {

      item.quantity--;

      if (item.quantity <= 0) {

        const index = cart.findIndex(
          product => product.id === id
        );

        cart.splice(index, 1);

      }

    }

    renderCart();

  }


  /* =====================================
     CHECKOUT SEMENTARA
  ===================================== */

  checkoutBtn.addEventListener("click", () => {

    if (cart.length === 0) return;

    alert(
      "Keranjang berhasil disiapkan! " +
      "Tahap checkout dan pembayaran akan dikembangkan selanjutnya."
    );

  });


  /* =====================================
     HEADER SCROLL
  ===================================== */

  const header = document.querySelector("header");

  let lastScroll = window.scrollY;

  window.addEventListener("scroll", () => {

    if (cartDrawer.classList.contains("show")) return;

    const currentScroll = window.scrollY;

    if (currentScroll <= 50 || currentScroll < lastScroll) {

      header.classList.remove("header-hidden");

    } else if (currentScroll > lastScroll) {

      header.classList.add("header-hidden");

    }

    lastScroll = currentScroll;

  }, { passive: true });


  /* =====================================
     INITIAL RENDER
  ===================================== */

  renderCart();

});

