<?php
require_once __DIR__ . '/../config/functions.php';
requireCustomer();
$db = Database::getConnection();
$uid = currentUserId();

$areas = $db->query('SELECT * FROM areas WHERE status="active" ORDER BY area_name')->fetchAll();
$services = $db->query('SELECT * FROM services WHERE status="active" ORDER BY service_name')->fetchAll();

// ---- Read filters ----
$areaId = isset($_GET['area']) && $_GET['area'] !== '' ? (int) $_GET['area'] : null;
$serviceId = isset($_GET['service']) && $_GET['service'] !== '' ? (int) $_GET['service'] : null;
$minSalary = isset($_GET['min_salary']) && $_GET['min_salary'] !== '' ? (float) $_GET['min_salary'] : null;
$maxSalary = isset($_GET['max_salary']) && $_GET['max_salary'] !== '' ? (float) $_GET['max_salary'] : null;
$minExp = isset($_GET['min_exp']) && $_GET['min_exp'] !== '' ? (int) $_GET['min_exp'] : null;
$minRating = isset($_GET['min_rating']) && $_GET['min_rating'] !== '' ? (float) $_GET['min_rating'] : null;
$availability = $_GET['availability'] ?? '';
$workingType = $_GET['working_type'] ?? '';
$verifiedOnly = isset($_GET['verified_only']) && $_GET['verified_only'] === '1';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 9;

// ---- Build query ----
$where = ['m.status = "active"'];
$params = [];

if ($areaId) { $where[] = 'm.area_id = ?'; $params[] = $areaId; }
if ($minSalary !== null) { $where[] = 'm.expected_salary >= ?'; $params[] = $minSalary; }
if ($maxSalary !== null) { $where[] = 'm.expected_salary <= ?'; $params[] = $maxSalary; }
if ($minExp !== null) { $where[] = 'm.experience_years >= ?'; $params[] = $minExp; }
if ($availability !== '') { $where[] = 'm.availability = ?'; $params[] = $availability; }
if ($workingType !== '') { $where[] = 'm.working_type = ?'; $params[] = $workingType; }
if ($verifiedOnly) { $where[] = 'v.verification_status = "VERIFIED"'; }
if ($serviceId) { $where[] = 'EXISTS (SELECT 1 FROM maid_services ms WHERE ms.maid_id = m.id AND ms.service_id = ?)'; $params[] = $serviceId; }

$havingRating = '';
if ($minRating !== null) {
    $havingRating = 'HAVING avg_rating >= ' . (float) $minRating;
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM (
    SELECT m.id,
        (SELECT AVG(overall_rating) FROM reviews WHERE maid_id = m.id AND status='visible') AS avg_rating
    FROM maids m
    JOIN maid_verification v ON v.maid_id = m.id
    WHERE $whereSql
    GROUP BY m.id
    $havingRating
) t";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRows = (int) $stmt->fetchColumn();

$pg = paginate($totalRows, $perPage, $page);

$sql = "SELECT m.*, a.area_name, v.verification_status,
        (SELECT AVG(overall_rating) FROM reviews WHERE maid_id = m.id AND status='visible') AS avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE maid_id = m.id AND status='visible') AS review_count
    FROM maids m
    JOIN areas a ON a.id = m.area_id
    JOIN maid_verification v ON v.maid_id = m.id
    WHERE $whereSql
    GROUP BY m.id
    $havingRating
    ORDER BY (v.verification_status='VERIFIED') DESC, m.created_at DESC
    LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$maids = $stmt->fetchAll();

// Favorites of this customer for quick heart toggle state
$favStmt = $db->prepare('SELECT maid_id FROM favorites WHERE customer_id = ?');
$favStmt->execute([$uid]);
$favIds = array_column($favStmt->fetchAll(), 'maid_id');

$activePage = 'search';
$pageTitle = 'Find a Maid - BUA.COM';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-topbar"><h1>Find a Maid</h1></div>
        <?php include __DIR__ . '/../includes/alerts.php'; ?>

        <div class="row g-4">
            <div class="col-lg-3">
                <form method="get" class="filter-panel">
                    <h6 class="fw-bold mb-3">Filters</h6>

                    <label class="form-label small fw-semibold">Area</label>
                    <select name="area" class="form-select form-select-sm mb-3">
                        <option value="">Any</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= (int) $a['id'] ?>" <?= $areaId == $a['id'] ? 'selected' : '' ?>><?= clean($a['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label small fw-semibold">Service</label>
                    <select name="service" class="form-select form-select-sm mb-3">
                        <option value="">Any</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= $serviceId == $s['id'] ? 'selected' : '' ?>><?= clean($s['service_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label small fw-semibold">Working Type</label>
                    <select name="working_type" class="form-select form-select-sm mb-3">
                        <option value="">Any</option>
                        <?php foreach (['full_time'=>'Full Time','part_time'=>'Part Time','live_in'=>'Live-in','hourly'=>'Hourly'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= $workingType === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label small fw-semibold">Availability</label>
                    <select name="availability" class="form-select form-select-sm mb-3">
                        <option value="">Any</option>
                        <?php foreach (['available'=>'Available','busy'=>'Busy','unavailable'=>'Unavailable'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= $availability === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label small fw-semibold">Salary Range (৳)</label>
                    <div class="d-flex gap-2 mb-3">
                        <input type="number" name="min_salary" class="form-control form-control-sm" placeholder="Min" value="<?= clean($_GET['min_salary'] ?? '') ?>">
                        <input type="number" name="max_salary" class="form-control form-control-sm" placeholder="Max" value="<?= clean($_GET['max_salary'] ?? '') ?>">
                    </div>

                    <label class="form-label small fw-semibold">Min Experience (years)</label>
                    <input type="number" name="min_exp" class="form-control form-control-sm mb-3" value="<?= clean($_GET['min_exp'] ?? '') ?>">

                    <label class="form-label small fw-semibold">Min Rating</label>
                    <select name="min_rating" class="form-select form-select-sm mb-3">
                        <option value="">Any</option>
                        <?php foreach ([4=>'4+', 3=>'3+', 2=>'2+'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= (isset($_GET['min_rating']) && $_GET['min_rating'] == $k) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="verified_only" value="1" id="verifiedOnly" <?= $verifiedOnly ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="verifiedOnly">Verified Only</label>
                    </div>

                    <button type="submit" class="btn btn-brand w-100 btn-sm">Apply Filters</button>
                    <a href="search-maids.php" class="btn btn-outline-secondary w-100 btn-sm mt-2">Clear</a>
                </form>
            </div>

            <div class="col-lg-9">
                <p class="text-muted"><?= $totalRows ?> maid(s) found</p>
                <?php if (empty($maids)): ?>
                    <div class="panel text-center text-muted">No maids match your filters. Try adjusting them.</div>
                <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($maids as $m):
                        $avg = $m['avg_rating'] ? round((float) $m['avg_rating'], 1) : 0.0;
                        $isFav = in_array($m['id'], $favIds); ?>
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
                                    <div class="review-stars small"><?= starRatingHtml($avg) ?> <span class="text-muted">(<?= (int) $m['review_count'] ?>)</span></div>
                                    <div class="maid-meta-row">
                                        <span><i class="fa-solid fa-briefcase"></i> <?= (int) $m['experience_years'] ?> yrs</span>
                                        <span><?= formatMoney((float) $m['expected_salary']) ?>/mo</span>
                                    </div>
                                    <div class="maid-meta-row">
                                        <span class="badge bg-light text-dark border"><?= ucwords(str_replace('_',' ',$m['working_type'])) ?></span>
                                        <span class="badge bg-light text-dark border"><?= ucfirst($m['availability']) ?></span>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <a href="maid-profile.php?id=<?= (int) $m['id'] ?>" class="btn btn-brand btn-sm flex-grow-1">View Profile</a>
                                        <form method="post" action="favorites.php">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="maid_id" value="<?= (int) $m['id'] ?>">
                                            <input type="hidden" name="action" value="<?= $isFav ? 'remove' : 'add' ?>">
                                            <button type="submit" class="fav-btn <?= $isFav ? 'active' : '' ?>"><i class="fa-<?= $isFav ? 'solid' : 'regular' ?> fa-heart"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4">
                    <?php
                    $qs = $_GET; unset($qs['page']);
                    $baseQuery = 'search-maids.php?' . http_build_query($qs);
                    echo renderPagination($pg['currentPage'], $pg['totalPages'], $baseQuery);
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
