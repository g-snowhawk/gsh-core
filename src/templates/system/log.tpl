{% extends "master.tpl" %}

{% block head %}
  <script src="{{ config.global.assets_path }}script/systemlog.js"></script>
{% endblock %}

{% block main %}
  <div class="wrapper">
    <h1>システムログ</h1>
    <div class="metadata">
      System Information<br>
      OS&nbsp;:&nbsp;{{ php.os }}<br>
      PHP&nbsp;:&nbsp;{{ php.version }}&nbsp;(&nbsp;{{ php.sapi }}&nbsp;)
    </div>
    <div class="hgroup">
      <h2>エラーログ</h2>
      <p><label><input type="checkbox" name="errorlog_rotate" value="1">Rotation</label></p>
    </div>
    <div class="log-block" id="error-log"></div>
    <div class="hgroup">
      <h2>アクセスログ</h2>
      <p><label><input type="checkbox" name="accesslog_rotate" value="1">Rotation</label></p>
    </div>
    <div class="log-block" id="access-log"></div>
    <div class="form-footer">
      <input type="submit" name="s1_submit" value="実行">
      <input type="hidden" name="mode" value="system.receive:logRotate">
    </div>
  </div>
{% endblock %}
