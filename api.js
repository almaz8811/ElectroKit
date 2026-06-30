// ─── SERVER API ──────────────────────────────────────────────────────────────

const API_URL = 'api.php';

async function apiCall(action, data = null) {
  try {
    const options = {
      method: data ? 'POST' : 'GET',
      headers: { 'Content-Type': 'application/json' }
    };
    if (data) options.body = JSON.stringify(data);

    const response = await fetch(`${API_URL}?action=${action}`, options);
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'API error');
    }
    return await response.json();
  } catch (err) {
    console.error(`API ${action} failed:`, err);
    alert(`Ошибка связи с сервером: ${err.message}\nДанные будут сохранены локально.`);
    return null;
  }
}

// ─── SETTINGS (SERVER) ───────────────────────────────────────────────────────

async function loadSettingsFromServer() {
  const result = await apiCall('getSettings');
  if (result && result.settings) {
    return result.settings;
  }
  return null;
}

async function saveSettingsToServer(settings) {
  await apiCall('saveSettings', { settings });
}

// ─── PROJECTS (SERVER) ───────────────────────────────────────────────────────

async function loadProjectsFromServer() {
  const result = await apiCall('getProjects');
  if (result && result.projects) {
    return result.projects;
  }
  return [];
}

async function saveProjectToServer(project) {
  const result = await apiCall('saveProject', { project });
  return result && result.project;
}

async function deleteProjectFromServer(id) {
  await apiCall('deleteProject', { id });
}

async function listProjectsFromServer() {
  const result = await apiCall('listProjects');
  if (result && result.projects) {
    return result.projects;
  }
  return [];
}
