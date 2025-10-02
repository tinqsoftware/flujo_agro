<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>MVP Asistencia Evento</title>
  <!-- Importa face-api.js primero -->
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

  <style>
    body { font-family: Arial; margin: 20px; text-align: center; }
    #video, #videoAsistencia { border: 1px solid #ccc; margin: 5px; }
    #bienvenida { font-size: 24px; margin-top: 20px; color: green; font-weight: bold; }
    #registroFotos img { width: 60px; margin: 5px; border: 1px solid #aaa; }
  </style>
</head>
<body>
  <h1>Evento: Jornada de Innovación 2025</h1>
  <button onclick="mostrarRegistro()">Registrar Rostro</button>
  <button onclick="iniciarAsistencia()">Iniciar Asistencia</button>

  <!-- Registro -->
  <div id="registro" style="display:none;">
    <h2>Registro de persona</h2>
    Nombre: <input type="text" id="nombre"><br><br>
    <video id="video" width="300" height="220" autoplay muted></video><br>
    <button onclick="capturarFoto()">Capturar Foto</button>
    <br><br>
    <label for="fileInput">O cargar imágenes:</label>
    <input type="file" id="fileInput" accept="image/*" multiple><br><br>
    <div id="registroFotos"></div>
    <button onclick="guardarRegistro()">Guardar Registro</button>
  </div>

  <!-- Asistencia -->
  <div id="asistencia" style="display:none;">
    <h2>Bienvenida</h2>
    <video id="videoAsistencia" width="400" height="300" autoplay muted></video>
    <div id="bienvenida"></div>
    <h3>Log de asistencias</h3>
    <pre id="log"></pre>
  </div>

  <canvas id="captureCanvas" width="300" height="220" style="display:none;"></canvas>

  <script>
    let registros = JSON.parse(localStorage.getItem("registros") || "[]");
    let fotosTemp = [];
    let faceMatcher = null;

    // Cargar modelos
    async function cargarModelos() {
      try {
        await Promise.all([
          faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
          faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
          faceapi.nets.faceRecognitionNet.loadFromUri('/models')
        ]);
        console.log("✅ Modelos cargados correctamente");
      } catch (e) {
        console.error("❌ Error cargando modelos:", e);
      }
    }
    cargarModelos();

    // Mostrar pantalla de registro
    function mostrarRegistro() {
      document.getElementById("registro").style.display = "block";
      document.getElementById("asistencia").style.display = "none";
      navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
        document.getElementById("video").srcObject = stream;
      });

      document.getElementById("fileInput").onchange = function(e) {
        for (let file of e.target.files) {
          const reader = new FileReader();
          reader.onload = evt => {
            fotosTemp.push(evt.target.result);
            let img = document.createElement("img");
            img.src = evt.target.result;
            document.getElementById("registroFotos").appendChild(img);
          }
          reader.readAsDataURL(file);
        }
      };
    }

    // Capturar foto
    function capturarFoto() {
      const video = document.getElementById("video");
      const canvas = document.getElementById("captureCanvas");
      const ctx = canvas.getContext("2d");
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const dataUrl = canvas.toDataURL();
      fotosTemp.push(dataUrl);

      let img = document.createElement("img");
      img.src = dataUrl;
      document.getElementById("registroFotos").appendChild(img);
    }

    // Guardar registro
    async function guardarRegistro() {
      const nombre = document.getElementById("nombre").value.trim();
      if (!nombre || fotosTemp.length < 1) {
        alert("Debes ingresar nombre y al menos 1 foto");
        return;
      }

      let descriptors = [];
      for (let foto of fotosTemp) {
        const img = await faceapi.fetchImage(foto);
        const detection = await faceapi.detectSingleFace(img, new faceapi.TinyFaceDetectorOptions())
          .withFaceLandmarks()
          .withFaceDescriptor();
        if (detection) descriptors.push(detection.descriptor);
      }

      if (descriptors.length === 0) {
        alert("❌ No se detectaron rostros en las fotos.");
        return;
      }

      registros.push({ nombre, descriptors: descriptors.map(d => Array.from(d)) });
      localStorage.setItem("registros", JSON.stringify(registros));
      alert("✅ Registro guardado!");
      fotosTemp = [];
      document.getElementById("registroFotos").innerHTML = "";
      document.getElementById("nombre").value = "";
      document.getElementById("fileInput").value = "";
    }

    // Iniciar asistencia
    function iniciarAsistencia() {
      document.getElementById("registro").style.display = "none";
      document.getElementById("asistencia").style.display = "block";

      navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
        document.getElementById("videoAsistencia").srcObject = stream;
      });

      const labeledDescriptors = registros.map(r =>
        new faceapi.LabeledFaceDescriptors(
          r.nombre,
          r.descriptors.map(d => new Float32Array(d))
        )
      );
      faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);

      const video = document.getElementById("videoAsistencia");
      video.addEventListener("play", () => detectarCaras(video));
    }

    async function detectarCaras(video) {
      const detecciones = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptors();

      if (detecciones.length > 0) {
        const resultados = detecciones.map(d => faceMatcher.findBestMatch(d.descriptor));
        resultados.forEach(res => {
          if (res.label !== "unknown") {
            document.getElementById("bienvenida").innerText = `Bienvenido ${res.label}`;
            const hora = new Date().toLocaleString();
            document.getElementById("log").textContent += `[${hora}] ${res.label}\n`;
          }
        });
      }
      setTimeout(() => detectarCaras(video), 200); // 5 FPS
    }
  </script>
</body>
</html>
