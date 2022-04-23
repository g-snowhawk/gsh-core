{% extends "master.tpl" %}

{% block head %}
  <script src="{{ config.global.assets_path }}script/TM/downloader.js"></script>
{% endblock %}

{% block main %}
  <div class="wrapper">
    <h1>拡張機能管理</h1>
    <p>{{ mtime|date('Y年n月j日 H:i') }} に更新された設定ファイルが存在します。<br>
       ダウンロードして現在の設定ファイルと差し替えることが推奨されます。</p>
    <div class="form-footer">
      <input type="submit" name="s1_submit" value="実行" data-request="TM.downloader">
      <input type="hidden" name="mode" value="system.receive:download">
    </div>
  </div>
{% endblock %}
