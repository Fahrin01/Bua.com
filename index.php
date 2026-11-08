<?php
require_once __DIR__ . '/config/functions.php';
$db = Database::getConnection();

$areas = $db->query('SELECT * FROM areas WHERE status = "active" ORDER BY area_name LIMIT 8')->fetchAll();
$services = $db->query('SELECT * FROM services WHERE status = "active" ORDER BY service_name')->fetchAll();

// Featured verified & available maids
$featuredMaids = $db->query('
    SELECT m.*, a.area_name, v.verification_status
    FROM maids m
    JOIN areas a ON a.id = m.area_id
    JOIN maid_verification v ON v.maid_id = m.id
    WHERE v.verification_status = "VERIFIED" AND m.status = "active" AND m.availability = "available"
    ORDER BY m.created_at DESC LIMIT 6
')->fetchAll();

$reviews = $db->query('
    SELECT r.*, u.name AS customer_name, m.name AS maid_name
    FROM reviews r
    JOIN users u ON u.id = r.customer_id
    JOIN maids m ON m.id = r.maid_id
    WHERE r.status = "visible"
    ORDER BY r.created_at DESC LIMIT 3
')->fetchAll();

$serviceIcons = [
    'House Cleaning' => 'fa-broom',
    'Cooking' => 'fa-utensils',
    'Baby Care' => 'fa-baby',
    'Elderly Care' => 'fa-person-cane',
    'Washing & Ironing' => 'fa-shirt',
    'Full Household Management' => 'fa-house-chimney',
];

$pageTitle = 'BUA.COM - Find. Verify. Hire.';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<!-- HERO -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="hero-title">Find a Trusted <span class="accent">Maid</span> Near You</h1>
                <p class="hero-sub">BUA.COM connects you with verified household help through trained area agents — no guesswork, no unsafe hiring. We search, verify, and you hire with confidence.</p>
                <div class="hero-badge-strip"><i class="fa-solid fa-shield-halved"></i> Every profile checked by an area agent &amp; admin</div>
            </div>
        </div>
    </div>
</section>

<!-- SEARCH CARD -->
<div class="container">
    <div class="search-card">
        <form action="<?= baseUrl() ?>/customer/search-maids.php" method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Area</label>
                <select name="area" class="form-select">
                    <option value="">Any Area</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= (int) $a['id'] ?>"><?= clean($a['area_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Service</label>
                <select name="service" class="form-select">
                    <option value="">Any Service</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= clean($s['service_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-brand w-100 py-2"><i class="fa-solid fa-magnifying-glass"></i> Search Maid</button>
            </div>
        </form>
    </div>
</div>

<!-- POPULAR AREAS -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Popular Areas</h2>
        <p class="section-sub">Verified maids available across Dhaka's most requested neighborhoods.</p>
        <div class="row g-3">
            <?php foreach ($areas as $a): ?>
                <div class="col-6 col-md-3">
                    <a class="area-chip" href="<?= baseUrl() ?>/customer/search-maids.php?area=<?= (int) $a['id'] ?>">
                        <i class="fa-solid fa-location-dot"></i>
                        <?= clean($a['area_name']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SERVICES -->
<section class="section" style="background:#e3f4fb;">
    <div class="container">
        <h2 class="section-title">Our Services</h2>
        <p class="section-sub">Choose the kind of help your household needs.</p>
        <div class="row g-3">
            <?php foreach ($services as $s): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a class="service-chip" href="<?= baseUrl() ?>/customer/search-maids.php?service=<?= (int) $s['id'] ?>">
                        <i class="fa-solid <?= $serviceIcons[$s['service_name']] ?? 'fa-hands-holding' ?>"></i>
                        <?= clean($s['service_name']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section">
    <div class="container">
        <h2 class="section-title text-center">How It Works</h2>
        <p class="section-sub text-center">Simple, safe, and fully managed by our team.</p>
        <div class="row">
            <div class="col-md-3 how-step">
                <div class="step-num">1</div>
                <h5>Search</h5>
                <p class="text-muted">Filter by area, service, salary and experience.</p>
            </div>
            <div class="col-md-3 how-step">
                <div class="step-num">2</div>
                <h5>Choose</h5>
                <p class="text-muted">Review verified profiles, ratings and history.</p>
            </div>
            <div class="col-md-3 how-step">
                <div class="step-num">3</div>
                <h5>Request</h5>
                <p class="text-muted">Submit a hiring request with your details.</p>
            </div>
            <div class="col-md-3 how-step">
                <div class="step-num">4</div>
                <h5>We Contact</h5>
                <p class="text-muted">Our admin calls the maid and confirms hiring.</p>
            </div>
        </div>
    </div>
</section>

<!-- FEATURED VERIFIED MAIDS -->
<section class="section" style="background:#e3f4fb;">
    <div class="container">
        <h2 class="section-title">Featured Verified Maids</h2>
        <p class="section-sub">Ready to work, fully verified by our agents and admin team.</p>
        <div class="row g-4">
            <?php foreach ($featuredMaids as $m):
                $rating = getMaidAverageRating((int) $m['id']); ?>
                <div class="col-md-4">
                    <div class="maid-card">
                        <img src="<?= maidPhotoUrl($m['photo']) ?>" class="maid-photo" alt="<?= clean($m['name']) ?>">
                        <div class="maid-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="maid-card-name"><?= clean($m['name']) ?></div>
                                    <div class="maid-card-area"><i class="fa-solid fa-location-dot"></i> <?= clean($m['area_name']) ?></div>
                                </div>
                                <?= verificationBadge($m['verification_status']) ?>
                            </div>
                            <div class="review-stars small"><?= starRatingHtml($rating['average']) ?> <span class="text-muted">(<?= $rating['total'] ?>)</span></div>
                            <div class="maid-meta-row">
                                <span><i class="fa-solid fa-briefcase"></i> <?= (int) $m['experience_years'] ?> yrs exp</span>
                                <span><?= formatMoney((float) $m['expected_salary']) ?>/mo</span>
                            </div>
                            <a href="<?= baseUrl() ?>/customer/maid-profile.php?id=<?= (int) $m['id'] ?>" class="btn btn-brand btn-sm w-100 mt-3">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- REVIEWS -->
<?php if (!empty($reviews)): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title">What Customers Say</h2>
        <p class="section-sub">Real feedback from families who hired through BUA.COM.</p>
        <div class="row g-4">
            <?php foreach ($reviews as $r): ?>
                <div class="col-md-4">
                    <div class="review-card">
                        <div class="review-stars"><?= starRatingHtml((float) $r['overall_rating']) ?></div>
                        <p class="fst-italic">"<?= clean($r['comment']) ?>"</p>
                        <p class="fw-semibold mb-0"><?= clean($r['customer_name']) ?></p>
                        <p class="text-muted small">Hired: <?= clean($r['maid_name']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- WHY CHOOSE US -->
<section class="section" style="background:#e3f4fb;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title">Why Choose BUA.COM</h2>
                <div class="why-item">
                    <div class="why-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <h5>Agent-Verified Profiles</h5>
                        <p class="text-muted mb-0">Every maid is personally registered and checked by a local area agent.</p>
                    </div>
                </div>
                <div class="why-item">
                    <div class="why-icon"><i class="fa-solid fa-phone-volume"></i></div>
                    <div>
                        <h5>We Handle the Calls</h5>
                        <p class="text-muted mb-0">No smartphone needed for maids — our admin team manages contact directly.</p>
                    </div>
                </div>
                <div class="why-item">
                    <div class="why-icon"><i class="fa-solid fa-star"></i></div>
                    <div>
                        <h5>Real Ratings &amp; Reviews</h5>
                        <p class="text-muted mb-0">Transparent feedback from real completed hires.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
