const contactForm = document.getElementById("contactForm");

contactForm.addEventListener("submit", (event) => {
  event.preventDefault();

  const data = new FormData(contactForm);
  const pesan = [
    "Halo Daun Cincau ID!",
    "",
    `Nama: ${String(data.get("nama")).trim()}`,
    `Kontak: ${String(data.get("kontak")).trim()}`,
    `Topik: ${data.get("subjek")}`,
    "",
    String(data.get("pesan")).trim(),
  ].join("\n");

  const url = `https://wa.me/6289516817752?text=${encodeURIComponent(pesan)}`;
  window.open(url, "_blank", "noopener,noreferrer");
});

const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

if (!reduceMotion.matches && "IntersectionObserver" in window) {
  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        entry.target.classList.add("kontak-reveal");
        observer.unobserve(entry.target);
      }
    },
    { threshold: 0.08 },
  );

  document.querySelectorAll("[data-reveal]").forEach((element) => {
    observer.observe(element);
  });
}
