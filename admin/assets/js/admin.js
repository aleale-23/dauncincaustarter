
document.addEventListener("DOMContentLoaded", () => {

    // =====================================
    // TOGGLE PASSWORD
    // =====================================

    document.querySelectorAll(".toggle-password").forEach(button => {

        button.addEventListener("click", () => {

            const target = document.getElementById(
                button.dataset.target
            );

            if (!target) return;

            const isHidden = target.type === "password";

            target.type = isHidden ? "text" : "password";

            button.innerHTML = isHidden
                ? `
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 3l18 18"/>
                        <path d="M10.6 10.6a2 2 0 002.8 2.8"/>
                        <path d="M9.9 5.2A10.8 10.8 0 0112 5c6.5 0 10 7 10 7a14 14 0 01-3 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.5 0 2.8-.3 4-.8"/>
                    </svg>
                `
                : `
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                `;

            button.setAttribute(
                "aria-label",
                isHidden
                    ? "Sembunyikan password"
                    : "Tampilkan password"
            );

            button.setAttribute(
                "aria-pressed",
                isHidden ? "true" : "false"
            );

        });

    });


    // =====================================
    // RESPONSIVE SIDEBAR CMS
    // =====================================

    const toggle = document.getElementById("menuToggle");

    const sidebar = document.getElementById("sidebar");

    if (toggle && sidebar) {

        toggle.addEventListener("click", () => {

            sidebar.classList.toggle("open");

        });

    }

});
