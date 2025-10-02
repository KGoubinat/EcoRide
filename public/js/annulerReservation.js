(function () {
  const buttons = document.querySelectorAll(".btn-danger[data-reservation-id]");
  const modal = document.getElementById("cancel-reservation-modal");
  const okBtn = document.getElementById("resv-cancel-confirm");
  const noBtn = document.getElementById("resv-cancel-cancel");
  const xBtn = document.getElementById("resv-cancel-close");
  let form = null;

  buttons.forEach((b) => {
    const f = b.closest("form");
    b.addEventListener("click", (e) => {
      e.preventDefault();
      form = f;
      if (!modal || !okBtn) {
        form?.submit();
        return;
      }
      modal.classList.add("show");
    });
  });

  function hide() {
    modal?.classList.remove("show");
  }
  okBtn?.addEventListener("click", () => {
    form?.submit();
    hide();
  });
  noBtn?.addEventListener("click", hide);
  xBtn?.addEventListener("click", hide);
  window.addEventListener("click", (e) => {
    if (e.target === modal) hide();
  });
})();
