<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /panel');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>agrupa.link</title>
    <meta name="description" content="Tus enlaces importantes en un solo lugar, listos para compartir">
    <link rel="icon" type="image/png" href="/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="agrupa.link" />
    <meta name="application-name" content="agrupa.link">
    <link rel="manifest" href="/favicon/site.webmanifest" />
    <meta name="theme-color" content="#131212">

    <meta property="og:url" content="https://agrupa.link">
    <meta property="og:type" content="website">
    <meta property="og:title" content="agrupa.link">
    <meta property="og:description" content="Tus enlaces importantes en un solo lugar, listos para compartir">
    <meta property="og:image" content="https://agrupa.link/assets/og-image.webp">

    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="agrupa.link">
    <meta property="twitter:url" content="https://agrupa.link">
    <meta name="twitter:title" content="agrupa.link">
    <meta name="twitter:description" content="Tus enlaces importantes en un solo lugar, listos para compartir">
    <meta name="twitter:image" content="https://agrupa.link/assets/og-image.webp">

    <link href="/style.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/nav.php'; ?>

<header>
    <h1>Todos tus enlaces</h1>
    <h2>Agrupados en un mismo link</h2>
</header>

<div class="buttons">
    <button class="button php-link" data-form="login">Accede a tu grupo</button>
    <button class="button dark php-link" data-form="register">Crea un grupo nuevo</button>
</div>

<div class="popupContainer">
    <div class="popupContent">
        <!-- Login -->
        <div id="login-form-container" class="formContainer" style="display: none;">
            <h1>Accede a tu grupo</h1>
            <form id="login-form" method="POST">
                <label>
                    <input
                        type="text" 
                        name="username" 
                        class="loginUsername" 
                        placeholder=" " 
                        required 
                        minlength="3" 
                        maxlength="14"
                        pattern="^(?!.*\.\.)(?!.*\.$)[^\W][\w.]{0,29}$"
                        title="El usuario debe tener entre 3 y 14 caracteres. Solo puede contener letras, números, guiones y guiones bajos">
                    <span>Usuario</span>
                </label>

                <label>
                    <input
                        type="password" 
                        name="password" 
                        placeholder=" " 
                        required 
                        minlength="8" 
                        maxlength="24"
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$"
                        title="La contraseña debe tener entre 8 y 24 caracteres, incluyendo al menos una mayúscula, una minúscula, un número y un carácter especial (@$!%*?&)">
                    <span>Contraseña</span>
                </label>
                <button type="submit" class="button">Acceder</button>
            </form>
            <div id="login-message" style="color: red;"></div>
        </div>

        <!-- Registro -->
        <div id="register-form-container" class="formContainer" style="display: none;">
            <h1>Crea un grupo nuevo</h1>
            <h2>Agrupa tus links en <br><strong>agrupa.link/<span class="namePlaceholder">usuario</span></strong></h2>
            <form id="register-form" method="POST">
                <label>
                    <input type="text" 
                        class="name" 
                        name="username" 
                        class="registerUsername" 
                        placeholder=" " 
                        required
                        minlength="3"
                        maxlength="14"
                        pattern="^(?!.*\.\.)(?!.*\.$)[^\W][\w.]{0,29}$"
                        title="El usuario debe tener entre 3 y 14 caracteres. Solo puede contener letras, números, guiones y guiones bajos">
                    <span>Usuario</span>
                </label>

                <label>
                    <input type="email" 
                        name="email" 
                        placeholder=" " 
                        required
                        pattern="[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?"
                        title="Introduce una dirección de correo válida (ejemplo: usuario@dominio.com)">
                    <span>Email</span>
                </label>

                <label>
                    <input type="password"
                        name="password" 
                        placeholder=" " 
                        required
                        minlength="8"
                        maxlength="24"
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$"
                        title="La contraseña debe tener entre 8 y 24 caracteres, incluyendo al menos una mayúscula, una minúscula, un número y un carácter especial (@$!%*?&)">
                    <span>Contraseña</span>
                </label>
                <button type="submit" class="button">Crear</button>
            </form>
            <div id="register-message" style="color: red;"></div>
        </div>
    </div>
    <button class="button popupClose dark small">Volver</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

const popupContainer = document.querySelector('.popupContainer');
const popupContent = document.querySelector('.popupContent');
const popupClose = document.querySelector('.popupClose');
const header = document.querySelector('header');
const buttons = document.querySelector('.buttons');
const loginFormContainer = document.querySelector('#login-form-container');
const registerFormContainer = document.querySelector('#register-form-container');

function animateElement(element, startOpacity, endOpacity, displayStyle, duration, callback) {
    element.style.opacity = startOpacity;
    element.style.transition = `opacity ${duration}ms ease`;

    if (startOpacity === '0' && endOpacity === '1') {
        element.style.display = displayStyle || 'flex';
    }

    requestAnimationFrame(() => {
        element.style.opacity = endOpacity;
    });

    setTimeout(() => {
        if (endOpacity === '0') {
            element.style.display = 'none';
        }
        if (callback) callback();
    }, duration);
}

function showPopup(formType) {
    loginFormContainer.style.display = 'none';
    registerFormContainer.style.display = 'none';

    let inputToFocus;
    if (formType === 'login') {
        loginFormContainer.style.display = 'flex';
        inputToFocus = loginFormContainer.querySelector('input[name="username"]');
    } else {
        registerFormContainer.style.display = 'flex';
        inputToFocus = registerFormContainer.querySelector('input[name="username"]');
    }

    animateElement(header, '1', '0', null, 300);
    animateElement(buttons, '1', '0', null, 300, () => {
        header.style.display = 'none';
        buttons.style.display = 'none';
        popupContainer.style.display = 'flex';
        animateElement(popupContainer, '0', '1', null, 300, () => {
            if (inputToFocus) {
                inputToFocus.focus();
            }
        });
    });
}

function hidePopup() {
    animateElement(popupContainer, '1', '0', null, 300, () => {
        popupContainer.style.display = 'none';
        header.style.display = 'flex';
        buttons.style.display = 'flex';
        animateElement(header, '0', '1', null, 300);
        animateElement(buttons, '0', '1', null, 300);
    });
}

document.querySelectorAll('.php-link').forEach(button => {
    button.addEventListener('click', () => {
        const formType = button.dataset.form;
        showPopup(formType);
    });
});

const loginForm = document.querySelector('#login-form');
loginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(loginForm);

    fetch('login.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            const loginMessage = document.querySelector('#login-message');
            loginMessage.textContent = data.message || 'Error desconocido.';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const loginMessage = document.querySelector('#login-message');
        loginMessage.textContent = 'Error al procesar la solicitud.';
    });
});

const registerForm = document.querySelector('#register-form');
registerForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(registerForm);

    fetch('register.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            const registerMessage = document.querySelector('#register-message');
            registerMessage.textContent = data.message || 'Error desconocido.';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const registerMessage = document.querySelector('#register-message');
        registerMessage.textContent = 'Error al procesar la solicitud.';
    });
});

const usernameInput = registerForm.querySelector('input[name="username"]');
const usernameSpan = document.querySelector('.namePlaceholder');
if (usernameInput && usernameSpan) {
    usernameInput.addEventListener('input', (event) => {
        usernameSpan.textContent = event.target.value || 'usuario';
    });
}

popupClose.addEventListener('click', hidePopup);
popupContainer.addEventListener('click', event => {
    if (event.target === popupContainer) hidePopup();
});

});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.js"></script>
<script>
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(33, window.innerWidth / window.innerHeight, 0.1, 1000);
const renderer = new THREE.WebGLRenderer();
renderer.setSize(window.innerWidth, window.innerHeight);
document.body.appendChild(renderer.domElement);

window.addEventListener('resize', () => {
    renderer.setSize(window.innerWidth, window.innerHeight);
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
});

const sphereMaterial = new THREE.MeshStandardMaterial({
    color: '#FAA7A7',
    roughness: 1,
});

const glowMaterial = new THREE.ShaderMaterial({
    uniforms: {
        glowColor: { value: new THREE.Color(0xFF6666) },
        glowIntensity: { value: 1.0 },
        glowDistance: { value: 1.0 },
    },
    vertexShader: `
        varying vec3 vPosition;

        void main() {
            vPosition = (modelMatrix * vec4(position, 1.0)).xyz;
            gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
        }
    `,
    fragmentShader: `
        uniform vec3 glowColor;
        uniform float glowIntensity;
        uniform float glowDistance;
        varying vec3 vPosition;

        void main() {
            float distance = length(vPosition);  
            float intensity = glowIntensity * exp(-distance * distance / glowDistance);
            gl_FragColor = vec4(glowColor, intensity);
        }
    `,
    blending: THREE.AdditiveBlending,
    side: THREE.BackSide,
    transparent: true,
});

const group = new THREE.Group();
const sphereGeometry = new THREE.SphereGeometry(1, 100, 100);

for (let i = 0; i < 5; i++) {
    const sphere = new THREE.Mesh(sphereGeometry, sphereMaterial);

    sphere.scale.setScalar(Math.random() * 1.5 + 0.75);
    sphere.position.set(
        (Math.random() - 0.5) * 10,
        (Math.random() - 0.5) * 10,
        (Math.random() - 0.5) * 10
    );

    const glowSphere = new THREE.Mesh(sphereGeometry, glowMaterial);
    glowSphere.scale.setScalar(sphere.scale.x * 1.25);
    glowSphere.position.copy(sphere.position);

    group.add(sphere);
    group.add(glowSphere);
}

scene.add(group);

const ambientLight = new THREE.AmbientLight(0xffffff, 1);
scene.add(ambientLight);

const pointLight = new THREE.PointLight(0xffffff, 0.7, 100);
pointLight.position.set(0, 0, 30);
scene.add(pointLight);

camera.position.z = 15;

function animate() {
    requestAnimationFrame(animate);

    group.rotation.y += 0.002;
    group.rotation.x += 0.001;

    renderer.render(scene, camera);
}

animate();
</script>

<?php include 'includes/footer.php'; ?>

</body>
</html>