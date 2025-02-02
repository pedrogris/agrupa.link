<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$user_id = $_SESSION['user_id'];

// Subir la imagen de perfil
if (isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024;

    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $filename = 'profile_' . $user_id . '_' . time() . '.webp';
        $upload_path = '../assets/' . $filename;

        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_image = $stmt->fetchColumn();

        if (convert_to_webp($file['tmp_name'], $upload_path, 300, 100)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->execute([$filename, $user_id]);

            if ($current_image && file_exists('../assets/' . $current_image)) {
                unlink('../assets/' . $current_image);
            }

            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Eliminar la imagen de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_profile_image') {
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_image = $stmt->fetchColumn();

    if ($current_image) {
        $image_path = '../assets/' . $current_image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        $stmt = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
        $stmt->execute([$user_id]);

        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Bio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_biography') {
    if (isset($_POST['biography'])) {
        $biography = htmlspecialchars($_POST['biography']);
        error_log("Biografía recibida: " . $biography);
    } else {
        error_log("No se ha recibido biografía");
    }

    $stmt = $pdo->prepare("UPDATE users SET biography = ? WHERE id = ?");
    $stmt->execute([$biography, $user_id]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_biography') {
        $biography = isset($_POST['biography']) ? htmlspecialchars($_POST['biography']) : '';
        
        $stmt = $pdo->prepare("UPDATE users SET biography = ? WHERE id = ?");
        $stmt->execute([$biography, $user_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'biography' => $biography]);
        exit;
    }    
}

// Cambio de tema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $theme = htmlspecialchars($_POST['theme']);
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $user_id]);
    
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'theme' => $theme]);
        exit;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Añadir link o tarjeta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if ($contentType === "application/json") {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);
        
        if (isset($decoded['action']) && $decoded['action'] === 'add') {
            $type = $decoded['type'];
            
            $stmt = $pdo->prepare("SELECT MAX(position) FROM links WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $maxPosition = $stmt->fetchColumn();
            $newPosition = $maxPosition !== null ? $maxPosition + 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO links (user_id, type, position) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $type, $newPosition]);
            
            $link_id = $pdo->lastInsertId();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'link_id' => $link_id
            ]);
            exit;
        }
    }
}

// Subir imagen a la tarjeta
if (isset($_FILES['card_image']) && isset($_POST['link_id'])) {
    $link_id = intval($_POST['link_id']);
    $file = $_FILES['card_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024;

    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $filename = 'card_' . $user_id . '_' . $link_id . '_' . time() . '.webp';
        $upload_path = '../assets/' . $filename;
        $stmt = $pdo->prepare("SELECT card_image FROM links WHERE id = ? AND user_id = ?");
        $stmt->execute([$link_id, $user_id]);
        $current_image = $stmt->fetchColumn();

        if (convert_to_webp($file['tmp_name'], $upload_path, 600, 100)) {
            $stmt = $pdo->prepare("UPDATE links SET card_image = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$filename, $link_id, $user_id]);

            if ($current_image && file_exists('../assets/' . $current_image)) {
                unlink('../assets/' . $current_image);
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'filename' => $filename]);
            exit;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid image']);
    exit;
}

// Eliminar imagen de la tarjeta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_card_image') {
    $link_id = intval($_POST['link_id']);
    
    $stmt = $pdo->prepare("SELECT card_image FROM links WHERE id = ? AND user_id = ?");
    $stmt->execute([$link_id, $user_id]);
    $current_image = $stmt->fetchColumn();

    if ($current_image) {
        $image_path = '../assets/' . $current_image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        $stmt = $pdo->prepare("UPDATE links SET card_image = NULL WHERE id = ? AND user_id = ?");
        $stmt->execute([$link_id, $user_id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Reordenar links
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if ($contentType === "application/json") {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if ($data['action'] === 'reorder') {
            try {
                $pdo->beginTransaction();
                
                foreach ($data['positions'] as $position) {
                    $stmt = $pdo->prepare("UPDATE links SET position = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$position['position'], $position['id'], $user_id]);
                }
                
                $pdo->commit();
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Database error']);
                exit;
            }
        }
    } else {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            if ($action === 'add') {
                $type = $_POST['type'] ?? 'link';
                $stmt = $pdo->prepare("INSERT INTO links (user_id, title, url, type, position) VALUES (?, '', '', ?, (SELECT COALESCE(MAX(position) + 1, 0) FROM links l2 WHERE user_id = ?))");
                $stmt->execute([$user_id, $type, $user_id]);
            } elseif ($action === 'delete') {
                $link_id = intval($_POST['link_id']);
                $stmt = $pdo->prepare("SELECT card_image FROM links WHERE id = ? AND user_id = ?");
                $stmt->execute([$link_id, $user_id]);
                $link = $stmt->fetch();
                
                if ($link && $link['card_image']) {
                    $image_path = '../assets/' . $link['card_image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                $stmt = $pdo->prepare("DELETE FROM links WHERE id = ? AND user_id = ?");
                $stmt->execute([$link_id, $user_id]);
            } elseif ($action === 'update_title') {
                $link_id = intval($_POST['link_id']);
                $title = htmlspecialchars($_POST['title']);
                
                $stmt = $pdo->prepare("UPDATE links SET title = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $link_id, $user_id]);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } elseif ($action === 'update_url') {
                $link_id = intval($_POST['link_id']);
                $url = $_POST['url'];
                
                if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid URL']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE links SET url = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$url, $link_id, $user_id]);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }
}

// Convertir a webp
function convert_to_webp($source_path, $destination_path, $min_dimension, $quality = 100) {
    $image_info = getimagesize($source_path);
    $mime = $image_info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    if ($width < $height) {
        $new_width = $min_dimension;
        $new_height = ($height / $width) * $min_dimension;
    } else {
        $new_height = $min_dimension;
        $new_width = ($width / $height) * $min_dimension;
    }

    $resized_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    $success = imagewebp($resized_image, $destination_path, $quality);

    imagedestroy($image);
    imagedestroy($resized_image);

    return $success;
}

$stmt = $pdo->prepare("SELECT username, profile_image, theme, biography FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM links WHERE user_id = ? ORDER BY position ASC");
$stmt->execute([$user_id]);
$links = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>agrupa.link</title>
<meta name="description" content="Todos tus enlaces importantes en un mismo lugar, listos para compartir">
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

const linkList = document.querySelector('.linkList');
const iframe = document.getElementById('preview');

function reloadIframe() {
    if (iframe) {
        iframe.src = iframe.src;
    }
}

// Imagen de perfil
let imageInput = document.getElementById('profile_image');
let imagePreview = document.getElementById('profile_image_preview');
let profileForm = document.getElementById('profile_image_form');
let spinner = document.getElementById('spinner');

let createDeleteButton = () => {
    let existingForm = document.querySelector('input[name="action"][value="delete_profile_image"]')?.closest('form');
    if (!existingForm) {
        let deleteForm = document.createElement('form');
        deleteForm.method = 'POST';
        deleteForm.style.display = 'inline-block';
        
        let actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_profile_image';
        
        let deleteButton = document.createElement('button');
        deleteButton.type = 'submit';
        deleteButton.className = 'actionButton';
        deleteButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"><path fill="#131212" fill-rule="evenodd" d="M10.4253 1.78143c.1952-.19527.1952-.51185 0-.70711-.1953-.195264-.51189-.195264-.70715 0l-4.0362 4.0362-4.03619-4.03619c-.19526-.19526-.51185-.19526-.707108 0-.195262.19526-.195262.51185 0 .70711L4.97484 5.81763.938636 9.85383c-.195262.19527-.195263.51187 0 .70707.195264.1953.511844.1953.707104 0l4.03621-4.03617L9.71816 10.561c.19527.1952.51184.1952.70714 0 .1952-.1953.1952-.5119 0-.70716L6.38905 5.81763l4.03625-4.0362Z" clip-rule="evenodd"/></svg>';
        
        deleteForm.appendChild(actionInput);
        deleteForm.appendChild(deleteButton);
        
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (confirm('¿Estás seguro de que quieres eliminar tu imagen de perfil?')) {
                fetch('', {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (imagePreview) {
                            imagePreview.style.display = 'none';
                            imagePreview.src = '';
                        }
                        
                        let placeholder = document.querySelector('.profile-image-placeholder');
                        if (placeholder) {
                            placeholder.style.display = 'flex';
                        }
                        
                        deleteForm.remove();
                        reloadIframe();
                        showAlert('Imagen de perfil eliminada');
                    }
                })
                .catch(error => {
                    console.error('Error deleting profile image:', error);
                    window.location.reload();
                });
            }
        });
        
        let profileActions = document.querySelector('.profile-actions');
        if (profileActions) {
            profileActions.insertBefore(deleteForm, profileForm);
        }
    }
};

if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                const placeholder = document.querySelector('.profile-image-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }

                imagePreview.style.opacity = "0.25";
                spinner.style.display = 'block';

                const formData = new FormData(profileForm);
                fetch('', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    } else {
                        showAlert('Error al subir la imagen');
                        throw new Error('Error al subir la imagen');
                    }
                })
                .then(() => {
                    imagePreview.style.opacity = "1";
                    spinner.style.display = 'none';
                    createDeleteButton();
                    reloadIframe();
                    showAlert('Imagen de perfil actualizada correctamente');
                })
                .catch(error => {
                    console.error(error);
                    imagePreview.style.opacity = "1";
                    spinner.style.display = 'none';
                });
            }
            reader.readAsDataURL(file);
        }
    });
}

// Eliminar imagen de perfil
let deleteProfileImageForm = document.querySelector('form[action=""][method="POST"] input[name="action"][value="delete_profile_image"]')?.closest('form');
let profileImagePreview = document.getElementById('profile_image_preview');
let profileImagePlaceholder = document.querySelector('.profile-image-placeholder');

if (deleteProfileImageForm) {
    deleteProfileImageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (confirm('¿Realmente quieres eliminar tu imagen de perfil?')) {
            fetch('', {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Imagen de perfil eliminada');

                    if (profileImagePreview) {
                        profileImagePreview.style.display = 'none';
                        profileImagePreview.src = '';
                    }
                    
                    if (profileImagePlaceholder) {
                        profileImagePlaceholder.style.display = 'flex';
                    }
                    
                    reloadIframe();
                }
            })
            .catch(error => {
                console.error('Error deleting profile image:', error);
                window.location.reload();
            });
        }
    });
}

// Bio
const bioTextarea = document.getElementById('biography');
const charCount = document.getElementById('charCount');
let updateTimeout;

function updateCharCount() {
    const length = bioTextarea.value.length;
    charCount.textContent = length;
}

async function updateBiography(biography) {
    const formData = new FormData();
    formData.append('action', 'update_biography');
    formData.append('biography', biography);

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        showAlert('Bio actualizada');
        reloadIframe();

        if (!response.ok) {
            console.error('Error HTTP: ' + response.status);
            return;
        }

        const data = await response.json();
    } catch (error) {
        console.error('Error:', error);
    }
}

bioTextarea.addEventListener('input', function(e) {
    updateCharCount();
    
    clearTimeout(updateTimeout);
    
    updateTimeout = setTimeout(() => {
        updateBiography(e.target.value);
    }, 500);
});

updateCharCount();

// Tema
const dropdownToggle = document.getElementById('dropdownToggle');
const dropdownMenu = document.getElementById('dropdownMenu');
const selectedThemeInput = document.getElementById('selectedTheme');
const themeForm = document.getElementById('themeForm');

const initializeDropdown = () => {
    const activeOption = dropdownMenu.querySelector('.active');
    if (activeOption) {
        const displayText = activeOption.getAttribute('data-display');
        dropdownToggle.textContent = displayText;
    }
};

const updateTheme = (theme, displayText) => {
    document.querySelectorAll('.dropdown-menu li').forEach(li => li.classList.remove('active'));
    const selectedLi = document.querySelector(`[data-theme="${theme}"]`);
    if (selectedLi) {
        selectedLi.classList.add('active');
        dropdownToggle.textContent = displayText;
    }

    const formData = new FormData();
    formData.append('theme', theme);

    fetch('', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadIframe();
            showAlert('Tema actualizado');
        }
    })
    .catch(error => {
        console.error('Error updating theme:', error);
        window.location.reload();
    });

    dropdownMenu.classList.remove('active', 'fade-in');
    dropdownMenu.classList.add('fade-out');
    dropdownToggle.classList.remove('active');
    setTimeout(() => {
        dropdownMenu.style.display = 'none';
        dropdownMenu.classList.remove('fade-out');
    }, 300);
};

dropdownToggle.addEventListener('click', () => {
    if (dropdownMenu.classList.contains('fade-in')) {
        dropdownMenu.classList.remove('fade-in');
        dropdownMenu.classList.add('fade-out');
        dropdownToggle.classList.remove('active');
        setTimeout(() => {
            dropdownMenu.style.display = 'none';
            dropdownMenu.classList.remove('fade-out');
        }, 300);
    } else {
        dropdownToggle.classList.add('active');
        dropdownMenu.style.display = 'block';
        dropdownMenu.classList.add('fade-in');
    }
});

dropdownMenu.addEventListener('click', (event) => {
    if (event.target.tagName === 'LI') {
        const selectedTheme = event.target.getAttribute('data-theme');
        const displayText = event.target.getAttribute('data-display');
        updateTheme(selectedTheme, displayText);
    }
});

document.addEventListener('click', (event) => {
    if (!dropdownMenu.contains(event.target) && !dropdownToggle.contains(event.target)) {
        if (dropdownMenu.style.display === 'block') {
            dropdownMenu.classList.remove('fade-in');
            dropdownMenu.classList.add('fade-out');
            dropdownToggle.classList.remove('active');
            setTimeout(() => {
                dropdownMenu.style.display = 'none';
                dropdownMenu.classList.remove('fade-out');
            }, 300);
        }
    }
});

initializeDropdown();

// Añadir link
function addLinkElementListeners(element) {
    // Separamos los listeners para título y URL
    const titleInput = element.querySelector('.title-input');
    const urlInput = element.querySelector('.url-input');
    
    let titleTimeout;
    titleInput.addEventListener('input', (event) => {
        clearTimeout(titleTimeout);
        titleTimeout = setTimeout(() => handleTitleUpdate(event.target), 500);
    });
    
    let urlTimeout;
    urlInput.addEventListener('input', (event) => {
        clearTimeout(urlTimeout);
        urlTimeout = setTimeout(() => handleUrlUpdate(event.target), 500);
    });

    // Mantenemos el listener para la imagen de la tarjeta igual
    const cardImageInput = element.querySelector('.card-image-input');
    if (cardImageInput) {
        cardImageInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                handleCardImageUpload(element, file);
            }
        });
    }
}

function handleTitleUpdate(element) {
    const linkElement = element.closest('.linkElement');
    const linkId = linkElement.dataset.id;

    const data = new FormData();
    data.append('action', 'update_title');
    data.append('link_id', linkId);
    data.append('title', element.value);

    fetch('', {
        method: 'POST',
        body: data,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadIframe();
            linkElement.classList.remove('invalid');
        }
    })
    .catch(error => {
        linkElement.classList.add('invalid');
    });
}

function handleUrlUpdate(element) {
    const linkElement = element.closest('.linkElement');
    const linkId = linkElement.dataset.id;

    const data = new FormData();
    data.append('action', 'update_url');
    data.append('link_id', linkId);
    data.append('url', element.value);

    fetch('', {
        method: 'POST',
        body: data,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadIframe();
            linkElement.classList.remove('invalid');
        } else {
            linkElement.classList.add('invalid');
        }
    })
    .catch(error => {
        linkElement.classList.add('invalid');
    });
}

// Añadir link
document.querySelectorAll('.add-link-type').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const type = this.dataset.type;
        
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add',
                type: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newLink = createLinkElement({
                    id: data.link_id,
                    type: type
                });
                linkList.appendChild(newLink);
                reloadIframe();
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            }
        });
    });
});

// Crear link
const MAX_LINKS = 10;

function createLinkElement(linkData) {
    const linkList = document.querySelector('.linkList');
    const currentLinkCount = linkList.querySelectorAll('.linkElement').length;

    if (currentLinkCount >= MAX_LINKS) {
        alert("Has alcanzado el límite máximo de 10 enlaces.");
        return;
    }

    const li = document.createElement('li');
    li.className = `linkElement new ${linkData.type === 'card' ? 'card' : ''}`;
    li.dataset.id = linkData.id;

    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'link-actions';
    actionsDiv.innerHTML = `
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="link_id" value="${linkData.id}">
            <button type="submit" class="delete">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M15.537 3.13259h-3.0427V2.09793c0-.88366-.7164-1.600005-1.6-1.600005H6.10407c-.88366 0-1.6.716345-1.6 1.599995v1.03467H1.46151c-.33137 0-.599999.26863-.599999.6 0 .1644.066117.31336.173209.42173.10878.11008.25983.1783.42682.1783H15.537c.3313 0 .6-.26863.6-.6 0-.12428-.0378-.23973-.1025-.33549-.0454-.06721-.1041-.12472-.1722-.16878-.0938-.06059-.2054-.09576-.3253-.09576Zm-4.2427-1.03466v1.03466H5.70407V2.09792c0-.22091.17908-.39999.4-.39999h4.79023c.2209 0 .4.17908.4.4Z" clip-rule="evenodd"/><path fill="#EAE6E3" fill-rule="evenodd" d="M3.71359 3.1286c-.88366 0-1.6.71635-1.6 1.6v8.68c0 1.9882 1.61177 3.6 3.59999 3.6h5.57002c1.9882 0 3.6-1.6118 3.6-3.6v-8.68c0-.88365-.7164-1.6-1.6-1.6H3.71359Zm-.4 1.6c0-.22091.17908-.4.4-.4h9.57001c.2209 0 .4.17909.4.4v8.68c0 1.3255-1.0745 2.4-2.4 2.4H5.71358c-1.32548 0-2.39999-1.0745-2.39999-2.4v-8.68Zm3.39622 2.28495c.33138 0 .6.26863.6.6v4.91415c0 .3314-.26863.6-.6.6s-.6-.2686-.6-.6V7.61355c0-.33137.26863-.6.6-.6Zm3.57869 0c.3314 0 .6.26863.6.6v4.91415c0 .3314-.2686.6-.6.6-.33132 0-.59995-.2686-.59995-.6V7.61355c0-.33137.26863-.6.59995-.6Z" clip-rule="evenodd"/></svg>
            </button>
        </form>
    `;

    li.appendChild(actionsDiv);

    if (linkData.type === 'card') {
        const cardContainer = document.createElement('div');
        cardContainer.className = 'cardContainer';
        cardContainer.innerHTML = `
            <div class="card-image-container">
                <div class="card-image-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#131212" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                </div>
                <img src="" alt="Card Image Preview" class="card-image-preview" style="display: none;">
                <div class="spinner" style="display: none;"><span class="loader"></span></div>
                <div class="card-actions">
                    <form method="POST" enctype="multipart/form-data" class="card-image-form">
                        <input type="file" name="card_image" class="card-image-input" accept="image/*" style="display: none;">
                        <button type="button" class="actionButton" onclick="this.previousElementSibling.click();">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" fill="none"><path fill="#131212" fill-rule="evenodd" d="M3.62472 2.2426c-1.32548 0-2.4 1.07452-2.4 2.4v6.3495c0 1.3255 1.07452 2.4 2.4 2.4h6.34949c1.32549 0 2.39999-1.0745 2.39999-2.4V8.81735c0-.33137.2686-.6.6-.6s.6.26863.6.6v2.17475c0 1.9882-1.6118 3.6-3.59999 3.6H3.62472c-1.98823 0-3.6000008-1.6118-3.6000008-3.6V4.6426c0-1.98822 1.6117708-3.6 3.6000008-3.6h2.17474c.33137 0 .6.26863.6.6s-.26863.6-.6.6H3.62472Z" clip-rule="evenodd"></path><path fill="#131212" fill-rule="evenodd" d="M9.74379 3.45875 11.11 2.09257c.3905-.39053 1.0237-.39053 1.4142 0 .3905.39052.3905 1.02369 0 1.41421L11.158 4.87297 9.74379 3.45875Zm-.70711.70711 1.41422 1.41421-4.29067 4.29067c-.39053.39056-1.02369.39056-1.41421 0-.39053-.39052-.39053-1.02369 0-1.41421l4.29066-4.29067ZM4.03891 10.5778c-.78105-.781-.78105-2.04733 0-2.82838l4.29067-4.29067.7071-.7071 1.36622-1.36619c.781-.781048 2.0473-.781048 2.8284 0 .781.78105.781 2.04738 0 2.82843l-1.3662 1.36618-.7071.70711-4.29066 4.29062c-.78105.7811-2.04738.7811-2.82843 0Z" clip-rule="evenodd"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
            <span class="linkContainer">
                <input type="text" 
                    class="title-input"
                    value=""
                    placeholder="Título"
                    minlength="1"
                    maxlength="70"
                    required>
                <input type="url" 
                    class="url-input" 
                    value="" 
                    placeholder="https://" 
                    minlength="10" 
                    maxlength="250">
            </span>
        `;
        li.appendChild(cardContainer);
    } else {
        const linkSpan = document.createElement('span');
        linkSpan.className = 'linkContainer';
        linkSpan.innerHTML = `
            <input type="text" 
                class="title-input"
                value=""
                placeholder="Título"
                minlength="1"
                maxlength="70"
                required>
            <input type="url" 
                class="url-input" 
                value="" 
                placeholder="https://" 
                minlength="10" 
                maxlength="250">
        `;
        li.appendChild(linkSpan);
    }

    const dragArea = document.createElement('div');
    dragArea.className = 'dragArea';
    dragArea.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="15" viewBox="0 0 11 15" fill="none"><path fill="#D9D9D9" d="M3.02991 2.08636c0 .82843-.67158 1.5-1.5 1.5-.82843 0-1.5000028-.67157-1.5000028-1.5 0-.82842.6715728-1.499995 1.5000028-1.499995.82842 0 1.5.671575 1.5 1.499995ZM10.0339 2.08636c0 .82843-.6716 1.5-1.50003 1.5-.82842 0-1.5-.67157-1.5-1.5 0-.82842.67158-1.499995 1.5-1.499995.82843 0 1.50003.671575 1.50003 1.499995ZM3.02991 7.56732c0 .82843-.67158 1.5-1.5 1.5-.82843 0-1.5000028-.67157-1.5000028-1.5s.6715728-1.5 1.5000028-1.5c.82842 0 1.5.67157 1.5 1.5ZM3.02991 13.0483c0 .8285-.67158 1.5-1.5 1.5-.82843 0-1.5000028-.6715-1.5000028-1.5 0-.8284.6715728-1.5 1.5000028-1.5.82842 0 1.5.6716 1.5 1.5ZM10.0339 7.56732c0 .82843-.6716 1.5-1.50003 1.5-.82842 0-1.5-.67157-1.5-1.5s.67158-1.5 1.5-1.5c.82843 0 1.50003.67157 1.50003 1.5ZM10.0339 13.0483c0 .8285-.6716 1.5-1.50003 1.5-.82842 0-1.5-.6715-1.5-1.5 0-.8284.67158-1.5 1.5-1.5.82843 0 1.50003.6716 1.50003 1.5Z"/></svg>
    `;
    li.appendChild(dragArea);

    addLinkElementListeners(li);

    return li;
}

// Actualizar link
function handleTitleUpdate(element) {
    const linkElement = element.closest('.linkElement');
    const linkId = linkElement.dataset.id;
    const titleInput = linkElement.querySelector('.title-input');

    const data = new FormData();
    data.append('action', 'update_title');
    data.append('link_id', linkId);
    data.append('title', titleInput.value);

    fetch('', {
        method: 'POST',
        body: data,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadIframe();
            linkElement.classList.remove('invalid');
        }
    })
    .catch(error => {
        linkElement.classList.add('invalid');
    });
}

function handleUrlUpdate(element) {
    const linkElement = element.closest('.linkElement');
    const linkId = linkElement.dataset.id;
    const urlInput = linkElement.querySelector('.url-input');

    const data = new FormData();
    data.append('action', 'update_url');
    data.append('link_id', linkId);
    data.append('url', urlInput.value);

    fetch('', {
        method: 'POST',
        body: data,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadIframe();
            linkElement.classList.remove('invalid');
        } else {
            linkElement.classList.add('invalid');
        }
    })
    .catch(error => {
        linkElement.classList.add('invalid');
    });
}

document.querySelectorAll('.title-input').forEach(input => {
    let timeout;
    input.addEventListener('input', (event) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => handleTitleUpdate(event.target), 500);
    });
});

document.querySelectorAll('.url-input').forEach(input => {
    let timeout;
    input.addEventListener('input', (event) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => handleUrlUpdate(event.target), 500);
    });
});

// Reordenar links
const sortable = new Sortable(linkList, {
    handle: '.dragArea',
    ghostClass: 'sortable-ghost',
    animation: 350,
    onChoose: () => {
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    },
    onStart: () => {
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    },
    onChange: () => {
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    },
    onEnd: () => {
        const positions = Array.from(linkList.children).map((el, index) => ({
            id: el.dataset.id,
            position: index
        }));
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'reorder',
                positions: positions
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                reloadIframe();
            } else {
                console.error('Error saving order:', data.error);
            }
        })
        .catch(error => {
            console.error('Request error:', error);
        });
    }
});

linkList.addEventListener('click', (event) => {
    if (event.target.closest('.delete')) {
        event.preventDefault();
        if (confirm('¿Estás seguro de que deseas eliminar este enlace?')) {
            const form = event.target.closest('form');
            const formData = new FormData(form);
            fetch('', {
                method: 'POST',
                body: formData
            }).then(() => {
                form.closest('.linkElement').remove();
                reloadIframe();
                showAlert('Enlace eliminado');
            });
        }
    }
});

// Subir imagen link
function handleCardImageUpload(linkElement, file) {
    const linkId = linkElement.dataset.id;
    const formData = new FormData();
    formData.append('card_image', file);
    formData.append('link_id', linkId);

    const imagePreview = linkElement.querySelector('.card-image-preview');
    const placeholder = linkElement.querySelector('.card-image-placeholder');
    const spinner = linkElement.querySelector('.spinner');

    if (imagePreview) imagePreview.style.opacity = "0.25";
    if (spinner) spinner.style.display = 'block';

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (imagePreview) {
                imagePreview.src = `/assets/${data.filename}`;
                imagePreview.style.display = 'block';
                imagePreview.style.opacity = "1";
            }
            if (placeholder) placeholder.style.display = 'none';
            if (spinner) spinner.style.display = 'none';
            
            const deleteButton = linkElement.querySelector('.delete-card-image');
            if (!deleteButton) {
                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.style.display = 'inline-block';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_card_image';
                
                const linkIdInput = document.createElement('input');
                linkIdInput.type = 'hidden';
                linkIdInput.name = 'link_id';
                linkIdInput.value = linkId;
                
                const deleteButton = document.createElement('button');
                deleteButton.type = 'submit';
                deleteButton.className = 'delete-card-image actionButton';
                deleteButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"><path fill="#131212" fill-rule="evenodd" d="M10.4253 1.78143c.1952-.19527.1952-.51185 0-.70711-.1953-.195264-.51189-.195264-.70715 0l-4.0362 4.0362-4.03619-4.03619c-.19526-.19526-.51185-.19526-.707108 0-.195262.19526-.195262.51185 0 .70711L4.97484 5.81763.938636 9.85383c-.195262.19527-.195263.51187 0 .70707.195264.1953.511844.1953.707104 0l4.03621-4.03617L9.71816 10.561c.19527.1952.51184.1952.70714 0 .1952-.1953.1952-.5119 0-.70716L6.38905 5.81763l4.03625-4.0362Z" clip-rule="evenodd"/></svg>';
                
                deleteForm.appendChild(actionInput);
                deleteForm.appendChild(linkIdInput);
                deleteForm.appendChild(deleteButton);
                
                const cardActions = linkElement.querySelector('.card-actions');
                if (cardActions) {
                    cardActions.appendChild(deleteForm);
                }
            }
            
            showAlert('Imagen subida correctamente');
            reloadIframe();
        }
    })
    .catch(error => {
        console.error('Error uploading image:', error);
        if (imagePreview) imagePreview.style.opacity = "1";
        if (spinner) spinner.style.display = 'none';
    });
}

document.querySelectorAll('.card-image-input').forEach(input => {
    input.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const linkElement = this.closest('.linkElement');
            handleCardImageUpload(linkElement, file);
        }
    });
});

// Eliminar imagen link
document.addEventListener('submit', function(e) {
    if (e.target.querySelector('input[name="action"][value="delete_card_image"]')) {
        e.preventDefault();
        if (confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
            fetch('', {
                method: 'POST',
                body: new FormData(e.target),
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const linkElement = e.target.closest('.linkElement');
                    const imagePreview = linkElement.querySelector('.card-image-preview');
                    const placeholder = linkElement.querySelector('.card-image-placeholder');
                    
                    if (imagePreview) {
                        imagePreview.style.display = 'none';
                        imagePreview.src = '';
                    }
                    if (placeholder) {
                        placeholder.style.display = 'flex';
                    }
                    
                    e.target.remove();
                    showAlert('Imagen eliminada');
                    reloadIframe();
                }
            });
        }
    }
});

// Alert
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.textContent = message;
    document.body.appendChild(alert);
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
    setTimeout(() => {
        alert.style.opacity = 0;
        setTimeout(() => {
            alert.remove();
        }, 500);
    }, 3000);
}

});
</script>
<script src="https://cdn.jsdelivr.net/npm/qr-creator@1.0.0/dist/qr-creator.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {

const shareElement = document.querySelector(".profileInfo, .a span");
const qrContainer = document.querySelector("#qrCodeContainer");

if (shareElement && qrContainer) {
let url = shareElement.textContent.trim();

if (!url.startsWith("http://") && !url.startsWith("https://")) {
    url = "https://" + url;
}

if (url) {
    QrCreator.render({
        text: url,
        radius: 0.5,
        ecLevel: 'L',
        fill: '#edeae8',
        background: null,
        size: 300
    }, qrContainer);
}

} else {
console.error("No se encontró el elemento .shareURL o el contenedor del QR.");
}

});
</script>

</head>
<body>

<?php include '../includes/nav.php'; ?>

<main>

<div class="mainGroup">
<div class="profile-section">
    <div class="profileImage">
        <div class="profileImageWrapper">
            <?php if ($user['profile_image'] && file_exists('../assets/' . $user['profile_image'])): ?>
                <img src="/assets/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-image" id="profile_image_preview">
            <?php else: ?>
                <div class="profile-image-placeholder">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <img src="" alt="Profile Image Preview" class="profile-image" id="profile_image_preview" style="display: none;">
            <?php endif; ?>
            <div id="spinner" class="spinner" style="display: none;"><span class="loader"></span></div>
        </div>
        <div class="profile-actions">        
            <?php if ($user['profile_image']): ?>
                <form method="POST" style="display: inline-block;">
                    <input type="hidden" name="action" value="delete_profile_image">
                    <button type="submit" class="actionButton">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"><path fill="#131212" fill-rule="evenodd" d="M10.496 1.85214c.2343-.23432.2343-.61422 0-.84853-.2343-.234317-.61424-.234317-.84855 0L5.68196 4.9691 1.71648 1.00362c-.23431-.234316-.61421-.234316-.848524 0-.234314.23431-.234314.61421 0 .84853L4.83343 5.81762.867937 9.78312c-.234314.23428-.234314.61418 0 .84848.234313.2344.614213.2344.848533 0l3.96549-3.96545 3.96551 3.96555c.23431.2343.61423.2343.84853 0 .2343-.2344.2343-.6143 0-.84857L6.53049 5.81762 10.496 1.85214Z" clip-rule="evenodd"/></svg>
                    </button>
                </form>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" id="profile_image_form">
                <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display: none;">
                <button type="button" class="actionButton" onclick="document.getElementById('profile_image').click();">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" fill="none"><path fill="#131212" fill-rule="evenodd" d="M3.62472 2.2426c-1.32548 0-2.4 1.07452-2.4 2.4v6.3495c0 1.3255 1.07452 2.4 2.4 2.4h6.34949c1.32549 0 2.39999-1.0745 2.39999-2.4V8.81735c0-.33137.2686-.6.6-.6s.6.26863.6.6v2.17475c0 1.9882-1.6118 3.6-3.59999 3.6H3.62472c-1.98823 0-3.6000008-1.6118-3.6000008-3.6V4.6426c0-1.98822 1.6117708-3.6 3.6000008-3.6h2.17474c.33137 0 .6.26863.6.6s-.26863.6-.6.6H3.62472Z" clip-rule="evenodd"/><path fill="#131212" fill-rule="evenodd" d="M9.74379 3.45875 11.11 2.09257c.3905-.39053 1.0237-.39053 1.4142 0 .3905.39052.3905 1.02369 0 1.41421L11.158 4.87297 9.74379 3.45875Zm-.70711.70711 1.41422 1.41421-4.29067 4.29067c-.39053.39056-1.02369.39056-1.41421 0-.39053-.39052-.39053-1.02369 0-1.41421l4.29066-4.29067ZM4.03891 10.5778c-.78105-.781-.78105-2.04733 0-2.82838l4.29067-4.29067.7071-.7071 1.36622-1.36619c.781-.781048 2.0473-.781048 2.8284 0 .781.78105.781 2.04738 0 2.82843l-1.3662 1.36618-.7071.70711-4.29066 4.29062c-.78105.7811-2.04738.7811-2.82843 0Z" clip-rule="evenodd"/></svg>
                </button>
            </form>
        </div>
    </div>
    <div class="profileInfo">
        <!-- <h2>@<?php echo htmlspecialchars($user['username']); ?></h2> -->
        <div class="qrGroup">
            <svg class="edges" xmlns="http://www.w3.org/2000/svg" width="128" height="127" viewBox="0 0 128 127" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M126.124 13.41c0-6.69645-5.429-12.12503-12.126-12.12503-.276 0-.5-.22385-.5-.499997 0-.276142.224-.5.5-.5 7.249 0 13.126 5.876287 13.126 13.125027 0 .2762-.224.5-.5.5-.277 0-.5-.2238-.5-.5ZM1.81091 113.472c0 6.697 5.42858 12.126 12.12509 12.126.2761 0 .5.223.5.5 0 .276-.2239.5-.5.5-7.2488 0-13.125087-5.877-13.125087-13.126-.000001-.276.223857-.5.499997-.5.27615 0 .5.224.5.5ZM113.998 125.597c6.697 0 12.125-5.428 12.125-12.125 0-.276.224-.5.5-.5.277 0 .5.224.5.5 0 7.249-5.876 13.125-13.125 13.125-.276 0-.5-.223-.5-.5 0-.276.224-.5.5-.5ZM13.936 1.28502c-6.6965 0-12.12507 5.42857-12.12507 12.12508 0 .2761-.22386.5-.5.5-.27615 0-.500003-.2239-.500003-.5C.810927 6.16131 6.68722.285021 13.936.285021c.2761 0 .5.223857.5.5 0 .276139-.2239.499999-.5.499999Z" clip-rule="evenodd"/></svg>
            <div id="qrCodeContainer"></div>
        </div>
        <a href="https://agrupa.link/<?php echo htmlspecialchars($user['username']); ?>" class="button dark icon" target="_blank">
            <span>agrupa.link/<?php echo htmlspecialchars($user['username']); ?></span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M8.55017.644486c.00001-.331371.26864-.5999962.60001-.5999914l4.20302.0000606c.3314.0000049.6.2686268.6.5999918l.0001 4.203043c0 .33137-.2686.6-.6.60001-.3314 0-.6-.26862-.6-.59999l-.0001-2.75455-6.26293 6.26297c-.23432.23431-.61422.23432-.84853 0-.23432-.23431-.23432-.61421 0-.84853l6.26296-6.26297-2.75454-.00004c-.33137 0-.59999-.268633-.59999-.600004Z" clip-rule="evenodd"/><path fill="#EAE6E3" fill-rule="evenodd" d="M3.99948 1.2426c-1.32549 0-2.4 1.07452-2.4 2.4v6.34949c0 1.32551 1.07451 2.40001 2.4 2.40001H10.349c1.3254 0 2.4-1.0745 2.4-2.40001V7.81735c0-.33137.2686-.6.6-.6.3313 0 .6.26863.6.6v2.17474c0 1.98821-1.6118 3.60001-3.6 3.60001H3.99948c-1.98823 0-3.600005-1.6118-3.600005-3.60001V3.6426c0-1.98822 1.611775-3.5999975 3.600005-3.5999975h2.17474c.33137 0 .6.2686295.6.6000005 0 .33137-.26863.599997-.6.599997H3.99948Z" clip-rule="evenodd"/></svg>
        </a>
    </div>
</div>

<div class="group bio-container">
    <h2>Biografía</h2>
    <textarea 
        id="biography" 
        name="biography" 
        class="bio-textarea"
        placeholder="Escribe tu biografía..."
        maxlength="150"
    ><?php echo htmlspecialchars($user['biography'] ?? ''); ?></textarea>
    <div class="bio-char-count">
        <span id="charCount">0</span>/150 caracteres
    </div>
</div>

<div class="group themeSwitch">
    <h2>Tema</h2>
    <form method="POST" id="themeForm">
        <div class="dropdown">
            <div class="dropdown-toggle" id="dropdownToggle"><?php echo htmlspecialchars($user['theme']); ?></div>
            <ul class="dropdown-menu" id="dropdownMenu">
                <li data-theme="agrupaDark" data-display="Oscuro" <?php echo $user['theme'] === 'agrupaDark' ? 'class="active"' : ''; ?>>Oscuro</li>
                <li data-theme="agrupaLight" data-display="Claro" <?php echo $user['theme'] === 'agrupaLight' ? 'class="active"' : ''; ?>>Claro</li>
                <li data-theme="agrupaSuave" data-display="Suave" <?php echo $user['theme'] === 'agrupaSuave' ? 'class="active"' : ''; ?>>Suave</li>
                <li data-theme="agrupaTech" data-display="Tech" <?php echo $user['theme'] === 'agrupaTech' ? 'class="active"' : ''; ?>>Tech</li>
                <li data-theme="agrupaFallout" data-display="Fallout" <?php echo $user['theme'] === 'agrupaFallout' ? 'class="active"' : ''; ?>>Fallout</li>
            </ul>
            <input type="hidden" name="theme" id="selectedTheme">
        </div>
    </form>
</div>

</div>

<div class="mainGroup">
<div class="group myLinks">
    <h2>Enlaces</h2>
    <ul class="linkList">
        <li class="no-links-message">Añade un link para comenzar</li>
    <?php foreach ($links as $link): ?>
        <li class="linkElement <?php echo $link['type'] === 'card' ? 'card' : ''; ?>" data-id="<?php echo $link['id']; ?>">
            <div class="link-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                    <button type="submit" class="delete">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M15.537 3.13259h-3.0427V2.09793c0-.88366-.7164-1.600005-1.6-1.600005H6.10407c-.88366 0-1.6.716345-1.6 1.599995v1.03467H1.46151c-.33137 0-.599999.26863-.599999.6 0 .1644.066117.31336.173209.42173.10878.11008.25983.1783.42682.1783H15.537c.3313 0 .6-.26863.6-.6 0-.12428-.0378-.23973-.1025-.33549-.0454-.06721-.1041-.12472-.1722-.16878-.0938-.06059-.2054-.09576-.3253-.09576Zm-4.2427-1.03466v1.03466H5.70407V2.09792c0-.22091.17908-.39999.4-.39999h4.79023c.2209 0 .4.17908.4.4Z" clip-rule="evenodd"/><path fill="#EAE6E3" fill-rule="evenodd" d="M3.71359 3.1286c-.88366 0-1.6.71635-1.6 1.6v8.68c0 1.9882 1.61177 3.6 3.59999 3.6h5.57002c1.9882 0 3.6-1.6118 3.6-3.6v-8.68c0-.88365-.7164-1.6-1.6-1.6H3.71359Zm-.4 1.6c0-.22091.17908-.4.4-.4h9.57001c.2209 0 .4.17909.4.4v8.68c0 1.3255-1.0745 2.4-2.4 2.4H5.71358c-1.32548 0-2.39999-1.0745-2.39999-2.4v-8.68Zm3.39622 2.28495c.33138 0 .6.26863.6.6v4.91415c0 .3314-.26863.6-.6.6s-.6-.2686-.6-.6V7.61355c0-.33137.26863-.6.6-.6Zm3.57869 0c.3314 0 .6.26863.6.6v4.91415c0 .3314-.2686.6-.6.6-.33132 0-.59995-.2686-.59995-.6V7.61355c0-.33137.26863-.6.59995-.6Z" clip-rule="evenodd"/></svg>
                    </button>
                </form>
            </div>

            <?php if ($link['type'] === 'card'): ?>
                <div class="cardContainer">
                    <div class="card-image-container">
                        <?php if ($link['card_image'] && file_exists('../assets/' . $link['card_image'])): ?>
                            <img src="/assets/<?php echo htmlspecialchars($link['card_image']); ?>" alt="Card Image" class="card-image-preview">
                        <?php else: ?>
                            <div class="card-image-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#131212" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                            <img src="" alt="Card Image Preview" class="card-image-preview" style="display: none;">
                        <?php endif; ?>
                        <div class="spinner" style="display: none;"><span class="loader"></span></div>
                        <div class="card-actions">
                            <?php if ($link['card_image']): ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="delete_card_image">
                                    <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                    <button type="submit" class="delete-card-image actionButton">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"><path fill="#131212" fill-rule="evenodd" d="M10.496 1.85214c.2343-.23432.2343-.61422 0-.84853-.2343-.234317-.61424-.234317-.84855 0L5.68196 4.9691 1.71648 1.00362c-.23431-.234316-.61421-.234316-.848524 0-.234314.23431-.234314.61421 0 .84853L4.83343 5.81762.867937 9.78312c-.234314.23428-.234314.61418 0 .84848.234313.2344.614213.2344.848533 0l3.96549-3.96545 3.96551 3.96555c.23431.2343.61423.2343.84853 0 .2343-.2344.2343-.6143 0-.84857L6.53049 5.81762 10.496 1.85214Z" clip-rule="evenodd"/></svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data" class="card-image-form">
                                <input type="file" name="card_image" class="card-image-input" accept="image/*" style="display: none;">
                                <button type="button" class="actionButton" onclick="this.previousElementSibling.click();">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" fill="none"><path fill="#131212" fill-rule="evenodd" d="M3.62472 2.2426c-1.32548 0-2.4 1.07452-2.4 2.4v6.3495c0 1.3255 1.07452 2.4 2.4 2.4h6.34949c1.32549 0 2.39999-1.0745 2.39999-2.4V8.81735c0-.33137.2686-.6.6-.6s.6.26863.6.6v2.17475c0 1.9882-1.6118 3.6-3.59999 3.6H3.62472c-1.98823 0-3.6000008-1.6118-3.6000008-3.6V4.6426c0-1.98822 1.6117708-3.6 3.6000008-3.6h2.17474c.33137 0 .6.26863.6.6s-.26863.6-.6.6H3.62472Z" clip-rule="evenodd"></path><path fill="#131212" fill-rule="evenodd" d="M9.74379 3.45875 11.11 2.09257c.3905-.39053 1.0237-.39053 1.4142 0 .3905.39052.3905 1.02369 0 1.41421L11.158 4.87297 9.74379 3.45875Zm-.70711.70711 1.41422 1.41421-4.29067 4.29067c-.39053.39056-1.02369.39056-1.41421 0-.39053-.39052-.39053-1.02369 0-1.41421l4.29066-4.29067ZM4.03891 10.5778c-.78105-.781-.78105-2.04733 0-2.82838l4.29067-4.29067.7071-.7071 1.36622-1.36619c.781-.781048 2.0473-.781048 2.8284 0 .781.78105.781 2.04738 0 2.82843l-1.3662 1.36618-.7071.70711-4.29066 4.29062c-.78105.7811-2.04738.7811-2.82843 0Z" clip-rule="evenodd"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <span class="linkContainer">
                        <input type="text" 
                            class="title-input"
                            value="<?php echo htmlspecialchars($link['title']); ?>"
                            placeholder="Título"
                            minlength="1"
                            maxlength="70"
                            required>
                        <input type="url" 
                            class="url-input" 
                            value="<?php echo htmlspecialchars($link['url']); ?>" 
                            placeholder="https://" 
                            minlength="10" 
                            maxlength="250">
                    </span>
                </div>
            <?php else: ?>
                <span class="linkContainer">
                    <input type="text" 
                        class="title-input"
                        value="<?php echo htmlspecialchars($link['title']); ?>"
                        placeholder="Título"
                        minlength="1"
                        maxlength="70"
                        required>
                    <input type="url" 
                        class="url-input" 
                        value="<?php echo htmlspecialchars($link['url']); ?>" 
                        placeholder="https://" 
                        minlength="10" 
                        maxlength="250">
                </span>
            <?php endif; ?>

            <div class="dragArea">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="15" viewBox="0 0 11 15" fill="none"><path fill="#D9D9D9" d="M3.02991 2.08636c0 .82843-.67158 1.5-1.5 1.5-.82843 0-1.5000028-.67157-1.5000028-1.5 0-.82842.6715728-1.499995 1.5000028-1.499995.82842 0 1.5.671575 1.5 1.499995ZM10.0339 2.08636c0 .82843-.6716 1.5-1.50003 1.5-.82842 0-1.5-.67157-1.5-1.5 0-.82842.67158-1.499995 1.5-1.499995.82843 0 1.50003.671575 1.50003 1.499995ZM3.02991 7.56732c0 .82843-.67158 1.5-1.5 1.5-.82843 0-1.5000028-.67157-1.5000028-1.5s.6715728-1.5 1.5000028-1.5c.82842 0 1.5.67157 1.5 1.5ZM3.02991 13.0483c0 .8285-.67158 1.5-1.5 1.5-.82843 0-1.5000028-.6715-1.5000028-1.5 0-.8284.6715728-1.5 1.5000028-1.5.82842 0 1.5.6716 1.5 1.5ZM10.0339 7.56732c0 .82843-.6716 1.5-1.50003 1.5-.82842 0-1.5-.67157-1.5-1.5s.67158-1.5 1.5-1.5c.82843 0 1.50003.67157 1.50003 1.5ZM10.0339 13.0483c0 .8285-.6716 1.5-1.50003 1.5-.82842 0-1.5-.6715-1.5-1.5 0-.8284.67158-1.5 1.5-1.5.82843 0 1.50003.6716 1.50003 1.5Z"/></svg>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
    <form method="POST" class="addLinkForm">
        <input type="hidden" name="type" value="link">
        <input type="hidden" name="action" value="add">
        <button type="button" class="button dark add-link-type" data-type="card">
            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="16" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M17.2709 1.26769H3.27094c-.99412 0-1.8.80589-1.8 1.8v9.50001c0 .5568.25285 1.0546.65001 1.3848l5.24378-5.24375c.23431-.23431.61421-.23431.84853.00001l2.11244 2.11244 4.1125-4.11244c.2343-.23432.6142-.23432.8485 0l3.7842 3.78424V3.06769c0-.99411-.8059-1.8-1.8-1.8Zm0 13.10001H3.40282l4.38617-4.38615L9.90147 12.094c.23433.2343.61423.2343.84853 0l4.1125-4.11245 4.1124 4.11245c.0299.0299.0621.0559.096.0781v.3956c0 .9941-.8059 1.8-1.8 1.8ZM3.27094.067688C1.61408.067688.270935 1.41083.270935 3.06769v9.50001c0 1.6568 1.343145 3 2.999995 3H17.2709c1.6569 0 3-1.3432 3-3V3.06769c0-1.65686-1.3431-3.000002-3-3.000002H3.27094ZM7.79987 7.133c.82842 0 1.5-.67158 1.5-1.5 0-.82843-.67158-1.5-1.5-1.5-.82843 0-1.5.67157-1.5 1.5 0 .82842.67157 1.5 1.5 1.5Z" clip-rule="evenodd"/></svg>
            <span>Imagen</span>
        </button>
        <button type="button" class="button dark add-link-type" data-type="link">
        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M17.0362 2.10357c2.3658 2.36582 2.3658 6.20157 0 8.56743l-.9088.9088c-.2343.2343-.6142.2343-.8485 0-.2344-.2344-.2344-.6143 0-.8486l.9088-.90877c1.8971-1.89719 1.8971-4.97314 0-6.87033-1.8972-1.89719-4.9732-1.89719-6.87037 0l-.9088.9088c-.23431.23431-.61421.23431-.84853 0-.23431-.23432-.23431-.61422 0-.84853l.9088-.9088c2.3658-2.365816 6.2016-2.365816 8.5674 0ZM2.23394 16.9059c-2.365815-2.3658-2.365815-6.2016 0-8.5674l.9088-.9088c.23431-.23431.61421-.23431.84853 0 .23431.23431.23431.61421 0 .84853l-.9088.9088c-1.89719 1.89717-1.89719 4.97317 0 6.87037 1.89719 1.8971 4.97314 1.8971 6.87033 0l.9088-.9088c.2343-.2344.6142-.2344.8485 0 .2343.2343.2343.6142 0 .8485l-.9088.9088c-2.36579 2.3658-6.20154 2.3658-8.56736 0ZM9.63509 6.40453c.33137 0 .60001.26862.60001.6v1.89995h1.9c.3314 0 .6.26863.6.6s-.2686.60002-.6.60002h-1.9v1.9c0 .3314-.26864.6-.60001.6-.33137 0-.6-.2686-.6-.6v-1.9h-1.9c-.33137 0-.6-.26865-.6-.60002s.26863-.6.6-.6h1.9V7.00453c0-.33138.26863-.6.6-.6Z" clip-rule="evenodd"/></svg>
            <span>Enlace</span>
        </button>
    </form>
    <a href="logout.php" class="button small dark icon logout">
        <span>Cerrar sesión</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M.92688 3.6426c0-1.98822 1.61178-3.5999975 3.6-3.5999975h4.63257c.33137 0 .6.2686295.6.6000005 0 .33137-.26863.599997-.6.599997H4.52688c-1.32548 0-2.4 1.07452-2.4 2.4v6.3495c0 1.3255 1.07452 2.4 2.4 2.4h4.63257c.33137 0 .6.2686.6.6s-.26863.6-.6.6H4.52688c-1.98823 0-3.6-1.6118-3.6-3.6V3.6426Z" clip-rule="evenodd"/><path fill="#EAE6E3" fill-rule="evenodd" d="M9.50183 3.42103c.23432-.23431.61417-.23431.84857.00001l2.9719 2.97205c.2343.23431.2343.6142 0 .84851l-2.9719 2.972c-.2344.2344-.61425.2344-.84857.0001-.23432-.23435-.23432-.61425-.00001-.84857l1.94768-1.94779H5.4208c-.33137 0-.6-.26863-.6-.6s.26863-.6.6-.6h6.0287L9.50182 4.26956c-.23431-.23432-.23431-.61422.00001-.84853Z" clip-rule="evenodd"/></svg>
    </a>
</div>
</div>

<iframe src="https://agrupa.link/<?php echo htmlspecialchars($user['username']); ?>" title="preview" id="preview"></iframe>

</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>
