document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("login-form");
  const errorMessage = document.getElementById("error-message");
  const btnLogin = document.getElementById("btnLogin");
  const inputUsuario = document.getElementById("usuario");
  const inputContrasena = document.getElementById("contrasena");
  const togglePassword = document.getElementById("togglePassword");

  function setLoading(isLoading) {
    btnLogin.disabled = isLoading;
    btnLogin.classList.toggle("is-loading", isLoading);
    inputUsuario.disabled = isLoading;
    inputContrasena.disabled = isLoading;
    togglePassword.disabled = isLoading;
  }

  function clearMessage() {
    errorMessage.hidden = true;
    errorMessage.className = "alert-message";
    errorMessage.innerHTML = "";
    inputUsuario.classList.remove("is-invalid");
    inputContrasena.classList.remove("is-invalid");
  }

  function showMessage(message, type = "error") {
    const icons = {
      error: "fa-circle-exclamation",
      warning: "fa-triangle-exclamation",
      info: "fa-circle-info",
    };

    errorMessage.hidden = false;
    errorMessage.className = `alert-message ${type}`;
    errorMessage.innerHTML = `<i class="fa-solid ${icons[type] || icons.error} mt-1"></i><span>${message}</span>`;
  }

  function validateForm() {
    let isValid = true;
    clearMessage();

    if (!inputUsuario.value.trim()) {
      inputUsuario.classList.add("is-invalid");
      isValid = false;
    }

    if (!inputContrasena.value.trim()) {
      inputContrasena.classList.add("is-invalid");
      isValid = false;
    }

    if (!isValid) {
      showMessage("Ingresa tu usuario y contraseña para continuar.", "warning");
      const firstInvalid = loginForm.querySelector(".is-invalid");
      if (firstInvalid) firstInvalid.focus();
    }

    return isValid;
  }

  togglePassword.addEventListener("click", function () {
    const isPassword = inputContrasena.type === "password";
    inputContrasena.type = isPassword ? "text" : "password";
    togglePassword.setAttribute("aria-label", isPassword ? "Ocultar contraseña" : "Mostrar contraseña");
    togglePassword.innerHTML = `<i class="fa-solid ${isPassword ? "fa-eye-slash" : "fa-eye"}"></i>`;
    inputContrasena.focus();
  });

  [inputUsuario, inputContrasena].forEach((input) => {
    input.addEventListener("input", function () {
      input.classList.remove("is-invalid");
      if (inputUsuario.value.trim() && inputContrasena.value.trim()) {
        clearMessage();
      }
    });
  });

  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    if (!validateForm()) return;

    const formData = new FormData(loginForm);
    setLoading(true);
    clearMessage();

    fetch("login.php", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(async (response) => {
        const contentType = response.headers.get("content-type") || "";
        const payload = contentType.includes("application/json") ? await response.json() : null;

        if (!response.ok) {
          console.error("Login HTTP error:", response.status, payload);
          throw new Error("HTTP_ERROR");
        }

        if (!payload) {
          console.error("Login response without JSON.");
          throw new Error("INVALID_JSON");
        }

        return payload;
      })
      .then((data) => {
        console.log("Respuesta login:", data);

        if (data.success) {
          showMessage("Acceso validado. Redirigiendo...", "info");

          const destino = parseInt(data.idEstado, 10) === 2 ? "rutaCliente.php" : "menu.php";
          window.location.href = destino;
          return;
        }

        inputUsuario.classList.add("is-invalid");
        inputContrasena.classList.add("is-invalid");
        showMessage(data.message || "No pudimos validar tus datos. Inténtalo nuevamente.", data.type || "error");
      })
      .catch((error) => {
        console.error("Error en la petición de login:", error);
        showMessage("No se pudo completar el inicio de sesión. Verifica tu conexión e inténtalo nuevamente.", "error");
      })
      .finally(() => {
        setLoading(false);
      });
  });
});
