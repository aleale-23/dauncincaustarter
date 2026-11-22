(() => {
  if (
    window.matchMedia("(prefers-reduced-motion: reduce)").matches ||
    !("IntersectionObserver" in window)
  ) {
    return;
  }

  const elements = document.querySelectorAll(`
    .edukasi-hero-content,
    .edukasi-hero-image,
    .edukasi-intro-image,
    .edukasi-intro-content,
    .edukasi-section-heading,
    .fakta-highlight,
    .fakta-item,
    .mitos-card,
    .tanaman-card,
    .manfaat-card,
    .edukasi-sajian-content,
    .edukasi-sajian-image,
    .tips-content,
    .site-footer .footer-brand,
    .site-footer .footer-column
  `);

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;

        entry.target.classList.add("edukasi-reveal");
        observer.unobserve(entry.target);
      }
    },
    { threshold: 0.05 },
  );

  elements.forEach((element) => observer.observe(element));
})();

(() => {
  const header = document.querySelector("body > header");
  if (!header) return;

  let lastScrollY = Math.max(0, window.scrollY);

  window.addEventListener(
    "scroll",
    () => {
      const currentScrollY = Math.max(0, window.scrollY);

      if (currentScrollY <= header.offsetHeight) {
        header.classList.remove("header-hidden");
      } else if (currentScrollY > lastScrollY) {
        header.classList.add("header-hidden");
      } else if (currentScrollY < lastScrollY) {
        header.classList.remove("header-hidden");
      }

      lastScrollY = currentScrollY;
    },
    { passive: true },
  );

  header.addEventListener("focusin", () => {
    header.classList.remove("header-hidden");
  });
})();
