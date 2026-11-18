/* =========================================
   JAVASCRIPT HALAMAN KONTAK
   DAUN CINCAU ID
========================================= */

document.addEventListener("DOMContentLoaded", () => {
  /* =====================================
     1. ANIMASI SAAT HALAMAN DIBUKA
  ===================================== */

  document.body.classList.add("contact-page-loaded");

  /* =====================================
     2. FORMULIR KIRIM PESAN WHATSAPP
  ===================================== */

  const contactForm = document.getElementById("contactForm");

  if (contactForm) {
    contactForm.addEventListener("submit", (event) => {
      event.preventDefault();

      // Validasi formulir
      if (!contactForm.reportValidity()) {
        return;
      }

      // Ambil data formulir
      const nama = document.getElementById("nama").value.trim();

      const kontak = document.getElementById("kontak").value.trim();

      const subjek = document.getElementById("subjek").value;

      const pesan = document.getElementById("pesan").value.trim();

      // Nomor WhatsApp Daun Cincau ID
      const nomorTujuan = "6289516817752";

      // Susun pesan otomatis
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

      // Buat URL WhatsApp
      const url =
        "https://wa.me/" +
        nomorTujuan +
        "?text=" +
        encodeURIComponent(isiPesan);

      // Buka WhatsApp
      const whatsappWindow = window.open(url, "_blank");

      if (whatsappWindow) {
        whatsappWindow.opener = null;
      } else {
        // Jika pop-up diblokir browser
        window.location.href = url;
      }
    });
  }

  /* =====================================
     3. SCROLL REVEAL ANIMATION
  ===================================== */

  const revealElements = document.querySelectorAll(`

    .contact-info,

    .contact-form-card,

    .contact-closing,

    .site-footer .footer-brand,

    .site-footer .footer-column,

    .footer-bottom

  `);

  // Cek pengaturan aksesibilitas
  const reduceMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  ).matches;

  // Jika pengguna mengurangi animasi
  // Tampilkan semua elemen secara langsung
  if (reduceMotion) {
    revealElements.forEach((element) => {
      element.classList.add("is-visible");
    });

    return;
  }

  // Fallback untuk browser yang tidak mendukung observer
  if (!("IntersectionObserver" in window)) {
    revealElements.forEach((element) => {
      element.classList.add("is-visible");
    });

    return;
  }

  // Membuat Intersection Observer
  const observer = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          // Berhenti mengamati setelah elemen muncul
          observer.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.12,
      rootMargin: "0px 0px -40px 0px",
    },
  );

  // Aktifkan animasi pada elemen
  revealElements.forEach((element) => {
    element.classList.add("reveal-on-scroll");

    observer.observe(element);
  });
});

/* =========================================
   SMART NAVBAR
   HIDE SAAT SCROLL DOWN
   SHOW SAAT SCROLL UP
========================================= */

(() => {
  const header = document.querySelector("body > header");

  if (!header) return;

  let lastScrollY = window.scrollY;

  const scrollThreshold = 6;

  // Jika halaman dibuka dalam posisi sudah di-scroll
  if (lastScrollY > 80) {
    header.classList.add("header-hidden");
  }

  window.addEventListener(
    "scroll",
    () => {
      const currentScrollY = window.scrollY;

      // Selalu tampil saat berada di paling atas
      if (currentScrollY <= 15) {
        header.classList.remove("header-hidden");
        lastScrollY = currentScrollY;
        return;
      }

      // Scroll ke bawah: sembunyikan navbar
      if (currentScrollY > lastScrollY + scrollThreshold) {
        header.classList.add("header-hidden");
        lastScrollY = currentScrollY;
      }

      // Scroll ke atas: tampilkan navbar
      else if (currentScrollY < lastScrollY - scrollThreshold) {
        header.classList.remove("header-hidden");
        lastScrollY = currentScrollY;
      }
    },
    { passive: true },
  );
})();
