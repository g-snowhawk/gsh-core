{% extends "master.tpl" %}

{% block main %}
  <div class="wrapper">
    <h1>拡張機能管理</h1>
    {% for package in packages %}
      {% set num = loop.index0 %}
      {% if loop.first %}
        <table class="nlist">
          <thead>
            <tr>
              <td rowspan="2">パッケージ</td>
              <td rowspan="2">説明</td>
              <td colspan="3" class="small">バージョン</td>
            </tr>
            <tr>
              <td class="small ver">現行</td>
              <td class="small ver">更新</td>
            </tr>
          </thead>
          <tbody>
      {% endif %}
      <tr>
        <td>{{ package.namespace|nl2br }}</td>
        <td>{{ package.detail|nl2br }}</td>
        <td class="ta-c">{{ package.current_version }}</td>
        {% if package.new_version is defined %}
          <td class="ta-c">
            <label>
              {{ package.new_version }}
              <input type="checkbox" name="paths[{{ loop.index }}]" value="{{ package.path }}">
              <input type="hidden" name="classes[{{ loop.index }}]" value="{{ package.classname }}">
            </label>
          </td>
        {% else %}
          <td class="ta-c">-</td>
        {% endif %}
      </tr>
      {% if loop.last %}
          </tbody>
        </table>
      {% endif %}
    {% else %}
      <p>利用できる追加機能は見つかりません。</p>
    {% endfor %}
    <div class="form-footer">
      <input type="submit" name="s1_submit" value="実行">
      <input type="hidden" name="mode" value="system.receive:setup">
    </div>
  </div>
{% endblock %}
