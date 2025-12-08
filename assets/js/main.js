document.addEventListener("DOMContentLoaded", function () {
  const wrapper = document.getElementById("wrapper");
  // use new sidebar toggle if present, otherwise fall back to legacy id
  let toggleButton = document.getElementById("menu-toggle-sidebar");
  if(!toggleButton) toggleButton = document.getElementById("menu-toggle");
  const collapseTriggers = document.querySelectorAll("[data-bs-toggle='collapse']");

  // ======== 1️⃣ Hapus preload class setelah load ========
  window.addEventListener("load", function () {
    document.body.classList.remove("preload");
  });

  // ======== 2️⃣ Baca status sidebar dari localStorage ========
  const isToggled = localStorage.getItem("sidebarToggled") === "true";
  if (isToggled) {
    wrapper.classList.add("toggled");
  }

  // ======== 3️⃣ Simpan status sidebar ========
  if(toggleButton) {
    toggleButton.addEventListener("click", function (e) {
    e.preventDefault();
    wrapper.classList.toggle("toggled");
    localStorage.setItem("sidebarToggled", wrapper.classList.contains("toggled"));
    });
  }

  // ======== 4️⃣ Simpan state collapse (expand/hide) ========
  collapseTriggers.forEach(trigger => {
    const targetId = trigger.getAttribute("data-bs-target");
    const collapseElement = document.querySelector(targetId);
    const savedState = localStorage.getItem(targetId);

    if (savedState === "true") {
      new bootstrap.Collapse(collapseElement, { show: true, toggle: false });
    }

    collapseElement.addEventListener("shown.bs.collapse", () => {
      localStorage.setItem(targetId, "true");
    });

    collapseElement.addEventListener("hidden.bs.collapse", () => {
      localStorage.setItem(targetId, "false");
    });
  });
});


