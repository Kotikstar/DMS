<?php

class GithubClient
{
    private string $token;
    private string $owner;
    private string $repo;
    private string $branch;
    private string $apiBase = 'https://api.github.com';

    public function __construct(array $config)
    {
        $this->token = $config['token'] ?? '';
        $this->owner = $config['owner'] ?? '';
        $this->repo = $config['repo'] ?? '';
        $this->branch = $config['branch'] ?? 'main';
    }

    private function request(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init();
        $headers = [
            'User-Agent: LC-System-Agent',
            'Accept: application/vnd.github+json',
        ];

        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        if ($data !== null) {
            $payload = json_encode($data);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Ошибка запроса к GitHub: ' . $error);
        }

        curl_close($ch);
        $decoded = json_decode($response, true);

        if ($status >= 400) {
            $message = $decoded['message'] ?? 'Неизвестная ошибка GitHub';
            throw new RuntimeException("GitHub API ({$status}): {$message}");
        }

        return $decoded;
    }

    private function requestRaw(string $method, string $url): string
    {
        $ch = curl_init();
        $headers = [
            'User-Agent: LC-System-Agent',
            'Accept: application/vnd.github.raw',
        ];

        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Ошибка запроса к GitHub: ' . $error);
        }

        curl_close($ch);

        if ($status >= 400) {
            throw new RuntimeException("GitHub API ({$status}): не удалось получить файл");
        }

        return (string) $response;
    }

    public function listDocuments(string $path): array
    {
        $url = sprintf('%s/repos/%s/%s/contents/%s?ref=%s', $this->apiBase, $this->owner, $this->repo, $path, $this->branch);
        $items = $this->request('GET', $url);

        return array_values(array_filter($items, fn($item) => ($item['type'] ?? '') === 'file'));
    }

    public function getDocument(string $path): array
    {
        $url = sprintf('%s/repos/%s/%s/contents/%s?ref=%s', $this->apiBase, $this->owner, $this->repo, $path, $this->branch);
        $data = $this->request('GET', $url);
        $content = base64_decode($data['content'] ?? '');

        return [
            'sha' => $data['sha'] ?? '',
            'content' => $content,
            'name' => $data['name'] ?? basename($path),
            'path' => $data['path'] ?? $path,
        ];
    }

    public function saveDocument(string $path, string $content, string $message, ?string $sha = null): array
    {
        $url = sprintf('%s/repos/%s/%s/contents/%s', $this->apiBase, $this->owner, $this->repo, $path);
        $payload = [
            'message' => $message,
            'content' => base64_encode($content),
            'branch' => $this->branch,
        ];

        if ($sha) {
            $payload['sha'] = $sha;
        }

        return $this->request('PUT', $url, $payload);
    }

    public function getHistory(string $path, int $limit = 10): array
    {
        $url = sprintf('%s/repos/%s/%s/commits?path=%s&per_page=%d', $this->apiBase, $this->owner, $this->repo, $path, $limit);
        return $this->request('GET', $url);
    }

    public function getRawUrl(string $path): string
    {
        return sprintf('https://raw.githubusercontent.com/%s/%s/%s/%s', $this->owner, $this->repo, $this->branch, $path);
    }

    public function downloadDocument(string $path): string
    {
        $url = sprintf('%s/repos/%s/%s/contents/%s?ref=%s', $this->apiBase, $this->owner, $this->repo, $path, $this->branch);
        return $this->requestRaw('GET', $url);
    }
}
