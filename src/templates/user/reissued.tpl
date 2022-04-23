{% extends "master.tpl" %}

{% block head %}
  <script src="{{ config.global.assets_path }}script/TM/print.js"></script>
  <script src="{{ config.global.assets_path }}script/TM/copy.js"></script>
{% endblock %}

{% block main %}
  {% if post.profile != 1 %}
    {% set mode = post.returnmode ? post.returnmode : 'user.response' %}
    <p id="backlink"><a href="?mode={{ mode }}">一覧に戻る</a></p>
  {% endif %}
  <div class="wrapper">
    <h1>パスワード再発行完了</h1>
    {% if err.vl_mail_subject == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
    {% endif %}
    <div class="fieldset{% if err.vl_mail_subject == 1 %} invalid{% endif %}">
      <label for="mail_subject"><sup class="necessary">(必須)</sup>件名</label>
      <input type="text" name="mail_subject" id="mail_subject" value="{{ post.mail_subject }}" data-validate="necessary">
    </div>
    {% if err.vl_mail_body == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
    {% endif %}
    <div class="fieldset{% if err.vl_mail_body == 1 %} invalid{% endif %}">
      <label for="mail_body"><sup class="necessary">(必須)</sup>本文</label>
      <textarea name="mail_body" id="mail_body" data-validate="necessary">{{ post.mail_body }}</textarea>
    </div>
    <div class="form-footer">
      <!--input type="button" name="s1_copy" value="コピー" class="copy-button" data-copy-target="reissued-result"-->
      <input type="submit" name="s1_submit" value="メール送信">
      <input type="button" name="s1_print" value="印刷" class="print-button" data-print-target="reissued-result">
      <input type="hidden" name="mode" value="user.receive:reissued-mail">
      <input type="hidden" name="returnmode" value="{{ post.returnmode }}">
    </div>
  </div>
{% endblock %}
