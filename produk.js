
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

  const modalDescription = document.getElementById(
    "modalProductDescription"
  );

  const closeModal = document.getElementById("closeModal");

  const modalOk = document.getElementById("modalOk");

  document.querySelectorAll(".detail-btn").forEach(button => {

    button.addEventListener("click", () => {

      modalName.textContent = button.dataset.name;

      modalDescription.textContent =
        button.dataset.description;

      productModal.classList.add("show");

    });

  });

  function hideModal() {
    productModal.classList.remove("show");
  }

  closeModal.addEventListener("click", hideModal);
  modalOk.addEventListener("click", hideModal);

  productModal.addEventListener("click", event => {

    if (event.target === productModal) {
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
