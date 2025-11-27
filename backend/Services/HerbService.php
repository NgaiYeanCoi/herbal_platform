<?php
declare(strict_types=1);

namespace HerbalPlatform\Services;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use HerbalPlatform\Data\XmlHerbRepository;
use RuntimeException;

class HerbService
{
    /**
     * @var array<string, bool>
     */
    private array $allowedFields = [
        'code' => true,
        'name' => true,
        'alias' => true,
        'category' => true,
        'price' => true,
        'origin' => true,
        'description' => true,
        'effect' => true,
        'stock' => true,
        'image_url' => true,
        'food_recipe' => true,
        'property' => true,
        'attention' => true,
        'created_at' => true,
    ];

    public function __construct(private XmlHerbRepository $repository)
    {
    }

    /**
     * @param array<string, string> $filters
     */
    public function paginate(int $page, int $pageSize, string $sortField, string $sortOrder, array $filters = []): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(50, $pageSize));

        if (!isset($this->allowedFields[$sortField])) {
            $sortField = 'name';
        }

        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        $items = $this->repository->all();
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));

        if ($keyword !== '') {
            $items = array_values(array_filter($items, static function (array $item) use ($keyword): bool {
                foreach (['name', 'alias', 'effect', 'description', 'origin', 'category'] as $field) {
                    if (!empty($item[$field]) && mb_stripos((string) $item[$field], $keyword) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        if ($category !== '') {
            $items = array_values(array_filter($items, static function (array $item) use ($category): bool {
                return isset($item['category']) && $item['category'] === $category;
            }));
        }
        usort($items, function (array $left, array $right) use ($sortField, $sortOrder): int {
            $a = $left[$sortField] ?? '';
            $b = $right[$sortField] ?? '';

            if ($sortField === 'price' || $sortField === 'stock') {
                $a = (float) $a;
                $b = (float) $b;
            } elseif ($sortField === 'created_at') {
                $a = strtotime((string) $a) ?: 0;
                $b = strtotime((string) $b) ?: 0;
            } else {
                $a = (string) $a;
                $b = (string) $b;
            }

            if ($a === $b) {
                return 0;
            }

            $result = $a <=> $b;
            return $sortOrder === 'asc' ? $result : -$result;
        });

        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $page = min($page, $totalPages);

        $offset = ($page - 1) * $pageSize;
        $data = array_slice($items, $offset, $pageSize);

        return [
            'items' => $data,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
        ];
    }

    public function detail(string $id): ?array
    {
        return $this->repository->find($id);
    }

    public function create(array $payload): array
    {
        $data = $this->normalizePayload($payload, true);
        $data['id'] = $data['id'] ?? $this->generateId();
        $data['created_at'] = $data['created_at'] ?? date(DATE_ATOM);
        $data['stock'] = $data['stock'] ?? '0';

        return $this->repository->add($data);
    }

    public function update(string $id, array $payload): array
    {
        $existing = $this->repository->find($id);
        if (!$existing) {
            throw new RuntimeException('指定的本草不存在。');
        }

        $data = $this->normalizePayload($payload + ['id' => $id]);
        $data['created_at'] = $data['created_at'] ?? ($existing['created_at'] ?? date(DATE_ATOM));

        return $this->repository->update($id, $data);
    }

    public function delete(string $id): void
    {
        $this->repository->delete($id);
    }

    public function rawXml(): string
    {
        $document = $this->repository->getDocument();
        $xml = $document->saveXML();

        if ($xml === false) {
            throw new RuntimeException('XML 序列化失败。');
        }

        return $xml;
    }

    public function search(string $keyword, string $field, bool $exact): string
    {
        $field = $this->sanitizeField($field);
        $document = $this->repository->getDocument();
        $xpath = new \DOMXPath($document);

        $expression = $this->buildXPathExpression($field, $keyword, $exact);
        $nodes = $xpath->query($expression);

        if (!$nodes instanceof DOMNodeList) {
            return '<herbs/>';
        }

        $subset = new DOMDocument('1.0', 'UTF-8');
        $root = $subset->createElement('herbs');
        $subset->appendChild($root);

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $root->appendChild($subset->importNode($node, true));
            }
        }

        $xml = $subset->saveXML();

        if ($xml === false) {
            throw new RuntimeException('无法生成查询 XML。');
        }

        return $xml;
    }

    private function normalizePayload(array $payload, bool $isCreate = false): array
    {
        $required = ['code', 'name', 'category', 'price'];
        foreach ($required as $field) {
            if ($isCreate && empty($payload[$field])) {
                throw new RuntimeException(sprintf('%s 字段不能为空。', $field));
            }
        }

        $normalized = [];
        foreach ($this->allowedFields as $field => $_) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = is_string($payload[$field]) ? trim($payload[$field]) : $payload[$field];

            if ($field === 'price') {
                $value = number_format((float) $value, 2, '.', '');
            }

            if ($field === 'stock') {
                $value = (string) max(0, (int) $value);
            }

            if ($field === 'created_at' && $value === '') {
                $value = date(DATE_ATOM);
            }

            $normalized[$field] = (string) $value;
        }

        return $normalized;
    }

    private function generateId(): string
    {
        return 'H' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function sanitizeField(string $field): string
    {
        return isset($this->allowedFields[$field]) ? $field : 'name';
    }

    private function buildXPathExpression(string $field, string $keyword, bool $exact): string
    {
        $escapedKeyword = $this->escapeForXPath($keyword);
        $node = sprintf('%s', $field);

        if ($exact) {
            return sprintf("//herb[%s=%s]", $node, $escapedKeyword);
        }

        return sprintf("//herb[contains(%s,%s)]", $node, $escapedKeyword);
    }

    private function escapeForXPath(string $value): string
    {
        if (!str_contains($value, "'")) {
            return sprintf("'%s'", $value);
        }

        $parts = explode("'", $value);
        $escaped = array_map(static fn ($part) => sprintf("'%s'", $part), $parts);
        return 'concat(' . implode(", \"'\", ", $escaped) . ')';
    }
}

