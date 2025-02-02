<?php
require 'includes/db.php';

$username = htmlspecialchars($_GET['username']);
$stmt = $pdo->prepare("SELECT id, profile_image, theme, biography FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo "Usuario no encontrado.";
    exit;
}

$user_id = $user['id'];
$profile_image = $user['profile_image'];
$bio = $user['biography'];
$theme = htmlspecialchars($user['theme']);
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
	<meta name="description" content="Tus enlaces importantes en un solo lugar, listos para compartir">
	<link rel="icon" type="image/png" href="/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="agrupa.link" />
	<meta name="application-name" content="agrupa.link">
    <link rel="manifest" href="/favicon/site.webmanifest" />
    <meta name="theme-color" content="#131212">

    <meta property="og:url" content="https://agrupa.link/pedro">
    <meta property="og:type" content="website">
    <meta property="og:title" content="agrupa.link">
    <meta property="og:description" content="Tus enlaces importantes en un solo lugar, listos para compartir">
    <meta property="og:image" content="https://agrupa.link/assets/og-image.webp">

    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="agrupa.link">
    <meta property="twitter:url" content="https://agrupa.link/<?php echo htmlspecialchars($username); ?>">
    <meta name="twitter:title" content="agrupa.link">
    <meta name="twitter:description" content="Tus enlaces importantes en un solo lugar, listos para compartir">
    <meta name="twitter:image" content="https://agrupa.link/assets/og-image.webp">

	<link href="/style.css" rel="stylesheet">
    <link href="/includes/themes/<?php echo $theme; ?>.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/nav.php'; ?>

<main>
    <div class="profile-section">
        <?php if ($user['profile_image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $user['profile_image'])): ?>
            <img src="/assets/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-image" id="profile_image_preview">
        <?php else: ?>
            <div class="profile-image-placeholder">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <img src="" alt="Profile Image Preview" class="profile-image" id="profile_image_preview" style="display: none;">
        <?php endif; ?>
        <h1>@<?php echo htmlspecialchars($username); ?></h1>
        <p><?php echo htmlspecialchars($user['biography']); ?></p>
    </div>
    <ul class="links">
        <?php foreach ($links as $link): ?>
            <li class="linkElement">
                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="linkContainer">
                    <?php if ($link['type'] === 'card' && $link['card_image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $link['card_image'])): ?>
                        <div class="cardContainer">
                            <div class="card-image-container">
                                <img src="/assets/<?php echo htmlspecialchars($link['card_image']); ?>" alt="Card Image" class="card-image">
                            </div>
                            <div class="cardBottom">
                                <div class="text">
                                    <div class="title"><?php echo htmlspecialchars($link['title']); ?></div>
                                    <div class="url"><?php echo htmlspecialchars($link['url']); ?></div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M8.55017.644486c.00001-.331371.26864-.5999962.60001-.5999914l4.20302.0000606c.3314.0000049.6.2686268.6.5999918l.0001 4.203043c0 .33137-.2686.6-.6.60001-.3314 0-.6-.26862-.6-.59999l-.0001-2.75455-6.26293 6.26297c-.23432.23431-.61422.23432-.84853 0-.23432-.23431-.23432-.61421 0-.84853l6.26296-6.26297-2.75454-.00004c-.33137 0-.59999-.268633-.59999-.600004Z" clip-rule="evenodd"/><path fill="#EAE6E3" fill-rule="evenodd" d="M3.99948 1.2426c-1.32549 0-2.4 1.07452-2.4 2.4v6.34949c0 1.32551 1.07451 2.40001 2.4 2.40001H10.349c1.3254 0 2.4-1.0745 2.4-2.40001V7.81735c0-.33137.2686-.6.6-.6.3313 0 .6.26863.6.6v2.17474c0 1.98821-1.6118 3.60001-3.6 3.60001H3.99948c-1.98823 0-3.600005-1.6118-3.600005-3.60001V3.6426c0-1.98822 1.611775-3.5999975 3.600005-3.5999975h2.17474c.33137 0 .6.2686295.6.6000005 0 .33137-.26863.599997-.6.599997H3.99948Z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text">
                            <div class="title"><?php echo htmlspecialchars($link['title']); ?></div>
                            <div class="url"><?php echo htmlspecialchars($link['url']); ?></div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"><path fill="#EAE6E3" fill-rule="evenodd" d="M8.55017.644486c.00001-.331371.26864-.5999962.60001-.5999914l4.20302.0000606c.3314.0000049.6.2686268.6.5999918l.0001 4.203043c0 .33137-.2686.6-.6.60001-.3314 0-.6-.26862-.6-.59999l-.0001-2.75455-6.26293 6.26297c-.23432.23431-.61422.23432-.84853 0-.23432-.23431-.23432-.61421 0-.84853l6.26296-6.26297-2.75454-.00004c-.33137 0-.59999-.268633-.59999-.600004Z" clip-rule="evenodd"/><path fill="#EAE6E3" fill-rule="evenodd" d="M3.99948 1.2426c-1.32549 0-2.4 1.07452-2.4 2.4v6.34949c0 1.32551 1.07451 2.40001 2.4 2.40001H10.349c1.3254 0 2.4-1.0745 2.4-2.40001V7.81735c0-.33137.2686-.6.6-.6.3313 0 .6.26863.6.6v2.17474c0 1.98821-1.6118 3.60001-3.6 3.60001H3.99948c-1.98823 0-3.600005-1.6118-3.600005-3.60001V3.6426c0-1.98822 1.611775-3.5999975 3.600005-3.5999975h2.17474c.33137 0 .6.2686295.6.6000005 0 .33137-.26863.599997-.6.599997H3.99948Z" clip-rule="evenodd"/></svg>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>