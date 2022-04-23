<!DOCTYPE html>
<html lang="ja" class="install" id="install-step{{ step }}">
  <meta charset="utf-8">
  <title>System Install: Step {{ step }} - Tak-Me System</title>
  <link rel="stylesheet" href="{{ config.global.assets_path }}style/default.css">
  <script src="{{ config.global.assets_path }}script/common.js"></script>
  <div id="container">
    <div id="page-header">
      <h1>システムインストール : 基本設定</h1>
    </div>
    <form id="TMS-mainform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}">
{% if err.vl_base_url == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_base_url == 1 %} invalid{% endif %}">
        <label for="base_url">URL</label>
        <input type="text" name="base_url" id="base_url" value="{{ post.base_url }}">
      </div>
{% if err.vl_domain_name == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_domain_name == 1 %} invalid{% endif %}">
        <label for="domain_name">ドメイン名</label>
        <input type="text" name="domain_name" id="domain_name" value="{{ post.domain_name }}">
      </div>
{% if err.vl_docroot == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
{% if err.vl_notexists_docroot == 1 %}
      <div class="error">
        <i>公開用ディレクトリが存在しません</i>
      </div>
{% endif %}
{% if err.vl_notwritable_docroot == 1 %}
      <div class="error">
        <i>公開用ディレクトリに書き込み権限がありません</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_docroot == 1 %} invalid{% endif %}">
        <label for="docroot">ドキュメントルート</label>
        <input type="text" name="docroot" id="docroot" value="{{ post.docroot }}">
      </div>
{% if err.vl_save_dir == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
{% if err.vl_notexists_save_dir == 1 %}
      <div class="error">
        <i>保存用ディレクトリが存在しません</i>
      </div>
{% endif %}
{% if err.vl_notwritable_save_dir == 1 %}
      <div class="error">
        <i>保存用ディレクトリに書き込み権限がありません</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_save_dir == 1 %} invalid{% endif %}">
        <label for="save_dir">データ保存パス</label>
        <input type="text" name="save_dir" id="save_dir" value="{{ post.save_dir }}">
      </div>
{% if err.vl_assets_path == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_assets_path == 1 %} invalid{% endif %}">
        <label for="assets_path">スタティックパス</label>
        <input type="text" name="assets_path" id="assets_path" value="{{ post.assets_path }}">
      </div>
{% if err.vl_exists_config == 1 %}
      <div class="error">
        <i>既に設定ファイルが存在します。削除または移動してください</i>
      </div>
      <p><code>{{ config_path }}</code></p>
{% endif %}
      <div class="form-footer">
        <input type="submit" name="s1_submit" value="登録">
        <input type="hidden" name="mode" value="{{ post.mode }}">
        <input type="hidden" name="tmp_file" value="{{ post.tmp_file }}">
      </div>
    </form>
  </div>
