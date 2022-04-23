<!DOCTYPE html>
<html lang="ja" class="install" id="install-step{{ step }}">
  <meta charset="utf-8">
  <title>System Install: Step {{ step }} - Tak-Me System</title>
  <link rel="stylesheet" href="{{ config.global.assets_path }}style/default.css">
  <script src="{{ config.global.assets_path }}script/common.js"></script>
  <div id="container">
    <div id="page-header">
      <h1>システムインストール : サイト設定</h1>
    </div>
    <form id="TMS-mainform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}">
      <p>設定ファイルをインストールできませんでした。<br>
         ダウンロードして下記パスにFTP等で設置してください。<br>
         {{ config_path }}</p>
{% if err.vl_once == 1 %}
      <p class="err">ダウンロードは１度しかできません。<br>
{% else %}
      <p>設置が完了したら、下記URLにアクセスしてください。<br>
         <a href="{{ base_url }}">{{ base_url }}</a></p>
      <div class="form-footer">
        <input type="submit" name="s1_submit" value="ダウンロード">
        <input type="hidden" name="mode" value="{{ post.mode }}">
        <input type="hidden" name="tmp_file" value="{{ post.tmp_file }}">
      </div>
{% endif %}
    </form>
  </div>
