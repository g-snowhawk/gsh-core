{% macro recursion(directory, parentDir, appname, level) %}
  {% import _self as self %}
  {% if directory is empty %}
    {% if apps.isAdmin() or apps.hasPermission(permission.prefix ~ 'noroot', permission.filter1, permission.filter2) != '1' %}
    <li><a href="?mode={{ appname }}.filemanager.receive:set-directory&amp;path=" class="drop-target{% if '' == session.current_dir %} current{% endif %}" data-drop-path="">/</a>
    {% endif %}
  {% endif %}
  {% set folders = apps.childDirectories(directory, parentDir) %}
  {% for folder in folders %}
    {% if loop.first %}
      <ul>
    {% endif %}
    <li><a href="?mode={{ appname }}.filemanager.receive:set-directory&amp;path={{ folder.path|url_encode }}" class="drop-target{% if (folder.parent is empty ? '' : folder.parent ~ '/') ~ folder.path == session.current_dir %} current{% endif %}" data-drop-path="{{ folder.path|url_encode }}">{{ aliases[folder.name] ?? folder.name }}</a>
    {% if folder.name is not null %}
      {% if filemanager_tree_depth is not defined or level < filemanager_tree_depth - 1 %}
        {{ self.recursion(folder.name, folder.parent, appname, level + 1) }}
      {% endif %}
    {% endif %}
    </li>
    {% if loop.last %}
      </ul>
    {% endif %}
  {% endfor %}
{% endmacro %}
