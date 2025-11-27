# 本草平台 (Herbal Platform)
一个专注于中医药本草知识展示与交流的平台，包含本草信息查询、用户管理、分类浏览等功能，传播中医药文化与知识。
## 项目功能
- 本草信息展示：提供本草植物的详细介绍、功效、产地等信息
- 用户系统：支持注册、登录、个人信息管理，区分普通用户、专业学习者、医生和管理员角色
- 分类浏览：按药用、食疗、观赏等类别筛选本草
- 搜索功能：快速查找所需本草信息
- 管理员后台：管理用户账号及权限
- 验证码安全机制：登录、注册、个人信息修改时的验证码验证
## 效果
### index.php
![图片](images/127.0.0.1_8080_project_PHP_herbal_demo_index.php.png)
### login.php
![图片](images/127.0.0.1_8080_project_PHP_herbal_demo_login.php.png)
### herb_list.php
![图片](images/127.0.0.1_8080_project_PHP_herbal_demo_herb_list.php.png)
### user_profile.php
![图片](images/127.0.0.1_8080_project_PHP_herbal_demo_user_profile.php.png)
### herb_detail.php
![图片](images/127.0.0.1_8080_project_PHP_herbal_demo_herb_detail.php_id=9.png)
### community.php
![图片](images/127.0.0.1_8080_project_PHP_herbal_demo_community.php.png)
## 环境要求
- PHP 7.4+
- MySQL 5.7+
- Composer（用于依赖管理）
- 浏览器（如 Chrome、Firefox、Safari 等）
## 安装步骤
1. 克隆仓库
   ```bash
   git clone https://github.com/NgaiYeanCoi/herbal_platform.git
   ```
   
2. 安装依赖
   ```bash
   # 先安装依赖可以参考https://www.runoob.com/w3cnote/composer-install-and-usage.html
   # 参考验证码依赖https://github.com/lifei6671/php-captcha
   cd herbal_platform
   composer install
   ```
   
3. 配置数据库
  - 1. 在项目根目录下的 `config.php` 文件中配置数据库连接信息
    ```php
    $host = 'localhost';       // 数据库主机
    $dbname = 'herbal_platform'; // 数据库名（需提前创建）
    $username = 'root';        // 数据库用户名
    $password = '10086';       // 数据库密码（替换为你的实际密码）
    ```
   - 2. 数据库表会自动创建：
      - 用户表 users 在首次访问 register.php 时自动创建
  
4. 新建数据库
   - 在 MySQL 中新建一个数据库，`herbal_platform`、本草表 `herbs`
        ```sql
        -- 创建数据库并设置字符集
        CREATE DATABASE IF NOT EXISTS herbal_platform DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        USE herbal_platform;
        CREATE TABLE IF NOT EXISTS `herbs` (
                                            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '本草ID',
                                            `name` varchar(100) NOT NULL COMMENT '本草名称',
                                            `alias` varchar(200) DEFAULT '' COMMENT '别名',
                                            `category` varchar(50) NOT NULL COMMENT '类别（药用/食疗/观赏）',
                                            `origin` varchar(200) DEFAULT '' COMMENT '产地',
                                            `effect` text COMMENT '功效说明',
                                            `description` text COMMENT '简介',
                                            `food_recipe` text COMMENT '食疗配方（专业数据）',
                                            `property` varchar(200) DEFAULT '' COMMENT '性味归经（专业数据）',
                                            `attention` text COMMENT '注意事项（专业数据）',
                                            `image_url` varchar(500) DEFAULT '' COMMENT '图片URL',
                                            `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                                            PRIMARY KEY (`id`),
                                            KEY `idx_category` (`category`),
                                            KEY `idx_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='本草植物信息表';
        ```
5. 配置管理员账户
   - 初次访问 register.php 时，注册一个用户，即可创建管理员账户
## 主要文件说明
- index.php：首页，展示本草推荐、分类导航
- login.php/register.php：用户登录与注册
- admin.php：管理员后台，管理用户信息
- herb_list.php：本草列表页，支持分类与搜索
- herb_detail.php：本草详情页，展示详细信息
- captcha.php：验证码生成逻辑，依赖 lifei6671/php-captcha
- config.php：数据库配置文件
- logout.php：退出登录
- user_profile.php：用户个人信息页
- herb_edit.php：本草信息编辑页（管理员）
- community.php：用户社区
- base.php：基础布局文件，包含导航栏、页脚等

## 基于 XML 存储的小型应用（三层架构 + AJAX + XSL）
- 数据存储：`data/herbs.xml`（示例字段不少于 3 项：`id`、`code`、`name`、`price`、`category`、`stock` 等）
- 表示层：`herb_list.php`（页面不改样式，新增交互控件与占位元素）
- 业务逻辑层：`backend/Services/HerbService.php`（分页、排序、筛选、XPath 查询、增删改）
- 数据访问层：`backend/Data/XmlHerbRepository.php`（XML 读写、节点查找、持久化）
- 前端交互：`assets/js/herb-xml.js`（AJAX 模块，负责分页、查询、CRUD 与 XSL 渲染）
- XSL 视图：`xml/herbs-table.xsl`（将 XML 转换为表格，支持指定字段与顺序排序）

### 运行与使用
1. 启动本地环境（如 XAMPP），确保站点根指向项目目录。
2. 访问 `herb_list.php`：
   - 筛选区：设置关键词、类别、排序与每页条数，点击“筛选”后触发 AJAX 更新列表。
   - XSL 视图区：选择排序字段与方向，点击“刷新”以 XSLT 在前端渲染 XML 表格。
   - XPath 查询区：选择字段与模式（精确/模糊），输入关键词并“查询”，结果以 XML 片段展示。
   - 管理员可在卡片上执行“编辑/删除”，并可通过“新增本草”模态框添加记录。

### API 接口
- 列表分页：`GET api/herbs.php?page=1&pageSize=6&sortField=name&sortOrder=asc&keyword=&category=` 返回 JSON。
- 原始 XML：`GET api/herbs.php?action=xml` 返回 XML 原文（供 XSLT 使用）。
- XPath 查询：`GET api/herbs.php?action=search&field=name&mode=fuzzy&keyword=黄芪` 返回 XML 片段。
- 详情：`GET api/herbs.php?action=detail&id=H001` 返回单条 JSON。
- 新增：`POST api/herbs.php`（管理员）请求体 JSON。
- 更新：`PUT api/herbs.php?id=H001`（管理员）请求体 JSON。
- 删除：`DELETE api/herbs.php?id=H001`（管理员）。

### 权限与安全
- 写操作（新增/更新/删除）需要管理员权限：`backend/Api/herbs.php:134-141`。
- 业务层进行字段白名单与数值规范化：`backend/Services/HerbService.php:189-222`。
- XML 写入采用临时文件 + 原子替换以降低风险：`backend/Data/XmlHerbRepository.php:177-191`。

### 前端模块说明（assets/js/herb-xml.js）
- 初始化与事件绑定：完成筛选、查询、XSL刷新与 CRUD 的事件挂载。
- `loadPage()`：调用分页接口并渲染卡片与分页条。
- `loadXslTable()`：获取 XML 与 XSL，前端进行 XSLT 渲染，支持数值/文本排序。
- `handleAdd/handleUpdate/handleDelete`：管理员新增、更新、删除操作，成功后自动刷新列表。
- `handleSearch()`：触发 XPath 查询并在页面展示返回的 XML 片段。

### 与原有系统的关系
- 用户系统仍使用 MySQL；本草数据模块基于 XML 存储，两者并行不冲突。
- 页面样式与布局保持不变，仅新增交互逻辑与视图占位（如 XSL 视图与查询结果区域）。

### 测试建议
- 浏览：在筛选区变更条件，确认列表与分页更新正确。
- XSL：切换排序字段与升降序，验证表格顺序正确。
- 查询：分别测试精确与模糊查询，检查返回片段是否符合预期。
- CRUD（管理员）：新增/编辑/删除后，检查列表与 XSL 视图是否同步刷新。

# 许可证
本项目基于 MIT 许可证开源，你可以在遵守许可证条款的前提下自由使用、修改和分发本项目的代码。
