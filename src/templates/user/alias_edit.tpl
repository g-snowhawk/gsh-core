{% extends "master.tpl" %}

{% block main %}
  <p id="backlink"><a href="?mode=user.response:profile">一覧に戻る</a></p>
  <div class="wrapper">
    <h1>代替アカウント編集</h1>
    {% if err.vl_fullname == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
    {% endif %}
    <div class="fieldset{% if err.vl_fullname == 1 %} invalid{% endif %}">
      <label for="fullname"><sup class="necessary">(必須)</sup>フルネーム</label>
      <input type="text" name="fullname" id="fullname" value="{{ post.fullname}}">
    </div>
    {% if err.vl_email == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
    {% endif %}
    <div class="fieldset{% if err.vl_email == 1 %} invalid{% endif %}">
      <label for="email"><sup class="necessary">(必須)</sup>E-mail</label>
      <input type="text" name="email" id="email" value="{{ post.email }}" data-validate="necessary">
    </div>
    <div class="fieldset">
      <label for="tel">電話番号</label>
      <input type="text" name="tel" id="tel" value="{{ post.tel }}">
    </div>
    {% if post.id is empty %}
      {% if err.vl_uname == 1 %}
            <div class="error">
              <i>入力してください</i>
            </div>
      {% endif %}
      <div class="fieldset{% if err.vl_uname == 1 %} invalid{% endif %}">
        <label for="uname"><sup class="necessary">(必須)</sup>ログイン名</label>
        <input type="text" placeholder="半角英数字" name="uname" id="uname" value="{{ post.uname }}">
      </div>
    {% endif %}

    {% if apps.hasPermission('root') and (apps.isParent(post.id) or post.id is empty) %}
      <p><label><input type="checkbox" name="admin" value="1"{% if post.admin == '1' %} checked{% endif %}>管理ユーザー</label></p>
    {% endif %}

    <script src="{{ config.global.assets_path }}script/tms_user.js" charset="utf-8"></script>
    <h2><a href="#update-password" class="accordion-switcher" data-callback="erasePassword">パスワード{% if post.id is empty %}設定{% else %}変更{% endif %}</a></h2>
    <div id="update-password" class="accordion">
      <div class="fieldset{% if err.vl_upass == 1 %} invalid{% endif %}">
        <label for="upass">パスワード</label>
        <input type="password" placeholder="半角英数字" name="upass" id="upass" autocomplete="off">
      </div>
      <div class="fieldset{% if err.vl_retype == 1 %} invalid{% endif %}">
        <label for="retype">再入力</label>
        <input type="password" placeholder="半角英数字" name="retype" id="retype" autocomplete="off">
      </div>
    </div>

    {% include 'edit_form_metadata.tpl' %}

    <div class="form-footer">
      <input type="submit" name="s1_submit" value="登録">
      <input type="hidden" name="mode" value="user.receive:saveAlias">
      <input type="hidden" name="id" value="{{ post.id }}">
    </div>
  </div>
{% endblock %}
