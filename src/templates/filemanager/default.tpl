{% extends "master.tpl" %}

{% block head %}
  <script src="{{ config.global.assets_path }}script/explorer.js"></script>
  <script src="{{ config.global.assets_path }}script/fileuploader.js"></script>
{% endblock %}

{% block main %}
  {% set appname = apps.currentApp('basename') %}
  <input type="hidden" name="mode" value="{{ appname }}.filemanager.receive:remove">
  <input type="hidden" name="ondrop_mode" value="{{ appname }}.filemanager.receive:move">
  <input type="hidden" name="rename_mode" value="{{ appname }}.filemanager.receive:rename">
  <div class="explorer">
    <div class="explorer-sidebar resizable" data-minwidth="120">
      <div class="tree">
        <h1 class="headline">フォルダ一覧</h1>
        <nav>
          <ul>
            {% import "filemanager/tree.tpl" as tree %}
            {{ tree.recursion(null, null, appname) }}
          </ul>
        </nav>
      </div>
    </div>
    <div class="explorer-mainframe">
      <div class="explorer-list">
        <h2 class="headline">ファイル一覧
          {% set parent = '' %}
          {% for dir in cwd %}
            {% set path = path is defined ? [path, dir]|join('/') : dir %}
            {% if loop.first %}
              <span class="breadcrumbs"><span class="root">/</span>
            {% endif %}
            {% if path is not empty %}
              <a href="?mode={{ appname }}.filemanager.receive:set-directory&amp;path={{ path|url_encode }}">{{ dir }}</a>
            {% endif %}
            {% if loop.last %}
              </span>
            {% else %}
              {% set parent = parent ~ '/' ~ dir %}
            {% endif %}
          {% endfor %}
        </h2>
        <div class="explorer-body{% if files|length == 0 %} flex-column{% endif %}">
          <table>
            <thead>
              <tr>
                <td>ファイル名</td>
                <td>URL</td>
                <td>サイズ</td>
                <td>更新日</td>
                <td>&nbsp;</td>
              </tr>
            </thead>
            <tbody>
            {% if (session.current_dir is not empty and apps.hasPermission(permission.prefix ~ 'noroot', permission.filter1, permission.filter2) == false) or '/' in session.current_dir %}
              <tr>
                <td class="link spacer link-to-parent"><a href="?mode={{ appname }}.filemanager.receive:set-directory&amp;path={{ parent|url_encode }}">../<i>Parent Directory</i></a></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
            {% endif %}
            {% set renameClass = (apps.hasPermission(permission.prefix ~ 'update', permission.filter1, permission.filter2)) ? 'renamable' : 'unrenamable' %}
            {% for unit in files %}
              <tr class="{{ unit.kind }}">
                {% if unit.kind == 'folder' %}
                  <td class="link spacer with-icon"><a href="?mode={{ appname }}.filemanager.receive:set-directory&amp;path={{ unit.path|url_encode }}" class="{{ renameClass }}">{{ unit.name }}</a></td>
                {% else %}
                  <td class="link spacer with-icon"><span class="{{ renameClass }}">{{ unit.name }}</span></td>
                {% endif %}
                <td class="url">
                  {% if unit.url is not empty %}
                    <a href="{{ unit.url }}" target="tms_filemanager" title="{{ unit.url }}">
                      {% if unit.uri is defined %}
                        <span class="dirname">{{ unit.uri.dirname }}</span><span class="basename">/{{ unit.uri.basename }}</span>
                      {% else %}
                        {{ unit.url }}
                      {% endif %}
                    </a>
                  {% endif %}
                </td>
                <td class="date">{{ unit.size }}</td>
                <td class="date">{{ unit.modify_date|date('Y年n月j日 H:i') }}</td>
                <td class="button reddy">
                  {% if apps.hasPermission(permission.prefix ~ 'delete', permission.filter1, permission.filter2) %}
                    <label><input type="radio" name="delete" value="{{ unit.kind }}:{{ unit.name }}">削除</label>
                  {% else %}
                    <span>&nbsp;</span>
                  {% endif %}
                </td>
              </tr>
              {% if loop.last %}
                  </tbody>
                </table>
              {% endif %}
            {% else %}
                </tbody>
              </table>
              <p class="notice">このフォルダは空です</p>
            {% endfor %}
        </div>
        <div class="footer-controls">
          <div id="file-selector" data-error-message="%d個のファイルアップロードに失敗しました" data-directory-message="%d個のディレクトリをスキップしました">
            <label class="droparea">
              <input type="file" name="file" id="file" multiple>
              <span>ここにファイルをドロップします<br><small>またはクリックしてファイルを選択します</small></span>
            </label>
          </div>
          <nav class="links">
            {% if apps.hasPermission(permission.prefix ~ 'create', permission.filter1, permission.filter2) %}
              <a href="?mode={{ appname }}.filemanager.response:add-folder" class="subform-opener"><i class="mark">+</i>新規フォルダ</a>
              <a href="?mode={{ appname }}.filemanager.receive:save-file" class="file-uploader"><i class="mark">+</i>新規ファイル</a>
            {% else %}
              <span>&nbsp;</span>
            {% endif %}
          </nav>
          <nav class="pagination">
            {% include 'pagination.tpl' %}
          </nav>
        </div>
      </div>
    </div>
  </div>
{% endblock %}
