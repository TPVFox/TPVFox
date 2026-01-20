document.addEventListener("DOMContentLoaded", () => {
  let vistaDS = true;

  document.getElementById("toggleVista").addEventListener("click", function () {
    vistaDS = !vistaDS;

    document.querySelectorAll(".ds").forEach((element) => {
      element.style.display = vistaDS ? "" : "none";
    });

    document.querySelectorAll(".temperatura-directa").forEach((element) => {
      element.style.display = vistaDS ? "none" : "";
    });

    this.textContent = vistaDS
      ? "Ver temperatura directa"
      : "Ver desglose por DS";
  });
});
