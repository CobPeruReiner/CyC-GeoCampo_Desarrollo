document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("login-form");
  const errorMessage = document.getElementById("error-message");

  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(loginForm);

    fetch("login.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("🔍 RESPUESTA DEL SERVIDOR:", data);

        if (data.success) {
          console.log("IDESTADO recibido:", data.idEstado);

          if (parseInt(data.idEstado, 10) === 2) {
            console.log("➡️ IDESTADO = 2 → vista supervisor");
            window.location.href = "rutaCliente.php";
          } else {
            console.log("➡️ IDESTADO = 1 → menú normal");
            window.location.href = "menu.php";
          }
        } else {
          console.log("❌ Login fallido:", data.message);
          errorMessage.innerHTML = `<span style="color: red;">${data.message}</span>`;
        }
      })
      .catch((error) => {
        console.error("⚠️ Error en la petición:", error);
        errorMessage.innerHTML = `<span style="color: red;">Error del servidor.</span>`;
      });
  });
});
