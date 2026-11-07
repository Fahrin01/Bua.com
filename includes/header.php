<?php
// Expects optional $pageTitle to be set before include
$pageTitle = $pageTitle ?? 'BUA.COM - Find. Verify. Hire.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle) ?></title>
<link rel="icon" href="<?= baseUrl() ?>/assets/images/favicon.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= baseUrl() ?>/assets/css/style.css">
</head>
<body>
