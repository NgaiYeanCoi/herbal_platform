<body>
<!-- 搜索区 -->
<div class="hero text-center" style=" padding: 4rem 0;">
    <div class="container">
        <h1>探索中医药本草的智慧</h1>
        <p class="lead mt-3">查询植物功效、食疗配方、专业数据</p>
        <form action="herb_list.php" method="get" class="mt-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="keyword" class="form-control" placeholder="输入植物名称、功效..." required>
                        <button class="btn btn-success" type="submit">搜索</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include 'base.php';