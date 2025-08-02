<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AGROEMSE | En Construcción</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Montserrat', sans-serif;
    }
    body {
      background-color: #f9f9f9;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background-color: white;
      border-bottom: 1px solid #ddd;
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .logo img {
      height: 40px;
    }
    nav {
      display: flex;
      gap: 1rem;
    }
    nav a {
      text-decoration: none;
      color: #0c2d4e;
      font-weight: bold;
    }
    main {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      text-align: center;
    }
    main img {
      max-width: 80%;
      height: auto;
      margin-bottom: 2rem;
    }
    h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: #0c2d4e;
    }
    p {
      max-width: 600px;
      font-size: 1rem;
      color: #333;
    }
    .menu-toggle {
      display: none;
      flex-direction: column;
      cursor: pointer;
    }
    .menu-toggle span {
      width: 25px;
      height: 3px;
      background-color: #0c2d4e;
      margin: 3px 0;
    }
    .mobile-menu {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      background-color: white;
      height: 100%;
      width: 200px;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
      padding: 2rem;
    }
    .mobile-menu a {
      display: block;
      margin: 1rem 0;
      text-decoration: none;
      color: #0c2d4e;
      font-weight: bold;
    }
    @media (max-width: 768px) {
      nav {
        display: none;
      }
      .menu-toggle {
        display: flex;
      }
      .mobile-menu {
        display: none;
      }
      .mobile-menu.open {
        display: block;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">
      <img src="/access/logo.jpg" alt="Logo AGROEMSE">
    </div>
  </header>

  <main>
    <h1>ESTAMOS EN CONSTRUCCIÓN</h1>
    <img src="/access/logo.jpg" style="width:80%" alt="Caja con hoja - AGROEMSE">
    <p>Estamos en el desarrollo de una plataforma hecha a la medida de las necesidades de AGROEMSE. Administrable, moderna y pensada para adaptarse a nuevos flujos.</p>
  </main>

  <script>
    function toggleMenu() {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('open');
    }
  </script>
</body>
</html>
