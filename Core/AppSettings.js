// App settings helper for TCGEngine apps.
// Settings are stored in browser localStorage, namespaced by app root.
(function() {
  if (typeof window === 'undefined') return;
  if (window.TCGSettings) return;

  var schemasByRoot = {};

  function normalizeRootName(rootName) {
    if (!rootName || typeof rootName !== 'string') return '';
    return rootName.replace(/^\.\//, '').replace(/^\//, '').trim();
  }

  function inferCurrentRootName() {
    if (typeof window.rootPath === 'string' && window.rootPath) {
      var fromRootPath = normalizeRootName(window.rootPath);
      if (fromRootPath) return fromRootPath;
    }
    var folderInput = document.getElementById('folderPath');
    if (folderInput && typeof folderInput.value === 'string' && folderInput.value) {
      return normalizeRootName(folderInput.value);
    }
    return '';
  }

  function storageKey(rootName, key) {
    var root = normalizeRootName(rootName || inferCurrentRootName());
    return 'tcg.settings.' + root + '.' + key;
  }

  function readStorage(rootName, key) {
    try {
      return localStorage.getItem(storageKey(rootName, key));
    } catch (e) {
      return null;
    }
  }

  function writeStorage(rootName, key, value) {
    try {
      localStorage.setItem(storageKey(rootName, key), value);
      return true;
    } catch (e) {
      return false;
    }
  }

  function getSchema(rootName) {
    var root = normalizeRootName(rootName || inferCurrentRootName());
    if (!root) return {};
    return schemasByRoot[root] || {};
  }

  function getDefinition(rootName, key) {
    var schema = getSchema(rootName);
    return schema && schema[key] ? schema[key] : null;
  }

  function parseByType(rawValue, type, defaultValue) {
    if (rawValue === null || rawValue === undefined) return defaultValue;

    if (type === 'boolean') {
      var s = String(rawValue).toLowerCase();
      if (s === '1' || s === 'true' || s === 'yes' || s === 'on') return true;
      if (s === '0' || s === 'false' || s === 'no' || s === 'off') return false;
      return !!defaultValue;
    }

    if (type === 'number') {
      var n = Number(rawValue);
      return Number.isFinite(n) ? n : defaultValue;
    }

    if (type === 'json') {
      try {
        return JSON.parse(String(rawValue));
      } catch (e) {
        return defaultValue;
      }
    }

    return String(rawValue);
  }

  function serializeByType(value, type) {
    if (type === 'boolean') return value ? 'true' : 'false';
    if (type === 'number') return String(Number(value));
    if (type === 'json') {
      try {
        return JSON.stringify(value);
      } catch (e) {
        return '{}';
      }
    }
    return String(value);
  }

  window.TCGSettings = {
    registerSchema: function(rootName, schema) {
      var root = normalizeRootName(rootName);
      if (!root || !schema || typeof schema !== 'object') return;
      if (!schemasByRoot[root]) schemasByRoot[root] = {};
      schemasByRoot[root] = Object.assign({}, schemasByRoot[root], schema);
    },
    getCurrentRootName: function() {
      return inferCurrentRootName();
    },
    get: function(key, options) {
      options = options || {};
      var rootName = options.rootName || inferCurrentRootName();
      var def = getDefinition(rootName, key);
      var type = options.type || (def && def.type) || 'string';
      var defaultValue = options.hasOwnProperty('defaultValue') ? options.defaultValue : (def ? def.defaultValue : undefined);
      var rawValue = readStorage(rootName, key);
      return parseByType(rawValue, type, defaultValue);
    },
    set: function(key, value, options) {
      options = options || {};
      var rootName = options.rootName || inferCurrentRootName();
      var def = getDefinition(rootName, key);
      var type = options.type || (def && def.type) || 'string';
      return writeStorage(rootName, key, serializeByType(value, type));
    },
    getAllForRoot: function(rootName) {
      var root = normalizeRootName(rootName || inferCurrentRootName());
      var schema = getSchema(root);
      var result = {};
      Object.keys(schema).forEach(function(key) {
        result[key] = window.TCGSettings.get(key, { rootName: root });
      });
      return result;
    }
  };
})();
