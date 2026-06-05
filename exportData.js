const EXCEL_TYPE =
  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8";
const EXCEL_EXTENSION = ".xlsx";

function downloadAsExcel(data, cartera = "", asesor = "") {
  const worksheet = XLSX.utils.json_to_sheet(data);
  const workbook = {
    Sheets: { data: worksheet },
    SheetNames: ["data"],
  };

  const excelBuffer = XLSX.write(workbook, {
    bookType: "xlsx",
    type: "array",
  });

  let asesorName = "TODOS_LOS_ASESORES";

  if (asesor && asesor.includes(",")) {
    const asesorData = asesor.split(",");
    const apellidos = asesorData[0].trim().split(" ");
    const nombres = asesorData[1].trim().split(" ");
    asesorName = `${nombres[0]}_${apellidos[0]}`;
  }

  const carteraSafe = cartera ? cartera.replace(/\s+/g, "_") : "SIN_CARTERA";

  saveAsExcel(excelBuffer, `${carteraSafe}_${asesorName}`);
}

function saveAsExcel(buffer, filename) {
  const data = new Blob([buffer], { type: EXCEL_TYPE });
  saveAs(data, filename + EXCEL_EXTENSION);
}
