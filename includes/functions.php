<?php
require_once __DIR__ . '/../config/db.php';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_price($price): string {
    return number_format((float)$price, 0, ',', '.') . ' đ';
}

function slugify(string $value): string {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $replace = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d'
    ];
    $value = strtr($value, $replace);
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value);
    return trim($value ?? '', '-');
}

function is_absolute_url(string $value): bool {
    return (bool)preg_match('#^(https?:)?//#i', $value);
}

function resolve_media_url(?string $value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return 'https://placehold.co/800x900?text=No+Image';
    }

    if (is_absolute_url($value)) {
        return $value;
    }

    if ($value[0] === '/') {
        return $value;
    }

    return BASE_URL . '/' . ltrim($value, '/');
}

function unique_slug(string $table, string $name, ?int $ignoreId = null): string {
    $base = slugify($name);
    if ($base === '') {
        $base = 'item';
    }

    $slug = $base;
    $index = 1;

    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }

        $slug = $base . '-' . $index++;
    }
}

function insert_lookup_item(string $table, string $name, int $sortOrder = 0): void {
    $stmt = db()->prepare("INSERT INTO {$table} (name, slug, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$name, unique_slug($table, $name), $sortOrder]);
}

function get_categories(): array {
    return db()->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function get_styles(): array {
    return db()->query('SELECT * FROM styles ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function get_product_types(?int $categoryId = null): array {
    $sql = 'SELECT pt.*, c.name AS category_name
            FROM product_types pt
            INNER JOIN categories c ON c.id = pt.category_id';
    $params = [];

    if ($categoryId) {
        $sql .= ' WHERE pt.category_id = ?';
        $params[] = $categoryId;
    }

    $sql .= ' ORDER BY c.sort_order ASC, c.id ASC, pt.sort_order ASC, pt.id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function product_type_exists_for_category(int $productTypeId, int $categoryId): bool {
    $stmt = db()->prepare('SELECT id FROM product_types WHERE id = ? AND category_id = ? LIMIT 1');
    $stmt->execute([$productTypeId, $categoryId]);
    return (bool)$stmt->fetchColumn();
}

function insert_product_type(string $name, int $categoryId, int $sortOrder = 0): void {
    $stmt = db()->prepare('INSERT INTO product_types (category_id, name, slug, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$categoryId, $name, unique_slug('product_types', $name), $sortOrder]);
}

function get_product_conditions(): array {
    return db()->query('SELECT * FROM product_conditions ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function product_gender_options(): array {
    return ['Nam', 'Nữ', 'Unisex'];
}

function generate_product_code_from_number(int $number): string {
    return 'SP' . str_pad((string)$number, 5, '0', STR_PAD_LEFT);
}

function next_product_code_preview(): string {
    $nextId = (int)db()->query('SELECT COALESCE(MAX(id), 0) + 1 FROM products')->fetchColumn();
    return generate_product_code_from_number(max(1, $nextId));
}

function generate_unique_product_code(): string {
    $number = (int)db()->query('SELECT COALESCE(MAX(id), 0) + 1 FROM products')->fetchColumn();
    $number = max(1, $number);

    while (true) {
        $code = generate_product_code_from_number($number);
        $stmt = db()->prepare('SELECT id FROM products WHERE product_code = ? LIMIT 1');
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
        $number++;
    }
}

function get_products($filters = null, bool $onlyActive = true): array {
    if (!is_array($filters)) {
        $filters = [
            'category_id' => $filters ? (int)$filters : null,
            'type_id'     => null,
            'gender'      => null,
            'price_min'   => null,
            'price_max'   => null,
            'q'           => '',
        ];
    } else {
        $filters = array_merge([
            'category_id' => null,
            'type_id'     => null,
            'gender'      => null,
            'price_min'   => null,
            'price_max'   => null,
            'q'           => '',
        ], $filters);
    }

    $sql = 'SELECT p.*,
                c.name AS category_name,
                s.name AS style_name,
                pt.name AS product_type_name,
                (
                    SELECT GROUP_CONCAT(pc.name ORDER BY pc.sort_order ASC, pc.id ASC SEPARATOR ", ")
                    FROM product_condition_maps pcm
                    INNER JOIN product_conditions pc ON pc.id = pcm.condition_id
                    WHERE pcm.product_id = p.id
                ) AS condition_names
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN styles s ON s.id = p.style_id
            LEFT JOIN product_types pt ON pt.id = p.product_type_id
            WHERE 1 = 1';

    $params = [];

    if ($onlyActive) {
        $sql .= ' AND p.is_active = 1';
    }

    if (!empty($filters['category_id'])) {
        $sql .= ' AND p.category_id = ?';
        $params[] = (int)$filters['category_id'];
    }

    if (!empty($filters['type_id'])) {
        $sql .= ' AND p.product_type_id = ?';
        $params[] = (int)$filters['type_id'];
    }

    if (!empty($filters['gender'])) {
        $sql .= ' AND p.gender = ?';
        $params[] = trim($filters['gender']);
    }

    if ($filters['price_min'] !== null && $filters['price_min'] !== '') {
        $sql .= ' AND COALESCE(NULLIF(p.sale_price, 0), p.original_price) >= ?';
        $params[] = (float)$filters['price_min'];
    }

    if ($filters['price_max'] !== null && $filters['price_max'] !== '') {
        $sql .= ' AND COALESCE(NULLIF(p.sale_price, 0), p.original_price) <= ?';
        $params[] = (float)$filters['price_max'];
    }

    if (!empty($filters['q'])) {
        $keyword = '%' . trim($filters['q']) . '%';
        $sql .= ' AND (
                    p.product_name LIKE ?
                    OR p.product_code LIKE ?
                    OR c.name LIKE ?
                    OR pt.name LIKE ?
                    OR s.name LIKE ?
                 )';
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
    }

    $sql .= ' ORDER BY p.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_product(int $id): ?array {
    $stmt = db()->prepare('SELECT p.*,
            c.name AS category_name,
            s.name AS style_name,
            pt.name AS product_type_name,
            (
                SELECT GROUP_CONCAT(pc.name ORDER BY pc.sort_order ASC, pc.id ASC SEPARATOR ", ")
                FROM product_condition_maps pcm
                INNER JOIN product_conditions pc ON pc.id = pcm.condition_id
                WHERE pcm.product_id = p.id
            ) AS condition_names
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN styles s ON s.id = p.style_id
        LEFT JOIN product_types pt ON pt.id = p.product_type_id
        WHERE p.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    return $product ?: null;
}

function get_product_images(int $productId): array {
    $stmt = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function get_product_condition_ids(int $productId): array {
    $stmt = db()->prepare('SELECT condition_id FROM product_condition_maps WHERE product_id = ? ORDER BY sort_order ASC, condition_id ASC');
    $stmt->execute([$productId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function sync_product_conditions(int $productId, array $conditionIds): void {
    db()->prepare('DELETE FROM product_condition_maps WHERE product_id = ?')->execute([$productId]);

    $conditionIds = array_values(array_unique(array_filter(array_map('intval', $conditionIds), fn($id) => $id > 0)));
    $stmt = db()->prepare('INSERT INTO product_condition_maps (product_id, condition_id, sort_order) VALUES (?, ?, ?)');
    foreach ($conditionIds as $index => $conditionId) {
        $stmt->execute([$productId, $conditionId, $index + 1]);
    }
}

function replace_product_gallery(int $productId, array $imageUrls): void {
    db()->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
    $stmt = db()->prepare('INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)');
    foreach (array_values($imageUrls) as $index => $url) {
        $stmt->execute([$productId, $url, $index + 1]);
    }
}

function normalize_uploaded_files(?array $files): array {
    if (!$files || empty($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? null,
            'tmp_name' => $files['tmp_name'][$index] ?? null,
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function handle_image_upload(?array $file): ?string {
    if (!$file || !isset($file['tmp_name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return null;
    }

    $ext = $allowed[$mime];
    $name = uniqid('img_', true) . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $target = $targetDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return 'uploads/' . $name;
}

function handle_multiple_image_uploads(?array $files): array {
    $uploaded = [];
    foreach (normalize_uploaded_files($files) as $file) {
        $path = handle_image_upload($file);
        if ($path) {
            $uploaded[] = $path;
        }
    }
    return $uploaded;
}

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function admin_require_login(): void {
    if (!is_admin_logged_in()) {
        redirect('/admin/login.php');
    }
}