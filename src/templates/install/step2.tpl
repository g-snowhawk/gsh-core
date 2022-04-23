<!DOCTYPE html>
<html lang="ja" class="install" id="install-step{{ step }}">
  <meta charset="utf-8">
  <title>System Install: Step {{ step }} - Tak-Me System</title>
  <link rel="stylesheet" href="{{ config.global.assets_path }}style/default.css">
  <script src="{{ config.global.assets_path }}script/common.js"></script>
  <div id="container">
    <div id="page-header">
      <h1>システムインストール : データベース設定</h1>
    </div>
    <form id="TMS-mainform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}">
{% if err.vl_db_driver == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% elseif err.vl_db_connection is defined %}
      <div class="error">
        <i>{{ err.vl_db_connection }}</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_db_driver == 1 %} invalid{% endif %}">
        <label for="db_driver">データベース</label>
        <select name="db_driver" id="db_driver">
          <option value="mysql"{% if post.db_driver == 'mysql' %} selected{% endif %}>MySQL</option>
          <!--option value="pgsql"{% if post.db_driver == 'pgsql' %} selected{% endif %}>PostgreSQL</option-->
          <option value="sqlite"{% if post.db_driver == 'sqlite' %} selected{% endif %}>SQLite</option>
        </select>
      </div>
{% if err.vl_db_host == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_db_host == 1 %} invalid{% endif %}">
        <label for="db_host">ホスト名</label>
        <input type="text" name="db_host" id="db_host" value="{{ post.db_host }}">
      </div>
      <div class="fieldset">
        <label for="db_port">ポート番号</label>
        <input type="text" name="db_port" id="db_port" value="{{ post.db_port }}">
      </div>
{% if err.vl_db_source == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_db_source == 1 %} invalid{% endif %}">
        <label for="db_source">データベース名</label>
        <input type="text" name="db_source" id="db_source" value="{{ post.db_source }}">
      </div>
      <div class="fieldset{% if err.vl_db_encoding == 1 %} invalid{% endif %}">
        <label for="db_encoding">文字コード</label>
        <select name="db_encoding" id="db_encoding">
          <option value="utf8">UTF-8</option>
          <option value="sjis">ShiftJIS</option>
          <option value="ujis">EUC-JP</option>
        </select>
      </div>
{% if err.vl_db_user == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_db_user == 1 %} invalid{% endif %}">
        <label for="db_user">ユーザー</label>
        <input type="text" name="db_user" id="db_user" value="{{ post.db_user }}">
      </div>
{% if err.vl_db_password == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_db_password == 1 %} invalid{% endif %}">
        <label for="db_password">パスワード</label>
        <input type="password" name="db_password" id="db_password" value="{{ post.db_password }}">
      </div>
      <div class="fieldset">
        <label for="password_encrypt_algorithm">パスワード暗号化</label>
        <select name="password_encrypt_algorithm" id="password_encrypt_algorithm">
{% for opt in algo %}
  {% if opt == '2y' %}
          <option value="{{ opt }}"{% if opt == post.password_encrypt_algorithm %} selected{% endif %}>blowfish</option>
  {% else %}
          <option value="{{ opt }}"{% if opt == post.password_encrypt_algorithm %} selected{% endif %}>{{ opt }}</option>
  {% endif %}
{% endfor %}
        </select>
      </div>
      <div class="fieldset">
        <label for="db_table_prefix">テーブル接頭辞</label>
        <input type="text" name="db_table_prefix" id="db_table_prefix" value="{{ post.db_table_prefix }}">
      </div>
      <div class="form-footer">
        <input type="submit" name="s1_submit" value="登録">
        <input type="hidden" name="mode" value="{{ post.mode }}">
        <input type="hidden" name="tmp_file" value="{{ post.tmp_file }}">
      </div>
    </form>
  </div>
