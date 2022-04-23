{% include 'header.tpl' %}
<div id="container">
  <div id="page-header">
    <h1>Tak-Me <span>System</span></h1>
  </div>
  <form id="TMS-mainform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}">
    <input type="hidden" name="stub" value="{{ stub }}">
    <div class="input">
      <input type="text" name="uname" id="uname" placeholder="ユーザー名" value="{{ post.uname }}" autocapitalize="off" required>
    </div>
    <div class="input">
      <input type="password" name="upass" id="upass" placeholder="パスワード" autocomplete="off" required>
    </div>
{% if err.vl_nocookie == 1 %}
    <div class="err">
      <p>Cookieを有効にして、ページを再読込みしてください</p>
    </div>
{% endif %}
{% if err.vl_empty == 1 %}
    <div class="err">
      <p>未入力です</p>
    </div>
{% endif %}
{% if err.vl_mismatch == 1 %}
    <div class="err">
      <p>認証に失敗しました</p>
    </div>
{% endif %}
    <div class="form-footer">
      <script src="{{ config.global.assets_path }}script/signin.js" charset="utf-8"></script>
      <noscript>
        <p class="err">本プログラムの使用にはJavaScriptが必要です<br>
          JavaScriptを有効にしてください</p>
      </noscript>
    </div>
  </form>
</div>
