{% set position = formposition is not empty ? formposition : 'left' %}
<form id="{{ subformPrefix }}subform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}"{% if form.confirm %} data-confirm="{{ form.confirm|url_encode }}"{% endif %} data-position="{{ position }}"{% if subformClass is defined %} class="{{ subformClass }}"{% endif %}>
  {% if dontStub != 'yes' %}
    <input type="hidden" name="stub" value="{{ stub }}">
  {% endif %}
  {% block main %}{% endblock %}
</form>
{% block side %}{% endblock %}
<div id="cancel-subform">
  <a href="#container" id="cancel-subform-button">閉じる</a>
</div>
<template id="polling-parts">
  <div id="log-block">
    <div class="dialog">
      <iframe src=""></iframe>
      <div class="footer">
        <button>OK</button>
      </div>
    </div>
  </div>
  <div id="progressbar">
    <div class="bar-outline">
      <div class="bar"></div>
    </div>
    <div class="description"></div>
  </div>
</template>
