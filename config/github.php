<?php
return [
    'token' => getenv('GITHUB_TOKEN') ?: '',
    'owner' => getenv('GITHUB_OWNER') ?: 'your-org-or-user',
    'repo'  => getenv('GITHUB_REPO') ?: 'docs-repo',
    'branch'=> getenv('GITHUB_BRANCH') ?: 'main',
    'docs_path' => 'docs'
];
