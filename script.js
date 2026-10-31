/* ==========================
   SCROLL ANIMATION
========================== */

document.addEventListener("DOMContentLoaded", () => {
  const sections = document.querySelectorAll(
    ".hero, .dashboard, .mobile, .farms, .cincau, .stats, .kebun-cincau, .product-gallery, .reference-banner, .site-footer, .footer-bottom",
  );

  // Siapkan animasi awal
  sections.forEach((section) => {
    section.style.opacity = "0";
    section.style.transform = "translateY(35px)";
    section.style.transition = "opacity 0.8s ease, transform 0.8s ease";
  });

  // Munculkan section saat masuk ke layar
  const observer = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";

          // Animasi cukup sekali
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.12,
    },
  );

  sections.forEach((section) => {
    observer.observe(section);
  });
});

// ==========================
// COUNTER STATISTIC
// ==========================

const counters = document.querySelectorAll(".numbers h1");

let started = false;

function startCounter() {
  if (started) return;

  let stats = document.querySelector(".stats").getBoundingClientRect().top;

  if (stats < window.innerHeight) {
    started = true;

    counters.forEach((counter) => {
      let target = counter.innerText;

      let number = parseInt(target.replace(/\D/g, ""));

      let current = 0;

      let interval = setInterval(() => {
        current += Math.ceil(number / 80);

        if (current >= number) {
          current = number;

          clearInterval(interval);
        }

        if (target.includes("$")) {
          counter.innerText = "$" + current.toLocaleString();
        } else if (target.includes("+")) {
          counter.innerText = current + "+";
        }
      }, 30);
    });
  }
}

window.addEventListener("scroll", startCounter);

// ==========================
// BUTTON INTERACTION
// ==========================

const investButtons = document.querySelectorAll(".card button");

investButtons.forEach((button) => {
  button.addEventListener("click", () => {
    button.innerHTML = "Added ✓";

    button.style.background = "#1d5b22";

    setTimeout(() => {
      button.innerHTML = "Invest";
    }, 2000);
  });
});

// ==========================
// SEARCH EFFECT
// ==========================

const search = document.querySelector(".search-box button");

search.addEventListener("click", () => {
  let input = document.querySelector(".search-box input");

  if (input.value === "") {
    alert("Please enter farm name");
  } else {
    alert("Searching farm: " + input.value);
  }
});

// ==========================
// NAVBAR SMOOTH SCROLL
// ==========================

const menu = document.querySelectorAll(".menu li");

menu.forEach((item) => {
  item.addEventListener("click", () => {
    window.scrollTo({
      top: document.body.scrollHeight * Math.random(),

      behavior: "smooth",
    });
  });
});

// ==========================
// PARALLAX HERO
// ==========================

window.addEventListener("mousemove", (e) => {
  const image = document.querySelector(".hero-image");

  let x = (e.clientX / window.innerWidth - 0.5) * 20;

  let y = (e.clientY / window.innerHeight - 0.5) * 20;

  image.style.transform = `
translate(${x}px,${y}px)
`;
});

function renderProducts() {
  const visible = products.filter(
    (product) =>
      (activeFilter === "semua" || product.category === activeFilter) &&
      product.name.toLocaleLowerCase("id").includes(keyword),
  );

  // Menampilkan hasil pencarian produk
}

/* ==========================
   HEADER HIDE & SHOW ON SCROLL
========================== */

(() => {
  const header = document.querySelector("header");

  if (!header) return;

  let lastScrollY = window.scrollY;
  let ticking = false;

  window.addEventListener(
    "scroll",
    () => {
      if (ticking) return;

      ticking = true;

      requestAnimationFrame(() => {
        const currentScrollY = window.scrollY;
        const difference = currentScrollY - lastScrollY;

        if (currentScrollY <= 50) {
          // Di paling atas, header selalu muncul
          header.classList.remove("header-hidden");
        } else if (difference > 3) {
          // Scroll turun
          header.classList.add("header-hidden");
        } else if (difference < -3) {
          // Scroll naik
          header.classList.remove("header-hidden");
        }

        lastScrollY = currentScrollY;
        ticking = false;
      });
    },
    { passive: true },
  );
})();

/* =========================
   PRODUCT CAROUSEL
========================= */

const galleryTrack = document.getElementById("galleryTrack");
const galleryDots = document.getElementById("galleryDots");

if (galleryTrack && galleryDots) {
  const cards = Array.from(galleryTrack.children);
  let currentIndex = 0;

  function visibleCount() {
    if (window.innerWidth <= 600) return 1;
    if (window.innerWidth <= 992) return 2;
    return 3;
  }

  function maxIndex() {
    return Math.max(0, cards.length - visibleCount());
  }

  function createDots() {
    galleryDots.innerHTML = "";

    for (let i = 0; i <= maxIndex(); i++) {
      const dot = document.createElement("button");

      dot.type = "button";
      dot.className = "gallery-dot";
      dot.setAttribute("aria-label", "Halaman " + (i + 1));

      dot.addEventListener("click", () => {
        currentIndex = i;
        updateCarousel();
      });

      galleryDots.appendChild(dot);
    }
  }

  function updateCarousel() {
    currentIndex = Math.min(currentIndex, maxIndex());

    const cardWidth = cards[0].getBoundingClientRect().width;
    const gap = parseFloat(getComputedStyle(galleryTrack).gap) || 0;

    galleryTrack.style.transform = `translateX(-${currentIndex * (cardWidth + gap)}px)`;

    Array.from(galleryDots.children).forEach((dot, index) => {
      dot.classList.toggle("active", index === currentIndex);
    });
  }

  createDots();
  updateCarousel();

  window.addEventListener("resize", () => {
    createDots();
    updateCarousel();
  });
}

/* ==========================
   PRODUCT CAROUSEL
   GESER SATU FOTO
========================== */

document.addEventListener("DOMContentLoaded", () => {
  const track = document.getElementById("galleryTrack");
  const dotsContainer = document.getElementById("galleryDots");
  const container = document.querySelector(".gallery-container");

  if (!track || !dotsContainer || !container) return;

  const cards = Array.from(track.children);

  let currentIndex = 0;
  let autoplay;

  function visibleCount() {
    if (window.innerWidth <= 600) return 1;
    if (window.innerWidth <= 992) return 2;
    return 3;
  }

  // Maksimal posisi geser
  function maxIndex() {
    return Math.max(0, cards.length - visibleCount());
  }

  // Membuat titik navigasi
  function createDots() {
    dotsContainer.innerHTML = "";

    for (let i = 0; i <= maxIndex(); i++) {
      const dot = document.createElement("button");

      dot.className = "gallery-dot";
      dot.type = "button";
      dot.setAttribute("aria-label", `Foto posisi ${i + 1}`);

      dot.addEventListener("click", () => {
        currentIndex = i;
        updateCarousel();
        restartAutoplay();
      });

      dotsContainer.appendChild(dot);
    }
  }

  // Menggeser satu foto
  function updateCarousel() {
    currentIndex = Math.min(currentIndex, maxIndex());

    const cardWidth = cards[0].getBoundingClientRect().width;
    const gap = parseFloat(getComputedStyle(track).gap) || 0;

    track.style.transform = `translateX(-${currentIndex * (cardWidth + gap)}px)`;

    Array.from(dotsContainer.children).forEach((dot, i) => {
      dot.classList.toggle("active", i === currentIndex);
    });
  }

  // Autoplay satu foto setiap 3 detik
  function startAutoplay() {
    clearInterval(autoplay);

    autoplay = setInterval(() => {
      currentIndex++;

      if (currentIndex > maxIndex()) {
        currentIndex = 0;
      }

      updateCarousel();
    }, 3000);
  }

  function restartAutoplay() {
    startAutoplay();
  }

  // Berhenti saat mouse diarahkan ke carousel
  container.addEventListener("mouseenter", () => {
    clearInterval(autoplay);
  });

  container.addEventListener("mouseleave", startAutoplay);

  // Jalankan
  createDots();
  updateCarousel();
  startAutoplay();

  window.addEventListener("resize", () => {
    createDots();
    updateCarousel();
  });
});
