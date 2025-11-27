<?php
declare(strict_types=1);

use HerbalPlatform\Data\XmlHerbRepository;
use HerbalPlatform\Services\HerbService;

require __DIR__ . '/../bootstrap.php';
// API入口：基于XML存储的本草数据服务
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$repository = new XmlHerbRepository(HERBAL_XML_PATH);
$service = new HerbService($repository);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'GET':
            // 浏览、分页、详情、XML原文、XPath查询
            handleGet($service);
            break;
        case 'POST':
            // 新增（管理员权限）
            requireAdmin();
            handlePost($service);
            break;
        case 'PUT':
            // 更新（管理员权限）
            requireAdmin();
            handlePut($service);
            break;
        case 'DELETE':
            // 删除（管理员权限）
            requireAdmin();
            handleDelete($service);
            break;
        case 'OPTIONS':
            respondNoContent();
            break;
        default:
            respondJson(['message' => 'Method Not Allowed'], 405);
    }
} catch (Throwable $exception) {
    respondJson(['message' => $exception->getMessage()], 400);
}

function handleGet(HerbService $service): void
{
    $action = $_GET['action'] ?? 'list';

    if ($action === 'xml') {
        // 返回后端XML原文，用于前端XSLT渲染
        header('Content-Type: application/xml; charset=utf-8');
        echo $service->rawXml();
        return;
    }

    if ($action === 'search') {
        // XPath精确/模糊查询，返回XML子集
        $keyword = trim($_GET['keyword'] ?? '');
        if ($keyword === '') {
            respondJson(['message' => 'keyword 参数不能为空'], 422);
            return;
        }

        $field = $_GET['field'] ?? 'name';
        $mode = strtolower($_GET['mode'] ?? 'fuzzy');
        $exact = $mode === 'exact';

        header('Content-Type: application/xml; charset=utf-8');
        echo $service->search($keyword, $field, $exact);
        return;
    }

    if ($action === 'detail') {
        // 返回单条记录JSON
        $id = $_GET['id'] ?? '';
        $record = $service->detail($id);
        if (!$record) {
            respondJson(['message' => '记录不存在'], 404);
            return;
        }

        respondJson($record);
        return;
    }

    $page = (int) ($_GET['page'] ?? 1);
    $pageSize = (int) ($_GET['pageSize'] ?? 5);
    $sortField = $_GET['sortField'] ?? 'name';
    $sortOrder = $_GET['sortOrder'] ?? 'asc';
    $keyword = trim((string) ($_GET['keyword'] ?? ''));
    $category = trim((string) ($_GET['category'] ?? ''));

    // 分页与筛选结果JSON
    respondJson($service->paginate(
        $page,
        $pageSize,
        $sortField,
        $sortOrder,
        [
            'keyword' => $keyword,
            'category' => $category,
        ]
    ));
}

function handlePost(HerbService $service): void
{
    // 新增节点
    $payload = readJsonBody();
    $created = $service->create($payload);
    respondJson($created, 201);
}

function handlePut(HerbService $service): void
{
    // 更新节点
    $payload = readJsonBody();
    $id = $_GET['id'] ?? $payload['id'] ?? '';

    if ($id === '') {
        respondJson(['message' => '缺少 id 参数'], 422);
        return;
    }

    $updated = $service->update($id, $payload);
    respondJson($updated);
}

function handleDelete(HerbService $service): void
{
    // 删除节点
    $id = $_GET['id'] ?? '';
    if ($id === '') {
        respondJson(['message' => '缺少 id 参数'], 422);
        return;
    }

    $service->delete($id);
    respondNoContent();
}

function requireAdmin(): void
{
    // 简易权限校验：仅管理员可执行写操作
    $user = $_SESSION['user'] ?? null;
    if (!$user || !is_array($user) || ($user['user_type'] ?? '') !== 'admin') {
        respondJson(['message' => '需要管理员权限'], 403);
        exit;
    }
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '[]', true);

    if (!is_array($decoded)) {
        throw new RuntimeException('请求体不是合法 JSON。');
    }

    return $decoded;
}

function respondJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function respondNoContent(): void
{
    http_response_code(204);
    header('Content-Type: application/json; charset=utf-8');
}

