<!DOCTYPE html>
<html lang="ja" class="install" id="install-step{{ step }}">
  <meta charset="utf-8">
  <title>System Install: Step {{ step }} - Tak-Me System</title>
  <link rel="stylesheet" href="{{ config.global.assets_path }}style/default.css">
  <script src="{{ config.global.assets_path }}script/common.js"></script>
  <div id="container">
    <div id="page-header">
      <h1>システムインストール : 管理ユーザー設定</h1>
    </div>
    <form id="TMS-mainform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}">
{% if err.vl_fullname == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% elseif err.vl_db_error is defined %}
      <div class="error">
        <i>{{ err.vl_db_error}}</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_fullname == 1 %} invalid{% endif %}">
        <label for="fullname">フルネーム</label>
        <input type="text" name="fullname" id="fullname" value="{{ post.fullname }}">
      </div>
{% if err.vl_email == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_email == 1 %} invalid{% endif %}">
        <label for="email">E-mail</label>
        <input type="text" name="email" id="email" value="{{ post.email }}">
      </div>
{% if err.vl_uname == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_uname == 1 %} invalid{% endif %}">
        <label for="uname">ログイン名</label>
        <input type="text" placeholder="半角英数字" name="uname" id="uname" value="{{ post.uname }}">
      </div>
{% if err.vl_upass == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_upass == 1 %} invalid{% endif %}">
        <label for="upass">パスワード</label>
        <input type="password" placeholder="半角英数字及び記号" name="upass" id="upass" value="{{ post.upass }}">
      </div>
{% if err.vl_retype == 1 %}
      <div class="error">
        <i>パスワードの入力を再確認してください</i>
      </div>
{% endif %}
      <div class="fieldset{% if err.vl_retype == 1 %} invalid{% endif %}">
        <label for="retype">再入力</label>
        <input type="password" placeholder="パスワードをもう一度入力" name="retype" id="retype" value="{{ post.retype }}">
      </div>
      <div class="form-footer">
        <input type="submit" name="s1_submit" value="登録">
        <input type="hidden" name="mode" value="{{ post.mode }}">
        <input type="hidden" name="tmp_file" value="{{ post.tmp_file }}">
      </div>
    </form>
  </div>
