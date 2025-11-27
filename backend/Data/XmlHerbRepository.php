<?php
declare(strict_types=1);

namespace HerbalPlatform\Data;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

class XmlHerbRepository
{
    private string $xmlPath;

    public function __construct(string $xmlPath)
    {
        $this->xmlPath = $xmlPath;
        $this->ensureStorage();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function all(): array
    {
        $document = $this->loadDocument();
        $items = [];

        foreach ($document->getElementsByTagName('herb') as $element) {
            if ($element instanceof DOMElement) {
                $items[] = $this->elementToArray($element);
            }
        }

        return $items;
    }

    public function find(string $id): ?array
    {
        $element = $this->findElement($id);
        return $element ? $this->elementToArray($element) : null;
    }

    public function add(array $herb): array
    {
        $document = $this->loadDocument();
        $root = $document->documentElement;

        if (!$root instanceof DOMElement) {
            throw new RuntimeException('无法找到根元素 herb。');
        }

        if ($this->findElement($herb['id'])) {
            throw new RuntimeException('该 ID 已存在。');
        }

        $root->appendChild($this->buildHerbElement($document, $herb));
        $root->setAttribute('generatedAt', date(DATE_ATOM));

        $this->saveDocument($document);

        return $herb;
    }

    public function update(string $id, array $payload): array
    {
        $document = $this->loadDocument();
        $element = $this->findElement($id, $document);

        if (!$element instanceof DOMElement) {
            throw new RuntimeException('要更新的节点不存在。');
        }

        foreach ($payload as $field => $value) {
            if ($field === 'id') {
                continue;
            }

            $child = null;
            foreach ($element->childNodes as $node) {
                if ($node instanceof DOMElement && $node->tagName === $field) {
                    $child = $node;
                    break;
                }
            }

            if (!$child instanceof DOMElement) {
                $child = $element->ownerDocument?->createElement($field);
                if ($child) {
                    $element->appendChild($child);
                }
            }

            if ($child instanceof DOMElement) {
                while ($child->firstChild) {
                    $child->removeChild($child->firstChild);
                }
                $child->appendChild($element->ownerDocument?->createTextNode((string) $value));
                if ($field === 'price') {
                    $child->setAttribute('unit', 'CNY');
                }
            }
        }

        $element->ownerDocument?->documentElement?->setAttribute('generatedAt', date(DATE_ATOM));
        $this->saveDocument($document);

        return $this->elementToArray($element);
    }

    public function delete(string $id): void
    {
        $document = $this->loadDocument();
        $element = $this->findElement($id, $document);

        if (!$element instanceof DOMElement) {
            throw new RuntimeException('要删除的节点不存在。');
        }

        $element->parentNode?->removeChild($element);
        $document->documentElement?->setAttribute('generatedAt', date(DATE_ATOM));

        $this->saveDocument($document);
    }

    public function getDocument(): DOMDocument
    {
        return $this->loadDocument();
    }

    /**
     * @param iterable<DOMElement> $elements
     */
    public function toDocument(iterable $elements): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('herbs');
        $document->appendChild($root);

        foreach ($elements as $element) {
            $import = $document->importNode($element, true);
            $root->appendChild($import);
        }

        return $document;
    }

    private function ensureStorage(): void
    {
        $directory = dirname($this->xmlPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!file_exists($this->xmlPath)) {
            $document = new DOMDocument('1.0', 'UTF-8');
            $root = $document->createElement('herbs');
            $root->setAttribute('generatedAt', date(DATE_ATOM));
            $document->appendChild($root);
            $this->saveDocument($document);
        }
    }

    private function loadDocument(): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        if (!$document->load($this->xmlPath)) {
            throw new RuntimeException('无法读取 XML 数据文件。');
        }

        return $document;
    }

    private function saveDocument(DOMDocument $document): void
    {
        $document->formatOutput = true;
        $xml = $document->saveXML();
        if ($xml === false) {
            throw new RuntimeException('XML 序列化失败。');
        }

        $tmpPath = $this->xmlPath . '.tmp';
        if (file_put_contents($tmpPath, $xml, LOCK_EX) === false) {
            throw new RuntimeException('无法写入临时 XML 文件。');
        }

        rename($tmpPath, $this->xmlPath);
    }

    private function findElement(string $id, ?DOMDocument $document = null): ?DOMElement
    {
        $document ??= $this->loadDocument();
        $xpath = new DOMXPath($document);
        $expression = sprintf("//herb[@id=%s]", $this->escapeForXPathLiteral($id));
        $node = $xpath->query($expression);

        if ($node === false) {
            return null;
        }

        return $node->item(0) instanceof DOMElement ? $node->item(0) : null;
    }

    private function escapeForXPathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return sprintf("'%s'", $value);
        }

        $segments = explode("'", $value);
        $wrapped = array_map(static fn (string $segment): string => sprintf("'%s'", $segment), $segments);

        return 'concat(' . implode(", \"'\", ", $wrapped) . ')';
    }

    private function elementToArray(DOMElement $element): array
    {
        $data = ['id' => $element->getAttribute('id')];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $data[$child->tagName] = trim($child->textContent);
            }
        }

        return $data;
    }

    private function buildHerbElement(DOMDocument $document, array $herb): DOMElement
    {
        $element = $document->createElement('herb');
        $element->setAttribute('id', $herb['id']);

        foreach ($herb as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            $child = $document->createElement($key);
            if ($key === 'price') {
                $child->setAttribute('unit', 'CNY');
            }

            $child->appendChild($document->createTextNode((string) $value));
            $element->appendChild($child);
        }

        return $element;
    }
}

