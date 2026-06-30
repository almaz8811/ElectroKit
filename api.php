<?php
/**
 * ElectroKit API - Server-side storage for settings and projects
 * Saves data to JSON files on Synology server
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Data directory - will be created if doesn't exist
$dataDir = __DIR__ . '/data';
$settingsFile = $dataDir . '/settings.json';
$projectsFile = $dataDir . '/projects.json';

// Create data directory if it doesn't exist
if (!file_exists($dataDir)) {
    if (!mkdir($dataDir, 0755, true)) {
        respondError('Failed to create data directory');
    }
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle requests
switch ($action) {
    case 'getSettings':
        handleGetSettings();
        break;

    case 'saveSettings':
        handleSaveSettings();
        break;

    case 'getProjects':
        handleGetProjects();
        break;

    case 'saveProject':
        handleSaveProject();
        break;

    case 'deleteProject':
        handleDeleteProject();
        break;

    case 'listProjects':
        handleListProjects();
        break;

    default:
        respondError('Invalid action');
}

// ─── SETTINGS ────────────────────────────────────────────────────────────────

function handleGetSettings() {
    global $settingsFile;

    if (!file_exists($settingsFile)) {
        respond(['settings' => null]);
    }

    $data = file_get_contents($settingsFile);
    $settings = json_decode($data, true);

    if ($settings === null) {
        respondError('Failed to parse settings');
    }

    respond(['settings' => $settings]);
}

function handleSaveSettings() {
    global $settingsFile;

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['settings'])) {
        respondError('No settings provided');
    }

    $settings = $data['settings'];
    $settings['_updated'] = date('c');

    if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        respondError('Failed to save settings');
    }

    respond(['success' => true, 'updated' => $settings['_updated']]);
}

// ─── PROJECTS ────────────────────────────────────────────────────────────────

function handleGetProjects() {
    global $projectsFile;

    if (!file_exists($projectsFile)) {
        respond(['projects' => []]);
    }

    $data = file_get_contents($projectsFile);
    $projects = json_decode($data, true);

    if ($projects === null) {
        respondError('Failed to parse projects');
    }

    respond(['projects' => $projects]);
}

function handleSaveProject() {
    global $projectsFile;

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['project'])) {
        respondError('No project provided');
    }

    $project = $data['project'];

    // Load existing projects
    $projects = [];
    if (file_exists($projectsFile)) {
        $existing = file_get_contents($projectsFile);
        $projects = json_decode($existing, true);
        if ($projects === null) $projects = [];
    }

    // Generate project ID if not exists
    if (!isset($project['id'])) {
        $project['id'] = uniqid('proj_', true);
    }

    $project['_saved'] = date('c');

    // Update or add project
    $found = false;
    for ($i = 0; $i < count($projects); $i++) {
        if ($projects[$i]['id'] === $project['id']) {
            $projects[$i] = $project;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $projects[] = $project;
    }

    // Save
    if (file_put_contents($projectsFile, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        respondError('Failed to save project');
    }

    respond(['success' => true, 'project' => $project]);
}

function handleDeleteProject() {
    global $projectsFile;

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['id'])) {
        respondError('No project ID provided');
    }

    $id = $data['id'];

    // Load existing projects
    if (!file_exists($projectsFile)) {
        respond(['success' => true]);
    }

    $existing = file_get_contents($projectsFile);
    $projects = json_decode($existing, true);
    if ($projects === null) $projects = [];

    // Filter out deleted project
    $projects = array_filter($projects, function($p) use ($id) {
        return $p['id'] !== $id;
    });

    // Re-index array
    $projects = array_values($projects);

    // Save
    if (file_put_contents($projectsFile, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        respondError('Failed to delete project');
    }

    respond(['success' => true]);
}

function handleListProjects() {
    global $projectsFile;

    if (!file_exists($projectsFile)) {
        respond(['projects' => []]);
    }

    $data = file_get_contents($projectsFile);
    $projects = json_decode($data, true);

    if ($projects === null) {
        respond(['projects' => []]);
    }

    // Return only metadata for list
    $list = array_map(function($p) {
        return [
            'id' => $p['id'],
            'name' => isset($p['name']) ? $p['name'] : 'Без названия',
            'type' => isset($p['type']) ? $p['type'] : 'apartment',
            '_saved' => isset($p['_saved']) ? $p['_saved'] : null,
            'roomCount' => isset($p['rooms']) ? count($p['rooms']) : 0
        ];
    }, $projects);

    respond(['projects' => $list]);
}

// ─── HELPERS ─────────────────────────────────────────────────────────────────

function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function respondError($message) {
    http_response_code(400);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
