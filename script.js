// ==========================
// SCROLL ANIMATION
// ==========================

const sections = document.querySelectorAll(
  ".hero, .dashboard, .mobile, .farms, .stats, footer",
);

window.addEventListener("scroll", () => {
  sections.forEach((section) => {
    let position = section.getBoundingClientRect().top;

    let screen = window.innerHeight - 100;

    if (position < screen) {
      section.style.opacity = "1";
      section.style.transform = "translateY(0)";
    }
  });
});

// initial style

sections.forEach((section) => {
  section.style.opacity = "0";

  section.style.transform = "translateY(50px)";

  section.style.transition = "1s ease";
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
  const visible = products.filter(product =>
    (activeFilter === "semua" ||
      product.category === activeFilter) &&
    product.name
      .toLocaleLowerCase("id")
      .includes(keyword)
  );

  // Menampilkan hasil pencarian produk
}