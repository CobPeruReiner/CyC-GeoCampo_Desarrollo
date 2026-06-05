let img1 = document.getElementById("imagen1");
let img2 = document.getElementById("imagen2");
let img3 = document.getElementById("imagen3");
let form = document.getElementById("agregar-gestion-form");
let formData = null;

let btnAdd = document.getElementById("btn-add-gestion");

WIDTH = 800;

const megasAllowed = 1;

const compressImage = (input, keyName) => {
  input.addEventListener("change", (event) => {
    let image_file = event.target.files[0];

    let reader = new FileReader();

    reader.readAsDataURL(image_file);

    reader.onload = (event) => {
      image_url = event.target.result;
      let image = document.createElement("img");
      image.src = image_url;

      image.onload = (e) => {
        let canvas = document.createElement("canvas");
        let ratio = WIDTH / image.width;
        canvas.width = WIDTH;
        canvas.height = image.height * ratio;

        let context = canvas.getContext("2d");
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        let new_image_url = canvas.toDataURL("image/jpeg", 60);

        let image_file = urlToFile(new_image_url);

        if (!formData) {
          formData = new FormData(form);
        }

        formData.set(keyName, image_file);
      };
    };
  });
};

let urlToFile = (url) => {
  let arr = url.split(",");
  let mime = arr[0].match(/:(.*?);/)[1];
  let data = arr[1];

  let dataStr = atob(data);
  let n = dataStr.length;
  let dataArr = new Uint8Array(n);

  while (n--) {
    dataArr[n] = dataStr.charCodeAt(n);
  }

  let file = new File([dataArr], "File.jpg", { type: mime });

  return file;
};

const imagesArray = [
  {
    value: img1,
    text: "imagen1",
  },
  {
    value: img2,
    text: "imagen2",
  },
  {
    value: img3,
    text: "imagen3",
  },
];

imagesArray.forEach((e) => {
  const currentFileLength = e.value.files.length;
  compressImage(e.value, e.text);
});

form.addEventListener("submit", (e) => {
  e.preventDefault();

  if (!formData) {
    formData = new FormData(form);
  }

  // coords
  let latitude = document.getElementById("latitud");
  let longitude = document.getElementById("longitud");

  if (latitude.value.length === 0 || longitude.value.length === 0) {
    alert(
      "Ubicación no detectada, activarla y actualizar la página para grabar gestión"
    );
    return;
  }

  fetch(form.action, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((res) => {
      return res.json();
    })
    .then((data) => {
      if (data.success) {
        alert(data.message);
        window.history.go(-1);
      } else {
        console.error(data.message);
        alert(data.message);
        location.reload();
      }
    })
    .catch((err) => {
      console.log(err);
      alert("Error, complete todos los campos y en orden nuevamente");
      location.reload();
    });
});

/********************************** NEW WAY *********************************/
