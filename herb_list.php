<?php
session_start();
include 'config.php';

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$initialFilters = [
    'keyword' => isset($_GET['keyword']) ? trim($_GET['keyword']) : '',
    'category' => isset($_GET['category']) ? trim($_GET['category']) : '',
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'time_desc',
    'page' => isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1,
    'pageSize' => isset($_GET['pageSize']) ? max(1, min(20, (int) $_GET['pageSize'])) : 6,
];

$typeToCategory = [
    'professional' => '药用',
    'doctor' => '药用',
    'public' => '食疗',
    'culture' => '观赏',
];

if ($initialFilters['category'] === '' && $type !== '' && isset($typeToCategory[$type])) {
    $initialFilters['category'] = $typeToCategory[$type];
}

$initialFilters['type'] = $type;
$isAdmin = isset($_SESSION['user']) && $_SESSION['user']['user_type'] === 'admin';

$frontendConfig = [
    'apiBase' => 'api/herbs.php',
    'xslPath' => 'xml/herbs-table.xsl',
    'isAdmin' => $isAdmin,
    'initialFilters' => $initialFilters,
];

ob_start();
?>
<div class="container mt-5" id="herbXmlApp" data-config="<?php echo htmlspecialchars(json_encode($frontendConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="mb-1">本草列表</h2>
            <p class="text-muted mb-0">基于 XML 存储 + AJAX 的数据浏览与维护</p>
        </div>
        <?php if ($isAdmin): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHerbModal">
                <i class="bi bi-plus-circle me-1"></i> 新增本草
            </button>
        <?php endif; ?>
    </div>

    <div id="alertPlaceholder" class="mt-3"></div>

    <form id="filterForm" class="row g-3 align-items-end mt-1">
        <?php if ($type !== ''): ?>
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>
        <div class="col-md-4">
            <label for="keywordInput" class="form-label">关键词</label>
            <input type="text" id="keywordInput" name="keyword" class="form-control" placeholder="输入植物名称、功效..." value="<?php echo htmlspecialchars($initialFilters['keyword'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-3">
            <label for="categorySelect" class="form-label">类别</label>
            <select id="categorySelect" name="category" class="form-select">
                <option value="">全部类别</option>
                <option value="药用" <?php echo $initialFilters['category'] === '药用' ? 'selected' : ''; ?>>药用</option>
                <option value="食疗" <?php echo $initialFilters['category'] === '食疗' ? 'selected' : ''; ?>>食疗</option>
                <option value="观赏" <?php echo $initialFilters['category'] === '观赏' ? 'selected' : ''; ?>>观赏</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="sortSelect" class="form-label">排序</label>
            <select id="sortSelect" name="sort" class="form-select">
                <option value="time_desc" <?php echo $initialFilters['sort'] === 'time_desc' ? 'selected' : ''; ?>>最新发布</option>
                <option value="time_asc" <?php echo $initialFilters['sort'] === 'time_asc' ? 'selected' : ''; ?>>最早发布</option>
                <option value="name_asc" <?php echo $initialFilters['sort'] === 'name_asc' ? 'selected' : ''; ?>>名称升序</option>
                <option value="name_desc" <?php echo $initialFilters['sort'] === 'name_desc' ? 'selected' : ''; ?>>名称降序</option>
            </select>
        </div>
        <div class="col-md-1">
            <label for="pageSizeInput" class="form-label">每页</label>
            <input type="number" id="pageSizeInput" name="pageSize" class="form-control" min="1" max="20" value="<?php echo (int) $initialFilters['pageSize']; ?>">
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-success">筛选</button>
        </div>
    </form>

    <div class="mt-2 text-muted" id="filterSummary"></div>

    <div class="card mt-4">
        <div class="card-header bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-0">XSL Table 视图</h5>
                    <small class="text-muted">直接读取 XML 并按指定字段排序浏览</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <select id="xslSortField" class="form-select form-select-sm">
                        <option value="price" data-type="numeric">价格</option>
                        <option value="name" data-type="text">名称</option>
                        <option value="category" data-type="text">类别</option>
                        <option value="stock" data-type="numeric">库存</option>
                    </select>
                    <select id="xslSortOrder" class="form-select form-select-sm">
                        <option value="ascending">升序</option>
                        <option value="descending">降序</option>
                    </select>
                    <button class="btn btn-outline-success btn-sm" id="refreshXslBtn">刷新</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="xslTableContainer" class="table-responsive text-center text-muted">
                正在加载 XML 视图...
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">XPath 精准/模糊查询</h5>
        </div>
        <div class="card-body">
            <form id="searchForm" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">字段</label>
                    <select name="field" class="form-select">
                        <option value="name">name</option>
                        <option value="alias">alias</option>
                        <option value="category">category</option>
                        <option value="origin">origin</option>
                        <option value="effect">effect</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">模式</label>
                    <select name="mode" class="form-select">
                        <option value="fuzzy">模糊</option>
                        <option value="exact">精确</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">关键词</label>
                    <input type="text" name="keyword" class="form-control" required>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-outline-primary">查询</button>
                </div>
            </form>
            <pre id="searchResult" class="bg-dark text-white rounded mt-3 p-3 small" style="min-height: 140px; max-height: 300px; overflow: auto;"></pre>
        </div>
    </div>

    <div class="row mt-4" id="herbCards"></div>
    <div class="text-center py-5 text-muted d-none" id="emptyState">
        未找到相关本草，请调整筛选条件。
    </div>

    <nav class="mt-4 d-none" id="paginationNav">
        <ul class="pagination justify-content-center" id="paginationList"></ul>
    </nav>
</div>

<?php if ($isAdmin): ?>
    <!-- 新增 -->
    <div class="modal fade" id="addHerbModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新增本草</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addHerbForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">内部编码</label>
                                <input type="text" name="code" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">名称</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">别名</label>
                                <input type="text" name="alias" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">类别</label>
                                <select name="category" class="form-select" required>
                                    <option value="">请选择</option>
                                    <option value="药用">药用</option>
                                    <option value="食疗">食疗</option>
                                    <option value="观赏">观赏</option>
                                    <option value="滋补">滋补</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">产地</label>
                                <input type="text" name="origin" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">参考价格 (CNY)</label>
                                <input type="number" step="0.01" name="price" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">库存</label>
                                <input type="number" name="stock" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">图片 URL</label>
                                <input type="text" name="image_url" class="form-control" placeholder="https://">
                            </div>
                            <div class="col-12">
                                <label class="form-label">功效</label>
                                <textarea name="effect" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">简介</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 更新 -->
    <div class="modal fade" id="updateHerbModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑本草</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="updateHerbForm">
                    <input type="hidden" name="id" id="updateId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">内部编码</label>
                                <input type="text" name="code" id="updateCode" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">名称</label>
                                <input type="text" name="name" id="updateName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">别名</label>
                                <input type="text" name="alias" id="updateAlias" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">类别</label>
                                <select name="category" id="updateCategory" class="form-select" required>
                                    <option value="药用">药用</option>
                                    <option value="食疗">食疗</option>
                                    <option value="观赏">观赏</option>
                                    <option value="滋补">滋补</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">产地</label>
                                <input type="text" name="origin" id="updateOrigin" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">参考价格 (CNY)</label>
                                <input type="number" step="0.01" name="price" id="updatePrice" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">库存</label>
                                <input type="number" name="stock" id="updateStock" class="form-control" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">图片 URL</label>
                                <input type="text" name="image_url" id="updateImage" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">功效</label>
                                <textarea name="effect" id="updateEffect" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">简介</label>
                                <textarea name="description" id="updateDescription" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 删除 -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    确认删除 <strong id="deleteHerbName"></strong> ?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" id="deleteConfirmBtn">删除</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
window.HERB_XML_CONFIG = <?php echo json_encode($frontendConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script type="module" src="assets/js/herb-xml.js"></script>
<?php
$pageContent = ob_get_clean();
include 'base.php';

