setInterval(() => {
  fetch("verificarToken.php")
    .then((res) => res.json())
    .then((data) => {
      if (!data.valid) {
        const modal = new bootstrap.Modal(
          document.getElementById("modalSesionExpirada"),
        );
        modal.show();
      }
    })
    .catch((error) => {
      console.error("Error al verificar token:", error);
    });
}, 10000);
